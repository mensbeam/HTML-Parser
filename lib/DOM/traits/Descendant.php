<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Descendant {
    use Compare;

    public function getDescendant($needle): \DOMNode {
        return static::descendant($needle, true);
    }

    public function hasDescendant($needle): bool {
        return static::descendant($needle, false);
    }

    protected function descendant($needle, bool $returnNode = true): \DOMNode {
        if ($this->hasChildNodes() === false) {
            return ($returnNode === true) ? null : false;
        }

        $context = $this->firstChild;

        do {
            $result = $this->compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }

            $result = $this->descendant($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        } while ($context = $context->nextSibling);

        return ($returnNode === true) ? null : false;
    }
}