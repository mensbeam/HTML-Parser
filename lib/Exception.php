<?php
declare(strict_types=1);
namespace dW\HTML5;

class Exception extends \Exception {
    const INVALID_CODE = 10000;
    const UNKNOWN_ERROR = 10001;
    const INCORRECT_PARAMETERS_FOR_MESSAGE = 10002;

    const PARSER_DOMDOCUMENT_EXPECTED = 10101;
    const PARSER_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED = 10102;
    const PARSER_DOMNODE_EXPECTED = 10103;

    const STACK_INVALID_INDEX = 10201;
    const STACK_DOMNODE_ONLY = 10202;
    const STACK_FRAGMENT_CONTEXT_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED = 10203;

    const ACTIVE_FORMATTING_ELEMENT_LIST_INVALID_INDEX = 10301;

    const DATASTREAM_NODATA = 10401;
    const DATASTREAM_INVALID_DATA_CONSUMPTION_LENGTH = 10402;

    const DOM_DOMELEMENT_STRING_OR_CLOSURE_EXPECTED = 10501;

    const TOKENIZER_INVALID_STATE = 10601;

    const TREEBUILDER_FORMELEMENT_EXPECTED = 10701;
    const TREEBUILDER_FRAGMENT_CONTEXT_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED = 10702;

    protected static $messages = [10000 => 'Invalid error code',
                                  10001 => 'Unknown error; escaping',
                                  10002 => 'Incorrect number of parameters for Exception message; %s expected',

                                  10101 => 'DOMDocument expected; found %s',
                                  10102 => 'DOMElement, DOMDocument, or DOMDocumentFragment expected; found %s',
                                  10103 => 'DOMNode expected; found %s',

                                  10201 => '%s is an invalid Stack index',
                                  10202 => 'Instances of DOMNode are the only types allowed in a Stack',
                                  10203 => 'DOMElement, DOMDocument, or DOMDocumentFragment expected for fragment context; found %s',

                                  10301 => '%s is an invalid ActiveFormattingElementsList index',

                                  10401 => 'Data string expected; found %s',
                                  10402 => '%s is an invalid data consumption length; a value of 1 or above is expected',

                                  10501 => 'The first argument must either be an instance of \DOMElement, a string, or a closure; found %s',

                                  10601 => 'The Tokenizer has entered an invalid state',

                                  10701 => 'Form element expected, found %s',
                                  10702 => 'DOMElement, DOMDocument, or DOMDocumentFragment expected; found %s'];

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
        $count = substr_count($message, '%s');
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