<?php
declare(strict_types=1);
namespace dW\HTML5;

class ProcessingInstruction extends \DOMProcessingInstruction {
    use Moonwalk;

    public function __toString(): string {
        # Append the literal string "<?" (U+003C LESS-THAN SIGN, U+003F QUESTION MARK),
        # followed by the value of current node’s target IDL attribute, followed by a
        # single U+0020 SPACE character, followed by the value of current node’s data
        # IDL attribute, followed by a single U+003E GREATER-THAN SIGN character (>).
        return "<?{$this->target} {$this->data}>";
    }
}
