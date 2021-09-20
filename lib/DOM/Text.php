<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Text extends \DOMText {
    use LeafNode, Moonwalk, ToString;


    /** Nonstandard */
    public function hasSibling(\DOMNode ...$nodes): bool {
        if ($this->parentNode === null) {
            return false;
        }

        foreach ($this->parentNode->childNodes as $child) {
            if ($child->isSameNode($this)) {
                continue;
            }

            foreach ($nodes as $n) {
                if ($n->isSameNode($child)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Nonstandard */
    public function hasSiblingElementWithName(string ...$nodeNames): bool {
        if ($this->parentNode === null) {
            return false;
        }

        foreach ($this->parentNode->childNodes as $child) {
            if ($child->isSameNode($this)) {
                continue;
            }

            foreach ($nodeNames as $n) {
                if ($n instanceof Element && $n->nodeName === $child->nodeName) {
                    return true;
                }
            }
        }

        return false;
    }
}
