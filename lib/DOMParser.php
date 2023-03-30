<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

use MensBeam\Mime\MimeType;
use MensBeam\Intl\Encoding;

/** The DOMParser interface allows authors to create new DOMDocument objects by parsing strings, as either HTML or XML. */
class DOMParser {
    protected const TYPES = [
        "text/html",
        "text/xml",
        "application/xml",
        "application/xhtml+xml",
        "image/svg+xml"
    ];

    /** Parses `$string` using either the HTML or XML parser, according to `$type`, and returns the resulting `DOMDocument`. 
     * 
     * `$type` can be `"text/html"` (which will invoke the HTML parser), or any of `"text/xml"`, `"application/xml"`, 
     * `"application/xhtml+xml"`, or `"image/svg+xml"` (which will invoke the XML parser).
     * 
     * For the XML parser, if `$string` cannot be parsed, then the returned `DOMDocument` will contain elements describing the resulting error.
     * 
     * Note that script elements are not evaluated during parsing, and the resulting document's encoding will always be UTF-8.
     * 
     * Values other than the above for `$type` will cause an `InvalidArgumentException` exception to be thrown.
     * 
     * Since PHP strings are bytes, `$type` may include a `charset` parameter. If no parameter is is supplied UTF-8 is assumed.
     */
    public function parseFromString(string $string, string $type): \DOMDocument {
        // start by parsing the type
        $t = MimeType::parseBytes($type);
        if (!in_array($t->essence, self::TYPES)) {
            throw new \InvalidArgumentException("\$type must be one of ".implode(", ", self::TYPES));
        }
        $charset = $t->params['charset'] ?? "UTF-8";
        $encoding = Encoding::matchLabel($charset);
        if (!$encoding) {
            throw new \InvalidArgumentException("Specified charset is not supported");
        }
        $charset = $encoding['name'];
        // parse the string as either HTML or XML
        if ($t->essence === "text/html") {
            // for HTML we invoke our parser
            $config = new Parser\Config;
            $config->encodingFallback = "UTF-8";
            $config->encodingPrescanBytes = 0;
            return Parser::parse($string, $charset, $config);
        } else {
            // for XML we have to jump through a few hoops to make sure the DOMDocument doesn't make a hash of things, or try to detect encoding
        }
    }
}