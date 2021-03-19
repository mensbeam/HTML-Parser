<?php
declare(strict_types=1);
namespace dW\HTML5;

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