<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class Exception extends \Exception {
    public const INVALID_QUIRKS_MODE = 101;
    public const FAILED_CREATING_DOCUMENT = 102;
    public const INVALID_DOCUMENT_CLASS = 103;

    protected static $messages = [
        101 => 'Fragment\'s quirks mode must be one of Parser::NO_QUIRKS_MODE, Parser::LIMITED_QUIRKS_MODE, or Parser::QUIRKS_MODE',
        102 => 'Unable to create instance of configured document class "%s"',
        103 => 'Configured document class "%s" must be a subclass of \DOMDocument',
    ];

    public function __construct(int $code, array $args = [], \Throwable $previous = null) {
        assert(isset(self::$messages[$code]), new \Exception("Exception code $code not defined"));

        $message = self::$messages[$code];

        // Count the number of replacements needed in the message.
        preg_match_all('/(\%(?:\d+\$)?s)/', $message, $matches);
        $count = count(array_unique($matches[1]));
        assert(count($args) === $count, new \Exception("Exception message expects $count arguments; got ".var_export($args, true)));

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = sprintf($message, ...$args);
        }

        parent::__construct($message, $code, $previous);
    }
}
