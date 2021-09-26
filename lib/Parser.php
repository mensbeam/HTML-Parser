<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

use MensBeam\HTML\Parser\Charset;
use MensBeam\HTML\Parser\Data;
use MensBeam\HTML\Parser\ParseError;
use MensBeam\HTML\Parser\ParseErrorDummy;
use MensBeam\HTML\Parser\OpenElementsStack;
use MensBeam\HTML\Parser\TemplateInsertionModesStack;
use MensBeam\HTML\Parser\Tokenizer;
use MensBeam\HTML\Parser\TreeBuilder;
use MensBeam\HTML\Parser\Output;

class Parser {
    public static $fallbackEncoding = "windows-1252";

    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    // Namespace constants
    public const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    public const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    public const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    public const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';
    public const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';
    public const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    public const NAMESPACE_MAP = [
        self::HTML_NAMESPACE   => "",
        self::MATHML_NAMESPACE => "math",
        self::SVG_NAMESPACE    => "svg",
        self::XLINK_NAMESPACE  => "xlink",
        self::XML_NAMESPACE    => "xml",
        self::XMLNS_NAMESPACE  => "xmlns",
    ];

    public static function parse(string $data, ?string $encodingOrContentType = null, ?\DOMDocument $document = null, ?\DOMElement $fragmentContext = null, ?int $fragmentQuirks = null, ?String $file = null): Output {
        // Initialize the various classes needed for parsing
        $document = $document ?? new \DOMDocument;
        if ((error_reporting() & \E_USER_WARNING)) {
            $errorHandler = new ParseError;
        } else {
            $errorHandler = new ParseErrorDummy;
        }
        $decoder = new Data($data, $file ?? "STDIN", $errorHandler, $encodingOrContentType);
        $document->documentEncoding = $decoder->encoding;
        $stack = new OpenElementsStack($fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $tokenList = $tokenizer->tokenize();
        $treeBuilder = new TreeBuilder($document, $decoder, $tokenizer, $tokenList, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext, $fragmentQuirks);
        // Override error handling
        $errorHandler->setHandler();
        try {
            // run the parser to completion
            $treeBuilder->constructTree();
        } finally {
            // Restore error handling
            $errorHandler->clearHandler();
        }
        // prepare the output
        $out = new Output;
        $out->document = $document;
        $out->encoding = $decoder->encoding;
        $out->quirksMode = $treeBuilder->quirksMode;
        return $out;
    }

    public static function parseFragment(\DOMElement $fragmentContext, ?int $fragmentQuirks, string $data, ?string $encodingOrContentType = null, ?\DOMDocument $document = null, ?String $file = null): \DOMDocumentFragment {
        // Create the requisite parsing context if none was supplied
        $document = $document ?? new \DOMDocument;
        // parse the fragment into the temporary document
        self::parse($data, $encodingOrContentType, $document, $fragmentContext, $fragmentQuirks, $file);
        // extract the nodes from the temp document into a fragment
        $fragment = $fragmentContext->ownerDocument->createDocumentFragment();
        foreach ($document->documentElement->childNodes as $node) {
            $node = $document->importNode($node, true);
            $fragment->appendChild($node);
        }
        return $fragment;
    }

    public static function fetchFile(string $file, ?string $encodingOrContentType = null): ?array {
        $f = fopen($file, "r");
        if (!$f) {
            return null;
        }
        $data = stream_get_contents($f);
        $encoding = Charset::fromCharset((string) $encodingOrContentType) ?? Charset::fromTransport((string) $encodingOrContentType);
        if (!$encoding) {
            $meta = stream_get_meta_data($f);
            if ($meta['wrapper_type'] === "http") {
                // Try to find a Content-Type header-field
                foreach ($meta['wrapper_data'] as $h) {
                    $h = explode(":", $h, 2);
                    if (count($h) === 2) {
                        if (preg_match("/^\s*Content-Type\s*$/i", $h[0])) {
                            // Try to get an encoding from it
                            $encoding = Charset::fromTransport($h[1]);
                            break;
                        }
                    }
                }
            }
        }
        return [$data, $encoding];
    }
}
