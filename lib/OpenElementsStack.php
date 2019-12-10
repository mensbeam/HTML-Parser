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
            throw new Exception(Exception::STACK_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED);
        }

        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
    }

    public function popUntil($target) {
        if ($target instanceof Element) {
            do {
                $node = $this->pop;
            } while (!$node->isSameNode($target));
        } elseif (is_string($target)) {
            do {
                $poppedNodeName = $this->pop()->nodeName;
            } while ($poppedNodeName !== $target);
        } elseif (is_array($target)) {
            do {
                $poppedNodeName = $this->pop()->nodeName;
            } while (!in_array($poppedNodeName, $target));
        } else {
            throw new Exception(Exception::STACK_ELEMENT_STRING_ARRAY_EXPECTED);
        }
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

    // Remove an arbitrary element from the array.
    public function remove($target) {
        $key = $this->search($target);
        if ($key === -1) {
            return;
        } elseif ($key === count($this->_storage) - 1) {
            $this->pop();
            return;
        }

        unset($this->_storage[$key]);
        $this->_storage = array_values($this->_storage);
    }

    public function generateImpliedEndTags($exclude = []) {
        $tags = ['caption', 'colgroup', 'dd', 'dt', 'li', 'optgroup', 'option', 'p', 'rb', 'rp', 'rt', 'rtc', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

        if (is_string($exclude)) {
            $exclude = [$exclude];
        }

        if (!is_array($exclude)) {
            throw new Exception(Exception::STACK_STRING_ARRAY_EXPECTED);
        }

        if (count($exclude) > 0) {
            $modified = false;
            foreach ($exclude as $e) {
                $key = array_search($e, $tags);
                if ($key !== false) {
                    unset($tags[$key]);
                    $modified = true;
                }
            }

            if ($modified) {
                $tags = array_values($tags);
            }
        }

        $currentNodeName = end($this->_storage)->nodeName;
        while (in_array($currentNodeName, $tags)) {
            $this->pop();
            $currentNodeName = end($this->_storage)->nodeName;
        }
    }

    public function hasElementInScope($target): bool {
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

        $list = [
            Parser::HTML_NAMESPACE => [
                'applet',
                'caption',
                'html',
                'table',
                'td',
                'th',
                'marquee',
                'object',
                'template'
            ],

            Parser::MATHML_NAMESPACE => [
                'mi',
                'mo',
                'mn',
                'ms',
                'mtext',
                'annotation-xml'
            ],

            Parser::SVG_NAMESPACE => [
                'foreignObject',
                'desc',
                'title'
            ]
        ];

        return $this->hasElementInScopeHandler($target, $list);
    }

    public function hasElementInListItemScope($target): bool {
        # The stack of open elements is said to have a particular element in list item scope when it has that element in the specific scope consisting of the following element types:
        #
        # All the element types listed above for the has an element in scope algorithm.
        # ol in the HTML namespace
        # ul in the HTML namespace

        $list = [
            Parser::HTML_NAMESPACE => [
                'applet',
                'caption',
                'html',
                'table',
                'td',
                'th',
                'marquee',
                'object',
                'template',
                'ol',
                'ul'
            ],

            Parser::MATHML_NAMESPACE => [
                'mi',
                'mo',
                'mn',
                'ms',
                'mtext',
                'annotation-xml'
            ],

            Parser::SVG_NAMESPACE => [
                'foreignObject',
                'desc',
                'title'
            ]
        ];

        return $this->hasElementInScopeHandler($target, $list);
    }

    public function hasElementInButtonScope($target): bool {
        # The stack of open elements is said to have a particular element in button
        # scope when it has that element in the specific scope consisting of the
        # following element types:
        #
        # All the element types listed above for the has an element in scope algorithm.
        # button in the HTML namespace

        $list = [
            Parser::HTML_NAMESPACE => [
                'applet',
                'caption',
                'html',
                'table',
                'td',
                'th',
                'marquee',
                'object',
                'template',
                'button'
            ],

            Parser::MATHML_NAMESPACE => [
                'mi',
                'mo',
                'mn',
                'ms',
                'mtext',
                'annotation-xml'
            ],

            Parser::SVG_NAMESPACE => [
                'foreignObject',
                'desc',
                'title'
            ]
        ];

        return $this->hasElementInScopeHandler($target, $list);
    }

    public function hasElementInTableScope($target): bool {
        # The stack of open elements is said to have a particular element in table scope
        # when it has that element in the specific scope consisting of the following
        # element types:
        #
        # All the element types listed above for the has an element in scope algorithm.
        # html in the HTML namespace
        # table in the HTML namespace
        # template in the HTML namespace

        // Not sure what to do here. I am going to assume the elements without a
        // namespace in the element types listed above are meant for the HTML namespace.
        // If so then these listed here are redundant. My interpretation therefore has
        // this being an alias for hasElementInScope.

        return $this->hasElementInScope($target);
    }

    public function hasElementInSelectScope(string $target): bool {
        # The stack of open elements is said to have a particular element in select
        # scope when it has that element in the specific scope consisting of all element
        # types except the following:
        #
        # All the element types listed above for the has an element in scope algorithm.
        # optgroup in the HTML namespace
        # option in the HTML namespace

        $list = [
            Parser::HTML_NAMESPACE => [
                'applet',
                'caption',
                'html',
                'table',
                'td',
                'th',
                'marquee',
                'object',
                'template',
                'button',
                'optgroup',
                'option'
            ],

            Parser::MATHML_NAMESPACE => [
                'mi',
                'mo',
                'mn',
                'ms',
                'mtext',
                'annotation-xml'
            ],

            Parser::SVG_NAMESPACE => [
                'foreignObject',
                'desc',
                'title'
            ]
        ];

        return $this->hasElementInScopeHandler($target, $list);
    }


    protected function hasElementInScopeHandler($target, array $list): bool {
        # 1. Initialize node to be the current node (the bottommost node of the stack).
        // Handled by loop.
        foreach (array_reverse($this->_storage) as $node) {
            # 2. If node is the target node, terminate in a match state.
            if ($target instanceof DOMElement) {
                if ($node->isSameNode($target)) {
                    return true;
                }
            } elseif (is_string($target)) {
                if ($node->nodeName === $target) {
                    return true;
                }
            }

            # 3. Otherwise, if node is one of the element types in list, terminate in a
            # failure state.
            else {
                foreach ($list as $namespace => $subList) {
                    if ($namespace === Parser::HTML_NAMESPACE) {
                        $namespace = '';
                    }

                    if ($node->namespaceURI !== $namespace) {
                        continue;
                    }

                    foreach ($subList as $name) {
                        if ($node->nodeName === $name) {
                            return false;
                        }
                    }
                }
            }

            # Otherwise, set node to the previous entry in the stack of open elements and
            # return to step 2. (This will never fail, since the loop will always terminate
            # in the previous step if the top of the stack — an html element — is reached.)
            // Handled by loop.
        }

        return false;
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
