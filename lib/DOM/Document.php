<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Document extends \DOMDocument {
    use EscapeString, Node, Serialize, Walk;

    // Quirks mode constants
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    public $documentEncoding = null;
    public $mangledAttributes = false;
    public $mangledElements = false;
    public $quirksMode = self::NO_QUIRKS_MODE;

    protected $_body = null;

    public function __construct() {
        parent::__construct();

        $this->registerNodeClass('DOMComment', '\MensBeam\HTML\Comment');
        $this->registerNodeClass('DOMDocumentFragment', '\MensBeam\HTML\DocumentFragment');
        $this->registerNodeClass('DOMElement', '\MensBeam\HTML\Element');
        $this->registerNodeClass('DOMProcessingInstruction', '\MensBeam\HTML\ProcessingInstruction');
        $this->registerNodeClass('DOMText', '\MensBeam\HTML\Text');
    }

    public function createAttribute($name) {
        return $this->createAttributeNS(null, $name);
    }

    public function createAttributeNS($namespaceURI, $qualifiedName) {
        // Normalize the attribute name and namespace URI per modern DOM specifications.
        if ($namespaceURI !== null) {
            $namespaceURI = trim($namespaceURI);
        }
        $qualifiedName = trim($qualifiedName);

        try {
            return parent::createAttributeNS($namespaceURI, $qualifiedName);
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledAttributes = true;
            $qualifiedName = $this->coerceName($qualifiedName);
            return $this->createAttributeNS($namespaceURI, $qualifiedName);
        }
    }

    public function createElement($name, $value = "") {
        return $this->createElementNS(null, $name, $value);
    }

    public function createElementNS($namespaceURI, $qualifiedName, $value = "") {
        // Normalize the element name and namespace URI per modern DOM specifications.
        if ($namespaceURI !== null) {
            $namespaceURI = trim($namespaceURI);
            $namespaceURI = ($namespaceURI === Parser::HTML_NAMESPACE) ? null : $namespaceURI;
        }
        $qualifiedName = ($namespaceURI === null) ? strtolower(trim($qualifiedName)) : trim($qualifiedName);

        try {
            if ($qualifiedName !== 'template' || $namespaceURI !== null) {
                $e = parent::createElementNS($namespaceURI, $qualifiedName, $value);
            } else {
                $e = new TemplateElement($this, $qualifiedName, $value);
                // Template elements need to have a reference kept in userland
                ElementMap::set($e);
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

    public function createEntityReference($name): bool {
        return false;
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
        } elseif (!$node->ownerDocument->isSameNode($this)) {
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

    public function __destruct() {
        ElementMap::destroy($this);
    }

    public function __get(string $prop) {
        if ($prop === 'body') {
            if ($this->documentElement === null || $this->documentElement->childNodes->length === 0) {
                return null;
            }

            $body = null;

            # The body element of a document is the first of the html element's children
            # that is either a body element or a frameset element, or null if there is no
            # such element.
            $n = $this->documentElement->firstChild;
            do {
                if ($n instanceof Element && $n->namespaceURI === null && ($n->nodeName === 'body' || $n->nodeName === 'frameset')) {
                    $body = $n;
                }
            } while ($n = $n->nextSibling);

            if ($body !== null) {
                // References are handled weirdly by PHP's DOM. Return a stored body element
                // unless it is changed so operations (like classList) can be done without
                // losing the reference.
                if ($body !== $this->_body) {
                    $this->_body = $body;
                }

                return $this->_body;
            }

            $this->_body = null;
            return null;
        }
    }

    public function __set(string $prop, $value) {
        if ($prop === 'body') {
            # On setting, the following algorithm must be run:
            #
            # 1. If the new value is not a body or frameset element, then throw a
            # "HierarchyRequestError" DOMException.
            if (!$value instanceof Element || $value->namespaceURI !== null) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }
            if ($value->nodeName !== 'body' && $value->nodeName !== 'frameset') {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }

            if ($this->_body !== null) {
                # 2. Otherwise, if the new value is the same as the body element, return.
                if ($value->isSameNode($this->_body)) {
                    return;
                }

                # 3. Otherwise, if the body element is not null, then replace the body element
                # with the new value within the body element's parent and return.
                $this->documentElement->replaceChild($value, $this->_body);
                $this->_body = $value;
                return;
            }

            # 4. Otherwise, if there is no document element, throw a "HierarchyRequestError"
            # DOMException.
            if ($this->documentElement === null) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }

            # 5. Otherwise, the body element is null, but there's a document element. Append
            # the new value to the document element.
            $this->documentElement->appendChild($value);
            $this->_body = $value;
        }
    }

    public function __toString() {
        return $this->serialize();
    }
}
