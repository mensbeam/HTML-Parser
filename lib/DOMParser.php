<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

use MensBeam\Mime\MimeType;
use MensBeam\Intl\Encoding;

/** The DOMParser interface allows authors to create new DOMDocument objects by parsing strings, as either HTML or XML */
class DOMParser {
    /** @var string A UTF-8 byte order mark */
    protected const BOM_UTF8 = "\xEF\xBB\xBF";
    /** @var string A UTF-16 (big-endian) byte order mark */
    protected const BOM_UTF16BE = "\xFE\xFF";
    /** @var string A UTF-16 (little-endian) byte order mark */
    protected const BOM_UTF16LE = "\xFF\xFE";
    /** @var string A pattern for matching an XML declaration; this matches the production listed in XML 1.0, which does not materially differ from that of XML 1.1 */
    protected const XML_DECLARATION_PATTERN = <<<XMLDECL
    /^
    <\?xml
    (\s+version=(?:"1\.[0-9]+"|'1\.[0-9]+'))
    (?:\s+encoding=("[A-Za-z][A-Za-z0-9\._\-]*"|'[A-Za-z][A-Za-z0-9\._\-]*'))?
    (\s+standalone=(?:"yes"|"no"|'yes'|'no'))?
    \s*\?>
    /sx
XMLDECL;
	/** @var array A list of standard encoding labels which DOMDocument either does not know or does not map to the correct encoding; this is a worst-case list taken from PHP 5.6 on Windows with some exclusions for encodings which are completely unsupported */
	const ENCODING_NAUGHTY_LIST = [
		"unicode-1-1-utf-8", "unicode11utf8", "unicode20utf8", "x-unicode20utf8",
		"iso88592", "iso88593", "iso88594", "iso88595", "csiso88596e",
		"csiso88596i", "iso-8859-6-e", "iso-8859-6-i", "iso88596", "iso88597",
		"sun_eu_greek", "csiso88598e", "iso-8859-8-e", "iso88598", "visual",
		"csiso88598i", "iso-8859-8-i", "logical", "iso885910", "iso885913",
		"iso885914", "csisolatin9", "iso885915", "l9", "koi", "koi8", "koi8_r",
		"x-mac-roman", "dos-874", "iso-8859-11", "iso8859-11", "iso885911",
		"tis-620", "x-cp1250", "x-cp1251", "ansi_x3.4-1968", "ascii", "cp819",
		"csisolatin1", "ibm819", "iso-8859-1", "iso-ir-100", "iso8859-1",
		"iso88591", "iso_8859-1", "iso_8859-1:1987", "l1", "latin1",
		"us-ascii", "x-cp1252", "x-cp1253", "iso88599", "x-cp1254",
		"x-cp1255", "x-cp1256", "x-cp1257", "cp1258", "windows-1258",
		"x-mac-ukrainian", "chinese", "csgb2312", "csiso58gb231280", "gb2312",
		"gb_2312", "gb_2312-80", "gbk", "iso-ir-58", "big5", "cn-big5",
		"csbig5", "x-x-big5", "x-euc-jp", "ms932", "windows-31j", "x-sjis",
		"cseuckr", "euc-kr", "replacement",
	];
	/** @var array A List of canonical encoding names DOMDocument does not understand, with liases to labels it does understand */
	const ENCODING_ALIAS_MAP = [
		'windows-1258' => "x-cp1258",
		'GBK' => "x-gbk",
		'Big5' => "big5-hkscs",
		'EUC-KR' => "korean",
	];

    /** Parses `$string` using either the HTML or XML parser, according to `$type`, and returns the resulting `DOMDocument`
     * 
     * `$type` can be `"text/html"` (which will invoke the HTML parser), or
     * any XML type (which will invoke the XML parser). A `charset` parameter
     * may be included to specify the document encoding; otherwise encoding
     * will be detected from document hints. This differs from the standard
     * interface which only accepts certain XML types, and requires Unicode
     * characters rather than bytes as input, obviating the need for encoding
     * detection
     * 
     * For the XML parser, if `$string` cannot be parsed, then the returned
     * `DOMDocument` will contain elements describing the resulting error
     * 
     * If no encoding is specified and none can be detected from the document,
     * the default encoding is Windows-1252 for HTML and UTF-8 for XML
     */
    public function parseFromString(string $string, string $type): \DOMDocument {
        // start by parsing the type
        $t = MimeType::parseBytes($type);
        // parse the string as either HTML or XML
        if ($t->isHtml) {
            // for HTML we invoke our parser which has its own handling for everything
            return $this->createDocumentHtml($string, $type);
        } elseif ($t->isXml) {
            // for XML we have to jump through a few hoops to deal with
            //   encoding
            return $this->createDocumentXml($this->fixXmlEncoding($string, $t->params['charset'] ?? ""));
        } else {
            throw new \InvalidArgumentException("\$type must be \"text/html\" or an XML type");
        }
    }

    protected function createDocumentHtml(string $string, string $type): \DOMDocument {
        return Parser::parse($string, $type)->document;
    }

    protected function createDocumentXml(string $string): \DOMDocument {
        $document = new \DOMDocument;
        if (!$document->loadXML($string, \LIBXML_NONET | \LIBXML_BIGLINES | \LIBXML_COMPACT |\LIBXML_NOWARNING | \LIBXML_NOERROR)) {
            $err = libxml_get_last_error();
            $message = trim(htmlspecialchars($err->message, \ENT_NOQUOTES | \ENT_SUBSTITUTE | \ENT_XML1, "UTF-8"));
            $string = <<<XMLDOC
<parsererror 
    xmlns="http://www.mozilla.org/newlayout/xml/parsererror.xml" 
    code="{$err->code}"
    message="$message"
    line="{$err->line}"
    column="{$err->column}"
>{$err->code}: "$message" on line {$err->line}, column {$err->column}</parsererror>
XMLDOC;
            return $this->createDocumentXml($string);
        }
        return $document;
    }

    protected function fixXmlEncoding(string $string, string $encoding) {
        // for XML we have to jump through a few hoops to deal with
        //   encoding; if we have a known encoding we want to make sure
        //   the XML parser doesn't try to do its own detection. We can
        //   treat byte order marks as authoritative. In their absence we
        //   can add BOMs to UTF-16 documents, but for other encodings we
        //   must parse XML declarations and validate that any encoding
        //   declaration is correct and change it if it is incorrect

        // this process is further complicated by libxml not understanding
        //   all labels from the Encoding specification (which we try to
        //   honour since it can be assumed to be a best practice), so we
        //   must also rewrite some encoding declarations
        
        // first check for a byte order mark; if one exists we can skip all this
        if (!Encoding::sniffBOM($string)) {
            // otherwise determine the embedded encoding of the document
            if (preg_match(self::XML_DECLARATION_PATTERN, $string, $match)) {
                $match[2] = ($match[2] ?? "") ?: '"utf-8"'; // declaration without encoding is UTF-8
                $xmlDeclaration = $match[0];
                $xmlVersion = $match[1];
                $xmlEncoding = substr($match[2], 1, strlen($match[2]) - 2);
                $xmlStandalone = $match[3] ?? "";
                $docEnc = Encoding::matchLabel($xmlEncoding);
            } else {
                $xmlDeclaration = "";
                $xmlVersion = " version=\"1.0\"";
                $xmlEncoding = "";
                $xmlStandalone = "";
                $docEnc = Encoding::matchLabel("utf-8");
            }
            // next check the type for a charset parameter if there is one
            $typeEnc = Encoding::matchLabel($encoding);
            // if the document encoding differs from the type encoding
            //   or the document encoding is not recognized by libxml,
            //   we need to mangle the document before parsing
            if (
                ($typeEnc && $docEnc && $docEnc['name'] !== $typeEnc['name'])
                || ($typeEnc && !$docEnc && $typeEnc !== "UTF-8")
                || ($docEnc && in_array($docEnc['label'], self::ENCODING_NAUGHTY_LIST))
            ) {
                $charset = ($typeEnc ?? $docEnc)['name'] ?? "UTF-8";
                // some canonical names are not recognized by libxml, so we must use other labels
                $charset = self::ENCODING_ALIAS_MAP[$charset] ?? $charset;
                if ($charset === "UTF-8") {
                    // if the string is UTF-8, adding a BOM is sufficient
                    return self::BOM_UTF8.$string;
                } elseif ($charset === "UTF-16BE") {
                    // if the string is UTF-16BE, adding a BOM is sufficient
                    return self::BOM_UTF16BE.$string;
                } elseif ($charset === "UTF-16LE") {
                    // if the string is UTF-16LE, adding a BOM is sufficient
                    return self::BOM_UTF16LE.$string;
                } elseif ($charset) {
                    // otherwise substitute the encoding declaration if any
                    return "<?xml".$xmlVersion." encoding=\"$charset\"".$xmlStandalone."?>".substr($string, strlen($xmlDeclaration));
                }
            }
        }
        return $string;
    }
}