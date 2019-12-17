<?php
declare(strict_types=1);
namespace dW\HTML5;

class ParseError {
    protected $data;

    const UNEXPECTED_EOF = 1;
    const INVALID_CONTROL_OR_NONCHARACTERS = 9;
    const ENTITY_UNEXPECTED_CHARACTER = 11;
    const INVALID_NUMERIC_ENTITY = 12;
    const INVALID_NAMED_ENTITY = 13;
    const INVALID_CODEPOINT = 14;

    const UNEXPECTED_NULL_CHARACTER                                         = 101;
    const UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME                      = 102;
    const EOF_BEFORE_TAG_NAME                                               = 103;
    const INVALID_FIRST_CHARACTER_OF_TAG_NAME                               = 104;
    const MISSING_END_TAG_NAME                                              = 105;
    const EOF_IN_TAG                                                        = 106;
    const EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT                              = 107;
    const UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME                      = 108;
    const DUPLICATE_ATTRIBUTE                                               = 109;
    const UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME                            = 110;
    const MISSING_ATTRIBUTE_VALUE                                           = 111;
    const UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE                  = 112;
    const MISSING_WHITESPACE_BETWEEN_ATTRIBUTES                             = 113;
    const UNEXPECTED_SOLIDUS_IN_TAG                                         = 114;
    const CDATA_IN_HTML_CONTENT                                             = 115;
    const INCORRECTLY_OPENED_COMMENT                                        = 116;
    const ABRUPT_CLOSING_OF_EMPTY_COMMENT                                   = 117;
    const EOF_IN_COMMENT                                                    = 118;
    const NESTED_COMMENT                                                    = 119;
    const INCORRECTLY_CLOSED_COMMENT                                        = 120;
    const EOF_IN_DOCTYPE                                                    = 121;
    const MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME                            = 122;
    const MISSING_DOCTYPE_NAME                                              = 123;
    const INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME                     = 124;
    const MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD                   = 125;
    const MISSING_DOCTYPE_PUBLIC_IDENTIFIER                                 = 126;
    const MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER                    = 127;
    const ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER                                  = 128;
    const MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS  = 129;
    const MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD                   = 130;
    const MISSING_DOCTYPE_SYSTEM_IDENTIFIER                                 = 131;
    const MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER                    = 132;
    const ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER                                  = 133;
    const UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER              = 134;
    const EOF_IN_CDATA                                                      = 135;
    const END_TAG_WITH_ATTRIBUTES                                           = 136;
    const END_TAG_WITH_TRAILING_SOLIDUS                                     = 137;
    const MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE                       = 138;
    const UNKNOWN_NAMED_CHARACTER_REFERENCE                                 = 139;
    const ABSENCE_OF_DIGITS_IN_CHARACTER_REFERENCE                          = 140;
    const NULL_CHARACTER_REFRERENCE                                         = 141;
    const CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE                         = 142;
    const SURROGATE_CHARACTER_REFERENCE                                     = 143;
    const NONCHARACTER_CHARACTER_REFERENCE                                  = 144;
    const CONTROL_CHARACTER_REFERENCE                                       = 145;

    protected static $messages = [
        self::UNEXPECTED_NULL_CHARACTER                                         => 'Unexpected null character',
        self::UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME                      => 'Unexpected "?" character instead of tag name',
        self::EOF_BEFORE_TAG_NAME                                               => 'End-of-file before tag name',
        self::INVALID_FIRST_CHARACTER_OF_TAG_NAME                               => 'Invalid first character "%s" of tag name',
        self::MISSING_END_TAG_NAME                                              => 'Missing end-tag name',
        self::EOF_IN_TAG                                                        => 'End-of-file in tag',
        self::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT                              => 'End-of-file in script (HTML comment-like) text',
        self::UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME                      => 'Unexpected equals sign before attribute name',
        self::DUPLICATE_ATTRIBUTE                                               => 'Duplicate attribute "%s" in start tag',
        self::UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME                            => 'Unexpected character "%s" in attribute name',
        self::MISSING_ATTRIBUTE_VALUE                                           => 'Missing attribute value',
        self::UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE                  => 'Unexpected character "%s" in unquoted attribute value',
        self::MISSING_WHITESPACE_BETWEEN_ATTRIBUTES                             => 'Missing whitespace between attributes',
        self::UNEXPECTED_SOLIDUS_IN_TAG                                         => 'Unexpected solidus in tag',
        self::CDATA_IN_HTML_CONTENT                                             => 'CDATA in HTML content',
        self::INCORRECTLY_OPENED_COMMENT                                        => 'Incorrectly opened comment',
        self::ABRUPT_CLOSING_OF_EMPTY_COMMENT                                   => 'Abrupt closing of empty comment',
        self::EOF_IN_COMMENT                                                    => 'End-of-file in comment',
        self::NESTED_COMMENT                                                    => 'Nested comment',
        self::INCORRECTLY_CLOSED_COMMENT                                        => 'Incorrectly closed comment',
        self::EOF_IN_DOCTYPE                                                    => 'End-of-file in DOCTYPE',
        self::MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME                            => 'Missing whitespace before DOCTYPE name',
        self::MISSING_DOCTYPE_NAME                                              => 'Missing DOCTYPE name',
        self::INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME                     => 'Invalid character sequence after DOCTYPE name',
        self::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD                   => 'Missing whitespace after DOCTYPE "PUBLIC" keyword',
        self::MISSING_DOCTYPE_PUBLIC_IDENTIFIER                                 => 'Missing DOCTYPE "PUBLIC" identifier',
        self::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER                    => 'Missing quote before DOCTYPE "PUBLIC" identifier',
        self::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER                                  => 'Abrupt DOCTYPE "PUBLIC" identifier',
        self::MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS  => 'Missing whitespace between DOCTYPE "PUBLIC" and "SYSTEM" identifiers',
        self::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD                   => 'Missing whitespace after DOCTYPE "SYSTEM" keyword',
        self::MISSING_DOCTYPE_SYSTEM_IDENTIFIER                                 => 'Missing DOCTYPE "SYSTEM" identifier',
        self::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER                    => 'Missing quote before DOCTYPE "SYSTEM" identifier',
        self::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER                                  => 'Abrupt DOCTYPE "SYSTEM" identifier',
        self::UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER              => 'Unexpected character "%s" after DOCTYPE "SYSTEM" identifier',
        self::EOF_IN_CDATA                                                      => 'End-of-file in CDATA section',
        self::END_TAG_WITH_ATTRIBUTES                                           => 'End-tag with attributes',
        self::END_TAG_WITH_TRAILING_SOLIDUS                                     => 'End-tag with trailing solidus',
        self::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE                       => 'Missing semicolon after character reference',
        self::UNKNOWN_NAMED_CHARACTER_REFERENCE                                 => 'Unknown named character reference "%s"',
        self::ABSENCE_OF_DIGITS_IN_CHARACTER_REFERENCE                          => 'Absence of digits in character reference',
        self::NULL_CHARACTER_REFRERENCE                                         => 'Null character reference',
        self::CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE                         => 'Character reference outside Unicode range',
        self::SURROGATE_CHARACTER_REFERENCE                                     => 'Surrogate character rereference',
        self::NONCHARACTER_CHARACTER_REFERENCE                                  => 'Non-character character reference',
        self::CONTROL_CHARACTER_REFERENCE                                       => 'Control-character character reference',
    ];

    public function setHandler() {
        // Set the errror handler and honor already-set error reporting rules.
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
            // Convert newlines and tabs in the arguments to words to better
            // express what they are.
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
        // TODO: the file path should be middle-elided when necessary so that
        // the message does not exceed 1024 bytes
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
