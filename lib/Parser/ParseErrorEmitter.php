<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

trait ParseErrorEmitter {
    /** @var ParseError $errorHandler */
    private $errorHandler;

    private function error(int $code, ...$arg): void {
        if ($this->errorHandler) {
            $data = ($this instanceof Data) ? $this : ($this->data ?? null);
            assert($data instanceof Data);
            assert($this->errorHandler instanceof ParseError);
            $chars = "";
            $offset = 0;
            $skip = [];
            if (in_array($code, [ParseError::UNEXPECTED_CHAR, ParseError::FOSTERED_CHAR])) {
                // character-related errors must have an error generated for each character
                assert(
                    (sizeof($arg) === 1 && is_string($arg[0]))
                    || (sizeof($arg) === 2 && is_array($arg[0]) && is_int($arg[1]))
                    || (sizeof($arg) === 2 && is_string($arg[0]) && $arg[1] === "exclude whitespace")
                );
                if (is_array($arg[0])) {
                    // pended characters come as a sequence of character tokens with an offset back into the data stream
                    $offset = $data->pointer - $arg[1];
                    $chars = "";
                    foreach ($arg[0] as $t) {
                        $chars .= $t->data;
                    }
                    $chars = sizeof(preg_split("//u", $chars)) - 3;
                } else {
                    $chars = preg_split("//u", $arg[0]);
                    // remove the two empty entries around the characters
                    array_shift($chars);
                    array_pop($chars);
                    // if we need to exclude whitespace, iterate through the array with keys in reverse and mark character offsets to be skipped
                    if (($arg[1] ?? "") === "exclude whitespace") {
                        foreach (array_reverse(array_reverse($chars), true) as $k => $v) {
                            if (strpos(Data::WHITESPACE, $v) !== false) {
                                $skip[] = $k;
                            }
                        }
                    }
                    $chars = sizeof($chars) - 1;
                }
                while ($chars >= 0) {
                    if (in_array($chars, $skip)) {
                        // skip the character if appropriate
                        $chars--;
                        continue;
                    }
                    list($line, $column) = $data->whereIs(-(($chars--) + $offset));
                    $message = $this->errorMessage($code);
                    $this->errorHandler->errors[] = [$line, $column, $code, [], $message];
                }
            } else {
                list($line, $column) = $data->whereIs(ParseError::REPORT_OFFSETS[$code] ?? 0);
                $message = $this->errorMessage($code, ...$arg);
                $this->errorHandler->errors[] = [$line, $column, $code, $arg, $message];
            }
        }
    }

    protected function errorMessage(int $code, ...$arg): string {
        assert(isset(ParseError::MESSAGES[$code]), new Exception(Exception::INVALID_CODE, $code));

        $message = ParseError::MESSAGES[$code];
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
                } elseif ($value === null) {
                    return 'nothing';
                } else {
                    return $value;
                }
            }, $arg);

            // Go through each of the arguments and run sprintf on the strings.
            $message = sprintf($message, ...$arg);
        }
        return $message;
    }
}
