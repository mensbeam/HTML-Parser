<?php
declare(strict_types=1);
namespace dW\HTML5;

abstract class Stack implements \ArrayAccess {
    protected $_storage = [];
    protected $fragmentCase;
    protected $fragmentContext;

    public function offsetSet($offset, $value) {
        if ($offset < 0) {
            throw new Exception(Exception::STACK_INVALID_INDEX, $offset);
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
        if ($offset < 0 || $offset > count($this->_storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX, $offset);
        }

        unset($this->_storage[$offset]);
        // Reindex the array.
        $this->_storage = array_values($this->_storage);
    }

    public function offsetGet($offset) {
        if ($offset < 0 || $offset > count($this->_storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX, $offset);
        }

        return $this->_storage[$offset];
    }

    public function pop() {
        return array_pop($this->_storage);
    }

    public function __get($property) {
        switch ($property) {
            case 'length': return count($this->_storage);
            break;
            default: return null;
        }
    }
}
