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
    /** @var A UTF-8 byte order mark */
    protected const BOM_UTF8 = "\xEF\xBB\xBF";
    /** @var A UTF-16 (big-endian) byte order mark */
    protected const BOM_UTF16BE = "\xFE\xFF";
    /** @var A UTF-16 (little-endian) byte order mark */
    protected const BOM_UTF16LE = "\xFF\xFE";

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
        if (!$t->isHtml && !$t->isXml) {
            throw new \InvalidArgumentException("\$type must be \"text/html\" or an XML type");
        }
        // parse the string as either HTML or XML
        if ($t->isHtml) {
            // for HTML we invoke our parser which has its own handling for everything
            return Parser::parse($string, $type)->document;
        } else {
            // for XML we have to jump through a few hoops to deal with encoding;
            //   if we have a known encoding we want to make sure the XML parser
            //   doesn't try to do its own detection. The best way to do this is
            //   to add a Unicode byte order mark if the string doesn't have one
            $doc = new \DOMDocument();
            try {
                // first check for a byte order mark; if one exists we can go straight to parsing
                if (!Encoding::sniffBOM($string)) {
                    // check the type for a charset parameter if there is no BOM
                    $charset = $t->params['charset'] ?? "";
                    if ($charset) {
                        $encoding = Encoding::matchLabel($charset);
                        if (!$encoding) {
                            throw new \InvalidArgumentException("Specified charset is not supported");
                        }
                        $charset = $encoding['name'];
                    }
                    if ($charset) {
                        // if the string is known to be UTF-8 or UTF-16 according to the type but has no BOM, add one
                        if ($charset === "UTF-8") {
                            $string = self::BOM_UTF8.$string;
                        } elseif ($charset === "UTF-16BE") {
                            $string = self::BOM_UTF16BE.$string;
                        } elseif ($charset === "UTF-16LE") {
                            $string = self::BOM_UTF16LE.$string;
                        } else {
                            // transcode the string to UTF-8 with a BOM where the string's encoding cannot include a BOM
                            $decoder = Encoding::createDecoder($charset, $string, true, false);
                            $string = self::BOM_UTF8;
                            while (strlen($c = $decoder->nextChar())) {
                                $string .= $c;
                                $string .= $decoder->asciiSpanNot("");
                            }
                            unset($decoder);
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
        }
    }
}