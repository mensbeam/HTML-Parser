<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Moonwalk {
    public function moonwalk(\Closure $filter): \Generator {
        return $this->moonwalkGenerator($this, $filter);
    }
    
    private function moonwalkGenerator(\DOMNode $node, \Closure $filter) {
        do {
            if ($filter($node)) {
                yield $node;
            }
        } while ($node = $node->parentNode);
    }
}