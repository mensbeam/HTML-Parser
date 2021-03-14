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

        $nodesLength = $node->childNodes->length;
        if ($nodesLength > 0) {
            // If the provided node is a document node and the first element in
            // the tree is a document type then print the document type. There's
            // no sense in checking for this on every single element in the tree.
            // If the document type is present it will always be the first node
            // because of how PHP's XML DOM works.
            $start = 0;
            if ($node->nodeType === XML_DOCUMENT_NODE && $node->childNodes->item(0)->nodeType === XML_DOCUMENT_TYPE_NODE) {
                # Append the literal string "<!DOCTYPE" (U+003C LESS-THAN SIGN, U+0021
                # EXCLAMATION MARK, U+0044 LATIN CAPITAL LETTER D, U+004F LATIN CAPITAL LETTER
                # O, U+0043 LATIN CAPITAL LETTER C, U+0054 LATIN CAPITAL LETTER T, U+0059
                # LATIN CAPITAL LETTER Y, U+0050 LATIN CAPITAL LETTER P, U+0045 LATIN CAPITAL
                # LETTER E), followed by a space (U+0020 SPACE), followed by the value of
                # current node's name IDL attribute, followed by the literal string ">" (U+003E
                # GREATER-THAN SIGN).
                $s .= "<!DOCTYPE {$node->childNodes->item(0)->name}>";
                $start = 1;
            }

            # 3. For each child node of the node, in tree order, run the following steps:
            for ($i = $start; $i < $nodesLength; $i++) {
                # 1. Let current node be the child node being processed.
                # 2. Append the appropriate string from the following list to s:
                $s .= $node->childNodes->item($i);
            }
        }

        # 4. The result of the algorithm is the string s.
        return $s;
    }
}