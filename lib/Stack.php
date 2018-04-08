<?php
declare(strict_types=1);
namespace dW\HTML5;

class Stack implements \ArrayAccess {
    protected $storage = [];

    // Temporarily change this from DOMNode to HTML5StartTagToken for the purposes of
    // testing the tokenizer.
    public function offsetSet($offset, $value) {
        if ($offset < 0) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        if (is_null($offset)) {
            $this->storage[] = $value;
        } else {
            $this->storage[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        unset($this->storage[$offset]);
    }

    public function offsetGet($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        return $this->storage[$offset];
    }

    public function pop() {
        return array_pop($this->storage);
    }

    public function search(mixed $needle): int {
        if (!$needle) {
            return false;
        }

        if ($needle instanceof DOMElement) {
            foreach ($this->storage as $key=>$value) {
                if ($value->isSameNode($needle)) {
                    return $key;
                }
            }
        } elseif (is_string($needle)) {
            foreach ($this->storage as $key=>$value) {
                if ($value->nodeName === $needle) {
                    return $key;
                }
            }
        }

        return false;
    }

    public function __get($property) {
        switch ($property) {
            case 'length': return count($this->storage);
            break;
            case 'currentNode':
                $currentNode = end($this->storage);
                return ($currentNode) ? $currentNode : null;
            break;
            case 'adjustedCurrentNode':
                # The adjusted current node is the context element if the parser was created by
                # the HTML fragment parsing algorithm and the stack of open elements has only one
                # element in it (fragment case); otherwise, the adjusted current node is the
                # current node.
                return (Parser::$self->fragmentCase && $this->length === 1) ? Parser::$self->fragmentContext : $this->currentNode;
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
