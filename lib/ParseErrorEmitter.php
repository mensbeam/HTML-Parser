<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait ParseErrorEmitter {
    /** @var ParseError $errorHandler */
    private $errorHandler;

    private function error(int $code, ...$arg): bool {
        $data = ($this instanceof Data) ? $this : ($this->data ?? null);
        assert($data instanceof Data);
        assert($this->errorHandler instanceof ParseError);
        list($line, $column) = $data->whereIs(ParseError::REPORT_OFFSETS[$code] ?? 0);
        return $this->errorHandler->emit($data->filePath, $line, $column, $code, ...$arg);
    }
}
