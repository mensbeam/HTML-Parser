<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class ParseErrorDummy extends ParseError {
    public function setHandler() {
        // Do nothing
    }

    public function clearHandler() {
        // Do nothing
    }

    public function emit(string $file, int $line, int $column, int $code, ...$arg): bool {
        return false;
    }
}
