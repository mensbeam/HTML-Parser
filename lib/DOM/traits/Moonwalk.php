<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait Moonwalk {
    /** Generator which walks up the DOM. Nonstandard. */
    public function moonwalk(?\Closure $filter = null): \Generator {
        return $this->moonwalkGenerator($this, $filter);
    }

    private function moonwalkGenerator(\DOMNode $node, ?\Closure $filter = null) {
        do {
            while (true) {
                if ($filter === null || $filter($node)) {
                    yield $node;
                }

                // If node is an instance of DocumentFragment then it might be the content
                // fragment of a template element, so iterate through all template elements
                // stored in the element map and see if node is the fragment of one of the
                // templates; if it is change node to the template element and reprocess. Magic!
                // Can walk backwards THROUGH templates!
                if ($node instanceof DocumentFragment) {
                    foreach (ElementMap::getIterator() as $element) {
                        if ($element->ownerDocument->isSameNode($node->ownerDocument) && $element instanceof TemplateElement && $element->content->isSameNode($node)) {
                            $node = $element;
                            continue;
                        }
                    }
                }

                break;
            }
        } while ($node = $node->parentNode);
    }
}
