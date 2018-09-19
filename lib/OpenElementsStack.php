<?php
declare(strict_types=1);
namespace dW\HTML5;

class OpenElementsStack extends Stack {
    protected $fragmentCase;
    protected $fragmentContext;

    public function __construct(bool $fragmentCase = false, $fragmentContext = null) {
        // If the fragment context is not null and is not a document fragment, document,
        // or element then we have a problem. Additionally, if the parser is created for
        // parsing a fragment and the fragment context is null then we have a problem,
        // too.
        if ((!is_null($fragmentContext) && !$fragmentContext instanceof DOMDocumentFragment && !$fragmentContext instanceof DOMDocument && !$fragmentContext instanceof DOMElement) ||
            (is_null($fragmentContext) && $fragmentCase)) {
            throw new Exception(Exception::STACK_DOCUMENTFRAG_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED, gettype($fragmentContext));
        }

        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
    }

    public function search($needle): int {
        if (!$needle) {
            return -1;
        }

        if ($needle instanceof DOMElement) {
            foreach (array_reverse($this->_storage) as $key => $value) {
                if ($value->isSameNode($needle)) {
                    return $key;
                }
            }
        } elseif (is_string($needle)) {
            foreach (array_reverse($this->_storage) as $key => $value) {
                if ($value->nodeName === $needle) {
                    return $key;
                }
            }
        } elseif ($needle instanceof \Closure) {
            foreach (array_reverse($this->_storage) as $key => $value) {
                if ($needle($value) === true) {
                    return $key;
                }
            }
        }

        return -1;
    }

    public function generateImpliedEndTags(string $exclude = null) {
        $tags = ['caption', 'colgroup', 'dd', 'dt', 'li', 'optgroup', 'option', 'p', 'rb', 'rp', 'rt', 'rtc', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

        if (!is_null($exclude)) {
            $key = array_search($exclude, $tags);
            if ($key !== false) {
                unset($tags[$key]);
                $tags = array_values($tags);
            }
        }

        $currentNodeName = end($this->_storage)->nodeName;
        while (in_array($currentNodeName, $tags)) {
            $this->pop();
            $currentNodeName = end($this->_storage)->nodeName;
        }
    }

    public function hasElementInListItemScope(string $elementName): bool {
        return $this->hasElementInScope($elementName, 0);
    }

    public function hasElementInButtonScope(string $elementName): bool {
        return $this->hasElementInScope($elementName, 1);
    }

    public function hasElementInTableScope(string $elementName): bool {
        return $this->hasElementInScope($elementName, 2);
    }

    public function hasElementInSelectScope(string $elementName): bool {
        return $this->hasElementInScope($elementName, 3);
    }

    protected function hasElementInScope(string $elementName, int $type): bool {
        switch ($type) {
            case 0: $func = 'isElementInListScope';
            break;
            case 1: $func = 'isElementInButtonScope';
            break;
            case 2: $func = 'isElementInTableScope';
            break;
            case 3: $func = 'isElementInSelectScope';
            break;
            default: return false;
        }

        foreach (array_reverse($this->_storage) as $key => $value) {
            if ($this->$func($value)) {
                return true;
            }
        }

        return false;
    }

    protected function isElementInListItemScope(Element $element): bool {
        $name = $element->name;
        $ns = $element->namespaceURI;

        # The stack of open elements is said to have a particular element in list item
        # scope when it has that element in the specific scope consisting of the
        # following element types:
        #
        # All the element types listed above for the has an element in scope
        # algorithm.
        # ol in the HTML namespace
        # ul in the HTML namespace

        return ($this->isElementInScope($element) || ($ns === '' && ($name === 'ol' || $name === 'ul'))) ? true : false;
    }

    protected function isElementInButtonScope(Element $element): bool {
        $name = $element->name;
        $ns = $element->namespaceURI;

        # The stack of open elements is said to have a particular element in button
        # scope when it has that element in the specific scope consisting of the
        # following element types:
        #
        # All the element types listed above for the has an element in scope
        # algorithm.
        # button in the HTML namespace

        return ($this->isElementInScope($element) || ($ns === '' && $name === 'button')) ? true : false;
    }

    protected function isElementInTableScope(Element $element): bool {
        $name = $element->name;

        # The stack of open elements is said to have a particular element in table scope
        # when it has that element in the specific scope consisting of the following
        # element types:
        #
        # html in the HTML namespace
        # table in the HTML namespace
        # template in the HTML namespace

        return ($element->namespaceURI === '' && ($name === 'html' || $name === 'table' || $name === 'template')) ? true : false;
    }

    protected function isElementInSelectScope(Element $element): bool {
        $name = $element->name;
        $ns = $element->namespaceURI;

        # The stack of open elements is said to have a particular element in select
        # scope when it has that element in the specific scope consisting of all element
        # types except the following:
        #
        # optgroup in the HTML namespace
        # option in the HTML namespace

        return ($element->namespaceURI === '' && ($name === 'optgroup' || $name === 'option')) ? false : true;
    }

    protected function isElementInScope(Element $element): bool {
        $name = $element->name;
        $ns = $element->namespaceURI;

        # The stack of open elements is said to have a particular element in scope when
        # it has that element in the specific scope consisting of the following element
        # types:
        #
        # applet
        # caption
        # html
        # table
        # td
        # th
        # marquee
        # object
        # template
        # MathML mi
        # MathML mo
        # MathML mn
        # MathML ms
        # MathML mtext
        # MathML annotation-xml
        # SVG foreignObject
        # SVG desc
        # SVG title

        return (($ns === '' && ($name === 'applet' || $name === 'caption' || $name === 'html' || $name === 'table' || $name === 'td' || $name === 'th' || $name === 'marquee' || $name === 'object' || $name === 'template')) ||
            ($ns === Parser::MATHML_NAMESPACE && ($name === 'mi' || $name === 'mo' || $name === 'mn' || $name === 'ms' || $name === 'mtext' || $name === 'annotation-xml')) ||
            ($ns === Parser::SVG_NAMESPACE && ($name === 'foreignObject' || $name === 'desc' || $name === 'title'))) ? true : false;
    }

    public function __get($property) {
        $value = parent::__get($property);
        if (!is_null($value)) {
            return $value;
        }

        switch ($property) {
            case 'adjustedCurrentNode':
                # The adjusted current node is the context element if the parser was created by
                # the HTML fragment parsing algorithm and the stack of open elements has only one
                # element in it (fragment case); otherwise, the adjusted current node is the
                # current node.
                return ($this->fragmentCase && $this->length === 1) ? $this->fragmentContext : $this->currentNode;
            break;
            case 'adjustedCurrentNodeName':
                $adjustedCurrentNode = $this->adjustedCurrentNode;
                return (!is_null($adjustedCurrentNode)) ? $adjustedCurrentNode->nodeName : null;
            break;
            case 'adjustedCurrentNodeNamespace':
                $adjustedCurrentNode = $this->adjustedCurrentNode;
                return (!is_null($adjustedCurrentNode)) ? $adjustedCurrentNode->namespaceURI: null;
            break;
            case 'currentNode':
                $currentNode = end($this->_storage);
                return ($currentNode) ? $currentNode : null;
            break;
            case 'currentNodeName':
                $currentNode = $this->currentNode;
                return ($currentNode && $currentNode->nodeType) ? $currentNode->nodeName : null;
            break;
            case 'currentNodeNamespace':
                $currentNode = $this->currentNode;
                return (!is_null($currentNode)) ? $currentNode->namespaceURI: null;
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
