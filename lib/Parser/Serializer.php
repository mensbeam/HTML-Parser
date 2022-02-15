<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

abstract class Serializer {
    use NameCoercion;

    // List of h-elements which are used to determine element grouping for the
    // purposes of reformatting whitespace
    protected const H_ELEMENTS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
    // List of preformatted elements where content is ignored for the purposes of
    // reformatting whitespace
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

    /* Used when reformatting whitespace when nodes are checked for being treated as block. */
    protected const BLOCK_QUERY = 'count(./*[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"][not(ancestor::iframe[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::listing[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noembed[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noframes[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::noscript[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::plaintext[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::pre[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::style[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::script[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::textarea[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::title[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"] or ancestor::xmp[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])][name()="address" or name()="article" or name()="aside" or name()="blockquote" or name()="base" or name()="body" or (name()="button" and not(last() = 1)) or name()="canvas" or name()="datalist" or name()="details" or name()="dialog" or name()="dd" or name()="div" or name()="dl" or name()="dt" or name()="fieldset" or name()="figcaption" or name()="figure" or name()="footer" or name()="form" or name()="frame" or name()="frameset" or name()="h1" or name()="h2" or name()="h3" or name()="h4" or name()="h5" or name()="h6" or name()="head" or name()="header" or name()="hr" or name()="html" or (name()="input" and not(last() = 1)) or name()="isindex" or name()="li" or name()="link" or name()="main" or name()="meta" or name()="nav" or name()="ol" or name()="optgroup" or name()="option" or (name()="output" and not(last() = 1)) or name()="p" or name()="picture" or name()="pre" or name()="section" or name()="select" or name()="script" or name()="source" or name()="style" or name()="table" or name()="tbody" or name()="td" or (name()="textarea" and not(last() = 1)) or name()="tfoot" or name()="th" or name()="thead" or name()="title" or name()="tr" or name()="ul" or name()="video"][1])';


    /** Serializes an HTML DOM node to a string. This is equivalent to the outerHTML getter
     *
     * @param \DOMDocument|\DOMElement|\DOMText|\DOMComment|\DOMProcessingInstruction|\DOMDocumentFragment|\DOMDocumentType $node The node to serialize
     * @param array $config The configuration parameters to use, if any. Possible options are as follows:
     *          booleanAttributeValues bool|null - Whether to include the values of boolean attributes on HTML elements during serialization. Per the standard this is true by default
     *          foreignVoidEndTags bool|null - Whether to print the end tags of foreign void elements rather than self-closing their start tags. Per the standard this is true by default
     *          groupElements bool|null - Group like "block" elements and insert extra newlines between groups
     *          indentStep int|null - The number of spaces or tabs (depending on setting of indentStep) to indent at each step. This is 1 by default and has no effect unless reformatWhitespace is true
     *          indentWithSpaces bool|null - Whether to use spaces or tabs to indent. This is true by default and has no effect unless reformatWhitespace is true
     *          reformatWhitespace bool|null - Whether to reformat whitespace (pretty-print) or not. This is false by default
    */
    public static function serialize(\DOMNode $node, array $config = []): string {
        return self::serializeNode($node, self::verifyConfiguration($config));
    }

    /** Serializes the children of an HTML DOM node to a string. This is equivalent to the innerHTML getter
     *
     * @param \DOMDocument|\DOMElement|\DOMDocumentFragment $node The node to serialize
     * @param array $config The configuration parameters to use, if any
    */
    public static function serializeInner(\DOMNode $node, array $config = []): string {
        return self::serializeInnerNodes($node, self::verifyConfiguration($config));
    }


    protected static function serializeInnerNodes(\DOMNode $node, array $config): string {
        # Let s be a string, and initialize it to the empty string.
        $s = '';

        if ($node instanceof \DOMElement && ($node->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
            # If the node serializes as void, then return the empty string.
            if (in_array($node->tagName, self::VOID_ELEMENTS)) {
                return '';
            }
            # If the node is a template element, then let the node instead be the template
            # element's template contents (a DocumentFragment node).
            elseif ($node->tagName === 'template') {
                $node = static::getTemplateContent($node);
            }
        }
        if ($node instanceof \DOMElement || $node instanceof \DOMDocument || $node instanceof \DOMDocumentFragment) {
            # For each child node of the node, in tree order, run the following steps:
            // NOTE: the steps in question are implemented in the "serialize" routine
            foreach ($node->childNodes as $n) {
                $s .= self::serializeNode($n, $config);
                $config['first'] = false;
            }
        } else {
            throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($node)]);
        }

        return $s;
    }

    protected static function serializeNode(\DOMNode $node, array $config): string {
        # 2. Let s be a string, and initialize it to the empty string.
        $s = '';

        # If current node is an Element
        if ($node instanceof \DOMElement) {
            extract($config);

            # If current node is an element in the HTML namespace, the MathML namespace, or
            # the SVG namespace, then let tagname be current node's local name.
            if (in_array($node->namespaceURI ?? Parser::HTML_NAMESPACE, [Parser::HTML_NAMESPACE, Parser::SVG_NAMESPACE, Parser::MATHML_NAMESPACE])) {
                $tagName = self::uncoerceName($node->localName);
            }
            # Otherwise, let tagname be current node's qualified name.
            else {
                $tagName = self::uncoerceName($node->tagName);
            }

            $htmlElement = ($node->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE;

            if ($reformatWhitespace) {
                $modify = false;

                $preformattedContent = $preformattedContent ?: static::isPreformattedContent($node);
                if (!$preformattedContent || in_array($node->tagName, self::PREFORMATTED_ELEMENTS)) {
                    // If the node is an HTML element...
                    if ($htmlElement) {
                        // If the element's parent is to be treated as block then we need to modify
                        // whitespace.
                        if (!$first && self::treatAsBlock($node->parentNode)) {
                            $modify = true;
                        }
                    }
                    // If the node is not an HTML element...
                    elseif ($foreignAsBlock) {
                        $modify = true;
                    } else {
                        // If the parent node is null then we need to modify whitespace; this means that
                        // it is the element itself that is being serialized. Foreign content without
                        // any context is printed as "block" content.
                        // If a foreign element with an html element parent and the foreign element
                        // should be treated as block then we also need to modify whitespace.
                        $parent = $node->parentNode;
                        if ($parent === null) {
                            $modify = true;
                            $foreignAsBlock = true;
                        } elseif (($parent->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            if (self::treatAsBlock($parent)) {
                                $modify = true;
                                $foreignAsBlock = true;
                            }
                        }
                        // Otherwise, if the node's parent is not an HTML element then moonwalk up
                        // the tree until the root foreign node is found, and if it is to be treated
                        // as block then we need to modify whitespace. This should only match when
                        // printing non-root foreign elements themselves while also being appended to
                        // the document.
                        // TODO: Figure out how to make this not fire on every single "inline" svg
                        // element.
                        elseif (static::treatForeignRootAsBlock($parent)) {
                            $modify = true;
                            $foreignAsBlock = true;
                        }
                    }

                    // Only modify here before printing the open tag if it's not the first element
                    // printed. Above whether to modify is still partially calculated because if
                    // printing just foreign nodes the foreignAsBlock flag needs to be set for any
                    // descendants.
                    if (!$first && $modify) {
                        // If the previous non text or non document type node sibling doesn't have the
                        // same name as the current node and neither are h1-h6 elements then add an
                        // additional newline. This causes like elements to be grouped together.
                        if ($config['groupElements']) {
                            $n = $node;
                            while ($n = $n->previousSibling) {
                                if (!$n instanceof \DOMText) {
                                    if ((!$n instanceof \DOMElement && !$n instanceof \DOMDocumentType) || ($n instanceof \DOMElement && $n->tagName !== $tagName && count(array_intersect([ $n->tagName, $tagName ], self::H_ELEMENTS)) !== 2)) {
                                        $s .= "\n";
                                    }
                                    break;
                                }
                            }
                        }

                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                // Disable whitespace reformatting when the content is preformatted.
                if ($preformattedContent) {
                    $reformatWhitespace = false;
                }

                $first = false;
            }

            # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
            $s .= "<$tagName";

            # If current node's is value is not null, and the element does not have an is
            # attribute in its attribute list, then append the string " is="", followed by
            # current node's is value escaped as described below in attribute mode, followed
            # by a U+0022 QUOTATION MARK character (").
            // DEVIATION: We don't support custom elements
            # For each attribute that the element has, append a U+0020 SPACE character, the
            # attribute's serialized name as described below, a U+003D EQUALS SIGN character (=),
            # a U+0022 QUOTATION MARK character ("), the attribute's value, escaped as
            # described below in attribute mode, and a second U+0022 QUOTATION MARK
            # character (").
            foreach ($node->attributes as $a) {
                # An attribute's serialized name for the purposes of the previous paragraph must
                # be determined as follows:

                # If the attribute has no namespace
                if ($a->namespaceURI === null) {
                    # The attribute's serialized name is the attribute's local name.
                    $name = self::uncoerceName($a->localName);
                }
                # If the attribute is in the XML namespace
                elseif ($a->namespaceURI === Parser::XML_NAMESPACE) {
                    # The attribute's serialized name is the string "xml:" followed
                    #   by the attribute's local name.
                    $name = "xml:".self::uncoerceName($a->localName);
                }
                # If the attribute is in the XMLNS namespace...
                elseif ($a->namespaceURI === Parser::XMLNS_NAMESPACE) {
                    #  ... and the attribute's local name is xmlns
                    if ($a->localName === "xmlns") {
                        # The attribute's serialized name is the string "xmlns".
                        $name = "xmlns";
                    }
                    # ... and the attribute's local name is not xmlns
                    else {
                        # The attribute's serialized name is the string "xmlns:"
                        #   followed by the attribute's local name.
                        $name = "xmlns:".self::uncoerceName($a->localName);
                    }
                }
                # If the attribute is in the XLink namespace
                elseif ($a->namespaceURI === Parser::XLINK_NAMESPACE) {
                    # The attribute's serialized name is the string "xlink:"
                    #   followed by the attribute's local name.
                    $name = "xlink:".self::uncoerceName($a->localName);
                }
                # If the attribute is in some other namespace
                else {
                    # The attribute's serialized name is the attribute's qualified name.
                    $name = ($a->prefix !== "") ? $a->prefix.":".$a->name : $a->name;
                }
                // retrieve the attribute value
                $value = self::escapeString((string) $a->value, true);
                if (
                    $booleanAttributeValues
                    || !$htmlElement
                    || !isset(self::BOOLEAN_ATTRIBUTES[$name])
                    || is_array(self::BOOLEAN_ATTRIBUTES[$name]) && !in_array($tagName, self::BOOLEAN_ATTRIBUTES[$name])
                    || (strlen($value) && strtolower($value) !== $name)
                ) {
                    // print the attribute value unless the stars align
                    $s .= " $name=\"$value\"";
                } else {
                    // omit the value if the stars do align
                    $s .= " $name";
                }
            }

            if ($htmlElement && $tagName === 'template') {
                $node = static::getTemplateContent($node);
                $hasChildNodes = $node->hasChildNodes();
            } else {
                $hasChildNodes = $node->hasChildNodes();
            }

            if (!$foreignVoidEndTags && !$htmlElement && !$hasChildNodes) {
                $s .= '/>';
                return $s;
            }

            # Append a U+003E GREATER-THAN SIGN character (>).
            $s .= '>';

            # If current node serializes as void, then continue on to the next child node at
            # this point.
            if ($htmlElement && in_array($tagName, self::VOID_ELEMENTS)) {
                return $s;
            }

            if ($hasChildNodes) {
                // PHP's compact function sucks. Sorry.
                $innerConfig = $config;

                if ($reformatWhitespace) {
                    $innerConfig['first'] = $first;
                    $innerConfig['indentionLevel'] = ++$indentionLevel;
                    $innerConfig['foreignAsBlock'] = $foreignAsBlock;
                    $innerConfig['preformattedContent'] = $preformattedContent;
                    $innerConfig['reformatWhitespace'] = $reformatWhitespace;
                }

                $s .= self::serializeInnerNodes($node, $innerConfig);

                if ($reformatWhitespace) {
                    $indentionLevel--;

                    if (!$preformattedContent) {
                        $modify = false;

                        $firstElementChild = null;
                        if (property_exists($node, 'firstElementChild')) {
                            $firstElementChild = $node->firstElementChild;
                        // @codeCoverageIgnoreStart
                        } else {
                            $n = $node->firstChild;
                            do {
                                if ($n instanceof \DOMElement) {
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
        }
        # If current node is a Text node
        elseif ($node instanceof \DOMText) {
            # If the parent of current node is a style, script, xmp,
            #   iframe, noembed, noframes, or plaintext element, or
            #   if the parent of current node is a noscript element
            #   and scripting is enabled for the node, then append
            #   the value of current node's data IDL attribute literally.
            $p = $node->parentNode;
            if ($p instanceof \DOMElement && ($p->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && in_array($p->tagName, self::RAWTEXT_ELEMENTS)) {
                // NOTE: scripting is assumed not to be enabled
                $s .= $node->data;
            }
            # Otherwise, append the value of current node's data IDL attribute, escaped as described below.
            else {
                $data = $node->data;
                if ($config['reformatWhitespace']) {
                    // The serializer should disable 'reformatWhitespace' on children of a
                    // preformatted element, but just in case check for it here.
                    $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                    if (!$preformattedContent) {
                        $treatAsBlock = self::treatAsBlock($node);
                        $modify = false;
                        if (($config['foreignAsBlock'] || $treatAsBlock || ($node->parentNode !== null && self::treatAsBlock($node->parentNode) && count($node->parentNode->childNodes) === 1)) && strspn($data, Data::WHITESPACE) === strlen($data)) {
                            return $s;
                        }

                        if ($treatAsBlock) {
                            // Block formatting context -- trim data and convert all whitespace to a single
                            // space
                            $data = preg_replace('/[\t\n\x0c\x0D ]+/', ' ', trim($data));
                            if ($data === '') {
                                return $s;
                            }
                        } elseif (preg_match(Data::WHITESPACE_REGEX, $data)) {
                            // Inline formatting context
                            $data = preg_replace([
                                // 1. Remove all whitespace before and after a newline
                                '/[\t\n\x0c\x0D ]*\n[\t\n\x0c\x0D ]*/',
                                // 2. Convert all tabs to a single space
                                '/\t/',
                                // 3. Convert all line breaks to a single space
                                '/\n/'
                            ], [
                                "\n",
                                ' ',
                                ' '
                            ], $data);

                            // Moonwalk and find the closest block element (actual block element, not
                            // elements treated as block for the purposes of serializing) then grab all
                            // descendant text nodes that aren't descendants of templates.
                            $xpath = new \DOMXPath($node->ownerDocument);
                            $textNodes = $xpath->query('./ancestor::*[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"][name()="address" or name()="article" or name()="aside" or name()="blockquote" or name="body" or name()="canvas" or name()="dd" or name()="div" or name()="dl" or name()="dt" or name()="fieldset" or name()="figcaption" or name()="figure" or name()="footer" or name()="form" or name()="h1" or name()="h2" or name()="h3" or name()="h4" or name()="h5" or name()="h6" or name()="head" or name()="header" or name()="hr" or name()="html" or name()="li" or name()="main" or name()="nav" or name()="ol" or name()="p" or name()="section" or name()="table" or name()="tfoot" or name()="ul" or name()="video"][1]/descendant::text()[not(ancestor::template[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])]', $node);

                            // If nothing was matched then the text node is either disconnected from its
                            // document and being serialized alone or an inline descendant of a document
                            // fragment.
                            if ($textNodes->length > 0) {
                                $firstOfLine = ($node === $textNodes->item(0));
                                $lastOfLine = ($node === $textNodes->item($textNodes->length - 1));
                            } else {
                                // If the text node is either disconnected from its document then firstOfLine
                                // and lastOfLine is true.
                                if ($node->parentNode === null) {
                                    $firstOfLine = $lastOfLine = true;
                                }
                                // Otherwise, it's an inline descendant of a document fragment. Find its root
                                // node and then grab all text node descendants of that fragment.
                                else {
                                    $n = $node;
                                    while ($n = $n->parentNode) {
                                        $root = $n;
                                    }

                                    $textNodes = $xpath->query('.//text()[not(ancestor::template[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"])]', $root);
                                    $firstOfLine = ($node === $textNodes->item(0));
                                    $lastOfLine = ($node === $textNodes->item($textNodes->length - 1));
                                }
                            }

                            // 4. Convert multiple spaces to a single space even across inline elements.
                            $data = preg_replace('/ +/', ' ', $data);
                            if (!$firstOfLine) {
                                foreach ($textNodes as $key => $t) {
                                    if ($t === $node && preg_match('/[\t\n\x0c\x0D ]+$/', $textNodes[$key - 1]->data)) {
                                        $data = ltrim($data);
                                        break;
                                    }
                                }
                            }

                            // 5. Spaces at the beginning and ending of a line (beginning and ending of
                            //    inline content) are removed.
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
        }
        # If current node is a Comment
        elseif ($node instanceof \DOMComment) {
            if ($config['reformatWhitespace'] && !$config['first']) {
                $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                if (!$preformattedContent && ($config['foreignAsBlock'] || self::treatAsBlock($node->parentNode))) {
                    $n = $node;
                    while ($n = $n->previousSibling) {
                        if (!$n instanceof \DOMText) {
                            if (!$n instanceof \DOMComment) {
                                $s .= "\n";
                            }

                            break;
                        }
                    }

                    $s .= "\n" . str_repeat($config['indentChar'], $config['indentionLevel'] * $config['indentStep']);
                }
            }

            # Append the literal string "<!--" (U+003C LESS-THAN SIGN, U+0021 EXCLAMATION
            # MARK, U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS), followed by the value of
            # current node’s data IDL attribute, followed by the literal string "-->"
            # (U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS, U+003E GREATER-THAN SIGN).
            $s .= "<!--{$node->data}-->";
        }
        # If current node is a ProcessingInstruction
        elseif ($node instanceof \DOMProcessingInstruction) {
            if ($config['reformatWhitespace'] && !$config['first']) {
                $preformattedContent = $config['preformattedContent'] ?: static::isPreformattedContent($node);
                if (!$preformattedContent && ($config['foreignAsBlock'] || self::treatAsBlock($node->parentNode))) {
                    $n = $node;
                    while ($n = $n->previousSibling) {
                        if (!$n instanceof \DOMText) {
                            if (!$n instanceof \DOMProcessingInstruction) {
                                $s .= "\n";
                            }

                            break;
                        }
                    }

                    $s .= "\n" . str_repeat($config['indentChar'], $config['indentionLevel'] * $config['indentStep']);
                }
            }

            # Append the literal string "<?" (U+003C LESS-THAN SIGN, U+003F QUESTION MARK),
            # followed by the value of current node’s target IDL attribute, followed by a
            # single U+0020 SPACE character, followed by the value of current node’s data
            # IDL attribute, followed by a single U+003E GREATER-THAN SIGN character (>).
            $s .= '<?' . self::uncoerceName($node->target) . " {$node->data}>";
        }
        # If current node is a DocumentType
        elseif ($node instanceof \DOMDocumentType) {
            if ($config['reformatWhitespace'] && !$config['first']) {
                $s .= "\n";
            }

            # Append the literal string "<!DOCTYPE" (U+003C LESS-THAN SIGN,
            #   U+0021 EXCLAMATION MARK, U+0044 LATIN CAPITAL LETTER D,
            #   U+004F LATIN CAPITAL LETTER O, U+0043 LATIN CAPITAL LETTER C,
            #   U+0054 LATIN CAPITAL LETTER T, U+0059 LATIN CAPITAL LETTER Y,
            #   U+0050 LATIN CAPITAL LETTER P, U+0045 LATIN CAPITAL LETTER E),
            #   followed by a space (U+0020 SPACE), followed by the value
            #   of current node's name IDL attribute, followed by the
            #   literal string ">" (U+003E GREATER-THAN SIGN).
            $s .= '<!DOCTYPE ' . trim($node->name) . '>';
        }
        // NOTE: Documents and document fragments have no outer content,
        //   so we can just serialize the inner content
        elseif ($node instanceof \DOMDocument || $node instanceof \DOMDocumentFragment) {
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

    protected static function fragmentHasHost(\DOMDocumentFragment $fragment): bool {
        // NOTE: PHP's DOM does not support the content property on template elements
        // natively. This method exists purely so implementors of userland PHP DOM
        // solutions may extend this method to get template contents how they need them.
        return false;
    }

    protected static function getTemplateContent(\DOMElement $node): \DOMNode {
        // NOTE: PHP's DOM does not support the content property on template elements
        // natively. This method exists purely so implementors of userland PHP DOM
        // solutions may extend this method to get template contents how they need them.
        return $node;
    }

    protected static function isPreformattedContent(\DOMNode $node): bool {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // be able to moonwalk through document fragment hosts.

        $n = $node;
        do {
            if ($n instanceof \DOMElement && ($n->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && in_array($n->tagName, self::PREFORMATTED_ELEMENTS)) {
                return true;
            }
        } while ($n = $n->parentNode);

        return false;
    }

    protected static function treatAsBlock(\DOMNode $node): bool {
        if ($node instanceof \DOMDocument || ($node instanceof \DOMDocumentFragment && !static::fragmentHasHost($node))) {
            return true;
        }

        if (!$node instanceof \DOMElement && !$node instanceof \DOMDocumentFragment) {
            $node = $node->parentNode;

            if ($node === null) {
                return false;
            }
        }

        $xpath = new \DOMXPath($node->ownerDocument);
        $result = ($xpath->evaluate(self::BLOCK_QUERY, $node) > 0);

        if (!$result) {
            $result = static::treatAsBlockWithTemplates($node);
        }

        return $result;
    }

    protected static function treatAsBlockWithTemplates(\DOMNode $node): bool {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // check for any templates and look within their content fragments for "block"
        // content.
        return false;
    }

    protected static function treatForeignRootAsBlock(\DOMNode $node): bool {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // be able to moonwalk through document fragment hosts.
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
