<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Node in the DOM spec is dirty. Many nodes which inherit from it inherit
// methods it cannot use which all check for this and throw exceptions. This is
// for nodes which DO have child nodes.
trait ContainerNode {
    use Node;

    public function appendChild($node) {
        $this->preInsertionValidity($node);

        $result = parent::appendChild($node);
        if ($result !== false && $result instanceof TemplateElement) {
            if ($result instanceof TemplateElement) {
                ElementMap::set($result);
            }
        }
        return $result;
    }

    public function insertBefore($node, $child = null) {
        $this->preInsertionValidity($node, $child);

        $result = parent::insertBefore($node, $child);
        if ($result !== false) {
            if ($result instanceof TemplateElement) {
                ElementMap::set($result);
            }
            if ($child instanceof TemplateElement) {
                ElementMap::delete($child);
            }
        }
        return $result;
    }

    public function removeChild($child) {
        $result = parent::removeChild($child);
        if ($result !== false && $result instanceof TemplateElement) {
            ElementMap::delete($child);
        }
        return $result;
    }

    public function replaceChild($node, $child) {
        $result = parent::replaceChild($node, $child);
        if ($result !== false) {
            if ($result instanceof TemplateElement) {
                ElementMap::set($child);
            }
            if ($child instanceof TemplateElement) {
                ElementMap::delete($child);
            }
        }
        return $result;
    }

    protected function preInsertionValidity(\DOMNode $node, ?\DOMNode $child = null) {
        // "parent" in the spec comments below is $this

        # 1. If parent is not a Document, DocumentFragment, or Element node, then throw
        # a "HierarchyRequestError" DOMException.
        // Not necessary because they've been disabled and return hierarchy request
        // errors in "leaf nodes".

        # 2. If node is a host-including inclusive ancestor of parent, then throw a
        # "HierarchyRequestError" DOMException.
        #
        # An object A is a host-including inclusive ancestor of an object B, if either
        # A is an inclusive ancestor of B, or if B’s root has a non-null host and A is a
        # host-including inclusive ancestor of B’s root’s host.
        // DEVIATION: The baseline for this library is PHP 7.1, and without
        // WeakReferences we cannot add a host property to DocumentFragment to check
        // against.
        // This is handled just fine by PHP's DOM.

        # 3. If child is non-null and its parent is not parent, then throw a
        # "NotFoundError" DOMException.
        // This is handled just fine by PHP's DOM.

        # 4. If node is not a DocumentFragment, DocumentType, Element, Text,
        # ProcessingInstruction, or Comment node, then throw a "HierarchyRequestError"
        # DOMException.
        if (!$node instanceof DocumentFragment && !$node instanceof \DOMDocumentType && !$node instanceof Element && !$node instanceof Text && !$node instanceof ProcessingInstruction && !$node instanceof Comment) {
            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
        }

        # 5. If either node is a Text node and parent is a document, or node is a
        # doctype and parent is not a document, then throw a "HierarchyRequestError"
        # DOMException.
        // Not necessary because they've been disabled and return hierarchy request
        // errors in "leaf nodes".

        # 6. If parent is a document, and any of the statements below, switched on node,
        # are true, then throw a "HierarchyRequestError" DOMException.
        // Handled by the Document class.
    }


    public function __get(string $prop) {
        switch ($prop) {
            case 'childElementCount':
                # The childElementCount getter steps are to return the number of children of
                # this that are elements.
                $count = 0;
                foreach ($this->childNodes as $child) {
                    if ($child instanceof Element) {
                        $count++;
                    }
                }

                return $count;

            case 'firstElementChild':
                # The firstElementChild getter steps are to return the first child that is an
                # element; otherwise null.
                foreach ($this->childNodes as $child) {
                    if ($child instanceof Element) {
                        return $child;
                    }
                }
                return null;

            case 'lastElementChild':
                # The lastElementChild getter steps are to return the last child that is an
                # element; otherwise null.
                for ($i = $this->childNodes->length - 1; $i >= 0; $i--) {
                    $child = $this->childNodes->item($i);
                    if ($child instanceof Element) {
                        return $child;
                    }
                }

                return null;

            default:
                return null;
        }
    }
}
