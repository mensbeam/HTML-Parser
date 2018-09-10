<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Node {
    public function getAncestor($needle): Element {
        return $this->ancestor($needle, true);
    }

    public static function hasAncestor($needle): bool {
        return $this->ancestor($needle, false);
    }

    protected function ancestor($needle, bool $returnNode = true) {
        $context = $this->parentNode;
        do {
            $result = static::compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        } while ($context = $context->parentNode);

        return ($returnNode === true) ? null : false;
    }

    protected function compare($needle, Element $context): \DOMNode {
        if (is_string($needle)) {
            if ($context->nodeName == $needle) {
                return $this;
            }
        } elseif ($needle instanceof \DOMNode) {
            if ($context->isSameNode($needle)) {
                return $context;
            }
        } elseif ($needle instanceof \Closure) {
            if ($needle($context) === true) {
                return $context;
            }
        } else {
            throw new Exception(Exception::DOM_DOMNODE_STRING_OR_CLOSURE_EXPECTED, gettype($needle));
        }

        return null;
    }
}