<?php
declare(strict_types=1);
namespace dW\HTML5;

use MensBeam\Intl\Encoding;

abstract class Charset {
    /** Matches an encoding label (e.g. "utf-8") to a compatible decoder class.
     * 
     * @param string $value The encoding label to match
     */
    public static function fromCharset(string $value): ?string {
        $encoding = Encoding::matchLabel($value);
        if ($encoding) {
            return $encoding['class'];
        }
        return null;
    }

    /** Extracts an encoding from an HTTP Content-Type header-field
     * and returns the class name of a compatible decoder.
     * 
     * @param string $contentType The value of a Content-Type header-field
     */
    public static function fromTransport(string $contentType): ?string {
        // Try to sniff out a charset from a Content-Type header-field.
        // This does cut some corners, but should be sufficient for practical use
        $s = preg_replace("/\s+/", " ", strtolower($contentType));
        $pos = 0;
        $end = strlen($s);
        // skip the type
        while ($pos < $end && @$s[$pos++] !== "/");
        // skip the subtype
        while ($pos < $end && @$s[$pos++] !== ";");
        // check parameters in sequence
        while ($pos < $end) {
            // skip any leading whitespace
            if (@$s[$pos] === " ") {
                $pos++;
            }
            // collect characters for the parameter name
            $param = "";
            while ($pos < $end && @$s[$pos] !== "=") {
                $param .= @$s[$pos++];
            }
            // skip the equals sign
            $pos++;
            if ($s[$pos] === '"') {
                // Value is a quoted-string
                $pos++;
                $value = "";
                while (!in_array($c = @$s[$pos++], ['"', ""])) {
                    if ($c === "\\") {
                        $value .= @$s[$pos++];
                    } else {
                        $value .= $c;
                    }
                }
                // only interpret the value if a closing quotation mark was seen
                if ($c !== '"') {
                    $value = "";
                }
            } else {
                // Value is a bare token
                $value = "";
                while (!in_array($c = @$s[$pos++], [';', " ", ""])) {
                    $value .= $c;
                }
            }
            // if the parameter was the character set, interpret its value and return
            if ($param === "charset") {
                $encoding = Encoding::matchLabel($value);
                if ($encoding) {
                    return $encoding['class'];
                } else {
                    return null;
                }
            }
        }
        return null;
    }

    public static function fromPrescan(string $data): ?string {
        return null;
    }
}
