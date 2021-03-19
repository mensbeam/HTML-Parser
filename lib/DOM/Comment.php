<?php
declare(strict_types=1);
namespace dW\HTML5;

class Comment extends \DOMComment {
    use Moonwalk;

    public function __toString(): string {
        # Append the literal string "<!--" (U+003C LESS-THAN SIGN, U+0021 EXCLAMATION
        # MARK, U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS), followed by the value of
        # current node’s data IDL attribute, followed by the literal string "-->"
        # (U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS, U+003E GREATER-THAN SIGN).
        return "<!--{$this->data}-->";
    }
}
