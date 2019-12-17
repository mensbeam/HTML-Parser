<?php
declare(strict_types=1);
namespace dW\HTML5;

trait ParseErrorEmitter {
    /** @var ParseError $errorHandler */
    private $errorHandler;

    private function error(int $code, ...$arg): bool {
        $data = ($this instanceof Data) ? $this : ($this->data ?? null);
        assert($data instanceof Data);
        assert($this->errorHandler instanceof ParseError);
        return $this->errorHandler->emit($data->filePath, $data->line, $data->column, $code, ...$arg);
    }
}
