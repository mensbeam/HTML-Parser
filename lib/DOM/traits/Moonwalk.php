<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait Moonwalk {
    public function moonwalk(?\Closure $filter = null): \Generator {
        return $this->moonwalkGenerator($this, $filter);
    }

    private function moonwalkGenerator(\DOMNode $node, ?\Closure $filter = null) {
        do {
            if ($filter === null || $filter($node)) {
                yield $node;
            }
        } while ($node = $node->parentNode);
    }
}
