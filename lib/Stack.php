<?php
declare(strict_types=1);
namespace dW\HTML5;

class Stack implements \ArrayAccess {
    protected $_storage = [];

    public function offsetSet($offset, $value) {
        if ($offset < 0) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        if (is_null($offset)) {
            $this->_storage[] = $value;
        } else {
            $this->_storage[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_storage[$offset]);
    }

    public function offsetUnset($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        unset($this->_storage[$offset]);
    }

    public function offsetGet($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        return $this->_storage[$offset];
    }

    public function pop() {
        return array_pop($this->_storage);
    }

    public function search(mixed $needle): int {
        if (!$needle) {
            return false;
        }

        if ($needle instanceof DOMElement) {
            foreach (array_reverse($this->_storage) as $key=>$value) {
                if ($value->isSameNode($needle)) {
                    return $key;
                }
            }
        } elseif (is_string($needle)) {
            foreach (array_reverse($this->_storage) as $key=>$value) {
                if ($value->nodeName === $needle) {
                    return $key;
                }
            }
        }

        return false;
    }

    public function __get($property) {
        switch ($property) {
            case 'length': return count($this->_storage);
            break;
            case 'currentNode':
                $currentNode = end($this->_storage);
                return ($currentNode) ? $currentNode : null;
            break;
            case 'adjustedCurrentNode':
                # The adjusted current node is the context element if the parser was created by
                # the HTML fragment parsing algorithm and the stack of open elements has only one
                # element in it (fragment case); otherwise, the adjusted current node is the
                # current node.
                return (Parser::$self->fragmentCase && $this->length === 1) ? Parser::$self->fragmentContext : $this->currentNode;
            break;
            case 'adjustedCurrentNodeNamespace':
                $adjustedCurrentNode = $this->adjustedCurrentNode;
                return (!is_null($adjustedCurrentNode)) ? $adjustedCurrentNode->namespaceURI : null;
            break;
            case 'currentNodeName':
                $currentNode = $this->currentNode;
                return ($currentNode && $currentNode->nodeType) ? $currentNode->nodeName : null;
            break;
            case 'currentNodeNamespace': return (!is_null($this->currentNode)) ? $this->currentNode->namespaceURI : null;
            break;
            default: return null;
        }
    }
}
