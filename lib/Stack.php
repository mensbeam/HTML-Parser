<?php
declare(strict_types=1);
namespace dW\HTML5;

class Stack implements \ArrayAccess {
    protected $_storage = [];
    protected $fragmentCase;
    protected $fragmentContext;

    public function __construct(bool $fragmentCase = false, $fragmentContext = null) {
        // If the fragment context is not null and is not a document fragment, document,
        // or element then we have a problem. Additionally, if the parser is created for
        // parsing a fragment and the fragment context is null then we have a problem,
        // too.
        if ((!is_null($fragmentContext) && !$fragmentContext instanceof DOMDocumentFragment && !$fragmentContext instanceof DOMDocument && !$fragmentContext instanceof DOMElement) ||
            (is_null($fragmentContext) && $fragmentCase)) {
            throw new Exception(Exception::STACK_FRAGMENT_CONTEXT_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED, gettype($fragmentContext));
        }

        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
    }

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
            return -1;
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

        return -1;
    }

    public function generateImpliedEndTags() {
        $currentNodeName = end($this->_storage)->nodeName;
        while ($currentNodeName === 'caption' || $currentNodeName === 'colgroup' || $currentNodeName === 'dd' || $currentNodeName === 'dt' || $currentNodeName === 'li' || $currentNodeName === 'optgroup' || $currentNodeName === 'option' || $currentNodeName === 'p' || $currentNodeName === 'rb' || $currentNodeName === 'rp' || $currentNodeName === 'rt' || $currentNodeName === 'rtc' || $currentNodeName === 'tbody' || $currentNodeName === 'td' || $currentNodeName === 'tfoot' || $currentNodeName === 'th' || $currentNodeName === 'thead' || $currentNodeName === 'tr') {
            $this->pop();
            $currentNodeName = end($this->_storage)->nodeName;
        }
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
                return ($this->fragmentCase && $this->length === 1) ? $this->fragmentContext : $this->currentNode;
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

    // Used when listing expected elements when returning parse errors
    public function __toString(): string {
        if (count($this->_storage) > 1) {
            // Don't output the name of the root element.
            for ($i = 1, $temp = []; $i < count($this->_storage) - 1; $i++) {
                $temp[] = $this->_storage[$i]->nodeName;
            }

            return implode(', ', array_unique($temp));
        } else {
            return '';
        }
    }
}
