<?php
declare(strict_types=1);
namespace dW\HTML5;

class Exception extends \Exception {
    const INVALID_CODE = 10000;
    const UNKNOWN_ERROR = 10001;
    const INCORRECT_PARAMETERS_FOR_MESSAGE = 10002;

    const PARSER_NONEMPTY_DOCUMENT = 10101;

    const STACK_INVALID_INDEX = 10201;
    const STACK_DOCUMENTFRAG_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED = 10202;

    const DATA_NODATA = 10301;
    const DATA_INVALID_DATA_CONSUMPTION_LENGTH = 10302;

    const DOM_DOMNODE_STRING_OR_CLOSURE_EXPECTED = 10401;
    const DOM_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED = 10402;

    const TOKENIZER_INVALID_STATE = 10501;

    const TREEBUILDER_FORMELEMENT_EXPECTED = 10601;
    const TREEBUILDER_DOCUMENTFRAG_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED = 10602;
    const TREEBUILDER_UNEXPECTED_END_OF_FILE = 10603;

    const DOM_DISABLED_METHOD = 10701;

    protected static $messages = [10000 => 'Invalid error code',
                                  10001 => 'Unknown error; escaping',
                                  10002 => 'Incorrect number of parameters for Exception message; %s expected',

                                  10101 => 'Non-empty Document supplied as argument for Parser',

                                  10201 => '%s is an invalid Stack index',
                                  10202 => 'Element, Document, or DOMDocumentFragment expected for fragment context; found %s',

                                  10301 => 'Data string expected; found %s',
                                  10302 => '%s is an invalid data consumption length; a value of 1 or above is expected',

                                  10401 => 'The first argument must either be an instance of \DOMNode, a string, or a closure; found %s',
                                  10402 => 'Element, Document, or DOMDocumentFragment expected; found %s',

                                  10501 => 'The Tokenizer has entered an invalid state',

                                  10601 => 'Form element expected, found %s',
                                  10602 => 'Element, Document, or DOMDocumentFragment expected; found %s',
                                  10603 => 'Unexpected end of file',

                                  10701 => 'Method %1$s::%2$s has been disabled from %1$s'];

    public function __construct(int $code, ...$args) {
        if (!isset(static::$messages[$code])) {
            throw new Exception(self::INVALID_CODE);
        }

        $message = static::$messages[$code];
        $previous = null;

        // Grab a previous exception if there is one.
        if ($args[0] instanceof \Throwable) {
            $previous = array_shift($args);
        } elseif (end($args) instanceof \Throwable) {
            $previous = array_pop($args);
        }

        // Count the number of replacements needed in the message.
        preg_match_all('/(\%(?:\d+\$)?s)/', $message, $matches);
        $count = count(array_unique($matches[1]));

        // If the number of replacements don't match the arguments then oops.
        if (count($args) !== $count) {
            throw new Exception(self::INCORRECT_PARAMETERS_FOR_MESSAGE, $count);
        }

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', array_merge([$message], $args));
        }

        parent::__construct($message, $code, $previous);
    }
}