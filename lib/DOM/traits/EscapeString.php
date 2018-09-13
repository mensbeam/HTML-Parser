<?php
declare(strict_types=1);
namespace dW\HTML5;

trait EscapeString {
    protected function escapeString(string $string, bool $attribute = false): string {
        # Escaping a string (for the purposes of the algorithm above) consists of
        # running the following steps:

        # 1. Replace any occurrence of the "&amp;" character by the string "&amp;amp;".
        # 2. Replace any occurrences of the U+00A0 NO-BREAK SPACE character by the
        # string "&amp;nbsp;".
        $string = str_replace(['&amp;', chr(0x00A0)], ['&amp;amp;', '&amp;nbsp;'], $string);
        # 3. If the algorithm was invoked in the attribute mode, replace any
        # occurrences of the "&quot;" character by the string "&amp;quot;".
        # 4. If the algorithm was not invoked in the attribute mode, replace any
        # occurrences of the "&lt;" character by the string "&amp;lt;", and any
        # occurrences of the "&gt;" character by the string "&amp;gt;".
        if ($attribute) {
            $string = str_replace(['&quot;', '&lt;', '&gt;'], ['&amp;quot;', '&amp;lt;', '&amp;gt;'], $string);
        }

        return $string;
    }
}