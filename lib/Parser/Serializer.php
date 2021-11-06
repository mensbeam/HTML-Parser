<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

abstract class Serializer {
    use NameCoercion;

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

    /** Serializes an HTML DOM node to a string. This is equivalent to the outerHTML getter
     *
     * @param \DOMDocument|\DOMElement|\DOMText|\DOMComment|\DOMProcessingInstruction|\DOMDocumentFragment|\DOMDocumentType $node The node to serialize
     * @param \MensBeam\HTML\Parser\Config|null $config The configuration parameters to use, if any
    */
    public static function serialize(\DOMNode $node, ?Config $config = null): string {
        $config = $config ?? new Config;
        $boolAttr = $config->serializeBooleanAttributeValues ?? true;
        $endTags = $config->serializeForeignVoidEndTags ?? true;

        $s = "";
        $stack = [];
        $n = $node;
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
                    # If current node serializes as void, then continue on to the
                    #   next child node at this point.
                    # Append the value of running the HTML fragment serialization
                    #   algorithm on the current node element (thus recursing into
                    #   this algorithm for that element), followed by a
                    #   U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS
                    #   character (/), tagname again, and finally a
                    #   U+003E GREATER-THAN SIGN character (>).
                    if (($n->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE || !in_array($tagName, self::VOID_ELEMENTS)) {
                        # If the node is a template element, then let the node instead
                        #   be the template element's template contents
                        #   (a DocumentFragment node).
                        if ($htmlElement && $n->tagName === "template" && ((property_exists($node, "content") && $node->content instanceof \DOMDocumentFragment) || $node->ownerDocument instanceof \MensBeam\HTML\DOM\InnerNode\Document)) {
                            // NOTE: The inner serializer will determine what to do with template content
                            $s .= self::serializeInner($n, $config)."</$tagName>";
                        } elseif ($n->hasChildNodes()) {
                            // If the element has children, store its tag name and
                            //   continue the loop with its first child; its end
                            //   tag will be written out further down
                            $stack[] = $tagName;
                            $n = $n->firstChild;
                            continue;
                        } else {
                            // Otherwise just append the end tag now
                            $s .= "</$tagName>";
                        }
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
                    $s .= self::escapeString($n->data);
                }
            }
            # If current node is a Comment
            elseif ($n instanceof \DOMComment) {
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
            // If the current node has no more siblings, go up the tree till a
            //   sibling is found or we've reached the original node
            while (!$n->nextSibling && $stack) {
                // Write out the stored end tag each time we go up the tree
                $tagName = array_pop($stack);
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
            # If the node is a template element, then let the node instead
            #   be the template element's template contents
            #   (a DocumentFragment node).
            elseif ($node->tagName === "template") {
                // NOTE: template elements won't necessarily have a content
                //   property because PHP's DOM does not support this natively
                if (property_exists($node, "content") && $node->content instanceof \DOMDocumentFragment) {
                    $node = $node->content;
                }
                // Special case for MensBeam's DOM which wraps DOM classes. While traversing
                // the DOM occurs within its inner DOM, template contents are entirely in the
                // userland wrapper class, so that must be accounted for.
                elseif ($node->ownerDocument instanceof \MensBeam\HTML\DOM\InnerNode\Document) {
                    $node = $node->ownerDocument->getInnerNode($node->ownerDocument->getWrapperNode($node)->content); // @codeCoverageIgnore
                }
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
}
