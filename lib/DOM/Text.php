<?php
declare(strict_types=1);
namespace dW\HTML5;

class Text extends \DOMText {
    use EscapeString, Moonwalk;

    function __toString(): string {
        # If the parent of current node is a style, script, xmp, iframe, noembed,
        # noframes, or plaintext element, or if the parent of current node is a noscript
        # element and scripting is enabled for the node, then append the value of
        # current node’s data IDL attribute literally.
        // DEVIATION: No scripting.

        # Otherwise, append the value of current node’s data IDL attribute, escaped as
        # described below.
        return $this->escapeString($this->data);
    }
}
