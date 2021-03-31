<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait Attributes {
    use EscapeString;

    protected $_classList;

    public function appendChild($node) {
        // If appending a class attribute node, and classList has been invoked set
        // the class using classList instead of appending the attribute node. Will
        // return the created node instead. TokenList appends an attribute node
        // internally to set the class attribute, so to prevent an infinite call loop
        // from occurring, a check between the normalized value and classList's
        // serialized value is performed. The spec is vague on how this is supposed to
        // be handled.
        if ($node instanceof \DOMAttr && $this->_classList !== null && $node->namespaceURI === null && $node->name === 'class' && preg_replace(Data::WHITESPACE_REGEX, ' ', $node->value) !== $this->_classList->value) {
            $this->_classList->value = $node->value;
            return $this->getAttributeNode('class');
        }

        $node = parent::appendChild($node);

        // Fix id attributes when appending attribute nodes.
        if ($node instanceof \DOMAttr && $node->namespaceURI === null && $node->name === 'id') {
            $this->setIdAttribute('id', true);
        }

        return $node;
    }

    public function getAttribute($name) {
        // Newer versions of the DOM spec have getAttribute return an empty string only
        // when the attribute exists and is empty, otherwise null. This fixes that.
        $value = parent::getAttribute($name);
        if ($value === '' && !$this->hasAttribute($name)) {
            return null;
        }

        return $value;
    }

    public function getAttributeNS($namespaceURI, $qualifiedName) {
        // Newer versions of the DOM spec have getAttributeNS return an empty string
        // only when the attribute exists and is empty, otherwise null. This fixes that.
        $value = parent::getAttributeNS($namespaceURI, $qualifiedName);
        if ($value === '' && !$this->hasAttribute($qualifiedName)) {
            return null;
        }

        return $value;
    }

    public function setAttribute($name, $value) {
        // If setting a class attribute and classList has been invoked use classList to
        // set it.
        if ($name === 'class' && $this->_classList !== null) {
            $this->_classList->value = $value;
        } else {
            try {
                parent::setAttribute($name, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $this->ownerDocument->mangledAttributes = true;
                $name = $this->coerceName($name);
                parent::setAttribute($name, $value);
            }
            if ($name === "id") {
                $this->setIdAttribute($name, true);
            }
        }
    }

    public function setAttributeNS($namespaceURI, $qualifiedName, $value) {
        // If setting a class attribute and classList has been invoked use classList to
        // set it.
        if ($qualifiedName === 'class' && $namespaceURI === null && $this->_classList !== null) {
            $this->_classList->value = $value;
        } elseif ($namespaceURI === Parser::XMLNS_NAMESPACE) {
            // NOTE: We create attribute nodes so that xmlns attributes
            //   don't get lost; otherwise they cannot be serialized
            $a = @$this->ownerDocument->createAttributeNS($namespaceURI, $qualifiedName);
            if ($a === false) {
                // The document element does not exist yet, so we need
                //   to insert this element into the document
                $this->ownerDocument->appendChild($this);
                $a = $this->ownerDocument->createAttributeNS($namespaceURI, $qualifiedName);
                $this->ownerDocument->removeChild($this);
            }
            $a->value = $this->escapeString($value, true);
            $this->appendChild($a);
        } else {
            try {
                parent::setAttributeNS($namespaceURI, $qualifiedName, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $this->ownerDocument->mangledAttributes = true;
                $qualifiedName = $this->coerceName($qualifiedName);
                parent::setAttributeNS($namespaceURI, $qualifiedName, $value);
            }
            if ($qualifiedName === "id" && $namespaceURI === null) {
                $this->setIdAttribute($qualifiedName, true);
            }
        }
    }

    public function setAttributeNode(\DOMAttr $attribute) {
        parent::setAttributeNode($attribute);
        if ($attribute->name === 'id') {
            $this->setIdAttribute($attribute->name, true);
        }
    }

    public function setAttributeNodeNS(\DOMAttr $attribute) {
        parent::setAttributeNodeNS($attribute);
        if ($attribute->name === 'id' && $attribute->namespaceURI === null) {
            $this->setIdAttribute($attribute->name, true);
        }
    }
}
