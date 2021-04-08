<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class ProcessingInstruction extends \DOMProcessingInstruction {
    use Moonwalk, Node;

    public function __toString(): string {
        # Append the literal string "<?" (U+003C LESS-THAN SIGN, U+003F QUESTION MARK),
        # followed by the value of current node’s target IDL attribute, followed by a
        # single U+0020 SPACE character, followed by the value of current node’s data
        # IDL attribute, followed by a single U+003E GREATER-THAN SIGN character (>).
        return "<?{$this->target} {$this->data}>";
    }
}
