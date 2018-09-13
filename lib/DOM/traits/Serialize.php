<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Serialize {
    protected function serialize(\DOMNode $node = null): string {
        if (is_null($node)) {
            $node = $this;
        }

        if (!$node instanceof Element && !$node instanceof Document && !$node instanceof DocumentFragment) {
            throw new Exception(Exception::DOM_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED, gettype($node));
        }

        # 8.3. Serializing HTML fragments
        #
        # 1. Let s be a string, and initialize it to the empty string.
        $s = '';

        # 2. If the node is a template element, then let the node instead be the
        # template element’s template contents (a DocumentFragment node).
        if ($node instanceof Element && $node->nodeName === 'template') {
            $node = $node->content;
        }

        # 3. For each child node of the node, in tree order, run the following steps:
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            # 1. Let current node be the child node being processed.
            # 2. Append the appropriate string from the following list to s:
            // Implementing this by allowing each of the node types to serialize themselves.
            $s .= $node->childNodes->item($i);
        }

        # 4. The result of the algorithm is the string s.
        return $s;
    }
}