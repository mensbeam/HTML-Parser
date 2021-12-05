<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

abstract class Stack implements \ArrayAccess, \Countable, \IteratorAggregate {
    protected $_storage = [];
    protected $count = 0;

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        assert($offset >= 0, new \Exception("Invalid stack index $offset"));

        if ($offset === null) {
            $this->_storage[] = $value;
        } else {
            $this->_storage[$offset] = $value; // @codeCoverageIgnore
        }
        $this->count = count($this->_storage);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
        return isset($this->_storage[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
        assert($offset >= 0 && $offset < count($this->_storage), new \Exception("Invalid stack index $offset"));
        array_splice($this->_storage, $offset, 1, []);
        $this->count = count($this->_storage);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        assert($offset >= 0 && $offset < count($this->_storage), new \Exception("Invalid stack index $offset"));
        return $this->_storage[$offset];
    }

    public function count(): int {
        return $this->count;
    }

    public function getIterator(): \Traversable {
        for ($a = $this->count - 1; $a > -1; $a--) {
            yield $a => $this->_storage[$a];
        }
    }

    public function pop() {
        $this->count = max($this->count - 1, 0);
        return array_pop($this->_storage);
    }

    public function isEmpty(): bool {
        return !$this->_storage;
    }

    public function top(int $offset = 0) {
        assert($offset >= 0, new \Exception("Invalid stack index $offset"));
        return ($c = $this->count) > $offset ? $this->_storage[$c - ($offset + 1)] : null;
    }
}
