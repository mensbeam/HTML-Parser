<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Element extends \DOMElement {
    use ContainerNode, DocumentOrElement, EscapeString, MagicProperties, Moonwalk, MoonwalkShallow, ToString, Walk, WalkShallow;

    protected $_classList;


    public function __get_classList(): ?TokenList {
        // MensBeam\HTML\TokenList uses WeakReference to prevent a circular reference,
        // so it requires PHP 7.4 to work.
        if (version_compare(\PHP_VERSION, '7.4.0', '>=')) {
            // Only create the class list if it is actually used.
            if ($this->_classList === null) {
                $this->_classList = new TokenList($this, 'class');
            }
            return $this->_classList;
        }
        return null; // @codeCoverageIgnore
    }

    public function __get_innerHTML(): string {
        ### DOM Parsing Specification ###
        # 2.3 The InnerHTML mixin
        #
        # On getting, return the result of invoking the fragment serializing algorithm
        # on the context object providing true for the require well-formed flag (this
        # might throw an exception instead of returning a string).
        // DEVIATION: Parsing of XML documents will not be handled by this
        // implementation, so there's no need for the well-formed flag.
        return $this->ownerDocument->serialize($this);
    }

    public function __set_innerHTML(string $value) {
        ### DOM Parsing Specification ###
        # 2.3 The InnerHTML mixin
        #
        # On setting, these steps must be run:
        # 1. Let context element be the context object's host if the context object is a
        # ShadowRoot object, or the context object otherwise.
        // DEVIATION: There is no scripting in this implementation.

        # 2. Let fragment be the result of invoking the fragment parsing algorithm with
        # the new value as markup, and with context element.
        $fragment = Parser::parseFragment($value, $this->ownerDocument, 'UTF-8', $this);

        # 3. If the context object is a template element, then let context object be the
        # template's template contents (a DocumentFragment).
        if ($this->nodeName === 'template') {
            $this->content = $fragment;
        }
        # 4. Replace all with fragment within the context object.
        else {
            # To replace all with a node within a parent, run these steps:
            #
            # 1. Let removedNodes be parent’s children.
            // DEVIATION: removedNodes is used below for scripting. There is no scripting in
            // this implementation.

            # 2. Let addedNodes be parent’s children.
            // DEVIATION: addedNodes is used below for scripting. There is no scripting in
            // this implementation.

            # 3. If node is a DocumentFragment node, then set addedNodes to node’s
            # children.

            // DEVIATION: Again, there is no scripting in this implementation.
            # 4. Otherwise, if node is non-null, set addedNodes to « node ».
            // DEVIATION: Yet again, there is no scripting in this implementation.

            # 5. Remove all parent’s children, in tree order, with the suppress observers
            # flag set.
            // DEVIATION: There are no observers to suppress as there is no scripting in
            // this implementation.
            while ($this->hasChildNodes()) {
                $this->removeChild($this->firstChild);
            }

            # 6. Otherwise, if node is non-null, set addedNodes to « node ».
            # If node is non-null, then insert node into parent before null with the
            # suppress observers flag set.
            // DEVIATION: Yet again, there is no scripting in this implementation.

            # 7. If either addedNodes or removedNodes is not empty, then queue a tree
            # mutation record for parent with addedNodes, removedNodes, null, and null.
            // DEVIATION: Normally the tree mutation record would do the actual replacement,
            // but there is no scripting in this implementation. Going to simply append the
            // fragment instead.
            $this->appendChild($fragment);
        }
    }

    public function __get_nextElementSibling(): Element {
        # The nextElementSibling getter steps are to return the first following sibling
        # that is an element; otherwise null.
        if ($this->parentNode !== null) {
            $start = false;
            foreach ($this->parentNode->childNodes as $child) {
                if (!$start) {
                    if ($child->isSameNode($this)) {
                        $start = true;
                    }

                    continue;
                }

                if (!$child instanceof Element) {
                    continue;
                }

                return $child;
            }
        }

        return null;
    }

    public function __get_outerHTML(): string {
        ### DOM Parsing Specification ###
        # 2.4 Extensions to the Element interface
        # outerHTML
        #
        # On getting, return the result of invoking the fragment serializing algorithm
        # on a fictional node whose only child is the context object providing true for
        # the require well-formed flag (this might throw an exception instead of
        # returning a string).
        // DEVIATION: Parsing of XML documents will not be handled by this
        // implementation, so there's no need for the well-formed flag.
        return $this->__toString();
    }

    public function __set_outerHTML(string $value) {
        ### DOM Parsing Specification ###
        # 2.4 Extensions to the Element interface
        # outerHTML
        #
        # On setting, the following steps must be run:
        # 1. Let parent be the context object's parent.
        $parent = $this->parentNode;

        # 2. If parent is null, terminate these steps. There would be no way to obtain a
        # reference to the nodes created even if the remaining steps were run.
        // The spec is unclear here as to what to do. What do you return? Most browsers
        // throw an exception here, so that's what we're going to do.
        if ($parent === null) {
            throw new DOMException(DOMException::OUTER_HTML_FAILED_NOPARENT);
        }
        # 3. If parent is a Document, throw a "NoModificationAllowedError" DOMException.
        elseif ($parent instanceof Document) {
            throw new DOMException(DOMException::NO_MODIFICATION_ALLOWED);
        }
        # 4. parent is a DocumentFragment, let parent be a new Element with:
        # • body as its local name,
        # • The HTML namespace as its namespace, and
        # • The context object's node document as its node document.
        elseif ($parent instanceof DocumentFragment) {
            $parent = $this->ownerDocument->createElement('body');
        }

        # 5. Let fragment be the result of invoking the fragment parsing algorithm with
        # the new value as markup, and parent as the context element.
        $fragment = Parser::parseFragment($value, $this->ownerDocument, 'UTF-8', $parent);

        # 6. Replace the context object with fragment within the context object's
        # parent.
        $this->parentNode->replaceChild($fragment, $this);
    }

    public function __get_previousElementSibling(): Element {
        # The previousElementSibling getter steps are to return the first preceding
        # sibling that is an element; otherwise null.
        if ($this->parentNode !== null) {
            foreach ($this->parentNode->childNodes as $child) {
                if ($child->isSameNode($this)) {
                    return null;
                }

                if (!$child instanceof Element) {
                    continue;
                }

                return $child;
            }
        }

        return null;
    }


    public function getAttribute($name) {
        // Newer versions of the DOM spec have getAttribute return an empty string only
        // when the attribute exists and is empty, otherwise null. This fixes that.
        $value = parent::getAttribute($name);
        if ($value === '' && !parent::hasAttribute($name)) {
            // the PHP DOM does not acknowledge the presence of XMLNS-namespace attributes
            foreach ($this->attributes as $a) {
                if ($a->nodeName === $name) {
                    return $a->value;
                }
            }
            return null;
        }
        return $value;
    }

    public function getAttributeNS($namespaceURI, $localName) {
        // Newer versions of the DOM spec have getAttributeNS return an empty string
        // only when the attribute exists and is empty, otherwise null. This fixes that.
        $value = parent::getAttributeNS($namespaceURI, $localName);
        if ($value === '' && !$this->hasAttributeNS($namespaceURI, $localName)) {
            return null;
        }
        return $value;
    }

    public function hasAttribute($name) {
        if (!parent::hasAttribute($name)) {
            foreach ($this->attributes as $a) {
                if ($a->nodeName === $name) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function setAttribute($name, $value) {
        $this->setAttributeNS(null, $name, $value);
    }

    public function setAttributeNS($namespaceURI, $qualifiedName, $value) {
        // Normalize the attribute name and namespace URI per modern DOM specifications.
        if ($namespaceURI !== null) {
            $namespaceURI = trim($namespaceURI);
        }
        $qualifiedName = trim($qualifiedName);
        if ($namespaceURI === null && ($this->namespaceURI ?? Parser::HTML_NAMESPACE) === Parser::HTML_NAMESPACE && !$this->hasAttributeNS($namespaceURI, $qualifiedName)) {
            $qualifiedName = trim(strtolower($qualifiedName));
        }
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
            $this->setAttributeNodeNS($a);
        } else {
            try {
                parent::setAttributeNS($namespaceURI, $qualifiedName, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $this->ownerDocument->mangledAttributes = true;
                if ($namespaceURI !== null) {
                    $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
                } else {
                    $qualifiedName = $this->coerceName($qualifiedName);
                }
                parent::setAttributeNS($namespaceURI, $qualifiedName, $value);
            }
            if ($qualifiedName === "id" && $namespaceURI === null) {
                $this->setIdAttribute($qualifiedName, true);
            }
        }
    }

    public function setAttributeNode(\DOMAttr $attribute) {
        return $this->setAttributeNodeNS($attribute, null);
    }

    public function setAttributeNodeNS(\DOMAttr $attribute) {
        $fixId = false;
        if ($attribute->namespaceURI === null) {
            if ($attribute->name === 'id') {
                $fixId = true;
            }
            // If appending a class attribute node, and classList has been invoked set
            // the class using classList instead of appending the attribute node. Will
            // return the created node instead. TokenList appends an attribute node
            // internally to set the class attribute, so to prevent an infinite call loop
            // from occurring, a check between the normalized value and classList's
            // serialized value is performed. The spec is vague on how this is supposed to
            // be handled.
            elseif ($this->_classList !== null && $attribute->name === 'class' && preg_replace(Data::WHITESPACE_REGEX, ' ', $attribute->value) !== $this->_classList->value) {
                $this->_classList->value = $attribute->value;
                return $this->getAttributeNode('class');
            }
        }
        $result = parent::setAttributeNodeNS($attribute);
        if ($fixId) {
            $this->setIdAttribute($attribute->name, true);
        }
        return $result;
    }
}
