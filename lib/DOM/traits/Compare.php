<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Compare {
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