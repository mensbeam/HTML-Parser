<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;
use MensBeam\HTML\Parser;

abstract class Serializer {
    use NameCoercion;

    protected const H_ELEMENTS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
    protected const PREFORMATTED_ELEMENTS = [ 'iframe', 'listing', 'noembed', 'noframes', 'noscript', 'plaintext', 'pre', 'style', 'script', 'textarea', 'title', 'xmp' ];
    protected const VOID_ELEMENTS = ["basefont", "bgsound", "frame", "keygen", "area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"];
    protected const RAWTEXT_ELEMENTS = ["style", "script", "xmp", "iframe", "noembed", "noframes", "plaintext"];
    protected const BOOLEAN_ATTRIBUTES = [
        'allowfullscreen' => ["iframe"],
        'async'           => ["script"],
        'autofocus'       => true,
        'autoplay'        => ["audio", "video"],
        'checked'         => ["input"],
        'compact'         => ["dir", "dl", "menu", "ol", "ul"],
        'controls'        => ["audio", "video"],
        'declare'         => ["object"],
        'default'         => ["track"],
        'defer'           => ["script"],
        'disabled'        => ["button", "fieldset", "input", "link", "optgroup", "option", "select", "textarea"],
        'formnovalidate'  => ["button", "input"],
        'hidden'          => true,
        'ismap'           => ["img"],
        'itemscope'       => true,
        'loop'            => ["audio", "video"],
        'multiple'        => ["input", "select"],
        'muted'           => ["audio", "video"],
        'nohref'          => ["area"],
        'nomodule'        => ["script"],
        'noresize'        => ["frame"],
        'noshade'         => ["hr"],
        'novalidate'      => ["form"],
        'nowrap'          => ["td", "th"],
        'open'            => ["details", "dialog"],
        'playsinline'     => ["video"],
        'readonly'        => ["input", "textarea"],
        'required'        => ["input", "select", "textarea"],
        'reversed'        => ["ol"],
        'selected'        => ["option"],
    ];

    protected const BLOCK_QUERY = 'count(.//*[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"][not(ancestor::iframe[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::listing[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noembed[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noframes[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noscript[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::plaintext[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::pre[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::style[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::script[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::textarea[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::title[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::xmp[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])][name()="address" or name()="article" or name()="aside" or name()="blockquote" or name()="base" or name()="body" or (name()="button" and not(last() = 1)) or name()="canvas" or name()="datalist" or name()="details" or name()="dialog" or name()="dd" or name()="div" or name()="dl" or name()="dt" or name()="fieldset" or name()="figcaption" or name()="figure" or name()="footer" or name()="form" or name()="frame" or name()="frameset" or name()="h1" or name()="h2" or name()="h3" or name()="h4" or name()="h5" or name()="h6" or name()="head" or name()="header" or name()="hr" or name()="html" or (name()="input" and not(last() = 1)) or name()="isindex" or name()="li" or name()="link" or name()="main" or name()="meta" or name()="nav" or name()="ol" or name()="optgroup" or name()="option" or (name()="output" and not(last() = 1)) or name()="p" or name()="picture" or name()="pre" or name()="section" or name()="select" or name()="script" or name()="source" or name()="style" or name()="table" or name()="tbody" or name()="td" or (name()="textarea" and not(last() = 1)) or name()="tfoot" or name()="th" or name()="thead" or name()="title" or name()="tr" or name()="ul" or name()="video"][1])';

    /** Serializes an HTML DOM node to a string. This is equivalent to the outerHTML getter
     *
     * @param \Dom\Document|\DOMDocument|\Dom\Element|\DOMElement|\Dom\Text|\DOMText|\Dom\Comment|\DOMComment|\Dom\ProcessingInstruction|\DOMProcessingInstruction|\Dom\DocumentFragment|\DOMDocumentFragment|\Dom\DocumentType|\DOMDocumentType $node The node to serialize
     * @param array $config The configuration parameters to use, if any. Possible options are as follows:
     *          booleanAttributeValues bool|null - Whether to include the values of boolean attributes on HTML elements during serialization. Per the standard this is true by default
     *          foreignVoidEndTags bool|null - Whether to print the end tags of foreign void elements rather than self-closing their start tags. Per the standard this is true by default
     *          groupElements bool|null - Group like "block" elements and insert extra newlines between groups
     *          indentStep int|null - The number of spaces or tabs (depending on setting of indentStep) to indent at each step. This is 1 by default and has no effect unless reformatWhitespace is true
     *          indentWithSpaces bool|null - Whether to use spaces or tabs to indent. This is true by default and has no effect unless reformatWhitespace is true
     *          printXMLDeclaration bool|null - Whether to print XML declarations or not. This is true by default and is irrelevant for HTML documents
     *          reformatWhitespace bool|null - Whether to reformat whitespace (pretty-print) or not. This is false by default
    */
    public static function serialize($node, array $config = []): string {
        if (
            !static::isElement($node) &&
            !static::isDocument($node) &&
            !static::isText($node) &&
            !static::isComment($node) &&
            !static::isProcessingInstruction($node) &&
            !static::isDocumentFragment($node) &&
            !static::isDocumentType($node)
        ) {
            throw new \InvalidArgumentException("\$node must be \"\Dom\Document|\DOMDocument|\Dom\Element|\DOMElement|\Dom\Text|\DOMText|\Dom\Comment|\DOMComment|\Dom\ProcessingInstruction|\DOMProcessingInstruction|\Dom\DocumentFragment|\DOMDocumentFragment|\Dom\DocumentType|\DOMDocumentType\"");
        }

        return self::serializeNode($node, self::verifyConfiguration($config));
    }

    /** Serializes the children of an HTML DOM node to a string. This is equivalent to the innerHTML getter
     *
     * @param \Dom\Document|\DOMDocument|\Dom\Element|\DOMElement|\Dom\DocumentFragment|\DOMDocumentFragment $node The node to serialize
     * @param array $config The configuration parameters to use, if any
    */
    public static function serializeInner($node, array $config = []): string {
        if (
            !static::isElement($node) &&
            !static::isDocument($node) &&
            !static::isDocumentFragment($node)
        ) {
            throw new \InvalidArgumentException("\$node must be \"\Dom\Document|\DOMDocument|\Dom\Element|\DOMElement|\Dom\DocumentFragment|\DOMDocumentFragment\"");
        }

        return self::serializeInnerNodes($node, self::verifyConfiguration($config));
    }


    /**
     * @param \Dom\Document|\DOMDocument|\Dom\Element|\DOMElement|\Dom\DocumentFragment|\DOMDocumentFragment $node
     */
    protected static function serializeInnerNodes($node, array $config): string {
        $s = '';

        if (static::isElement($node) && ($node->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
            // Use localName for comparisons — it's lowercase in both old and new DOM,
            // whereas tagName in new DOM returns uppercase for HTML elements.
            $localName = $node->localName;

            if (in_array($localName, self::VOID_ELEMENTS)) {
                return '';
            } elseif ($localName === 'template') {
                $node = static::getTemplateContent($node);
            }
        }

        if (static::isElement($node) || static::isDocument($node) || static::isDocumentFragment($node)) {
            foreach ($node->childNodes as $n) {
                $s .= self::serializeNode($n, $config);
                $config['first'] = false;
            }
        } else {
            throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($node)]);
        }

        return $s;
    }

    /**
     * @param \DOMNode|\Dom\Node $node
     */
    protected static function serializeNode($node, array $config): string {
        $newDOM = (is_a($node, '\Dom\Node'));
        $s = '';

        if (static::isElement($node)) {
            /** @var \DOMElement|\Dom\Element $node */
            extract($config);

            $namespaceURI = $node->namespaceURI ?? Parser::HTML_NAMESPACE;

            // Use localName for the serialized tag name — it's always lowercase for HTML
            // elements in both DOM implementations, and uncoerceName handles coercion.
            if (in_array($namespaceURI, [Parser::HTML_NAMESPACE, Parser::SVG_NAMESPACE, Parser::MATHML_NAMESPACE])) {
                $tagName = self::uncoerceName($node->localName);
            } else {
                $tagName = self::uncoerceName($node->tagName);
            }

            $htmlElement = $namespaceURI === Parser::HTML_NAMESPACE;

            if ($reformatWhitespace) {
                $modify = false;

                $preformattedContent = $preformattedContent ?: static::isPreformattedContent($node);
                if (!$preformattedContent || in_array($tagName, self::PREFORMATTED_ELEMENTS)) {
                    if ($htmlElement) {
                        if (!$first && self::treatAsBlock($node->parentNode)) {
                            $modify = true;
                        }
                    } elseif ($foreignAsBlock) {
                        $modify = true;
                    } else {
                        $parent = $node->parentNode;
                        if ($parent === null) {
                            $modify = true;
                            $foreignAsBlock = true;
                        } elseif (($parent->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            if (self::treatAsBlock($parent)) {
                                $modify = true;
                                $foreignAsBlock = true;
                            }
                        } elseif (static::treatForeignRootAsBlock($parent)) {
                            $modify = true;
                            $foreignAsBlock = true;
                        }
                    }

                    if (!$first && $modify) {
                        if ($config['groupElements']) {
                            $n = $node;
                            while ($n = $n->previousSibling) {
                                if (!static::isText($n)) {
                                    if (
                                        (!static::isElement($n) && !static::isDocumentType($n)) ||
                                        (static::isElement($n) && $n->localName !== $tagName && count(array_intersect([ $n->localName, $tagName ], self::H_ELEMENTS)) !== 2)
                                    ) {
                                        $s .= "\n";
                                    }
                                    break;
                                }
                            }
                        }

                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                if ($preformattedContent) {
                    $reformatWhitespace = false;
                }

                $first = false;
            }

            $s .= "<$tagName";

            foreach ($node->attributes as $a) {
                if ($a->namespaceURI === null) {
                    $name = self::uncoerceName($a->localName);
                } elseif ($a->namespaceURI === Parser::XML_NAMESPACE) {
                    $name = "xml:".self::uncoerceName($a->localName);
                } elseif ($a->namespaceURI === Parser::XMLNS_NAMESPACE) {
                    if ($a->localName === "xmlns") {
                        $name = "xmlns";
                    } else {
                        $name = "xmlns:".self::uncoerceName($a->localName);
                    }
                } elseif ($a->namespaceURI === Parser::XLINK_NAMESPACE) {
                    $name = "xlink:".self::uncoerceName($a->localName);
                } elseif ($newDOM) {
                    $name = ($a->prefix !== "") ? $a->prefix.":".$a->localName : $a->localName;
                } else {
                    $name = ($a->prefix !== "") ? $a->prefix.":".$a->name : $a->name;
                }

                $value = self::escapeString((string) $a->value, true);
                if (
                    $booleanAttributeValues
                    || !$htmlElement
                    || !isset(self::BOOLEAN_ATTRIBUTES[$name])
                    || is_array(self::BOOLEAN_ATTRIBUTES[$name]) && !in_array($tagName, self::BOOLEAN_ATTRIBUTES[$name])
                    || (strlen($value) && strtolower($value) !== $name)
                ) {
                    $s .= " $name=\"$value\"";
                } else {
                    $s .= " $name";
                }
            }

            // Handle template elements — get content fragment for both DOM types
            if ($htmlElement && $tagName === 'template') {
                $contentNode = static::getTemplateContent($node);
                $hasChildNodes = $contentNode->hasChildNodes();
            } else {
                $contentNode = $node;
                $hasChildNodes = $node->hasChildNodes();
            }

            if (!$foreignVoidEndTags && !$htmlElement && !$hasChildNodes) {
                $s .= '/>';
                return $s;
            }

            $s .= '>';

            if ($htmlElement && in_array($tagName, self::VOID_ELEMENTS)) {
                return $s;
            }

            if ($hasChildNodes) {
                $innerConfig = $config;

                if ($reformatWhitespace) {
                    $innerConfig['first'] = $first;
                    $innerConfig['indentionLevel'] = ++$indentionLevel;
                    $innerConfig['foreignAsBlock'] = $foreignAsBlock;
                    $innerConfig['preformattedContent'] = $preformattedContent;
                    $innerConfig['reformatWhitespace'] = $reformatWhitespace;
                }

                $s .= self::serializeInnerNodes($contentNode, $innerConfig);

                if ($reformatWhitespace) {
                    $indentionLevel--;

                    if (!$preformattedContent) {
                        $firstElementChild = null;
                        if (property_exists($contentNode, 'firstElementChild')) {
                            $firstElementChild = $contentNode->firstElementChild;
                        // @codeCoverageIgnoreStart
                        } else {
                            $n = $contentNode->firstChild;
                            do {
                                if (static::isElement($n)) {
                                    $firstElementChild = $n;
                                    break;
                                }
                            } while ($n = $n->nextSibling);
                        }
                        // @codeCoverageIgnoreEnd

                        if ($firstElementChild !== null && ($foreignAsBlock || ($htmlElement && self::treatAsBlock($node)))) {
                            $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                        }
                    }
                }
            }

            $s .= "</$tagName>";
        } elseif (static::isText($node)) {
            /** @var \DOMText|\Dom\Text $node */
            $p = $node->parentNode;
            // Use localName for RAWTEXT_ELEMENTS comparison
            if (static::isElement($p) && ($p->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && in_array($p->localName, self::RAWTEXT_ELEMENTS)) {
                $s .= $node->data;
            } else {
                $data = $node->data;
                if ($config['reformatWhitespace']) {
                    $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                    if (!$preformattedContent) {
                        $treatAsBlock = self::treatAsBlock($node);
                        if (
                            ($config['foreignAsBlock'] || $treatAsBlock || ($node->parentNode !== null && self::treatAsBlock($node->parentNode) && count($node->parentNode->childNodes) === 1)) &&
                            strspn($data, Data::WHITESPACE) === strlen($data)
                        ) {
                            return $s;
                        }

                        if ($treatAsBlock) {
                            $data = preg_replace('/[\t\n\x0c\x0D ]+/', ' ', trim($data));
                            if ($data === '') {
                                return $s;
                            }
                        } elseif (preg_match(Data::WHITESPACE_REGEX, $data)) {
                            $data = preg_replace([
                                '/[\t\n\x0c\x0D ]*\n[\t\n\x0c\x0D ]*/',
                                '/\t/',
                                '/\n/'
                            ], [
                                "\n",
                                ' ',
                                ' '
                            ], $data);

                            $xpath = static::createXPath($node);
                            $textNodes = $xpath->query('./ancestor::*[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"][name()="address" or name()="article" or name()="aside" or name()="blockquote" or name="body" or name()="canvas" or name()="dd" or name()="div" or name()="dl" or name()="dt" or name()="fieldset" or name()="figcaption" or name()="figure" or name()="footer" or name()="form" or name()="h1" or name()="h2" or name()="h3" or name()="h4" or name()="h5" or name()="h6" or name()="head" or name()="header" or name()="hr" or name()="html" or name()="li" or name()="main" or name()="nav" or name()="ol" or name()="p" or name()="section" or name()="table" or name()="tfoot" or name()="ul" or name()="video"][1]/descendant::text()[not(ancestor::template[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])]', $node);

                            if ($textNodes->length > 0) {
                                $firstOfLine = ($node === $textNodes->item(0));
                                $lastOfLine = ($node === $textNodes->item($textNodes->length - 1));
                            } else {
                                if ($node->parentNode === null) {
                                    $firstOfLine = $lastOfLine = true;
                                } else {
                                    $n = $node;
                                    while ($n = $n->parentNode) {
                                        $root = $n;
                                    }

                                    $textNodes = $xpath->query('.//text()[not(ancestor::template[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])]', $root);
                                    $firstOfLine = ($node === $textNodes->item(0));
                                    $lastOfLine = ($node === $textNodes->item($textNodes->length - 1));
                                }
                            }

                            $data = preg_replace('/ +/', ' ', $data);
                            if (!$firstOfLine) {
                                foreach ($textNodes as $key => $t) {
                                    // To get Intelephense to fuck off...
                                    /** @var (\DOMNodeList<\DOMText>|\Dom\NodeList<\DomText>)&\ArrayAccess $textNodes */
                                    if ($t === $node && preg_match('/[\t\n\x0c\x0D ]+$/', $textNodes[$key - 1]->data)) {
                                        $data = ltrim($data);
                                        break;
                                    }
                                }
                            }

                            if ($firstOfLine) {
                                $data = ltrim($data);
                            }
                            if ($lastOfLine) {
                                $data = rtrim($data);
                            }
                        }
                    }
                }

                $s .= self::escapeString($data);
            }
        } elseif (static::isComment($node)) {
            /** @var \DOMComment|\Dom\Comment $node */
            if ($config['reformatWhitespace'] && !$config['first']) {
                $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                if (!$preformattedContent && ($config['foreignAsBlock'] || self::treatAsBlock($node->parentNode))) {
                    $n = $node;
                    while ($n = $n->previousSibling) {
                        if (!static::isText($n)) {
                            if (!static::isComment($n)) {
                                $s .= "\n";
                            }
                            break;
                        }
                    }

                    $s .= "\n" . str_repeat($config['indentChar'], $config['indentionLevel'] * $config['indentStep']);
                }
            }

            $s .= "<!--{$node->data}-->";
        } elseif (static::isProcessingInstruction($node)) {
            /** @var \DOMProcessingInstruction|\Dom\ProcessingInstruction $node */
            if ($node->target !== 'xml' || $config['printXMLDeclaration']) {
                if ($config['reformatWhitespace'] && !$config['first']) {
                    $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                    if (!$preformattedContent && ($config['foreignAsBlock'] || self::treatAsBlock($node->parentNode))) {
                        $n = $node;
                        while ($n = $n->previousSibling) {
                            if (!static::isText($n)) {
                                if (!static::isProcessingInstruction($n)) {
                                    $s .= "\n";
                                }
                                break;
                            }
                        }

                        $s .= "\n" . str_repeat($config['indentChar'], $config['indentionLevel'] * $config['indentStep']);
                    }
                }

                $s .= '<?' . self::uncoerceName($node->target) . " {$node->data}>";
            }
        } elseif (static::isDocumentType($node)) {
            /** @var \DOMDocumentType|\Dom\DocumentType $node */
            if ($config['reformatWhitespace'] && !$config['first']) {
                $s .= "\n";
            }

            $s .= '<!DOCTYPE ' . trim($node->name) . '>';
        } elseif (static::isDocument($node) || static::isDocumentFragment($node)) {
            return self::serializeInnerNodes($node, $config);
        } else {
            throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($node)]);
        }

        return $s;
    }


    protected static function verifyConfiguration(array $config): array {
        $config['booleanAttributeValues'] = $config['booleanAttributeValues'] ?? true;
        $config['foreignVoidEndTags'] = $config['foreignVoidEndTags'] ?? true;
        $config['groupElements'] = $config['groupElements'] ?? true;
        $config['printXMLDeclaration'] = $config['printXMLDeclaration'] ?? true;
        $config['reformatWhitespace'] = $config['reformatWhitespace'] ?? false;

        if ($config['reformatWhitespace']) {
            $config['indentWithSpaces'] = $config['indentWithSpaces'] ?? true;
            $config['indentStep'] = $config['indentStep'] ?? 1;
        }

        foreach ($config as $key => $value) {
            switch ($key) {
                case 'booleanAttributeValues':
                case 'foreignVoidEndTags':
                case 'groupElements':
                case 'indentWithSpaces':
                case 'printXMLDeclaration':
                case 'reformatWhitespace':
                    if (!is_bool($value)) {
                        $type = gettype($value);
                        if ($type === 'object') {
                            $type = get_class($value);
                        }
                        trigger_error("Value for serializer configuration option \"$key\" must be a boolean; $type given", \E_USER_WARNING);
                        continue 2;
                    }
                break;
                case 'indentStep':
                    if (!is_int($value)) {
                        $type = gettype($value);
                        if ($type === 'object') {
                            $type = get_class($value);
                        }
                        trigger_error("Value for serializer configuration option \"$key\" must be an integer; $type given", \E_USER_WARNING);
                        continue 2;
                    }
                break;
                default:
                    trigger_error("\"$key\" is an invalid serializer configuration option", \E_USER_WARNING);
                    unset($config[$key]);
                    continue 2;
            }

            $config[$key] = $value;
        }

        if ($config['reformatWhitespace']) {
            $config['first'] = true;
            $config['indentChar'] = ($config['indentWithSpaces']) ? ' ' : "\t";
            $config['indentionLevel'] = 0;
            $config['foreignAsBlock'] = false;
            $config['preformattedContent'] = false;
        }

        return $config;
    }


    // Node type helpers — use is_a() with string class names so PHP 7.2 doesn't
    // fatal when \Dom\* classes don't exist

    /** @param mixed $node */
    protected static function isElement($node): bool {
        return $node instanceof \DOMElement || is_a($node, 'Dom\Element');
    }

    /** @param mixed $node */
    protected static function isDocument($node): bool {
        return $node instanceof \DOMDocument || is_a($node, 'Dom\Document');
    }

    /** @param mixed $node */
    protected static function isDocumentFragment($node): bool {
        return $node instanceof \DOMDocumentFragment || is_a($node, 'Dom\DocumentFragment');
    }

    /** @param mixed $node */
    protected static function isText($node): bool {
        return $node instanceof \DOMText || is_a($node, 'Dom\Text');
    }

    /** @param mixed $node */
    protected static function isComment($node): bool {
        return $node instanceof \DOMComment || is_a($node, 'Dom\Comment');
    }

    /** @param mixed $node */
    protected static function isProcessingInstruction($node): bool {
        return $node instanceof \DOMProcessingInstruction || is_a($node, 'Dom\ProcessingInstruction');
    }

    /** @param mixed $node */
    protected static function isDocumentType($node): bool {
        return $node instanceof \DOMDocumentType || is_a($node, 'Dom\DocumentType');
    }

    /**
     * Returns the owner document for either DOM type.
     *
     * @param \DOMNode|\Dom\Node $node
     * @return \DOMDocument|\Dom\Document
     */
    protected static function getOwnerDocument($node) {
        if (static::isDocument($node)) {
            return $node;
        }
        return $node->ownerDocument;
    }

    /**
     * Creates an XPath evaluator appropriate for the node's DOM type.
     *
     * @param \DOMNode|\Dom\Node $node
     * @return \DOMXPath|\Dom\XPath
     */
    protected static function createXPath($node) {
        $doc = static::getOwnerDocument($node);
        if (is_a($doc, 'Dom\Document')) {
            return new \Dom\XPath($doc);
        }
        return new \DOMXPath($doc);
    }


    /**
     * @param \DOMDocumentFragment|\Dom\DocumentFragment $fragment
     */
    protected static function fragmentHasHost($fragment): bool {
        return false;
    }

    /**
     * Returns the template content node. For new DOM, \Dom\HTMLTemplateElement has
     * a content property returning a DocumentFragment. For old DOM, subclasses must
     * override this method to provide template contents.
     *
     * NOTE: As of PHP 8.4, \Dom\HTMLTemplateElement is never actually instantiated
     * by the parser or createElement() — template elements are created as plain
     * \Dom\HTMLElement instead. The is_a() check below is therefore currently dead
     * code, but is retained for when the bug is fixed.
     * @see https://github.com/php/php-src/issues/23334
     *
     * @param \DOMElement|\Dom\Element $node
     * @return \DOMNode|\Dom\Node
     */
    protected static function getTemplateContent($node) {
        // @codeCoverageIgnoreStart
        if (is_a($node, 'Dom\HTMLTemplateElement')) {
            /** @var \Dom\HTMLTemplateElement $node */
            return $node->content;
        }
        // @codeCoverageIgnoreEnd
        // Old DOM: PHP's DOM does not support the content property on template elements
        // natively. Subclasses may override this to return template contents.
        return $node;
    }

    /**
     * @param \DOMNode|\Dom\Node $node
     */
    protected static function isPreformattedContent($node): bool {
        $n = $node;
        do {
            if (
                static::isElement($n) &&
                ($n->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE &&
                in_array($n->localName, self::PREFORMATTED_ELEMENTS)
            ) {
                return true;
            }
        } while ($n = $n->parentNode);

        return false;
    }

    /**
     * @param \DOMNode|\Dom\Node $node
     */
    protected static function treatAsBlock($node): bool {
        if (static::isDocument($node) || (static::isDocumentFragment($node) && !static::fragmentHasHost($node))) {
            return true;
        }

        if (!static::isElement($node) && !static::isDocumentFragment($node)) {
            $node = $node->parentNode;

            if ($node === null) {
                return false;
            }
        }

        $xpath = static::createXPath($node);
        $result = ($xpath->evaluate(self::BLOCK_QUERY, $node) > 0);

        if (!$result) {
            $result = static::treatAsBlockWithTemplates($node);
        }

        return $result;
    }

    /**
     * @param \DOMNode|\Dom\Node $node
     */
    protected static function treatAsBlockWithTemplates($node): bool {
        return false;
    }

    /**
     * @param \DOMNode|\Dom\Node $node
     */
    protected static function treatForeignRootAsBlock($node): bool {
        $n = $node;
        do {
            if ($n->parentNode !== null && ($n->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE) {
                continue;
            }

            if (self::treatAsBlock($n->parentNode)) {
                return true;
            }

            break;
        } while ($n = $n->parentNode);

        return false;
    }
}