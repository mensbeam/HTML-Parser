<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

use MensBeam\Intl\Encoding\UTF8;

trait NameCoercion {

    protected function coerceName(string $name): string {
        // This matches the inverse of the production of NameChar in XML 1.0,
        //   with the added exclusion of ":" from allowed characters
        // See https://www.w3.org/TR/REC-xml/#NT-NameStartChar
        preg_match_all('/[^\-\.0-9\x{B7}\x{300}-\x{36F}\x{203F}-\x{2040}A-Za-z_\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]/u', $name, $m);
        foreach (array_unique($m[0], \SORT_STRING) as $c) {
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = str_replace($c, $esc, $name);
        }
        // Apply stricter rules to the first character
        if (preg_match('/^[^A-Za-z_\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]/u', $name, $m)) {
            $c = $m[0];
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = $esc.substr($name, strlen($c));
        }
        return $name;
    }

    protected function uncoerceName(string $name): string {
        preg_match_all('/U[0-9A-F]{6}/', $name, $m);
        foreach (array_unique($m[0], \SORT_STRING) as $o) {
            $c = UTF8::encode(hexdec(substr($o, 1)));
            $name = str_replace($o, $c, $name);
        }
        return $name;
    }

    protected function escapeString(string $string, bool $attribute = false): string {
        # Escaping a string (for the purposes of the algorithm above) consists of
        # running the following steps:

        # 1. Replace any occurrence of the "&" character by the string "&amp;".
        # 2. Replace any occurrences of the U+00A0 NO-BREAK SPACE character by the
        # string "&nbsp;".
        $string = str_replace(['&', "\u{A0}"], ['&amp;', '&nbsp;'], $string);
        # 3. If the algorithm was invoked in the attribute mode, replace any
        # occurrences of the """ character by the string "&quot;".
        # 4. If the algorithm was not invoked in the attribute mode, replace any
        # occurrences of the "<" character by the string "&lt;", and any
        # occurrences of the ">" character by the string "&gt;".
        return ($attribute) ? str_replace('"', '&quot;', $string) : str_replace(['<', '>'], ['&lt;', '&gt;'], $string);
    }
}
