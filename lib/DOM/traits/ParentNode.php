<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

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


    public function prepend(...$nodesOrStrings) {
        $nodeToPrepend = ($nodesOrStrings->length > 1) ? $document->createDocumentFragment() : null;
        foreach ($nodesOrStrings as &$nodeOrString) {
            if (!$nodeOrString instanceof \DOMNode && !is_string($nodeOrString)) {
                trigger_error(sprintf("Uncaught TypeError: %s::%s(): Argument #1 (\$%s) must be of type \DOMNode|string, %s given", __CLASS__, __METHOD__, 'nodesOrStrings', gettype($nodeOrString)));
            }

            if (is_string($nodeOrString)) {
                $nodeOrString = $this->ownerDocument->createTextNode($nodeOrString);
            }

            if ($nodeToPrepend !== null) {
                $nodeToPrepend->appendChild($nodeOrString);
            } else {
                $nodeToPrepend = $nodeOrString;
            }
        }

        $this->parentNode->insertBefore($nodeToPrepend, $this->parentNode->firstChild);
    }
}
