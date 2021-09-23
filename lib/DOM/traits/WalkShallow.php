<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait WalkShallow {
    /**
     * Generator which just walks through a node's child nodes. Nonstandard.
     *
     * @param ?\Closure $filter - An optional closure to use to filter
     */
    public function walkShallow(?\Closure $filter = null): \Generator {
        $node = (!$this instanceof TemplateElement) ? $this : $this->content;

        foreach ($node->childNodes as $child) {
            if ($filter === null || $filter($child)) {
                yield $child;
            }
        }
    }
}
