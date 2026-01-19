<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

class OpenElementsStack extends Stack {
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
            "select",
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

    /** @var ?\DOMElement */
    protected $fragmentContext = null;
    /** @var ?string */
    protected $htmlNamespace;
    /** @var ?\DOMElement */
    public $currentNode = null;
    /** @var ?string */
    public $currentNodeName = null;
    /** @var ?string */
    public $currentNodeNamespace = null;
    /** @var ?\DOMElement */
    public $adjustedCurrentNode = null;
    /** @var ?string */
    public $adjustedCurrentNodeName = null;
    /** @var ?string */
    public $adjustedCurrentNodeNamespace = null;

    public function __construct(?string $htmlNamespace, ?\DOMElement $fragmentContext = null) {
        $this->fragmentContext = $fragmentContext;
        $this->htmlNamespace = $htmlNamespace;
    }

    public function pop() {
        $out = array_pop($this->_storage);
        $this->computeProperties();
        return $out;
    }

    public function offsetSet($offset, $value) {
        assert($offset >= 0, new \Exception("Invalid stack index $offset"));

        if ($offset === null) {
            $this->_storage[] = $value;
        } else {
            $this->_storage[$offset] = $value;
        }
        $this->computeProperties();
    }

    public function offsetUnset($offset) {
        assert($offset >= 0 && $offset < count($this->_storage), new \Exception("Invalid stack index $offset"));
        array_splice($this->_storage, $offset, 1, []);
        $this->computeProperties();
    }

    public function insert(\DOMElement $element, ?int $at = null): void  {
        assert($at === null || ($at >= 0 && $at <= count($this->_storage)), new \Exception("Invalid stack index ".var_export($at, true)));
        if ($at === null) {
            $this[] = $element; // @codeCoverageIgnore
        } else {
            array_splice($this->_storage, $at, 0, [$element]);
        }
        $this->computeProperties();
    }

    public function popUntil(string ...$target): void {
        do {
            $node = array_pop($this->_storage);
            assert(isset($node), new \Exception("Stack is incorrectly empty"));
        } while ($node->namespaceURI !== $this->htmlNamespace || !in_array($node->nodeName, $target));
        $this->computeProperties();
    }

    public function popUntilSame(\DOMElement $target): void {
        do {
            $node = array_pop($this->_storage);
        } while (!$node->isSameNode($target));
        $this->computeProperties();
    }

    public function find(string ...$name): int {
        foreach ($this as $k => $node) {
            if ($node->namespaceURI === $this->htmlNamespace && in_array($node->nodeName, $name)) {
                return $k;
            }
        }
        return -1;
    }

    public function findNot(string ...$name): int {
        foreach ($this as $k => $node) {
            if ($node->namespaceURI !== $this->htmlNamespace || !in_array($node->nodeName, $name)) {
                return $k;
            }
        }
        return -1;
    }

    public function findSame(\DOMElement $target): int {
        for ($k = (sizeof($this->_storage) - 1); $k > -1; $k--) {
            if ($this->_storage[$k]->isSameNode($target)) {
                return $k;
            }
        }
        return -1;
    }

    public function removeSame(\DOMElement $target): void {
        $pos = $this->findSame($target);
        if ($pos > -1) {
            unset($this[$pos]);
        }
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
        while (!$this->isEmpty() && $this->top()->namespaceURI === $this->htmlNamespace && ($map[$this->top()->nodeName] ?? false)) {
            array_pop($this->_storage);
            $this->count--;
        }
        $this->computeProperties();
    }

    public function generateImpliedEndTagsThoroughly(): void {
        # When the steps below require the UA to generate all implied end tags
        #   thoroughly, then, while the current node is {elided list of element names},
        #   the UA must pop the current node off the stack of open elements.
        while (!$this->isEmpty() && $this->top()->namespaceURI === $this->htmlNamespace && (self::IMPLIED_END_TAGS_THOROUGH[$this->top()->nodeName] ?? false)) {
            array_pop($this->_storage);
            $this->count--;
        }
        $this->computeProperties();
    }

    public function clearToTableContext(): void {
        # When the algorithm requires the UA to clear the stack back to a
        #   table context, it means that the UA must, while the current node
        #   is not a table, template, or html element, pop elements from the
        #   stack of open elements.
        assert(count($this->_storage) > 0, new \Exception("Stack is incorrectly empty"));
        $pos = $this->find("table", "template", "html");
        assert($pos > -1, new \Exception("Expected table context is missing"));
        $stop = $pos + 1;
        while (count($this->_storage) > $stop) {
            array_pop($this->_storage);
        }
        $this->computeProperties();
    }

    public function clearToTableBodyContext(): void {
        # When the steps above require the UA to clear the stack back to a
        #   table body context, it means that the UA must, while the current
        #   node is not a tbody, tfoot, thead, template, or html element,
        #   pop elements from the stack of open elements.
        assert(count($this->_storage) > 0, new \Exception("Stack is incorrectly empty"));
        $pos = $this->find("tbody", "tfoot", "thead", "template", "html");
        assert($pos > -1, new \Exception("Expected table body context is missing"));
        $stop = $pos + 1;
        while (count($this->_storage) > $stop) {
            array_pop($this->_storage);
        }
        $this->computeProperties();
    }

    public function clearToTableRowContext(): void {
        # When the steps above require the UA to clear the stack back to a
        #   table row context, it means that the UA must, while the current
        #   node is not a tr, template, or html element, pop elements from
        #   the stack of open elements.
        assert(count($this->_storage) > 0, new \Exception("Stack is incorrectly empty"));
        $pos = $this->find("tr", "template", "html");
        assert($pos > -1, new \Exception("Expected table row context is missing"));
        $stop = $pos + 1;
        while (count($this->_storage) > $stop) {
            array_pop($this->_storage);
        }
        $this->computeProperties();
    }

    public function hasElementInScope(...$target): bool {
        # The stack of open elements is said to have a particular element in scope when
        # it has that element in the specific scope consisting of the following element
        # types:
        #
        # {elided}
        return $this->hasElementInScopeHandler($target, self::GENERAL_SCOPE);
    }

    public function hasElementInListItemScope(...$target): bool {
        $scope = self::GENERAL_SCOPE;
        $scope[Parser::HTML_NAMESPACE] = array_merge($scope[Parser::HTML_NAMESPACE], self::LIST_ITEM_SCOPE);
        return $this->hasElementInScopeHandler($target, $scope);
    }

    public function hasElementInButtonScope(...$target): bool {
        $scope = self::GENERAL_SCOPE;
        $scope[Parser::HTML_NAMESPACE] = array_merge($scope[Parser::HTML_NAMESPACE], self::BUTTON_SCOPE);
        return $this->hasElementInScopeHandler($target, $scope);
    }

    public function hasElementInTableScope(...$target): bool {
        return $this->hasElementInScopeHandler($target, self::TABLE_SCOPE);
    }

    protected function hasElementInScopeHandler(array $targets, array $list, $matchType = true): bool {
        # The stack of open elements is said to have an element target node
        #   in a specific scope consisting of a list of element types list
        #   when the following algorithm terminates in a match state:
        # Initialize node to be the current node (the bottommost node of the stack).
        foreach ($this as $node) {
            # If node is the target node, terminate in a match state.
            foreach ($targets as $target) {
                if ($target instanceof \DOMElement) {
                    if ($node->isSameNode($target)) {
                        return true;
                    }
                } else {
                    if ($node->namespaceURI === $this->htmlNamespace && $node->nodeName === $target) {
                        return true;
                    }
                }
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
        assert(false, new \Exception("Scope handler left stack in invalid state:".(string)$this)); // @codeCoverageIgnore
    } // @codeCoverageIgnore

    protected function computeProperties(): void {
        $this->count = count($this->_storage);
        $this->currentNode = $this->top();
        # The adjusted current node is the context element if the parser was created by
        # the HTML fragment parsing algorithm and the stack of open elements has only one
        # element in it (fragment case); otherwise, the adjusted current node is the
        # current node.
        if ($this->fragmentContext && $this->count === 1) {
            $this->adjustedCurrentNode = $this->fragmentContext;
        } else {
            $this->adjustedCurrentNode = $this->currentNode;
        }
        if ($this->currentNode) {
            $this->currentNodeName = $this->currentNode->nodeName;
            $this->currentNodeNamespace = $this->currentNode->namespaceURI;
        } else {
            $this->currentNodeName = null; // @codeCoverageIgnore
            $this->currentNodeNamespace = null; // @codeCoverageIgnore
        }
        if ($this->adjustedCurrentNode) {
            $this->adjustedCurrentNodeName = $this->adjustedCurrentNode->nodeName;
            $this->adjustedCurrentNodeNamespace = $this->adjustedCurrentNode->namespaceURI;
        } else {
            $this->adjustedCurrentNodeName = null; // @codeCoverageIgnore
            $this->adjustedCurrentNodeNamespace = null; // @codeCoverageIgnore
        }
    }

    public function __toString(): string {
        $out = [];
        foreach ($this as $node) {
            $ns = $node->namespaceURI ?? Parser::HTML_NAMESPACE;
            $prefix = Parser::NAMESPACE_MAP[$ns] ?? "?";
            $prefix .= $prefix ? " " : "";
            $out[] = $prefix.$node->nodeName;
        }
        return implode(" < ", $out);
    }
}
