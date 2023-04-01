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
    (?:\s+encoding=(?:"[A-Za-z][A-Za-z0-9\._\-]*"|'[A-Za-z][A-Za-z0-9\._\-]*'))?
    (\s+standalone=(?:"yes"|"no"|'yes'|'no'))?
    (\s*)\?>
    /sx
XMLDECL;

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
            return Parser::parse($string, $type)->document;
        } elseif ($t->isXml) {
            // for XML we have to jump through a few hoops to deal with encoding;
            //   if we have a known encoding we want to make sure the XML parser
            //   doesn't try to do its own detection. The only way to do this is
            //   to convert to UTF-8 where necessary and remove any XML
            //   declaration encoding information
            $doc = new \DOMDocument();
            try {
                // first check for a byte order mark; if one exists we can go straight to parsing
                if (!Encoding::sniffBOM($string)) {
                    // check the type for a charset parameter if there is no BOM
                    $charset = $t->params['charset'] ?? "";
                    if ($charset) {
                        $encoding = Encoding::matchLabel($charset);
                        if ($encoding) {
                            $charset = $encoding['name'];
                        }
                    }
                    // if a supported encoding was parsed from the type, act
                    //   accordingly; otherwise skip to parsing and let the
                    //   XML parser detect encoding
                    if ($charset) {
                        // if the string is UTF-16, transcode it to UTF-8 so
                        //   we're always dealing with an ASCII-compatible
                        //   encoding (XML's parsing rules ensure documents
                        //   in semi-ASCII-compatible encodings like Shift_JIS
                        //   or ISO 2022-JP never contain non-ASCII characters
                        //   before encoding information is seen)
                        if ($charset === "UTF-16BE" || $charset === "UTF-16LE") {
                            $decoder = Encoding::createDecoder($charset, $string, true, false);
                            $string = "";
                            while (strlen($c = $decoder->nextChar())) {
                                $string .= $c;
                                $string .= $decoder->asciiSpanNot("");
                            }
                            unset($decoder);
                            $charset = "UTF-8";
                        }
                        // look for an XML declaration
                        if (preg_match(self::XML_DECLARATION_PATTERN, $string, $match)) {
                            // substitute the information if one is found
                            $string = "<?xml".$match[1]." encoding=\"$charset\"".$match[2].$match[3]."?>".substr($string, strlen($match[0]));
                        } else {
                            // add a declaration if none is found
                            $string = "<?xml version=\"1.0\" encoding=\"$charset\" ?>".$string;
                        }
                    }
                }
                // parse the document
                if (!$doc->loadXML($string, \LIBXML_NONET | \LIBXML_BIGLINES | \LIBXML_COMPACT |\LIBXML_NOWARNING | \LIBXML_NOERROR)) {
                    throw new \Exception(libxml_get_last_error()->message);
                }
            } catch (\Exception $e) {
                $doc->appendChild($doc->createElementNS("http://www.mozilla.org/newlayout/xml/parsererror.xml", "parserror"));
                $doc->documentElement->appendChild($doc->createTextNode($e->getMessage()));
            }
            return $doc;
        } else {
            throw new \InvalidArgumentException("\$type must be \"text/html\" or an XML type");
        }
    }
}