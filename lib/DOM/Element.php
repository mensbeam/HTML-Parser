<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Element extends \DOMElement {
    use EscapeString, Moonwalk, Serialize, Walk;

    protected $_classList;

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
        try {
            if ($this->_classList !== null && $name === 'class') {
                $this->_classList->value = $value;
            } else {
                parent::setAttribute($name, $value);
            }
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

    public function setAttributeNS($namespaceURI, $qualifiedName, $value) {
        try {
            if ($namespaceURI === null && $this->_classList !== null && $qualifiedName === 'class') {
                $this->_classList->value = $value;
            } else {
                parent::setAttributeNS($namespaceURI, $qualifiedName, $value);
            }
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

    public function setAttributeNode(\DOMAttr $attribute) {
        parent::setAttributeNode($attribute);

        if ($attribute->name === 'id') {
            $this->setIdAttribute($attribute->name, true);
        }
    }

    public function setAttributeNodeNS(\DOMAttr $attribute) {
        parent::setAttributeNodeNS($attribute);

        if ($attribute->name === 'id') {
            $this->setIdAttribute($attribute->name, true);
        }
    }

    public function __get(string $prop) {
        switch ($prop) {
            case 'classList':
                // MensBeam\HTML\TokenList uses WeakReference to prevent a circular reference,
                // so it requires PHP 7.4 to work.
                if (version_compare(\PHP_VERSION, '7.4.0', '>=')) {
                    // Only create the class list if it is actually used.
                    if ($this->_classList === null) {
                        $this->_classList = new TokenList($this, 'class');
                    }

                    return $this->_classList;
                }

                return null;
            break;
            ### DOM Parsing Specification ###
            # 2.3 The InnerHTML mixin
            #
            # On getting, return the result of invoking the fragment serializing algorithm
            # on the context object providing true for the require well-formed flag (this
            # might throw an exception instead of returning a string).
            // DEVIATION: Parsing of XML documents will not be handled by this
            // implementation, so there's no need for the well-formed flag.
            case 'innerHTML': return $this->serialize($this);
            break;
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
            // OPTIMIZATION: When following the instructions above the fragment serializing
            // algorithm (Element::serialize) would invoke Element::__toString, so just
            // doing that instead of multiple function calls.
            case 'outerHTML': return $this->__toString();
            break;
        }
    }

    public function __set(string $prop, $value) {
        switch ($prop) {
            case 'innerHTML':
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
            break;

            case 'outerHTML':
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
            break;
        }
    }

    public function __toString(): string {
        # If current node is an element in the HTML namespace, the MathML namespace,
        # or the SVG namespace, then let tagname be current node’s local name.
        # Otherwise, let tagname be current node’s qualified name.
        if ($this->namespaceURI === null || $this->namespaceURI === Parser::MATHML_NAMESPACE || $this->namespaceURI === Parser::SVG_NAMESPACE) {
            $tagName = $this->localName;
        } else {
            $tagName = $this->nodeName;
        }

        // Since tag names can contain characters that are invalid in PHP's XML DOM
        // uncoerce the name when printing if necessary.
        if (strpos($tagName, 'U') !== false) {
            $tagName = $this->uncoerceName($tagName);
        }

        # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
        $s = "<$tagName";

        # If current node's is value is not null, and the element does not have an is
        # attribute in its attribute list, then append the string " is="", followed by
        # current node's is value escaped as described below in attribute mode, followed
        # by a U+0022 QUOTATION MARK character (").
        // DEVIATION: There is no scripting support in this implementation.

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

        # If current node serializes as void, then continue on to the next child node at
        # this point.
        if ($this->serializesAsVoid()) {
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
