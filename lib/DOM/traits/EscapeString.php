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

    protected function CoerceName(string $name): string {
        // This matches the inverse of the production of NameChar in XML 1.0,
        //   with the added exclusion of ":" from allowed characters
        // See https://www.w3.org/TR/REC-xml/#NT-NameStartChar
        preg_match_all('/[^\-\.0-9\x{B7}\x{300}-\x{36F}\x{203F}-\x{2040}A-Za-z_\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]/u', $name, $m, \PREG_OFFSET_CAPTURE);
        var_export($m);
        exit;
    }
}
