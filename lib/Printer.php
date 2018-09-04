<?php
declare(strict_types=1);
namespace dW\HTML5;

class Printer {

    public static function serialize($node): string {
        if (!$node instanceof DOMElement && !$node instanceof DOMDocument && !$node instanceof DOMDocumentFragment) {
            throw new Exception(Exception::PRINTER_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED, gettype($node));
        }

        # 1. Let s be a string, and initialize it to the empty string.
        $s = '';

        # 2. If the node is a template element, then let the node instead be the
        # template element’s template contents (a DocumentFragment node).
        // TODO

        # 3. For each child node of the node, in tree order, run the following steps:
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            # 1. Let current node be the child node being processed.
            $currentNode = $node->childNodes->item($i);

            # 2. Append the appropriate string from the following list to s:

            # If current node is an Element
            if ($currentNode instanceof DOMElement) {
                # If current node is an element in the HTML namespace, the MathML namespace,
                # or the SVG namespace, then let tagname be current node’s local name.
                # Otherwise, let tagname be current node’s qualified name.
                if (is_null($currentNode->namespaceURI) || $currentNode->namespaceURI === Parser::MATHML_NAMESPACE || $currentNode->namespaceURI === Parser::SVG_NAMESPACE) {
                    $tagName = $currentNode->localName;
                } else {
                    $tagName = $currentNode->nodeName;
                }

                # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
                $s .= "<$tagName";

                # For each attribute that the element has, append a U+0020 SPACE character,
                # the attribute’s serialized name as described below, a U+003D EQUALS SIGN
                # character (=), a U+0022 QUOTATION MARK character ("), the attribute’s value,
                # escaped as described below in attribute mode, and a second U+0022 QUOTATION
                # MARK character (").
                for ($j = 0; $j < $currentNode->attributes->length; $j++) {
                    $attr = $currentNode->attributes->item($j);

                    # An attribute’s serialized name for the purposes of the previous paragraph
                    # must be determined as follows:
                    switch ($attr->namespaceURI) {
                        # If the attribute has no namespace
                        case null:
                            # The attribute’s serialized name is the attribute’s local name.
                            $name = $attr->localName;
                        break;
                        # If the attribute is in the XML namespace
                        case Parser::XML_NAMESPACE:
                            # The attribute’s serialized name is the string "xml:" followed by the
                            # attribute’s local name.
                            $name = 'xml:' . $attr->localName;
                        break;
                        # If the attribute is in the XMLNS namespace...
                        case Parser::XMLNS_NAMESPACE:
                            # ...and the attribute’s local name is xmlns
                            if ($attr->localName === 'xmlns') {
                                # The attribute’s serialized name is the string "xmlns".
                                $name = 'xmlns';
                            }
                            # ... and the attribute’s local name is not xmlns
                            else {
                                # The attribute’s serialized name is the string "xmlns:" followed by the
                                # attribute’s local name.
                                $name = 'xmlns:' . $attr->localName;
                            }
                        break;
                        # If the attribute is in the XLink namespace
                        case Parser::XLINK_NAMESPACE:
                            # The attribute’s serialized name is the string "xlink:" followed by the
                            # attribute’s local name.
                            $name = 'xlink:' . $attr->localName;
                        break;
                        # If the attribute is in some other namespace
                        default:
                            # The attribute’s serialized name is the attribute’s qualified name.
                            $name = $attr->name;
                    }

                    $value = static::escapeString($attr->value, true);

                    $s .= " $name=\"$value\"";
                }

                # While the exact order of attributes is UA-defined, and may depend on factors
                # such as the order that the attributes were given in the original markup, the
                # sort order must be stable, such that consecutive invocations of this
                # algorithm serialize an element’s attributes in the same order.
                // Okay.

                # Append a U+003E GREATER-THAN SIGN character (>).
                $s .= '>';

                # If current node is an area, base, basefont, bgsound, br, col, embed, frame,
                # hr, img, input, link, meta, param, source, track or wbr element, then continue
                # on to the next child node at this point.
                $currentNodeName = $currentNode->nodeName;
                if ($currentNodeName === 'area' || $currentNodeName === 'base' || $currentNodeName === 'basefont' || $currentNodeName === 'bgsound' || $currentNodeName === 'br' || $currentNodeName === 'col' || $currentNodeName === 'embed' || $currentNodeName === 'frame' || $currentNodeName === 'hr' || $currentNodeName === 'img' || $currentNodeName === 'input' || $currentNodeName === 'link' || $currentNodeName === 'meta' || $currentNodeName === 'param' || $currentNodeName === 'source' || $currentNodeName === 'track' || $currentNodeName === 'wbr') {
                    continue;
                }

                # Append the value of running the HTML fragment serialization algorithm on the
                # current node element (thus recursing into this algorithm for that element),
                # followed by a U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS character (/),
                # tagname again, and finally a U+003E GREATER-THAN SIGN character (>).

                $s .= static::serialize($currentNode);
                $s .= "</$currentNodeName>";
            }
        }
    }

    protected static escapeString(string $string, bool $attribute = false): string {
        # Escaping a string (for the purposes of the algorithm above) consists of
        # running the following steps:
        ## 1. Replace any occurrence of the "&amp;" character by the string "&amp;amp;".
        ## 2. Replace any occurrences of the U+00A0 NO-BREAK SPACE character by the
        ## string "&amp;nbsp;".
        $string = str_replace(['&amp;', chr(0x00A0)], ['&amp;amp;', '&amp;nbsp;'], $string);
        ## 3. If the algorithm was invoked in the attribute mode, replace any
        ## occurrences of the "&quot;" character by the string "&amp;quot;".
        ## 4. If the algorithm was not invoked in the attribute mode, replace any
        ## occurrences of the "&lt;" character by the string "&amp;lt;", and any
        ## occurrences of the "&gt;" character by the string "&amp;gt;".
        if ($attribute) {
            $string = str_replace(['&quot;', '&lt;', '&gt;'], ['&amp;quot;', '&amp;lt;', '&amp;gt;'], $string);
        }

        return $string;
    }
}