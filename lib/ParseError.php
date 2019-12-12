<?php
declare(strict_types=1);
namespace dW\HTML5;

class ParseError {
    protected $data;

    const TAG_NAME_EXPECTED = 0;
    const UNEXPECTED_EOF = 1;
    const UNEXPECTED_CHARACTER = 2;
    const ATTRIBUTE_EXISTS = 3;
    const UNEXPECTED_END_OF_TAG = 4;
    const UNEXPECTED_START_TAG = 5;
    const UNEXPECTED_END_TAG = 6;
    const UNEXPECTED_DOCTYPE = 7;
    const INVALID_DOCTYPE = 8;
    const INVALID_CONTROL_OR_NONCHARACTERS = 9;
    const UNEXPECTED_XMLNS_ATTRIBUTE_VALUE = 10;
    const ENTITY_UNEXPECTED_CHARACTER = 11;
    const INVALID_NUMERIC_ENTITY = 12;
    const INVALID_NAMED_ENTITY = 13;
    const INVALID_CODEPOINT = 14;

    protected static $messages = [
        self::TAG_NAME_EXPECTED                => 'Tag name expected',
        self::UNEXPECTED_EOF                   => 'Unexpected end-of-file',
        self::UNEXPECTED_CHARACTER             => 'Unexpected "%s" character',
        self::ATTRIBUTE_EXISTS                 => '%s attribute already exists; discarding',
        self::UNEXPECTED_END_OF_TAG            => 'Unexpected end-of-tag',
        self::UNEXPECTED_START_TAG             => 'Unexpected %s start tag',
        self::UNEXPECTED_END_TAG               => 'Unexpected %s end tag',
        self::UNEXPECTED_DOCTYPE               => 'Unexpected DOCTYPE',
        self::INVALID_DOCTYPE                  => 'Invalid DOCTYPE',
        self::INVALID_CONTROL_OR_NONCHARACTERS => 'Invalid Control or Non-character; removing',
        self::UNEXPECTED_XMLNS_ATTRIBUTE_VALUE => 'Unexpected xmlns attribute value',
        self::ENTITY_UNEXPECTED_CHARACTER      => 'Unexpected "%s" character in entity; %s expected',
        self::INVALID_NUMERIC_ENTITY           => '"%s" is an invalid numeric entity',
        self::INVALID_NAMED_ENTITY             => '"%s" is an invalid name for an entity',
        self::INVALID_CODEPOINT                => '"%s" is an invalid character codepoint'
    ];

    public function setHandler() {
        // Set the error handler and honor already-set error reporting rules.
        set_error_handler([$this, 'errorHandler'], error_reporting());
    }

    public function clearHandler() {
        restore_error_handler();
    }

    protected function prepareMessage(string $file, int $line, int $column, int $code, ...$arg): string {
        assert(isset(static::$messages[$code]), new Exception(Exception::INVALID_CODE));

        $message = static::$messages[$code];
        // Count the number of replacements needed in the message.
        $count = substr_count($message, '%s');
        // If the number of replacements don't match the arguments then oops.
        assert(count($arg) === $count, new Exception(Exception::INCORRECT_PARAMETERS_FOR_MESSAGE, $count));

        if ($count > 0) {
            // Convert newlines and tabs in the arguments to words to better express what they
            // are.
            $arg = array_map(function($value) {
                if ($value === "\n") {
                    return 'Newline';
                } elseif ($value === "\t") {
                    return 'Tab';
                } elseif (is_null($value)) {
                    return 'nothing';
                } else {
                    return $value;
                }
            }, $arg);

            // Go through each of the arguments and run sprintf on the strings.
            $message = sprintf($message, ...$arg);
        }
        // Wrap with preamble and location
        // TODO: the file path should be middle-elided when necessary so that the message does not exceed 1024 bytes
        $message = sprintf("HTML5 Parse Error: \"%s\" in %s", $message, $file);
        if ($line) {
            $message .= sprintf(" on line %s, column %s", $line, $column);
        }
        return $message;
    }

    public function emit(string $file, int $line, int $column, int $code, ...$arg): bool {
        return trigger_error($this->prepareMessage($file, $line, $column, $code, ...$arg), \E_USER_WARNING);
    }

    public function errorHandler(int $code, string $message, string $file, int $line) {
        if ($code === E_USER_WARNING) {
            echo "$message\n";
        }
    }
}
