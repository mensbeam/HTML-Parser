<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait MoonwalkShallow {
    /**
     * Generator which just walks through a node's child nodes in reverse.
     * Nonstandard.
     *
     * @param ?\Closure $filter - An optional closure to use to filter
     */
    public function moonwalkShallow(?\Closure $filter = null): \Generator {
        if ($this->hasChildNodes()) {
            $childNodesLength = $this->childNodes->length;
            for ($childNodesLength = $this->childNodes->length, $i = $childNodesLength - 1; $i >= 0; $i--) {
                $child = $this->childNodes[$i];
                if ($filter === null || $filter($child)) {
                    yield $child;
                }
            }
        }
    }
}
