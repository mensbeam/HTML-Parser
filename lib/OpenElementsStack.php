<?php
declare(strict_types=1);
namespace dW\HTML5;

class OpenElementsStack extends \splStack {
    protected const IMPLIED_END_TAGS = [
        'dd'       => true,
        'dt'       => true,
        'li'       => true,
        'optgroup' => true,
        'option'   => true,
        'p'        => true,
        'rb'       => true,
        'rp'       => true,
        'rt'       => true,
        'rtc'      => true,
    ];
    protected const IMPLIED_END_TAGS_THOROUGH = [
        'caption'  => true,
        'colgroup' => true,
        'dd'       => true,
        'dt'       => true,
        'li'       => true,
        'optgroup' => true,
        'option'   => true,
        'p'        => true,
        'rb'       => true,
        'rp'       => true,
        'rt'       => true,
        'rtc'      => true,
        'tbody'    => true,
        'td'       => true,
        'tfoot'    => true,
        'th'       => true,
        'thead'    => true,
        'tr'       => true,
    ];
    protected const GENERAL_SCOPE = [
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
        ],
    ];
    protected const LIST_ITEM_SCOPE = [
        // everything in general scope, and these in the HTML namespace
        'ol',
        'ul',
    ];
    protected const BUTTON_SCOPE = [
        // everything in general scope, and these in the HTML namespace
        'button',
    ];
    protected const TABLE_SCOPE = [
        Parser::HTML_NAMESPACE => [
            'html',
            'table',
            'template',
        ],
    ];
    protected const SELECT_SCOPE = [
        // all elements EXCEPT these
        Parser::HTML_NAMESPACE => [
            'optgroup',
            'option',
        ],
    ];



    protected $fragmentCase;
    protected $fragmentContext;

    public function __construct(bool $fragmentCase = false, $fragmentContext = null) {
        // If the fragment context is not null and is not a document fragment, document,
        // or element then we have a problem. Additionally, if the parser is created for
        // parsing a fragment and the fragment context is null then we have a problem,
        // too.
        assert(is_null($fragmentContext) || $fragmentContext instanceof \DOMDocumentFragment || $fragmentContext instanceof \DOMDocument || $fragmentContext instanceof \DOMElement,new Exception(Exception::STACK_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED));
        assert(!$fragmentCase || !is_null($fragmentContext), new Exception(Exception::STACK_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED));

        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
    }

    public function popUntil(string ...$target): void {
        do {
            $node = $this->pop();
        } while (!in_array($node->nodeName, $target));
    }

    public function popUntilSame(Element $target): void {
        do {
            $node = $this->pop();
        } while (!$node->isSameNode($target));
    }

    public function find(string ...$name): int {
        foreach ($this as $k => $node) {
            if (in_array($node->nodeName, $name)) {
                return $k;
            }
        }
        return -1;
    }

    public function findNot(string ...$name): int {
        foreach ($this as $k => $node) {
            if (!in_array($node->nodeName, $name)) {
                return $k;
            }
        }
        return -1;
    }

    public function findSame(\DOMElement $node): int {
        foreach ($this as $k => $node) {
            if ($node->isSameNode($node)) {
                return $k;
            }
        }
        return -1;
    }

    public function generateImpliedEndTags(string ...$exclude): void {
        # When the steps below require the UA to generate implied end tags, 
        #   then, while the current node is {elided list of element names},
        #   the UA must pop the current node off the stack of open elements.
        #
        # If a step requires the UA to generate implied end tags but lists
        #   an element to exclude from the process, then the UA must perform
        #   the above steps as if that element was not in the above list.
        $map = self::IMPLIED_END_TAGS;
        foreach($exclude as $name) {
            $map[$name] = false;
        }
        while (!$this->isEmpty() && ($map[$this->top()->nodeName] ?? false)) {
            $this->pop();
        }
    }

    public function generateImpliedEndTagsThoroughly(): void {
        # When the steps below require the UA to generate all implied end tags 
        #   thoroughly, then, while the current node is {elided list of element names},
        #   the UA must pop the current node off the stack of open elements.
        while (!$this->isEmpty() && (self::IMPLIED_END_TAGS_THOROUGH[$this->top()->nodeName] ?? false)) {
            $this->pop();
        }
    }

    public function hasElementInScope($target): bool {
        # The stack of open elements is said to have a particular element in scope when
        # it has that element in the specific scope consisting of the following element
        # types:
        #
        # {elided}
        return $this->hasElementInScopeHandler($target, self::GENERAL_SCOPE);
    }

    public function hasElementInListItemScope($target): bool {
        $scope = self::GENERAL_SCOPE;
        $scope[Parser::HTML_NAMESPACE] = array_merge($scope[Parser::HTML_NAMESPACE], self::LIST_ITEM_SCOPE);
        return $this->hasElementInScopeHandler($target, $scope);
    }

    public function hasElementInButtonScope($target): bool {
        $scope = self::GENERAL_SCOPE;
        $scope[Parser::HTML_NAMESPACE] = array_merge($scope[Parser::HTML_NAMESPACE], self::BUTTON_SCOPE);
        return $this->hasElementInScopeHandler($target, $scope);
    }

    public function hasElementInTableScope($target): bool {
        return $this->hasElementInScopeHandler($target, self::TABLE_SCOPE);
    }

    public function hasElementInSelectScope(string $target): bool {
        # The stack of open elements is said to have a particular element 
        #   in select scope when it has that element in the specific scope 
        #   consisting of all element types EXCEPT the following:
        #
        # optgroup in the HTML namespace
        # option in the HTML namespace
        return $this->hasElementInScopeHandler($target, self::SELECT_SCOPE, false);
    }

    protected function hasElementInScopeHandler($target, array $list, $matchType = true): bool {
        assert(is_string($target) || $target instanceof \DOMElement, new \Exception("Invalid input type"));
        # The stack of open elements is said to have an element target node
        #   in a specific scope consisting of a list of element types list
        #   when the following algorithm terminates in a match state:
        if ($target instanceof \DOMElement) {
            # Initialize node to be the current node (the bottommost node of the stack).
            foreach ($this as $node) {
                # If node is the target node, terminate in a match state.
                if ($node->isSameNode($target)) {
                    return true;
                }
                # Otherwise, if node is one of the element types in list, terminate in a failure state.
                $ns = $node->namespaceURI ?? Parser::HTML_NAMESPACE;
                if (in_array($node->nodeName, $list[$ns] ?? []) === $matchType) {
                    return false;
                }
                # Otherwise, set node to the previous entry in the stack of 
                #   open elements and return to step 2. (This will never fail, 
                #   since the loop will always terminate in the previous step 
                #   if the top of the stack — an html element — is reached.)
            }
        } else {
            # Initialize node to be the current node (the bottommost node of the stack).
            foreach ($this as $node) {
                # If node is the target node, terminate in a match state.
                if ($node->nodeName === $target) {
                    return true;
                }
                # Otherwise, if node is one of the element types in list, terminate in a failure state.
                $ns = $node->namespaceURI ?? Parser::HTML_NAMESPACE;
                if (in_array($node->nodeName, $list[$ns] ?? []) === $matchType) {
                    return false;
                }
                # Otherwise, set node to the previous entry in the stack of 
                #   open elements and return to step 2. (This will never fail, 
                #   since the loop will always terminate in the previous step 
                #   if the top of the stack — an html element — is reached.)
            }
        }
    }

    public function __get($property) {
        switch ($property) {
            case 'adjustedCurrentNode':
                # The adjusted current node is the context element if the parser was created by
                # the HTML fragment parsing algorithm and the stack of open elements has only one
                # element in it (fragment case); otherwise, the adjusted current node is the
                # current node.
                return ($this->fragmentCase && count($this) === 1) ? $this->fragmentContext : $this->__get('currentNode');
            case 'adjustedCurrentNodeName':
                $adjustedCurrentNode = $this->__get('adjustedCurrentNode');
                return (!is_null($adjustedCurrentNode)) ? $adjustedCurrentNode->nodeName : null;
            case 'adjustedCurrentNodeNamespace':
                $adjustedCurrentNode = $this->__get('adjustedCurrentNode');
                return (!is_null($adjustedCurrentNode)) ? $adjustedCurrentNode->namespaceURI: null;
            case 'currentNode':
                return $this->isEmpty() ? null : $this->top();
            case 'currentNodeName':
                $currentNode = $this->__get('currentNode');
                return ($currentNode && $currentNode->nodeType) ? $currentNode->nodeName : null;
            case 'currentNodeNamespace':
                $currentNode = $this->__get('currentNode');
                return (!is_null($currentNode)) ? $currentNode->namespaceURI: null;
            default: 
                return null;
        }
    }
}
