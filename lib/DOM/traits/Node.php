<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Extensions to PHP's DOM cannot inherit from an extended Node parent, so a
// trait is the next best thing...
trait Node {
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

    // Disable C14N
    public function C14N($exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
    }

    // Disable C14NFile
    public function C14NFile($uri, $exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
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
        if (!$this instanceof Document && !$this instanceof DocumentFragment && !$this instanceof Element) {
            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
        }

        # 2. If node is a host-including inclusive ancestor of parent, then throw a
        # "HierarchyRequestError" DOMException.
        #
        # An object A is a host-including inclusive ancestor of an object B, if either
        # A is an inclusive ancestor of B, or if B’s root has a non-null host and A is a
        # host-including inclusive ancestor of B’s root’s host.
        // DEVIATION: The baseline for this library is PHP 7.1, and without
        // WeakReferences we cannot add a host property to DocumentFragment to check
        // against.
        if ($node instanceof Element && $node->isAncestorOf($this)) {
            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
        }

        # 3. If child is non-null and its parent is not parent, then throw a
        # "NotFoundError" DOMException.
        if ($child !== null && !$child->parentNode->isSameNode($this)) {
            throw new DOMException(DOMException::NOT_FOUND);
        }

        # 4. If node is not a DocumentFragment, DocumentType, Element, Text,
        # ProcessingInstruction, or Comment node, then throw a "HierarchyRequestError"
        # DOMException.
        if (!$node instanceof DocumentFragment && !$node instanceof \DOMDocumentType && !$node instanceof Element && !$node instanceof Text && !$node instanceof ProcessingInstruction && !$node instanceof Comment) {
            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
        }

        # 5. If either node is a Text node and parent is a document, or node is a
        # doctype and parent is not a document, then throw a "HierarchyRequestError"
        # DOMException.
        if (($node instanceof Text && $this instanceof Document) || ($node instanceof \DOMDocumentType && !$this instanceof Document)) {
            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
        }

        # 6. If parent is a document, and any of the statements below, switched on node,
        # are true, then throw a "HierarchyRequestError" DOMException.
        if ($this instanceof Document) {
            # DocumentFragment node
            #    If node has more than one element child or has a Text node child.
            #    Otherwise, if node has one element child and either parent has an element
            #    child, child is a doctype, or child is non-null and a doctype is following
            #    child.
            if ($node instanceof DocumentFragment) {
                if ($node->childNodes->length > 1 || $node->firstChild instanceof Text) {
                    throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                } else {
                    if ($node->firstChild instanceof \DOMDocumentType) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }

                    foreach ($this->childNodes as $c) {
                        if ($c instanceof Element) {
                            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                        }
                    }

                    if ($child !== null) {
                        $n = $child;
                        while ($n = $n->nextSibling) {
                            if ($n instanceof \DOMDocumentType) {
                                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                            }
                        }
                    }
                }
            }
            # element
            #    parent has an element child, child is a doctype, or child is non-null and a
            #    doctype is following child.
            elseif ($node instanceof Element) {
                if ($child instanceof \DOMDocumentType) {
                    throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                }

                if ($child !== null) {
                    $n = $child;
                    while ($n = $n->nextSibling) {
                        if ($n instanceof \DOMDocumentType) {
                            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                        }
                    }
                }

                foreach ($this->childNodes as $c) {
                    if ($c instanceof Element) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }
            }

            # doctype
            #    parent has a doctype child, child is non-null and an element is preceding
            #    child, or child is null and parent has an element child.
            elseif ($node instanceof \DOMDocumentType) {
                foreach ($this->childNodes as $c) {
                    if ($c instanceof \DOMDocumentType) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }

                if ($child !== null) {
                    $n = $child;
                    while ($n = $n->prevSibling) {
                        if ($n instanceof Element) {
                            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                        }
                    }
                } else {
                    foreach ($this->childNodes as $c) {
                        if ($c instanceof Element) {
                            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                        }
                    }
                }
            }
        }
    }
}
