<?php
declare(strict_types=1);
namespace dW\HTML5;

class DocumentType extends \DOMDocumentType {
    function __toString(): string {
        # Append the literal string "<!DOCTYPE" (U+003C LESS-THAN SIGN, U+0021
        # EXCLAMATION MARK, U+0044 LATIN CAPITAL LETTER D, U+004F LATIN CAPITAL LETTER
        # O, U+0043 LATIN CAPITAL LETTER C, U+0054 LATIN CAPITAL LETTER T, U+0059 LATIN
        # CAPITAL LETTER Y, U+0050 LATIN CAPITAL LETTER P, U+0045 LATIN CAPITAL LETTER
        # E), followed by a space (U+0020 SPACE), followed by the value of current
        # node’s name IDL attribute, followed by the literal string ">" (U+003E
        # GREATER-THAN SIGN).
        return "<!DOCTYPE {$this->name}>";
    }
}
