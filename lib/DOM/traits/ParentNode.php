<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

if (version_compare(\PHP_VERSION, '8.0', '>=')) {
    # 4.2.6. Mixin ParentNode
    trait ParentNode {
        public function __get_children(): \DOMNodeList {
            # The children getter steps are to return an HTMLCollection collection rooted at
            # this matching only element children.
            // DEVIATION: HTMLCollection doesn't exist in PHP's DOM, and \DOMNodeList is
            // almost identical; so, using that. PHP's DOM doesn't provide the end user any
            // way to create a \DOMNodeList from scratch, so going to cheat and use XPath to
            // make one for us.

            $isDocument = ($this instanceof Document);
            $document = ($isDocument) ? $this : $this->ownerDocument;
            return $document->xpath->query('//*', (!$isDocument) ? $this : null);
        }

        public function replaceChildren(...$nodes) {
            # The replaceChildren(nodes) method steps are:
            # 1. Let node be the result of converting nodes into a node given nodes and
            #    this’s node document.
            $node = $this->convertNodesToNode($nodes);
            # 2. Ensure pre-insertion validity of node into this before null.
            $this->preInsertionValidity($node);
            # 3. Replace all with node within this.
            #
            # To replace all with a node within a parent, run these steps:
            # 1. Let removedNodes be parent’s children.
            $removedNodes = $this->childNodes;
            # 2. Let addedNodes be the empty set.
            $addedNodes = [];
            # 3. If node is a DocumentFragment node, then set addedNodes to node’s children.
            if ($node instanceof DocumentFragment) {
                $addedNodes = $node->childNodes;
            }
            # 4. Otherwise, if node is non-null, set addedNodes to « node ».
            elseif ($node !== null) {
                $addedNodes = node;
            }
            # 5. Remove all parent’s children, in tree order, with the suppress observers
            # flag set.
            // DEVIATION: There is no scripting in this implementation, so cannnot set
            // suppress observers flag.
            while ($this->hasChildNodes()) {
                $this->removeChild($this->firstChild);
            }
            # 6. If node is non-null, then insert node into parent before null with the
            # suppress observers flag set.
            // DEVIATION: There is no scripting in this implementation, so cannnot set
            // suppress observers flag.
            if ($node !== null) {
                $this->appendChild($node);
            }
            # 7. If either addedNodes or removedNodes is not empty, then queue a tree
            # mutation record for parent with addedNodes, removedNodes, null, and null.
            // DEVIATION: There is no scripting in this implementation
        }

        private function convertNodesToNode(array $nodes): \DOMNode {
            # To convert nodes into a node, given nodes and document, run these steps:
            # 1. Let node be null.
            # 2. Replace each string in nodes with a new Text node whose data is the string
            #    and node document is document.
            # 3. If nodes contains one node, then set node to nodes[0].
            # 4. Otherwise, set node to a new DocumentFragment node whose node document is
            #    document, and then append each node in nodes, if any, to it.
            // The spec would have us iterate through the provided nodes and then iterate
            // through them again to append. Let's optimize this a wee bit, shall we?
            $document = ($this instanceof Document) ? $this : $this->ownerDocument;
            $node = ($node->length > 1) ? $document->createDocumentFragment() : null;
            foreach ($nodes as &$n) {
                // Can't do union types until PHP 8... OTL
                if (!$n instanceof \DOMNode && !is_string($n)) {
                    trigger_error(sprintf("Uncaught TypeError: %s::%s(): Argument #1 (\$%s) must be of type \DOMNode|string, %s given", __CLASS__, __METHOD__, 'nodes', gettype($n)));
                }

                if (is_string($n)) {
                    $n = $this->ownerDocument->createTextNode($n);
                }

                if ($node !== null) {
                    $node->appendChild($n);
                } else {
                    $node = $n;
                }
            }

            return $node;
        }
    }
} else {
    trait ParentNode {
        public function __get_childElementCount(): int {
            # The childElementCount getter steps are to return the number of children of
            # this that are elements.
            $count = 0;
            foreach ($this->childNodes as $child) {
                if ($child instanceof Element) {
                    $count++;
                }
            }

            return $count;
        }

        public function __get_children(): \DOMNodeList {
            # The children getter steps are to return an HTMLCollection collection rooted at
            # this matching only element children.
            // DEVIATION: HTMLCollection doesn't exist in PHP's DOM, and \DOMNodeList is
            // almost identical; so, using that. PHP's DOM doesn't provide the end user any
            // way to create a \DOMNodeList from scratch, so going to cheat and use XPath to
            // make one for us.

            $isDocument = ($this instanceof Document);
            $document = ($isDocument) ? $this : $this->ownerDocument;
            return $document->xpath->query('//*', (!$isDocument) ? $this : null);
        }

        public function __get_firstElementChild(): Element {
            # The firstElementChild getter steps are to return the first child that is an
            # element; otherwise null.
            foreach ($this->childNodes as $child) {
                if ($child instanceof Element) {
                    return $child;
                }
            }
            return null;
        }

        public function __get_lastElementChild(): Element {
            # The lastElementChild getter steps are to return the last child that is an
            # element; otherwise null.
            for ($i = $this->childNodes->length - 1; $i >= 0; $i--) {
                $child = $this->childNodes->item($i);
                if ($child instanceof Element) {
                    return $child;
                }
            }

            return null;
        }


        public function append(...$nodes): void {
            # The append(nodes) method steps are:
            # 1. Let node be the result of converting nodes into a node given nodes and
            #    this’s node document.
            $node = $this->convertNodesToNode($nodes);
            # 2. Append node to this.
            $this->appendChild($node);
        }

        public function prepend(...$nodes): void {
            # The prepend(nodes) method steps are:
            #
            # 1. Let node be the result of converting nodes into a node given nodes and
            #    this’s node document.
            $node = $this->convertNodesToNode($nodes);
            # 2. Pre-insert node into this before this’s first child.
            $this->insertBefore($node, $this->firstChild);
        }

        public function replaceChildren(...$nodes) {
            # The replaceChildren(nodes) method steps are:
            # 1. Let node be the result of converting nodes into a node given nodes and
            #    this’s node document.
            $node = $this->convertNodesToNode($nodes);
            # 2. Ensure pre-insertion validity of node into this before null.
            $this->preInsertionValidity($node);
            # 3. Replace all with node within this.
            #
            # To replace all with a node within a parent, run these steps:
            # 1. Let removedNodes be parent’s children.
            $removedNodes = $this->childNodes;
            # 2. Let addedNodes be the empty set.
            $addedNodes = [];
            # 3. If node is a DocumentFragment node, then set addedNodes to node’s children.
            if ($node instanceof DocumentFragment) {
                $addedNodes = $node->childNodes;
            }
            # 4. Otherwise, if node is non-null, set addedNodes to « node ».
            elseif ($node !== null) {
                $addedNodes = node;
            }
            # 5. Remove all parent’s children, in tree order, with the suppress observers
            # flag set.
            // DEVIATION: There is no scripting in this implementation, so cannnot set
            // suppress observers flag.
            while ($this->hasChildNodes()) {
                $this->removeChild($this->firstChild);
            }
            # 6. If node is non-null, then insert node into parent before null with the
            # suppress observers flag set.
            // DEVIATION: There is no scripting in this implementation, so cannnot set
            // suppress observers flag.
            if ($node !== null) {
                $this->appendChild($node);
            }
            # 7. If either addedNodes or removedNodes is not empty, then queue a tree
            # mutation record for parent with addedNodes, removedNodes, null, and null.
            // DEVIATION: There is no scripting in this implementation
        }

        private function convertNodesToNode(array $nodes): \DOMNode {
            # To convert nodes into a node, given nodes and document, run these steps:
            # 1. Let node be null.
            # 2. Replace each string in nodes with a new Text node whose data is the string
            #    and node document is document.
            # 3. If nodes contains one node, then set node to nodes[0].
            # 4. Otherwise, set node to a new DocumentFragment node whose node document is
            #    document, and then append each node in nodes, if any, to it.
            // The spec would have us iterate through the provided nodes and then iterate
            // through them again to append. Let's optimize this a wee bit, shall we?
            $document = ($this instanceof Document) ? $this : $this->ownerDocument;
            $node = ($node->length > 1) ? $document->createDocumentFragment() : null;
            foreach ($nodes as &$n) {
                // Can't do union types until PHP 8... OTL
                if (!$n instanceof \DOMNode && !is_string($n)) {
                    trigger_error(sprintf("Uncaught TypeError: %s::%s(): Argument #1 (\$%s) must be of type \DOMNode|string, %s given", __CLASS__, __METHOD__, 'nodes', gettype($n)));
                }

                if (is_string($n)) {
                    $n = $this->ownerDocument->createTextNode($n);
                }

                if ($node !== null) {
                    $node->appendChild($n);
                } else {
                    $node = $n;
                }
            }

            return $node;
        }
    }
}
