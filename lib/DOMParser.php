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
            return $this->createDocumentHtml($string, $type);
        } elseif ($t->isXml) {
            // for XML we have to jump through a few hoops to deal with
            //   encoding; if we have a known encoding we want to make sure
            //   the XML parser doesn't try to do its own detection. We can
            //   treat byte order marks as authoritative. In their absence we
            //   can add BOMs to UTF-16 documents, but for other encodings we
            //   must parse XML declarations and validate that any encoding
            //   declaration is correct and change it if it is incorrect
            try {
                // first check for a byte order mark; if one exists we can go straight to parsing
                if (!Encoding::sniffBOM($string)) {
                    // check the type for a charset parameter if there is no BOM
                    $charset = $t->params['charset'] ?? "";
                    if ($charset) {
                        if ($encoding = Encoding::matchLabel($charset)) {
                            $charset = $encoding['name'];
                        }
                    }
                    // if a supported encoding was parsed from the type, act
                    //   accordingly; otherwise skip to parsing and let the
                    //   XML parser detect encoding
                    if ($charset === "UTF-16BE") {
                        // if the string is UTF-16BE, adding a BOM is sufficient
                        $string = self::BOM_UTF16BE.$string;
                     } elseif ($charset === "UTF-16LE") {
                        // if the string is UTF-16LE, adding a BOM is sufficient
                        $string = self::BOM_UTF16LE.$string;
                     } elseif ($charset) {
                        // for ASCII-compatible encodings look for an XML declaration
                        if (preg_match(self::XML_DECLARATION_PATTERN, $string, $match)) {
                            // if an existing encoding declaration is found,
                            //   keep it only if it matches; if no encoding
                            //   declaration is found but the encoding is UTF-8
                            //   this is also acceptable
                            $keep = false;
                            if ($match[2]) {
                                $candidate = substr($match[2], 1, strlen($match[2]) - 2);
                                if ($encoding = Encoding::matchLabel($candidate)) {
                                    if ($charset === $encoding['name']) {
                                        $keep = true;
                                    }
                                }
                            } elseif ($charset === "UTF-8") {
                                $keep = true;
                            }
                            // substitute the encoding declaration where necessary
                            if (!$keep) {
                                $string = "<?xml".$match[1]." encoding=\"$charset\"".$match[3].$match[4]."?>".substr($string, strlen($match[0]));
                            }
                        } elseif ($charset !== "UTF-8") {
                            // add a declaration if none is found and the encoding is not UTF-8
                            $string = "<?xml version=\"1.0\" encoding=\"$charset\" ?>".$string;
                        }
                    }
                }
                // parse the document
                return $this->createDocumentXml($string);
            } catch (\Exception $e) {
                $string = "<parsererror xmlns=\"http://www.mozilla.org/newlayout/xml/parsererror.xml\">".htmlspecialchars($e->getMessage(), \ENT_NOQUOTES | \ENT_SUBSTITUTE | \ENT_XML1, "UTF-8")."</parsererror>";
                return $this->createDocumentXml($string);
            }
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
            throw new \Exception(libxml_get_last_error()->message);
        }
        return $document;
    }
}