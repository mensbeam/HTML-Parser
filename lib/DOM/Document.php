<?php
declare(strict_types=1);
namespace dW\HTML5;

class Document extends \DOMDocument {
    use Descendant, Printing;

    public function __construct() {
        parent::__construct();

        $this->registerNodeClass('DOMComment', '\dW\HTML5\Comment');
        $this->registerNodeClass('DOMDocumentFragment', '\dW\HTML5\DocumentFragment');
        $this->registerNodeClass('DOMElement', '\dW\HTML5\Element');
        $this->registerNodeClass('DOMProcessingInstruction', '\dW\HTML5\ProcessingInstruction');
        $this->registerNodeClass('DOMText', '\dW\HTML5\Text');
    }

    public function fixIdAttributes() {
        // TODO: Accept DOMDocumentFragment, append it to a document, fix shit, and
        // then poop out a fragment so selecting id attributes works on fragments.

        // Fix id attributes so they may be selected by the DOM. Fix the PHP id attribute
        // bug. Allows DOMDocument->getElementById() to work on id attributes.
        $this->relaxNGValidateSource('<grammar xmlns="http://relaxng.org/ns/structure/1.0" datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
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

        $this->normalize();
    }

    public function load($source, $options = null): bool {
        Parser::parse((string)$source, $this, true);

        return true;
    }

    public function loadHTML($source, $options = null): bool {
        Parser::parse((string)$source, $this);

        return true;
    }

    public function loadXML($source, $options = null) {}
}
