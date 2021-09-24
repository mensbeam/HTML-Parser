<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Node in the DOM spec is dirty. Many nodes which inherit from it inherit
// methods it cannot use which all check for this and throw exceptions. This is
// for nodes which DO NOT have child nodes.
trait LeafNode {
    use Node;


    public function appendChild($node) {
        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
    }

    public function insertBefore($node, $child = null) {
        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
    }

    public function removeChild($child) {
        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
    }

    public function replaceChild($node, $child) {
        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
    }
}
