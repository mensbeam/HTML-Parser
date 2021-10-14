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

    public function seerializeOuter(\DOMNode $node): string {
        $s = "";
        $stack = [];
        $n = $node;
        do {
            # If current node is an Element
            if ($n instanceof \DOMElement) {
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
                    $s .= " ".self::serializeAttribute($a);
                }
                # Append a U+003E GREATER-THAN SIGN character (>).
                $s .= ">";
                # If current node serializes as void, then continue on to the
                #   next child node at this point.
                # Append the value of running the HTML fragment serialization
                #   algorithm on the current node element (thus recursing into
                #   this algorithm for that element), followed by a 
                #   U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS
                #   character (/), tagname again, and finally a
                #   U+003E GREATER-THAN SIGN character (>).
                if (($n->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && !in_array($tagName, self::VOID_ELEMENTS)) {
                    if ($n->hasChildNodes()) {
                        $stack[] = $tagName;
                        $n = $n->firstChild;
                        continue;
                    } else {
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
                if (($n->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && in_array($n->parentNode->tagName, self::RAWTEXT_ELEMENTS)) {
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
                return self::serializeInner($n);
            } else {
                throw new Exception(Exception::UNSUPPORTED_NODE_TYPE, [get_class($n)]);
            }
            while (!$n->nextSibling && $stack) {
                $tagName = array_pop($stack);
                $s .= "</$tagName>";
                $n = $n->parentNode;
            }
            if (!$stack && $n->isSameNode($node)) {
                break;
            }
            $n = $n->nextSibling;
        } while (true);
        return $s;
    }

    protected static function serializeAttribute(\DOMAttr $a): string {
        # For each attribute that the element has, append a 
        #   U+0020 SPACE character, the attribute's serialized name as
        #   described below, a U+003D EQUALS SIGN character (=), a
        #   U+0022 QUOTATION MARK character ("), the attribute's
        #   value, escaped as described below in attribute mode, and
        #   a second U+0022 QUOTATION MARK character (").
        // NOTE: We won't add the space here; it's only appropriate
        //   if serializing an element.
        
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
                $a = "xmlns";
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
            $name = $a->name;
        }
        $value = self::escapeString($a->value);
        return "$name=\"$value\"';"
    }
}
