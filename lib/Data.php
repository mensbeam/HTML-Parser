<?php
declare(strict_types=1);
namespace dW\HTML5;

use MensBeam\Intl\Encoding;
use MensBeam\Intl\Encoding\Encoding as EncodingEncoding;

class Data {
    use ParseErrorEmitter;

    // Used to get the file path for error reporting.
    public $filePath;
    // Whether the encoding is certain or tentative; this is a feature of the specification, but not relevant for this implementation
    public $encodingCertain = false;
    // The canonical name of the encoding
    public $encoding;

    // Internal storage for the Intl data object.
    protected $data;
    // Used for error reporting to display line number.
    protected $_line = 1;
    // Used for error reporting to display column number.
    protected $_column = 0;
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
    // Whether to track positions for reporting parse errors
    protected $track = true;

    const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const DIGIT = '0123456789';
    const HEX = '0123456789ABCDEFabcdef';
    const WHITESPACE = "\t\n\x0C\x0D ";
    const WHITESPACE_SAFE = "\t\x0C ";


    public function __construct(string $data, string $filePath = 'STDIN', ParseError $errorHandler = null, ?string $encodingOrContentType = '') {
        $this->errorHandler = $errorHandler ?? new ParseError;
        $this->filePath = $filePath;
        $encodingOrContentType = (string) $encodingOrContentType;
        // don't track the current line/column position if erroro reporting has been suppressed
        if (!(error_reporting() & \E_USER_WARNING)) {
            $this->track = false;
        }

        if ($encoding = Charset::fromBOM($data)) {
            // encoding determined from Unicode byte order mark
            $this->encodingCertain = true;
        } elseif ($encoding = Charset::fromCharset($encodingOrContentType)) {
            $this->encodingCertain = true;
        } elseif ($encoding = Charset::fromTransport($encodingOrContentType)) {
            $this->encodingCertain = true;
        } elseif ($encoding = Charset::fromPrescan($data)) {
            // Encoding is tentative
        } else {
            // Encoding is tentative; fall back to the configured default encoding
            $encoding = Charset::fromCharset(Parser::$fallbackEncoding) ?? "windows-1252";
        }
        $this->encoding = $encoding;
        $this->data = Encoding::createDecoder($encoding, $data, false, true);
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
            if ($advancePointer && $this->track) {
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
            $this->_column = 0;
            $this->_line++;
        } elseif ($char === '') {
            $this->eof = true;
            return false;
        } else {
            $this->_column++;
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
                    $this->astrals[$here] = true;
                }
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
            if ($retreatPointer && $this->track) {
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

    public function consumeWhile(string $match, int $limit = null): string {
        $start = $this->data->posChar();
        $out =  $this->data->asciiSpan($match, $limit);
        if ($this->track) {
            $this->_column += ($this->data->posChar() - $start);
        }
        return $out;
    }

    public function consumeUntil(string $match, int $limit = null): string {
        $start = $this->data->posChar();
        if ($this->track) {
            // control characters produce parse errors
            $match .= "\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";
        }
        $out =  $this->data->asciiSpanNot($match."\r\n", $limit);
        if ($this->track) {
            $this->_column += ($this->data->posChar() - $start);
        }
        return $out;
    }

    public function peek(int $length = 1): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        $string = $this->data->peekChar($length);

        return $string;
    }

    /** Returns an indexed array with the line and column positions of the requested offset from the current position */
    public function whereIs(int $relativePos): array {
        if ($this->track) {
            if ($this->eof) {
                $relativePos++;
                if ($this->astrals[$this->data->posChar()] ?? false) {
                    $relativePos++;
                }
            }
            if ($relativePos === 0) {
                if (!$this->_column && $this->_line > 1) {
                    return [$this->_line - 1, $this->newlines[$this->data->posChar()] + 1];
                } else {
                    return [$this->_line, $this->_column];
                }
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
            } else {
                return [$this->_line, $this->_column + $relativePos];
            }
        } else {
            return [0, 0];
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
