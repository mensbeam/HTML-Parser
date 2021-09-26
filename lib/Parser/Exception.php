<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Exception extends \Exception {
    const INVALID_CODE = 100;
    const UNKNOWN_ERROR = 101;
    const INCORRECT_PARAMETERS_FOR_MESSAGE = 102;
    const UNREACHABLE_CODE = 103;

    const PARSER_NONEMPTY_DOCUMENT = 201;
    const INVALID_QUIRKS_MODE = 202;

    const STACK_INVALID_INDEX = 301;
    const STACK_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED = 302;
    const STACK_ELEMENT_STRING_ARRAY_EXPECTED = 303;
    const STACK_STRING_ARRAY_EXPECTED = 304;
    const STACK_INCORRECTLY_EMPTY = 305;
    const STACK_INVALID_STATE = 306;
    const STACK_NO_CONTEXT_EXISTS = 307;
    const STACK_INVALID_VALUE = 308;
    const STACK_INVALID_OFFSET = 309;
    const STACK_ROOT_ELEMENT_DELETE = 310;

    const DATA_NODATA = 401;
    const DATA_INVALID_DATA_CONSUMPTION_LENGTH = 402;

    const TOKENIZER_INVALID_STATE = 501;
    const TOKENIZER_INVALID_CHARACTER_REFERENCE_STATE = 502;

    const TREEBUILDER_FORMELEMENT_EXPECTED = 601;
    const TREEBUILDER_DOCUMENTFRAG_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED = 602;
    const TREEBUILDER_UNEXPECTED_END_OF_FILE = 603;
    const TREEBUILDER_NON_EMPTY_TARGET_DOCUMENT = 604;
    const TREEBUILDER_INVALID_TOKEN_CLASS = 605;
    const TREEBUILDER_INVALID_INSERTION_LOCATION = 606;

    protected static $messages = [
        100 => 'Invalid error code',
        101 => 'Unknown error; escaping',
        102 => 'Incorrect number of parameters for Exception message; %s expected',
        103 => 'Unreachable code',

        201 => 'Non-empty Document supplied as argument for Parser',
        202 => 'Fragment\'s quirks mode must be one of Parser::NO_QUIRKS_MODE, Parser::LIMITED_QUIRKS_MODE, or Parser::QUIRKS_MODE',

        301 => 'Invalid Stack index at %s',
        302 => 'Element, Document, or DOMDocumentFragment expected for fragment context',
        303 => 'Element, string, or array expected',
        304 => 'String or array expected',
        305 => 'Stack is incorrectly empty',
        306 => 'Stack is in an invalid state; dump: %s',
        307 => 'No %s context exists in stack',
        308 => 'Stack value is invalid',
        309 => 'Invalid stack offset; offset must be %s',
        310 => 'Root element cannot be deleted from the stack',

        401 => 'Data string expected; found %s',
        402 => '%s is an invalid data consumption length; a value of 1 or above is expected',

        501 => 'The Tokenizer has entered an invalid state: %s',
        502 => 'Invalid character reference consumption state: %s',

        601 => 'Form element expected, found %s',
        602 => 'Element, Document, or DOMDocumentFragment expected; found %s',
        603 => 'Unexpected end of file',
        604 => 'Target document is not empty',
        605 => 'Invalid token class: %s',
        606 => 'Invalid insertion location'
    ];

    public function __construct(int $code, ...$args) {
        if (!isset(self::$messages[$code])) {
            throw new self(self::INVALID_CODE);
        }

        $message = self::$messages[$code];
        $previous = null;

        if ($args) {
            // Grab a previous exception if there is one.
            if ($args[0] instanceof \Throwable) {
                $previous = array_shift($args);
            } elseif (end($args) instanceof \Throwable) {
                $previous = array_pop($args);
            }
        }

        // Count the number of replacements needed in the message.
        preg_match_all('/(\%(?:\d+\$)?s)/', $message, $matches);
        $count = count(array_unique($matches[1]));

        // If the number of replacements don't match the arguments then oops.
        if (count($args) !== $count) {
            throw new self(self::INCORRECT_PARAMETERS_FOR_MESSAGE, $count);
        }

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', array_merge([$message], $args));
        }

        parent::__construct($message, $code, $previous);
    }
}
