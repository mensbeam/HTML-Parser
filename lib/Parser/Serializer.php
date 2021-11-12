<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

abstract class Serializer {
    use NameCoercion;

    // Elements treated as block elements when reformatting whitespace
    protected const BLOCK_ELEMENTS = [ 'address', 'article', 'aside', 'blockquote', 'base', 'body', 'details', 'dialog', 'dd', 'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'isindex', 'li', 'link', 'main', 'meta', 'nav', 'ol', 'p', 'picture', 'pre', 'section', 'script', 'source', 'style', 'table', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'ul' ];
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

    protected const BLOCK_QUERY = 'count(./descendant::*[namespace-uri()="" or namespace-uri()="http://www.w3.org/1999/xhtml"][name()="address" or name()="article" or name()="aside" or name()="blockquote" or name()="base" or name()="body" or name()="details" or name()="dialog" or name()="dd" or name()="div" or name()="dl" or name()="dt" or name()="fieldset" or name()="figcaption" or name()="figure" or name()="footer" or name()="form" or name()="frame" or name()="frameset" or name()="h1" or name()="h2" or name()="h3" or name()="h4" or name()="h5" or name()="h6" or name()="head" or name()="header" or name()="hr" or name()="html" or name()="isindex" or name()="li" or name()="link" or name()="main" or name()="meta" or name()="nav" or name()="ol" or name()="p" or name()="picture" or name()="pre" or name()="section" or name()="script" or name()="source" or name()="style" or name()="table" or name()="td" or name()="tfoot" or name()="th" or name()="thead" or name()="title" or name()="tr" or name()="ul"][1])';

    /** Serializes an HTML DOM node to a string. This is equivalent to the outerHTML getter
     *
     * @param \DOMDocument|\DOMElement|\DOMText|\DOMComment|\DOMProcessingInstruction|\DOMDocumentFragment|\DOMDocumentType $node The node to serialize
     * @param \MensBeam\HTML\Parser\Config|null $config The configuration parameters to use, if any
    */
    public static function serialize(\DOMNode $node, ?Config $config = null): string {
        $config = $config ?? new Config;
        $boolAttr = $config->serializeBooleanAttributeValues ?? true;
        $endTags = $config->serializeForeignVoidEndTags ?? true;
        $reformatWhitespace = $config->reformatWhitespace ?? false;

        if ($reformatWhitespace) {
            $indentStep = $config->indentStep ?? 1;
            $indentChar = ($config->indentWithSpaces ?? true) ? ' ' : "\t";
        }

        $s = "";
        $stack = [];
        $n = $node;

        if ($reformatWhitespace) {
            $first = true;
            $indentionLevel = 0;
            $modifyStack = [];
        }

        do {
            # If current node is an Element
            if ($n instanceof \DOMElement) {
                $htmlElement = ($n->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE;
                # If current node is an element in the HTML namespace,
                #   the MathML namespace, or the SVG namespace, then let
                #   tagname be current node's local name. Otherwise, let
                #   tagname be current node's qualified name.
                if (in_array($n->namespaceURI ?? Parser::HTML_NAMESPACE, [Parser::HTML_NAMESPACE, Parser::SVG_NAMESPACE, Parser::MATHML_NAMESPACE])) {
                    $tagName = self::uncoerceName($n->localName);
                } else {
                    $tagName = self::uncoerceName($n->tagName);
                }

                if ($reformatWhitespace) {
                    $hasChildNodes = $n->hasChildNodes();
                    $modify = false;

                    // If the node is an HTML element, and either its tag name is in the list of
                    // elements to be treated as block for the purposes of serialization or the
                    // element itself is to be treated as block then we need to modify whitespace.
                    if ($htmlElement) {
                        if (in_array($tagName, self::BLOCK_ELEMENTS) || self::treatAsBlock($n)) {
                            $modify = true;
                        }
                    } else {
                        // If a foreign element with an html element parent
                        if (($n->parentNode ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            // If the foreign element should be treated as block then we need to modify
                            // whitespace
                            $modify = self::treatAsBlock($n);
                        }
                        // Otherwise, walk up the DOM and find the root foreign ancestor. If that
                        // ancestor is to be treated as block then we need to modify whitespace.
                        else {
                            $modify = self::treatForeignRootAsBlock($n);
                        }
                    }

                    // If we're modifying whitespace...
                    if ($modify) {
                        $previousNonTextNodeSiblingName = null;
                        $nn = $n;
                        while ($nn = $nn->previousSibling) {
                            if (!$nn instanceof \DOMText) {
                                $previousNonTextNodeSiblingName = $nn->nodeName;
                                break;
                            }
                        }

                        // If the previous non text node sibling doesn't have the same name as the
                        // current node and neither are h1-h6 elements then add an additional newline.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $tagName && count(array_intersect([ $previousNonTextNodeSiblingName, $tagName ], self::H_ELEMENTS)) !== 2) {
                            $s .= "\n";
                        }


                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
                $s .= "<$tagName";
                # If current node's is value is not null, and the element does
                #   not have an is attribute in its attribute list, then
                #   append the string " is="", followed by current node's is
                #   value escaped as described below in attribute mode,
                #   followed by a U+0022 QUOTATION MARK character (").
                // DEVIATION: We don't support custom elements
                # For each attribute that the element has, append a
                #   U+0020 SPACE character, the attribute's serialized name as
                #   described below, a U+003D EQUALS SIGN character (=), a
                #   U+0022 QUOTATION MARK character ("), the attribute's
                #   value, escaped as described below in attribute mode, and
                #   a second U+0022 QUOTATION MARK character (").
                foreach ($n->attributes as $a) {
                    # An attribute's serialized name for the purposes of the previous
                    #   paragraph must be determined as follows:

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
                        $boolAttr
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

                # Append a U+003E GREATER-THAN SIGN character (>).
                // If we're minimizing void foreign elements, insert a slash first where appropriate
                if (!$endTags && !$htmlElement && !$n->hasChildNodes()) {
                    $s .= "/>";
                } else {
                    $s .= ">";
                    # If current node serializes as void, then continue on to the next child node at
                    # this point.
                    # Append the value of running the HTML fragment serialization algorithm on the
                    # current node element (thus recursing into this algorithm for that element),
                    # followed by a U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS character (/),
                    # tagname again, and finally a U+003E GREATER-THAN SIGN character (>).
                    if (($n->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE || !in_array($tagName, self::VOID_ELEMENTS)) {
                        # If the node is a template element, then let the node instead be the template
                        # element's template contents (a DocumentFragment node).
                        if ($htmlElement && $tagName === "template") {
                            // NOTE: Templates are handled by their own serializer
                            // Disable pretty printing when serializing templates in preformatted content
                            $templateConfig = $config;
                            $isPreformattedContent = self::isPreformattedContent($n);
                            if ($reformatWhitespace && $isPreformattedContent) {
                                $templateConfig->reformatWhitespace = false;
                            }

                            $ss = self::serializeTemplate($n, $templateConfig);

                            if ($reformatWhitespace) {
                                if (!$isPreformattedContent && $indentionLevel > 0) {
                                    $firstChild = self::templateFirstChild($n);
                                    if (self::treatAsBlock($firstChild)) {
                                        $ss = explode("\n", $ss);
                                        foreach ($ss as $key => $value) {
                                            $ss[$key] = str_repeat($indentChar, ($indentionLevel + 1) * $indentStep) . $ss[$key];
                                        }
                                        $ss = implode("\n", $ss);
                                    }
                                }
                            }

                            $s .= $ss;
                        } elseif ($n->hasChildNodes()) {
                            if ($reformatWhitespace) {
                                // If formatting output and the element's whitespace has already been modified
                                // increment the indention level
                                $indentionLevel++;
                                $prettyPrintStack[] = $n;
                            }

                            // If the element has children, store its tag name and continue the loop with
                            // its first child; its end tag will be written out further down
                            $stack[] = $tagName;
                            $n = $n->firstChild;
                            continue;
                        }

                        // Otherwise just append the end tag now
                        $s .= "</$tagName>";
                    }
                }
            }
            # If current node is a Text node
            elseif ($n instanceof \DOMText) {
                # If the parent of current node is a style, script, xmp,
                #   iframe, noembed, noframes, or plaintext element, or
                #   if the parent of current node is a noscript element
                #   and scripting is enabled for the node, then append
                #   the value of current node's data IDL attribute literally.
                $p = $n->parentNode;
                if ($p instanceof \DOMElement && ($p->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && in_array($p->tagName, self::RAWTEXT_ELEMENTS)) {
                    // NOTE: scripting is assumed not to be enabled
                    $s .= $n->data;
                }
                # Otherwise, append the value of current node's data IDL attribute, escaped as described below.
                else {
                    $t = $n->data;
                    if ($reformatWhitespace && !self::isPreformattedContent($n)) {
                        if (self::treatAsBlock($n) || self::treatForeignRootAsBlock($n)) {
                            // If the text node's data is made up of only whitespace characters continue
                            // onto the next node
                            if (strspn($t, Data::WHITESPACE) === strlen($t)) {
                                // FIXME: this is temporary
                                goto next;
                            }
                        }

                        // Condense spaces and tabs into a single space.
                        $t = preg_replace('/ +/', ' ', str_replace("\t", '    ', $t));
                    }

                    $s .= self::escapeString($t);
                }
            }
            # If current node is a Comment
            elseif ($n instanceof \DOMComment) {
                if ($reformatWhitespace && !self::isPreformattedContent($n)) {
                    $modify = false;
                    $parentIsHTMLElement = (($n->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE);
                    if ($parentIsHTMLElement) {
                        if (self::treatAsBlock($n)) {
                            $modify = true;
                        }
                    } else {
                        if ($n->parentNode->parentNode !== null && ($n->parentNode->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            if (self::treatAsBlock($n)) {
                                $modify = true;
                            }
                        } elseif (self::treatForeignRootAsBlock($n)) {
                            $modify = true;
                        }
                    }

                    if ($modify) {
                        $previousNonTextNodeSiblingName = null;
                        $nn = $n;
                        while ($nn = $nn->previousSibling) {
                            if (!$nn instanceof \DOMText) {
                                $previousNonTextNodeSiblingName = $nn->nodeName;
                                break;
                            }
                        }

                        // Add an additional newline if the previous sibling wasn't a comment.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $n->nodeName) {
                            $s .= "\n";
                        }

                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                # Append the literal string "<!--" (U+003C LESS-THAN SIGN,
                #   U+0021 EXCLAMATION MARK, U+002D HYPHEN-MINUS,
                #   U+002D HYPHEN-MINUS), followed by the value of current
                #   node's data IDL attribute, followed by the literal
                #   string "-->" (U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS,
                #   U+003E GREATER-THAN SIGN).
                $s .= "<!--".$n->data."-->";
            }
            # If current node is a ProcessingInstruction
            elseif ($n instanceof \DOMProcessingInstruction) {
                if ($reformatWhitespace && !self::isPreformattedContent($n)) {
                    $modify = false;
                    $parentIsHTMLElement = (($n->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE);
                    if ($parentIsHTMLElement) {
                        if (self::treatAsBlock($n)) {
                            $modify = true;
                        }
                    } else {
                        if ($n->parentNode->parentNode !== null && ($n->parentNode->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            if (self::treatAsBlock($n)) {
                                $modify = true;
                            }
                        } elseif (self::treatForeignRootAsBlock($n)) {
                            $modify = true;
                        }
                    }

                    if ($modify) {
                        $previousNonTextNodeSiblingName = null;
                        $nn = $n;
                        while ($nn = $nn->previousSibling) {
                            if (!$nn instanceof \DOMText) {
                                $previousNonTextNodeSiblingName = $nn->nodeName;
                                break;
                            }
                        }

                        // Add an additional newline if the previous sibling wasn't a comment.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $n->nodeName) {
                            $s .= "\n";
                        }

                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                # Append the literal string "<?" (U+003C LESS-THAN SIGN,
                #   U+003F QUESTION MARK), followed by the value of
                #   current node's target IDL attribute, followed by a
                #   single U+0020 SPACE character, followed by the value
                #   of current node's data IDL attribute, followed by a
                #   single U+003E GREATER-THAN SIGN character (>).
                $s .= "<?".self::uncoerceName($n->target)." ".$n->data.">";
            }
            # If current node is a DocumentType
            elseif ($n instanceof \DOMDocumentType) {
                # Append the literal string "<!DOCTYPE" (U+003C LESS-THAN SIGN,
                #   U+0021 EXCLAMATION MARK, U+0044 LATIN CAPITAL LETTER D,
                #   U+004F LATIN CAPITAL LETTER O, U+0043 LATIN CAPITAL LETTER C,
                #   U+0054 LATIN CAPITAL LETTER T, U+0059 LATIN CAPITAL LETTER Y,
                #   U+0050 LATIN CAPITAL LETTER P, U+0045 LATIN CAPITAL LETTER E),
                #   followed by a space (U+0020 SPACE), followed by the value
                #   of current node's name IDL attribute, followed by the
                #   literal string ">" (U+003E GREATER-THAN SIGN).
                $s .= "<!DOCTYPE ".trim($n->name).">";
            }
            // NOTE: Documents and document fragments have no outer content,
            //   so we can just serialize the inner content
            elseif ($n instanceof \DOMDocument || $n instanceof \DOMDocumentFragment) {
                return self::serializeInner($n, $config);
            } else {
                throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($n)]);
            }

            next:
            // If the current node has no more siblings, go up the tree till a
            //   sibling is found or we've reached the original node
            while (!$n->nextSibling && $stack) {
                // Write out the stored end tag each time we go up the tree
                $tagName = array_pop($stack);

                if ($reformatWhitespace) {
                    $indentionLevel--;
                    $tag = array_pop($prettyPrintStack);
                    $modify = false;

                    if (!self::isPreformattedContent($n)) {
                        if (($tag->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                            if (self::treatAsBlock($tag->firstChild)) {
                                $modify = true;
                            }
                        } else {
                            $firstElementChild = null;
                            if (property_exists($tag, 'firstElementChild')) {
                                $firstElementChild = $tag->firstElementChild;
                            } else {
                                $t = $tag->firstChild;
                                do {
                                    if ($t instanceof \DOMElement) {
                                        $firstElementChild = $t;
                                        break;
                                    }
                                } while ($t = $t->nextSibling);
                            }

                            if ($firstElementChild !== null) {
                                if (($tag->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                                    if (self::treatAsBlock($tag)) {
                                        $modify = true;
                                    }
                                } elseif (self::treatForeignRootAsBlock($tag)) {
                                    $modify = true;
                                }
                            }
                        }
                    }

                    if ($modify) {
                        $s .= "\n" . str_repeat($indentChar, $indentionLevel * $indentStep);
                    }
                }

                $s .= "</$tagName>";
                $n = $n->parentNode;
            }
            $n = $n->nextSibling;
        } while ($stack);  // Loop until we have traversed the subtree of the target node in full
        return $s;
    }

    /** Serializes the children of an HTML DOM node to a string. This is equivalent to the innerHTML getter
     *
     * @param \DOMDocument|\DOMElement|\DOMDocumentFragment $node The node to serialize
     * @param \MensBeam\HTML\Parser\Config|null $config The configuration parameters to use, if any
    */
    public static function serializeInner(\DOMNode $node, ?Config $config = null): string {
        # Let s be a string, and initialize it to the empty string.
        $s = "";

        if ($node instanceof \DOMElement && ($node->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
            # If the node serializes as void, then return the empty string.
            if (in_array($node->tagName, self::VOID_ELEMENTS)) {
                return "";
            }
            # If the node is a template element, then let the node instead be the template
            # element's template contents (a DocumentFragment node).
            elseif ($node->tagName === "template") {
                return self::serializeTemplate($node, $config);
            }
        }
        if ($node instanceof \DOMElement || $node instanceof \DOMDocument || $node instanceof \DOMDocumentFragment) {
            # For each child node of the node, in tree order, run the following steps:
            // NOTE: the steps in question are implemented in the "serialize" routine
            foreach ($node->childNodes as $n) {
                $s .= self::serialize($n, $config);
            }
        } else {
            throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($node)]);
        }

        return $s;
    }


    protected static function serializeTemplate(\DOMElement $node, ?Config $config = null): string {
        # Let s be a string, and initialize it to the empty string.
        $s = "";

        // NOTE: PHP's DOM does not support the content property on template elements
        // natively, so there is nothing additional to do here. This method exists
        // purely so implementors of userland PHP DOM solutions may extend this method
        // to get template contents how they need them.

        # For each child node of the node, in tree order, run the following steps:
        // NOTE: the steps in question are implemented in the "serialize" routine
        foreach ($node->childNodes as $n) {
            $s .= self::serialize($n, $config);
        }

        return $s;
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

    protected static function templateFirstChild(\DOMElement $template): \DOMNode {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // be able to get the template's first child.
        return $template->firstChild;
    }

    protected static function treatAsBlock(\DOMNode $node): bool {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // check for any templates and look within their content fragments for "block"
        // content.

        if ($node->parentNode === null) {
            return false;
        }

        if ($node->parentNode instanceof \DOMDocument) {
            return true;
        }

        $xpath = new \DOMXPath($node->ownerDocument);
        return ($xpath->evaluate(self::BLOCK_QUERY, $node->parentNode) > 0);
    }

    protected static function treatForeignRootAsBlock(\DOMNode $node): bool {
        // NOTE: This method is used only when pretty printing. Implementors of userland
        // PHP DOM solutions with template contents will need to extend this method to
        // be able to moonwalk through document fragment hosts.
        $n = $node;
        while ($n = $n->parentNode) {
            if (($n->parentNode->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE) {
                if (self::treatAsBlock($n)) {
                    return true;
                }
                break;
            }
        }

        return false;
    }
}
