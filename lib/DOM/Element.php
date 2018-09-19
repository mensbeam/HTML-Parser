<?php
declare(strict_types=1);
namespace dW\HTML5;

class Element extends \DOMElement {
    use Ancestor, Descendant, EscapeString, Serialize {
        Ancestor::compare insteadof Descendant;
    }

    // Used for template elements
    public $content = null;

    protected $selfClosingElements = ['area', 'base', 'basefont', 'bgsound', 'br', 'col', 'embed', 'frame', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    public function __construct(string $name, string $value = '', string $namespaceURI = '') {
        parent::__construct($name, $value, $namespaceURI);

        if ($name === 'template' && $namespaceURI === '') {
            $this->content = $this->ownerDocument->createDocumentFragment();
        }
    }

    public function isMathMLTextIntegrationPoint(): bool {
        return (
            $this->namespaceURI === Parser::MATHML_NAMESPACE && (
                $this->nodeName === 'mi' || $this->nodeName === 'mo' || $this->nodeName === 'mn' || $this->nodeName === 'ms' || $this->nodeName === 'mtext'
            )
        );
    }

    public function isHTMLIntegrationPoint(): bool {
        $encoding = strtolower($this->getAttribute('encoding'));

        return ((
                $this->namespaceURI === Parser::MATHML_NAMESPACE &&
                $this->nodeName === 'annotation-xml' && (
                    $encoding === 'text/html' || $encoding === 'application/xhtml+xml'
                )
            ) || (
                $this->namespaceURI === Parser::SVG_NAMESPACE && (
                    $this->nodeName === 'foreignObject' || $this->nodeName === 'desc' || $this->nodeName === 'title'
                )
            )
        );
    }

    public function __toString(): string {
        # If current node is an element in the HTML namespace, the MathML namespace,
        # or the SVG namespace, then let tagname be current node’s local name.
        # Otherwise, let tagname be current node’s qualified name.
        if (is_null($this->namespaceURI) || $this->namespaceURI === Parser::MATHML_NAMESPACE || $this->namespaceURI === Parser::SVG_NAMESPACE) {
            $tagName = $this->localName;
        } else {
            $tagName = $this->nodeName;
        }

        # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
        $s = "<$tagName";

        # For each attribute that the element has, append a U+0020 SPACE character,
        # the attribute’s serialized name as described below, a U+003D EQUALS SIGN
        # character (=), a U+0022 QUOTATION MARK character ("), the attribute’s value,
        # escaped as described below in attribute mode, and a second U+0022 QUOTATION
        # MARK character (").
        for ($j = 0; $j < $this->attributes->length; $j++) {
            $attr = $this->attributes->item($j);

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

            $value = $this->escapeString($attr->value, true);

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
        if (in_array($tagName, $this->selfClosingElements)) {
            return $s;
        }

        # Append the value of running the HTML fragment serialization algorithm on the
        # current node element (thus recursing into this algorithm for that element),
        # followed by a U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS character (/),
        # tagname again, and finally a U+003E GREATER-THAN SIGN character (>).
        $s .= $this->serialize($this);
        $s .= "</$tagName>";

        return $s;
    }
}
