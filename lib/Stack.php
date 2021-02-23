<?php
declare(strict_types=1);
namespace dW\HTML5;

abstract class Stack implements \ArrayAccess, \Countable, \IteratorAggregate {
    protected $_storage = [];
    protected $fragmentCase;
    protected $fragmentContext;

    public function offsetSet($offset, $value) {
        assert($offset >= 0, new Exception(Exception::STACK_INVALID_INDEX, $offset));

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
        assert($offset >= 0 && $offset < count($this->_storage), new Exception(Exception::STACK_INVALID_INDEX, $offset));
        array_splice($this->_storage, $offset, 1, []);
    }

    public function offsetGet($offset) {
        assert($offset >= 0 && $offset < count($this->_storage), new Exception(Exception::STACK_INVALID_INDEX, $offset));
        return $this->_storage[$offset];
    }
    
    public function count(): int {
        return count($this->_storage);
    }

    public function getIterator(): \Traversable {
        for ($a = count($this->_storage) - 1; $a > -1; $a--) {
            yield $a => $this->_storage[$a];
        }
    }

    public function pop() {
        return array_pop($this->_storage);
    }

    public function isEmpty(): bool {
        return !$this->_storage;
    }

    public function top(int $offset = 0) {
        assert($offset >= 0, new \Exception("Offset must be at least 0"));
        return ($c = count($this->_storage)) > $offset ? $this->_storage[$c - ($offset + 1)] : null;
    }
}
