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
    protected $_column = 1;
    // array of normalized CR+LF pairs, denoted by the character offset of the LF
    protected $normalized = [];
    // Holds the character position and column number of each newline
    protected $newlines = [];
    // Holds the character position of each supplementary plane character, which count as two columns when reporting errors
    protected $astrals = [];
    // The character position of the forward-most input stream error emitted
    protected $lastError = 0;
    // Whether the EOF imaginary character has been consumed
    protected $eof = false;

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

        $this->data = new \MensBeam\Intl\Encoding\UTF8($data, false, true);
    }

    public function consume(int $length = 1, $advancePointer = true): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        for ($i = 0, $string = ''; $i < $length; $i++) {
            $char = $this->data->nextChar();

            # Before the tokenization stage, the input stream must be 
            #   preprocessed by normalizing newlines.
            # Thus, newlines in HTML DOMs are represented by U+000A LF characters, 
            #   and there are never any U+000D CR characters in the input to the tokenization stage.
            if ($char === "\r") {
                // if this is a CR+LF pair, skip the CR and note the normalization
                if ($this->data->peekChar() === "\n") {
                    $char = $this->data->nextChar();
                    $this->normalized[$this->data->posChar()] = true;
                }
                // otherwise just silently change the character to LF; 
                // the bare CR will be trivial to process when seeking backwards
                else {
                    $char = "\n";
                }
            }
            // append the character to the output string
            $string .= $char;
            // unless we're peeking, track line and column position, and whether we've hit EOF
            if ($advancePointer) {
                if (!$this->checkChar($char)) {
                    break;
                }
            }
        }
        return $string;
    }

    protected function checkChar(string $char): bool {
        // track line and column number, and EOF
        if ($char === "\n") {
            $this->newlines[$this->data->posChar()] = $this->_column;
            $this->_column = 1;
            $this->_line++;
        } elseif ($char === '') {
            $this->eof = true;
            return false;
        } else {
            $len = strlen($char);    
            $here = $this->data->posChar();
            if ($this->lastError < $here) {
                // look for erroneous characters
                if ($len === 1) {
                    $ord = ord($char);
                    if (($ord < 0x20 && !in_array($ord, [0x0, 0x9, 0xA, 0xC])) || $ord === 0x7F) {
                        $this->error(ParseError::CONTROL_CHARACTER_IN_INPUT_STREAM);
                        $this->lastError = $here;
                    }
                } elseif ($len === 2) {
                    if  (ord($char[0]) == 0xC2) {
                        $ord = ord($char[1]);
                        if ($ord >= 0x80 && $ord <= 0x9F) {
                            $this->error(ParseError::CONTROL_CHARACTER_IN_INPUT_STREAM);
                            $this->lastError = $here;
                        }
                    }
                } elseif ($len === 3) {
                    $head = ord($char[0]);
                    if ($head === 0xED) {
                        $tail = (ord($char[1]) << 8) + ord($char[2]);
                        if ($tail >= 0xA080 && $tail <= 0xBFBF) {
                            $this->error(ParseError::SURROGATE_IN_INPUT_STREAM);
                            $this->lastError = $here;
                        }
                    } elseif ($head === 0xEF) {
                        $tail = (ord($char[1]) << 8) + ord($char[2]);
                        if (($tail >= 0xB790 && $tail <= 0xB7AF) || $tail >= 0xBFBE) {
                            $this->error(ParseError::NONCHARACTER_IN_INPUT_STREAM);
                            $this->lastError = $here;
                        } elseif ($tail === 0xBFBD && $this->data->posErr === $here) {
                            $this->error(ParseError::NONCHARACTER_IN_INPUT_STREAM, $this->data->posByte);
                            $this->lastError = $here;
                        }
                    }
                } elseif ($len === 4) {
                    $tail = (ord($char[2]) << 8) + ord($char[3]);
                    if ($tail >= 0xBFBE) {
                        $this->error(ParseError::NONCHARACTER_IN_INPUT_STREAM);
                        $this->lastError = $here;
                    }
                }
            }
            $this->_column++;
            if ($len === 4) {
                // If the character is on a supplementary Unicode plane, 
                //  it counts as two columns for the purposes of error reporting
                $this->astrals[$here] = true;
                $this->_column++;
            }
        }
        return true;
    }

    public function unconsume(int $length = 1, bool $retreatPointer = true): void {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        if ($this->eof) {
            $length--;
            $this->eof = false;
        }
        while ($length-- > 0) {
            $here = $this->data->posChar();
            // if the previous character was a normalized CR+LF pair, we need to go back two
            if (isset($this->normalized[$here])) {
                $this->data->seek(-1);
            }
            // recalculate line and column positions, if requested
            if ($retreatPointer) {
                $col = $this->newlines[$here] ?? 0;
                if ($col) {
                    $this->_column = $col;
                    $this->_line--;
                } else {
                    $this->_column--;
                    if ($this->astrals[$here] ?? false) {
                        $this->_column--;
                    }
                }
            }
            $this->data->seek(-1);
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

        $start = $this->data->posChar();
        $count = 0;
        $string = '';
        while (true) {
            $char = $this->consume(1, false);

            if ($char === '') {
                break;
            }

            $inArray = in_array($char, $match);

            // strspn
            if ($while && !$inArray) {
                $this->unconsume(1, false);
                break;
            }
            // strcspn
            elseif (!$while && $inArray) {
                $this->unconsume(1, false);
                break;
            }

            if ($advancePointer) {
                $this->checkChar($char);
            }

            $count++;
            $string .= $char;
            if ($count === $limit) {
                break;
            }
        }

        if (!$advancePointer && $count) {
            $this->data->seek(-($this->data->posChar - $start));
        }

        return $string;
    }

    /** Returns an indexed array with the line and column positions of the requested offset from the current position */
    public function whereIs(int $relativePos): array {
        if ($relativePos === 0) {
            return [$this->_line, $this->_column];
        } elseif ($relativePos < 0) {
            $pos = $this->data->posChar();
            $line = $this->_line;
            $col = $this->_column;
            do {
                // If the current position is the start of a line, 
                //  get the column position of the end of the previous line
                if (isset($this->newlines[$pos])) {
                    $line--;
                    $col = $this->newlines[$pos];
                    // If the newline was a normalized CR+LF pair, 
                    //  go back one extra character
                    if (isset($this->normalized[$pos])) {
                        $pos--;
                    }
                } else {
                    $col--;
                    // supplementary plane characters count as two
                    if ($this->astrals[$pos] ?? false) {
                        $this->_column--;
                    }
                }
                $pos--;
            } while (++$relativePos < 0);
            return [$line, $col];
        }
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
