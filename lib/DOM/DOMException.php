<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class DOMException extends \Exception {
    // From PHP's DOMException; keeping error codes consistent
    const HIERARCHY_REQUEST_ERROR = 3;
    const WRONG_DOCUMENT = 4;
    const INVALID_CHARACTER = 5;
    const NO_MODIFICATION_ALLOWED = 7;
    const SYNTAX_ERROR = 12;

    const DOCUMENT_ELEMENT_DOCUMENTFRAG_EXPECTED = 100;
    const STRING_EXPECTED = 101;
    const OUTER_HTML_FAILED_NOPARENT = 102;

    protected static $messages = [
          3 => 'Hierarchy request error; supplied node is not allowed here',
          4 => 'Supplied node does not belong to this document',
          5 => 'Invalid character',
          7 => 'Modification not allowed here',
         12 => 'Syntax error',
        100 => 'Document, Element, or DocumentFragment expected; found %s',
        101 => 'The "%s" argument should be a string; found %s',
        102 => 'Failed to set the "outerHTML" property; the element does not have a parent node'
    ];

    public function __construct(int $code, ...$args) {
        if (!isset(self::$messages[$code])) {
            throw new Exception(Exception::INVALID_CODE);
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
        $count = count($matches[1]);

        // If the number of replacements don't match the arguments then oops.
        if (count($args) !== $count) {
            throw new Exception(Exception::INCORRECT_PARAMETERS_FOR_MESSAGE, $count);
        }

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', array_merge([$message], $args));
        }

        parent::__construct($message, $code, $previous);
    }
}
