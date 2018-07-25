<?php
declare(strict_types=1);
namespace dW\HTML5;

class ParseError {
    // DataStream object passed to it used to get information used in error
    // reporting.
    public static $data;

    const TAG_NAME_EXPECTED = 0;
    const UNEXPECTED_EOF = 1;
    const UNEXPECTED_CHARACTER = 2;
    const ATTRIBUTE_EXISTS = 3;
    const UNEXPECTED_TAG_END = 4;
    const UNEXPECTED_START_TAG = 5;
    const UNEXPECTED_END_TAG = 6;
    const UNEXPECTED_DOCTYPE = 7;
    const INVALID_DOCTYPE = 8;
    const INVALID_CONTROL_OR_NONCHARACTERS = 9;
    const INVALID_XMLNS_ATTRIBUTE_VALUE = 10;

    protected static $messages = ['Tag name expected; found %s',
                                  'Unexpected end-of-file; %s expected',
                                  'Unexpected "%s" character; %s expected',
                                  '%s attribute already exists; discarding',
                                  'Unexpected tag end; %s expected',
                                  'Unexpected %s start tag; %s expected',
                                  'Unexpected %s end tag; %s expected',
                                  'Unexpected DOCTYPE; %s expected',
                                  'Invalid DOCTYPE',
                                  'Invalid Control or Non-character; removing',
                                  'Invalid xmlns attribute value; %s expected'];

    public static function errorHandler($code, $message, $file, $line, array $context) {
        if ($code === E_USER_WARNING) {
            $errMsg = sprintf("HTML5 Parse Error: \"%s\" in %s", $message, static::$data->filePath);

            if (static::$data->length !== 0) {
                $errMsg .= sprintf(" on line %s, column %s\n", static ::$data->line, static::$data->column);
            } else {
                $errMsg .= "\n";
            }

            echo $errMsg;
        }
    }

    public static function trigger(int $code, DataStream $data, ...$args): bool {
        if (!isset(static::$messages[$code])) {
            throw new Exception(Exception::INVALID_CODE);
        }

        static::$data = $data;

        // Set the error handler and honor already-set error reporting rules.
        set_error_handler('\\dW\\HTML5\\ParseError::errorHandler', error_reporting());

        $message = static::$messages[$code];

        // Count the number of replacements needed in the message.
        $count = substr_count($message, '%s');
        // If the number of replacements don't match the arguments then oops.
        if (count($args) !== $count) {
            throw new Exception(static::INCORRECT_PARAMETERS_FOR_MESSAGE, $count);
        }

        if ($count > 0) {
            // Convert newlines and tabs in the arguments to words to better express what they
            // are.
            $args = array_map(function($value) {
                switch ($value) {
                    case "\n": return 'Newline';
                    break;
                    case "\t": return 'Tab';
                    break;
                    default: return $value;
                }
            }, $args);

            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', array_merge([$message], $args));
        }

        $output = trigger_error($message, E_USER_WARNING);
        restore_error_handler();
        return $output;
    }
}
