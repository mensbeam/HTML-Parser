<?php
declare(strict_types=1);
namespace dW\HTML5;

trait ParseErrorEmitter {
    /** @var ParseError $errorHandler */
    private $errorHandler;

    private function error(int $code, ...$arg): bool {
        $data = ($this instanceof Data) ? $this : ($this->data ?? null);
        if ($this->errorHandler) {
            if ($data) {
                return $this->errorHandler->emit($data->filePath, $data->line, $data->column, $code, ...$arg);
            } else {
                throw new \Exception("Emitted parse error without data stream");
            }
        } else {
            throw new \Exception("Emitted error without error handler");
        }
    }
}
