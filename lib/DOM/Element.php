<?php
declare(strict_types=1);
namespace dW\HTML5;

class Element extends \DOMElement {
    use Node;

    // Used for template elements
    public $content = null;

    public function __construct(string $name, string $value = '', string $namespaceURI = '') {
        parent::__construct($name, $value, $namespaceURI);

        if ($name === 'template' && $namespaceURI === '') {
            $this->content = $this->ownerDocument->createDocumentFragment();
        }
    }

    public function isMathMLTextIntegrationPoint(): bool {
        return (
            $this->namespaceURI === Parser::MATHML_NAMESPACE && (
                $this->nodeName === 'mi' || $this->nodeName === 'mo' || $this->nodeName === 'mn' || $this->nodeName === 'ms' || $this->nodeName === 'mtext'
            )
        );
    }

    public function isHTMLIntegrationPoint(): bool {
        $encoding = strtolower($this->getAttribute('encoding'));

        return ((
                $this->namespaceURI === Parser::MATHML_NAMESPACE &&
                $this->nodeName === 'annotation-xml' && (
                    $encoding === 'text/html' || $encoding === 'application/xhtml+xml'
                )
            ) || (
                $this->namespaceURI === Parser::SVG_NAMESPACE && (
                    $this->nodeName === 'foreignObject' || $this->nodeName === 'desc' || $this->nodeName === 'title'
                )
            )
        );
    }

    public function getDescendant($needle): \DOMNode {
        return static::descendant($needle, true);
    }

    public function hasDescendant($needle): bool {
        return static::descendant($needle, false);
    }

    protected function descendant($needle, bool $returnNode = true): \DOMNode {
        if ($this->hasChildNodes() === false) {
            return ($returnNode === true) ? null : false;
        }

        $context = $this->firstChild;

        do {
            $result = $this->compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }

            $result = $this->descendant($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        } while ($context = $context->nextSibling);

        return ($returnNode === true) ? null : false;
    }
}
