<?php
declare(strict_types=1);
namespace dW\HTML5;

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
