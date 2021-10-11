<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

use MensBeam\HTML\Parser\Charset;
use MensBeam\HTML\Parser\Data;
use MensBeam\HTML\Parser\ParseError;
use MensBeam\HTML\Parser\Config;
use MensBeam\HTML\Parser\EncodingChangeException;
use MensBeam\HTML\Parser\Exception;
use MensBeam\HTML\Parser\OpenElementsStack;
use MensBeam\HTML\Parser\TemplateInsertionModesStack;
use MensBeam\HTML\Parser\Tokenizer;
use MensBeam\HTML\Parser\TreeConstructor;
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
        self::HTML_NAMESPACE   => "html",
        self::MATHML_NAMESPACE => "math",
        self::SVG_NAMESPACE    => "svg",
        self::XLINK_NAMESPACE  => "xlink",
        self::XML_NAMESPACE    => "xml",
        self::XMLNS_NAMESPACE  => "xmlns",
    ];

    /** Parses a string to produce a document object
     * 
     * @param string $data The string to parse. This may be in any valid encoding
     * @param string|null $encodingOrContentType The document encoding, or HTTP Content-Type header value, if known. If no provided encoding detection will be attempted
     * @param \MensBeam\HTML\Parser\Config|null $config The configuration parameters to use, if any
     */
    public static function parse(string $data, ?string $encodingOrContentType = null, ?Config $config = null): Output {
        // parse the document
        return static::parseDocumentOrFragment($data, $encodingOrContentType, null, null, $config ?? new Config);
    }

    public static function parseFragment(\DOMElement $contextElement, ?int $quirksMode, string $data, ?string $encodingOrContentType = null, ?Config $config = null): \DOMDocumentFragment {
        // parse the fragment into a temporary document
        $out = self::parseDocumentOrFragment($data, $encodingOrContentType, $contextElement, $quirksMode, $config ?? new Config);
        $document = $out->document;
        // extract the nodes from the temporary document into a fragment belonging to the context element's document
        $fragment = $contextElement->ownerDocument->createDocumentFragment();
        foreach ($document->documentElement->childNodes as $node) {
            $node = $fragment->ownerDocument->importNode($node, true);
            $fragment->appendChild($node);
        }
        return $fragment;
    }

    protected static function parseDocumentOrFragment(string $data, ?string $encodingOrContentType, ?\DOMElement $fragmentContext, ?int $fragmentQuirks, Config $config): Output {
        // check the document class
        if (isset($config->documentClass)) {
            try {
                $document = new $config->documentClass;
            } catch (\Throwable $e) {
                throw new Exception(Exception::FAILED_CREATING_DOCUMENT, [$config->documentClass], $e);
            }
            if (!$document instanceof \DOMDocument) {
                throw new Exception(Exception::INVALID_DOCUMENT_CLASS, [get_class($document)]);
            }
        } else {
            $document = new \DOMDocument();
        }
        // sort out other needed configuration
        $htmlNamespace = ($config->htmlNamespace) ? self::HTML_NAMESPACE : null;
        // Initialize the various classes needed for parsing
        $errorHandler = $config->errorCollection ? new ParseError : null;
        $decoder = new Data($data, $encodingOrContentType, $errorHandler, $config);
        $stack = new OpenElementsStack($htmlNamespace, $fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $tokenList = $tokenizer->tokenize();
        $treeConstructor = new TreeConstructor($document, $decoder, $tokenizer, $tokenList, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext, $fragmentQuirks, $config);
        try {
            $treeConstructor->constructTree();
        } catch (EncodingChangeException $e) {
            // We are supposed to reparse with a new encoding
            // Clear out the document
            if ($document->doctype) {
                $document->removeChild($document->doctype);
            }
            while ($document->hasChildNodes()) {
                $document->removeChild($document->firstChild);
            }
            // save the target encoding
            $encoding = $decoder->encoding;
            // Destroy our existing objects
            unset($errorHandler, $decoder, $stack, $tokenizer, $tokenList, $treeConstructor);
            // Parse a second time
            return static::parseDocumentOrFragment($data, $encoding, $fragmentContext, $fragmentQuirks, $config);
        }
        // prepare the output
        $out = new Output;
        $out->document = $document;
        $out->encoding = $decoder->encoding;
        $out->quirksMode = $treeConstructor->quirksMode;
        if ($errorHandler) {
            $out->errors = $errorHandler->errors;
        }
        return $out;
    }
}
