<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Document extends \DOMDocument {
    use EscapeString, Moonwalk, Serialize, Walk;

    // Quirks mode constants
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    public $documentEncoding = null;
    public $mangledAttributes = false;
    public $mangledElements = false;
    public $quirksMode = self::NO_QUIRKS_MODE;

    // An array of all template elements created in the document
    // This exists because values of properties on derived DOM classes
    //   are lost unless at least one PHP reference is kept for the
    //   element somewhere in userspace. This is that somewhere.
    protected $templateElements = [];

    public function __construct() {
        parent::__construct();

        $this->registerNodeClass('DOMComment', '\MensBeam\HTML\Comment');
        $this->registerNodeClass('DOMDocumentFragment', '\MensBeam\HTML\DocumentFragment');
        $this->registerNodeClass('DOMElement', '\MensBeam\HTML\Element');
        $this->registerNodeClass('DOMProcessingInstruction', '\MensBeam\HTML\ProcessingInstruction');
        $this->registerNodeClass('DOMText', '\MensBeam\HTML\Text');
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

    public function createElement($name, $value = "") {
        try {
            if ($name !== 'template') {
                $e = parent::createElement($name, $value);
            } else {
                $e = new TemplateElement($this, $name, $value);
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
            if ($qualifiedName !== 'template' || $namespaceURI !== null) {
                $e = parent::createElementNS($namespaceURI, $qualifiedName, $value);
            } else {
                $e = new TemplateElement($this, $qualifiedName, $value);
                $this->templateElements[] = $e;
                $e->content = $this->createDocumentFragment();
            }

            return $e;
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledElements = true;
            if ($namespaceURI !== null) {
                $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
            } else {
                $qualifiedName = $this->coerceName($qualifiedName);
            }
            return parent::createElementNS($namespaceURI, $qualifiedName, $value);
        }
    }

    public function load($filename, $options = null, ?string $encodingOrContentType = null): bool {
        $data = Parser::fetchFile($filename, $encodingOrContentType);
        if (!$data) {
            return false;
        }
        [$data, $encodingOrContentType] = $data;
        Parser::parse($data, $this, $encodingOrContentType, null, (string)$filename);
        return true;
    }

    public function loadHTML($source, $options = null, ?string $encodingOrContentType = null): bool {
        assert(is_string($source), new DOMException(DOMException::STRING_EXPECTED, 'source', gettype($source)));
        Parser::parse($source, $this, $encodingOrContentType);
        return true;
    }

    public function loadHTMLFile($filename, $options = null, ?string $encodingOrContentType = null): bool {
        return $this->load($filename, $options, $encodingOrContentType);
    }

    public function loadXML($source, $options = null): bool {
        return false;
    }

    public function save($filename, $options = null) {
        return file_put_contents($filename, $this->serialize());
    }

    public function saveHTML(\DOMNode $node = null): string {
        if ($node === null) {
            $node = $this;
        } elseif ($node->ownerDocument !== $this) {
            throw new DOMException(DOMException::WRONG_DOCUMENT);
        }

        return $node->serialize();
    }

    public function saveHTMLFile($filename): int {
        return $this->save($filename);
    }

    public function saveXML(?\DOMNode $node = null, $options = null): bool {
        return false;
    }

    public function validate(): bool {
        return true;
    }

    public function xinclude($options = null): bool {
        return false;
    }

    public function __toString() {
        return $this->serialize();
    }
}
