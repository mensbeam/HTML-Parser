<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait Walk {
    public function walk(\Closure $filter): \Generator {
        return $this->walkGenerator($this, $filter);
    }
    
    private function walkGenerator(\DOMNode $node, \Closure $filter) {
        if ($filter($node)) {
            yield $node;
        }

        if ($node->hasChildNodes()) {
            $children = $node->childNodes;
            foreach ($children as $c) {
                yield from $this->walkGenerator($c, $filter);
            }
        }
    }
}
