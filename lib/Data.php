<?php
declare(strict_types=1);
namespace dW\HTML5;

class Data {
    use ParseErrorEmitter;

    // Used to get the file path for error reporting.
    public $filePath;

    // Internal storage for the Intl data object.
    protected $data;
    // Used for error reporting to display line number.
    protected $_line = 1;
    // Used for error reporting to display column number.
    protected $_column = 0;
    // Used for error reporting when unconsuming to calculate column number from
    // last newline.
    protected $newlines = [];
    // Whether the EOF imaginary character has been consumed
    protected $eof = false;


    // Used for debugging to print out information as data is consumed.
    public static $debug = false;


    const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const DIGIT = '0123456789';
    const HEX = '0123456789ABCDEFabcdef';
    const WHITESPACE = "\t\n\x0c\x0d ";


    public function __construct(string $data, string $filePath = 'STDIN', ParseError $errorHandler = null) {
        $this->errorHandler = $errorHandler ?? new ParseError;
        if ($filePath !== 'STDIN') {
            $this->filePath = realpath($filePath);
            $data = file_get_contents($this->filePath);
        } else {
            $this->filePath = $filePath;
        }

        // DEVIATION: The spec has steps for parsing and determining the character
        // encoding. At this moment this implementation won't determine a character
        // encoding and will just assume UTF-8.


        // Normalize line breaks. Convert CRLF and CR to LF.
        // Break the string up into a traversable object.
        $this->data = new \MensBeam\Intl\Encoding\UTF8(str_replace(["\r\n", "\r"], "\n", $data), false, true);

        # One leading U+FEFF BYTE ORDER MARK character must be ignored if any are present
        # in the input stream.

        if ($this->data->nextChar() !== '\xEF\xBB\xBF') {
            // rewind to the start of the string if the first character was not a BOM
            $this->data->rewind();
        }
    }

    public function consume(int $length = 1): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        for ($i = 0, $string = ''; $i < $length; $i++) {
            $char = $this->data->nextChar();

            if ($char === "\n") {
                $this->newlines[] = $this->data->posChar();
                $this->_column = 1;
                $this->_line++;
            } else {
                $this->_column++;
            }

            $string .= $char;
        }

        if ($char === '') {
            $this->eof = true;
        }

        return $string;
    }

    public function unconsume(int $length = 1) {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        if (!$this->eof) {
            $this->data->seek(0 - $length);

            $string = $this->data->peekChar($length);
            $numOfNewlines = substr_count($string, "\n");

            if ($numOfNewlines > 0) {
                $this->_line -= $numOfNewlines;

                $count = $this->newlines;
                $index = count($this->newlines) - ($numOfNewlines - 1);
                $this->_column = 1 + (($count > 0 && isset($this->newlines[$index])) ? $this->data->posChar() - $this->newlines[$index] : $this->data->posChar());
            } else {
                $this->_column -= $length;
            }
        }
    }

    public function consumeWhile(string $match, int $limit = 0): string {
        return $this->span($match, true, true, $limit);
    }

    public function consumeUntil(string $match, int $limit = 0): string {
        return $this->span($match, false, true, $limit);
    }

    public function peek(int $length = 1): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        $string = $this->data->peekChar($length);

        return $string;
    }

    public function peekWhile(string $match, int $limit = 0): string {
        return $this->span($match, true, false, $limit);
    }

    public function peekUntil(string $match, int $limit = 0): string {
        return $this->span($match, false, false, $limit);
    }

    protected function span(string $match, bool $while = true, bool $advancePointer = true, int $limit = -1): string {
        // Break the matching characters into an array of characters. Unicode friendly.
        $match = preg_split('/(?<!^)(?!$)/Su', $match);

        $count = 0;
        $string = '';
        while (true) {
            $char = $this->data->nextChar();
            $count++;

            if ($char === '') {
                break;
            }

            $inArray = in_array($char, $match);

            // strspn
            if ($while && !$inArray) {
                break;
            }
            // strcspn
            elseif (!$while && $inArray) {
                break;
            }

            if ($advancePointer) {
                if ($char === "\n") {
                    $this->newlines[] = $this->data->posChar();
                    $this->_column = 1;
                    $this->_line++;
                } else {
                    $this->_column++;
                }
            }

            $string .= $char;
            if ($count === $limit) {
                break;
            }
        }

        // If the end (or limit) is reached the pointer isn't moved when the last character
        // is checked, so it only needs to be moved backwards if not wanting the
        // pointer to move.
        if ($char === '' || $count === $limit) {
            if (!$advancePointer) {
                $this->data->seek(0 - $count - 1);
            }
        } else {
            $this->data->seek(($advancePointer) ? -1 : 0 - $count - 2);
        }

        return $string;
    }

    public function __get($property) {
        switch ($property) {
            case 'column': return $this->_column;
            break;
            case 'line': return $this->_line;
            break;
            case 'pointer': return $this->data->posChar();
            break;
            default: return null;
        }
    }
}
