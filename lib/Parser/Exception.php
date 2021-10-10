<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class Exception extends \Exception {
    const PARSER_NONEMPTY_DOCUMENT = 201;
    const INVALID_QUIRKS_MODE = 202;

    protected static $messages = [
        201 => 'Non-empty Document supplied as argument for Parser',
        202 => 'Fragment\'s quirks mode must be one of Parser::NO_QUIRKS_MODE, Parser::LIMITED_QUIRKS_MODE, or Parser::QUIRKS_MODE',
    ];

    public function __construct(int $code, array $args = [], \Throwable $previous = null) {
        assert(isset(self::$messages[$code]), new \Exception("Exception code $code not defined"));

        $message = self::$messages[$code];

        // Count the number of replacements needed in the message.
        preg_match_all('/(\%(?:\d+\$)?s)/', $message, $matches);
        $count = count(array_unique($matches[1]));
        assert(count($args) !== $count, new \Exception("Exception message expects $count arguments"));

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', array_merge([$message], $args));
        }

        parent::__construct($message, $code, $previous);
    }
}
