<?php
declare(strict_types=1);
namespace dW\HTML5;

class DOM {
    public static function getAncestor(mixed $needle, \DOMElement $context): \DOMElement {
        return static::ancestor($needle, $context, true);
    }

    public static function hasAncestor(mixed $needle, \DOMElement $context): bool {
        return static::ancestor($needle, $context, false);
    }

    public static function getDescendant(mixed $needle, \DOMElement $context): \DOMNode {
        return static::descendant($needle, $context, true);
    }

    public static function hasDescendant(mixed $needle, \DOMElement $context): bool {
        return static::descendant($needle, $context, false);
    }

    public static function isMathMLTextIntegrationPoint(\DOMElement $node): bool {
        return (
            $node->namespaceURI === Parser::MATHML_NAMESPACE && (
                $node->nodeName === 'mi' || $node->nodeName === 'mo' || $node->nodeName === 'mn' || $node->nodeName === 'ms' || $node->nodeName === 'mtext'
            )
        );
    }

    public static function isHTMLIntegrationPoint(\DOMElement $node): bool {
        $encoding = strtolower($node->getAttribute('encoding'));

        return ((
                $node->namespaceURI === Parser::MATHML_NAMESPACE &&
                $node->nodeName === 'annotation-xml' && (
                    $encoding === 'text/html' || $encoding === 'application/xhtml+xml'
                )
            ) || (
                $node->namespaceURI === Parser::SVG_NAMESPACE && (
                    $node->nodeName === 'foreignObject' || $node->nodeName === 'desc' || $node->nodeName === 'title'
                )
            )
        );
    }

    public static function fixIdAttributes(\DOMDocument $dom) {
        // TODO: Accept DOMDocumentFragment, append it to a document, fix shit, and
        // then poop out a fragment so selecting id attributes works on fragments.

        // Fix id attributes so they may be selected by the DOM. Fix the PHP id attribute
        // bug. Allows DOMDocument->getElementById() to work on id attributes.
        $dom->relaxNGValidateSource('<grammar xmlns="http://relaxng.org/ns/structure/1.0" datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
 <start>
  <element>
   <anyName/>
   <ref name="anythingID"/>
  </element>
 </start>
 <define name="anythingID">
  <zeroOrMore>
   <choice>
    <element>
     <anyName/>
     <ref name="anythingID"/>
    </element>
    <attribute name="id"><data type="ID"/></attribute>
    <zeroOrMore><attribute><anyName/></attribute></zeroOrMore>
    <text/>
   </choice>
  </zeroOrMore>
 </define>
</grammar>');

        $dom->normalize();
        return $dom;
    }

    protected static function ancestor(mixed $needle, \DOMElement $context, bool $returnNode = true) {
        while ($context = $context->parentNode) {
            $result = static::compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        }

        return ($returnNode === true) ? null : false;
    }

    protected static function compare(mixed $needle, \DOMNode $context): \DOMNode {
        if (is_string($needle)) {
            if ($context instanceof \DOMElement && $context->nodeName == $needle) {
                return $context;
            }
        } elseif ($needle instanceof \DOMNode) {
            if ($context->isSameNode($needle)) {
                return $context;
            }
        } elseif ($needle instanceof \Closure) {
            if ($needle($context) === true) {
                return $context;
            }
        } else {
            throw new Exception(Exception::DOM_DOMELEMENT_STRING_OR_CLOSURE_EXPECTED, gettype($needle));
        }

        return null;
    }

    protected static function descendant(mixed $needle, \DOMElement $context, bool $returnNode = true): \DOMNode {
        if ($context->hasChildNodes() === false) {
            return ($returnNode === true) ? null : false;
        }

        $context = $context->firstChild;

        do {
            $result = static::compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }

            $result = static::descendant($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        } while ($context = $context->nextSibling);

        return ($returnNode === true) ? null : false;
    }
}
