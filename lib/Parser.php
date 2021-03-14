<?php
declare(strict_types=1);
namespace dW\HTML5;

class Parser {
    public static $fallbackEncoding = "windows-1252";

    // Namespace constants
    const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';
    const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    const NAMESPACE_MAP = [
        self::HTML_NAMESPACE   => "",
        self::MATHML_NAMESPACE => "math",
        self::SVG_NAMESPACE    => "svg",
        self::XLINK_NAMESPACE  => "xlink",
        self::XML_NAMESPACE    => "xml",
        self::XMLNS_NAMESPACE  => "xmlns",
    ];

    public static function parse(string $data, ?Document $document = null, ?string $encodingOrContentType = null, ?\DOMElement $fragmentContext = null, ?String $file = null): Document {
        // Initialize the various classes needed for parsing
        $document = $document ?? new Document;
        $errorHandler = new ParseError;
        $decoder = new Data($data, $file ?? "STDIN", $errorHandler, $encodingOrContentType);
        $stack = new OpenElementsStack($fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $tokenList = $tokenizer->tokenize();
        $treeBuilder = new TreeBuilder($document, $decoder, $tokenizer, $tokenList, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext);
        // Override error handling
        $errorHandler->setHandler();
        try {
            // run the parser to completion
            $treeBuilder->constructTree();
        } finally {
            // Restore error handling
            $errorHandler->clearHandler();
        }
        return $document;
    }

    public static function parseFragment(string $data, ?Document $document = null, ?string $encodingOrContentType = null, ?\DOMElement $fragmentContext = null, ?String $file = null): DocumentFragment {
        // Create the requisite parsing context if none was supplied
        $document = $document ?? new Document;
        $tempDocument = new Document;
        $fragmentContext = $fragmentContext ?? $document->createElement("div");
        // parse the fragment into the temporary document
        self::parse($data, $tempDocument, $encodingOrContentType, $fragmentContext, $file);
        // extract the nodes from the temp document into a fragment
        $fragment = $document->createDocumentFragment();
        foreach ($tempDocument->documentElement->childNodes as $node) {
            $document->importNode($node, true);
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
