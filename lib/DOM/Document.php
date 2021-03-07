<?php
declare(strict_types=1);
namespace dW\HTML5;

class Document extends \DOMDocument {
    use Descendant, Serialize;

    // Quirks mode constants
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    public $quirksMode = self::NO_QUIRKS_MODE;

    // An array of all template elements created in the document
    // This exists because values of properties on derived DOM classes
    //   are lost unless at least one PHP reference is kept for the
    //   element somewhere in userspace. This is that somewhere.
    protected $templateElements = [];

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

    public function loadXML($source, $options = null) {
        throw new Exception(Exception::DOM_DISABLED_METHOD, __CLASS__, __FUNCTION__);
    }

    public function save($filename, $options = null) {
        throw new Exception(Exception::DOM_DISABLED_METHOD, __CLASS__, __FUNCTION__);
    }

    public function saveHTML(\DOMNode $node = null): string {
        return $this->serialize($node);
    }

    public function saveHTMLFile($filename) {}

    public function saveXML(\DOMNode $node = null, $options = null) {
        throw new Exception(Exception::DOM_DISABLED_METHOD, __CLASS__, __FUNCTION__);
    }

    public function createElement($name, $value = "") {
        $e = parent::createElement($name, $value);
        if ($name === "template") {
            $this->templateElements[] = $e;
            $e->content = $this->createDocumentFragment();
        }
        return $e;
    }

    public function createElementNS($namespaceURI, $qualifiedName, $value = "") {
        $e = parent::createElementNS($namespaceURI, $qualifiedName, $value);
        if ($qualifiedName === "template" && $namespaceURI === null) {
            $this->templateElements[] = $e;
            $e->content = $this->createDocumentFragment();
        }
        return $e;
    }

    public function __toString() {
        return $this->serialize();
    }
}
