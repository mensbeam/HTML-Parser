<?php
declare(strict_types=1);
namespace dW\HTML5;

class Document extends \DOMDocument {
    use Descendant, Serialize, EscapeString;

    // Quirks mode constants
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    public $quirksMode = self::NO_QUIRKS_MODE;
    public $mangledElements = false;
    public $mangledAttributes = false;
    public $documentEncoding = null;

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

    public function load($source, $options = null, ?string $encodingOrContentType = null): bool {
        $data = Parser::fetchFile($source, $encodingOrContentType);
        if (!$data) {
            return false;
        }
        [$data, $encodingOrContentType] = $data;
        Parser::parse($data, $this, $encodingOrContentType, null, (string) $source);
        return true;
    }

    public function loadHTML($source, $options = null, ?string $encodingOrContentType = null): bool {
        Parser::parse((string)$source, $this, $encodingOrContentType);
        return true;
    }

    public function saveHTMLFile($filename) {}

    public function createElement($name, $value = "") {
        try {
            $e = parent::createElement($name, $value);
            if ($name === "template") {
                $this->templateElements[] = $e;
                $e->content = $this->createDocumentFragment();
            }
            return $e;
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H is the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledElements = true;
            $name = $this->coerceName($name);
            return parent::createElement($name, $value);
        }
    }

    public function createElementNS($namespaceURI, $qualifiedName, $value = "") {
        try {
            $e = parent::createElementNS($namespaceURI, $qualifiedName, $value);
            if ($qualifiedName === "template" && $namespaceURI === null) {
                $this->templateElements[] = $e;
                $e->content = $this->createDocumentFragment();
            }
            return $e;
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledElements = true;
            $qualifiedName = $this->coerceName($qualifiedName);
            return parent::createElementNS($namespaceURI, $qualifiedName, $value);
        }
    }

    public function createAttribute($name) {
        try {
            return parent::createAttribute($name);
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledAttributes = true;
            $name = $this->coerceName($name);
            return parent::createAttribute($name);
        }
    }

    public function createAttributeNS($namespaceURI, $qualifiedName) {
        try {
            return parent::createAttributeNS($namespaceURI, $qualifiedName);
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledAttributes = true;
            $qualifiedName = $this->coerceName($qualifiedName);
            return parent::createAttributeNS($namespaceURI, $qualifiedName);
        }
    }

    public function __toString() {
        return $this->serialize();
    }
}
