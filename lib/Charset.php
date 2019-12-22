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
            return $encoding['name'];
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
                    return $encoding['name'];
                } else {
                    return null;
                }
            }
        }
        return null;
    }

    public static function fromPrescan(string $data, int $endAfter = 1024): ?string {
        # When an algorithm requires a user agent to prescan a byte stream to 
        #   determine its encoding, given some defined end condition, then it 
        #   must run the following steps. 
        # These steps either abort unsuccessfully or return a character 
        #   encoding. If at any point during these steps (including during 
        #   instances of the get an attribute algorithm invoked by this one) 
        #   the user agent either runs out of bytes (meaning the position 
        #   pointer created in the first step below goes beyond the end of the 
        #   byte stream obtained so far) or reaches its end condition, then 
        #   abort the prescan a byte stream to determine its encoding 
        #   algorithm unsuccessfully.
        $s = substr($data, 0, $endAfter);

        # Let position be a pointer to a byte in the input byte stream, 
        #   initially pointing at the first byte.
        $pos = 0;
        
        # Loop: If position points to:
        while ($pos < $endAfter) {
            // OPTIMIZATION: Start my skipping anything not a less-than sign
            if (@$s[$pos] === "<") {
                $pos++;
                
                # A sequence of bytes starting with: 0x3C 0x21 0x2D 0x2D (`<!--`)
                if (@$s[$pos] === "!" && @$s[$pos + 1] === "-" && @$s[$pos + 2] === "-") {
                    # Advance the position pointer so that it points at the 
                    #   first 0x3E byte which is preceded by two 0x2D bytes 
                    #   (i.e. at the end of an ASCII '-->' sequence) and 
                    #   comes after the 0x3C byte that was found.e (The two 
                    #   0x2D bytes can be the same as those in the '<!--' 
                    #   sequence.)
                    $pos = (strpos($s, "-->", $pos) ?: $endAfter) + 3;
                }
                # A sequence of bytes starting with: 0x3C, 0x4D or 0x6D, 
                #   0x45 or 0x65, 0x54 or 0x74, 0x41 or 0x61, and one of 
                #   0x09, 0x0A, 0x0C, 0x0D, 0x20, 0x2F (case-insensitive 
                #   ASCII '<meta' followed by a space or slash)
                elseif (preg_match("<^meta[\x09\x0A\x0C\x0D /]$>i", substr($s, $pos, 5))) {
                    # Advance the position pointer so that it points at 
                    #   the next 0x09, 0x0A, 0x0C, 0x0D, 0x20, or 0x2F 
                    #   byte (the one in sequence of characters matched above).
                    $pos += 5;
                    # Let attribute list be an empty list of strings.
                    # Let got pragma be false.
                    # Let need pragma be null.
                    # Let charset be the null value (which, for the purposes 
                    #   of this algorithm, is distinct from an unrecognized 
                    #   encoding or the empty string).
                    $attrList = [];
                    $gotPragma = false;
                    $needPragma = null;
                    $charset = null;

                    # Attributes: Get an attribute and its value. 
                    # If no attribute was sniffed, then jump to the processing step below.
                    while ($attr = self::getAttribute($s, $pos)) {
                        # If the attribute's name is already in attribute list, 
                        #   then return to the step labeled attributes.
                        if (isset($attrList[$attr['name']])) {
                            continue;
                        }
                        # Add the attribute's name to attribute list.
                        $attrList[$attr['name']] = true;
                        # Run the appropriate step from the following list, if one applies:

                        # If the attribute's name is "http-equiv"
                        if ($attr['name'] === "http-equiv") {
                            # If the attribute's value is "content-type", then set got pragma to true.
                            if ($attr['value'] === "content-type") {
                                $gotPragma = true;
                            }
                        }
                        # If the attribute's name is "content"
                        elseif ($attr['name'] === "content") {
                            # Apply the algorithm for extracting a character encoding from a meta 
                            #   element, giving the attribute's value as the string to parse. 
                            # If a character encoding is returned, and if charset is still set to 
                            #   null, let charset be the encoding returned, and set need pragma to true.
                            
                            // OPTIMIZATION: Check if charset is null before performing the algorithm
                            if (is_null($charset) && $candidate = self::fromMeta($attr['value'])) {
                                $charset = $candidate;
                                $needPragma = true;
                            }
                        }
                        # If the attribute's name is "charset"
                        elseif ($attr['name'] === "charset") {
                            # Let charset be the result of getting an encoding from the attribute's 
                            #   value, and set need pragma to false.
                            $candidate = self::fromCharset($attr['value']);
                            $charset = $candidate ?? false; // false signifies 'failure'
                            $needPragma = false;
                        }
                    }

                    # Processing: If need pragma is null, then jump to the step below labeled next byte.
                    # If need pragma is true but got pragma is false, then jump to the step below labeled next byte.
                    if (is_null($needPragma) || ($needPragma && !$gotPragma)) {
                        continue;
                    }
                    # If charset is failure, then jump to the step below labeled next byte.
                    if ($charset === false) {
                        $pos++;
                        continue;
                    }
                    # If charset is a UTF-16 encoding, then set charset to UTF-8.
                    elseif ($charset === "UTF-16") {
                        $charset = "UTF-8";
                    }
                    # If charset is x-user-defined, then set charset to windows-1252.
                    elseif ($charset === "x-user-defined") {
                        $charset = "windows-1252";
                    }
                    # Abort the prescan a byte stream to determine its encoding algorithm,
                    #   returning the encoding given by charset.
                    return $charset;
                }
                # A sequence of bytes starting with a 0x3C byte (<), optionally a 0x2F byte (/), 
                #   and finally a byte in the range 0x41-0x5A or 0x61-0x7A (A-Z or a-z)
                elseif (($s[$pos] === "/" && ctype_alpha($s[$pos + 1])) || (ctype_alpha($s[$pos]))) {
                    # Advance the position pointer so that it points at the next 
                    #   0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), 0x20 (SP), or 0x3E (>) byte.
                    while (!in_array(@$s[$pos++], ["\x09", "\x0A", "\x0C", "\x0D", " ", ">", ""]));
                    # Repeatedly get an attribute until no further attributes can be found, 
                    #   then jump to the step below labeled next byte.
                    while(self::getAttribute($s, $pos));
                }
                # A sequence of bytes starting with: 0x3C 0x21 (`<!`)
                # A sequence of bytes starting with: 0x3C 0x2F (`</`)
                # A sequence of bytes starting with: 0x3C 0x3F (`<?`)
                elseif (in_array(@$s[$pos], ["!", "/", "?"])) {
                    # Advance the position pointer so that it points at the first 
                    #   0x3E byte (>) that comes after the 0x3C byte that was found.
                    $pos = (strpos($s, ">", $pos) ?: $endAfter) + 1;
                }
            }
            # Any other byte
            else {
                # Do nothing with that byte.
                $pos++;
            }
        }
    }

    protected static function getAttribute(string $s, &$pos): array {
        # When the prescan a byte stream to determine its encoding 
        #   algorithm says to get an attribute, it means doing this:

        # If the byte at position is one of 
        #   0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), 0x20 (SP), 
        #   or 0x2F (/) then advance position to the next byte and 
        #   redo this step.
        while (in_array(@$s[$pos], ["\x09", "\x0A", "\x0C", "\x0D", " ", "/"])) {
            $pos++;
        }
        $char = @$s[$pos];
        
        # If the byte at position is 0x3E (>), 
        #   then abort the get an attribute algorithm. There isn't one.
        if ($char === ">") {
            return [];
        }
        # Otherwise, the byte at position is the start of the attribute name.
        #  Let attribute name and attribute value be the empty string.
        $name = "";
        $value = "";
        
        # Process the byte at position as follows:
        while ($char !== "") {
            # If it is 0x3D (=), and the attribute name is longer than the empty string
            if ($char === "=" && $name !== "") {
                # Advance position to the next byte and jump to the step below labeled value.
                $pos++;
                goto value;
            }
            # If it is 0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), or 0x20 (SP)
            elseif (in_array($char, ["\x09", "\x0A", "\x0C", "\x0D", " "])) {
                goto spaces;
            }
            # If it is 0x2F (/) or 0x3E (>)
            elseif ($char === "/" || $char === ">") {
                # Abort the get an attribute algorithm.
                # The attribute's name is the value of attribute name, its value is the empty string.
                return ['name' => $name, 'value' => $value];
            }
            # If it is in the range 0x41 (A) to 0x5A (Z)
            # Anything else
            else {
                # Append the code point with the same value as the byte at position to attribute name.
                # (It doesn't actually matter how bytes outside the ASCII range are handled here,
                #    since only ASCII bytes can contribute to the detection of a character encoding.)
 
                // OPTIMIZATION: Also handle uppercase characters
                $name .= strtolower($char);
            }

            # Advance position to the next byte and return to the previous step.
            $char = @$s[++$pos];
        }
        
        if ($char === "") {
            // Out of bytes
            return [];
        }
        
        spaces:
        #  If the byte at position is one of 0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), 
        #   or 0x20 (SP) then advance position to the next byte, then, repeat this step.
        while (in_array(@$s[$pos], ["\x09", "\x0A", "\x0C", "\x0D", " ", "/"])) {
            $pos++;
        }
        $char = @$s[$pos];
        if ($char === "") {
            // Out of bytes
            return [];
        }
        # If the byte at position is not 0x3D (=), abort the get an attribute algorithm.
        # The attribute's name is the value of attribute name, its value is the empty string.
        if ($char !== "=") {
            return ['name' => $name, 'value' => $value];
        }
        # Advance position past the 0x3D (=) byte.
        $char = @$s[++$pos];

        value:
        # If the byte at position is one of 0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), 
        #   or 0x20 (SP) then advance position to the next byte, then, repeat this step.
        while (in_array(@$s[$pos], ["\x09", "\x0A", "\x0C", "\x0D", " ", "/"])) {
            $pos++;
        }
        $char = @$s[$pos];
        if ($char === "") {
            // Out of bytes
            return [];
        }
        # Process the byte at position as follows:
        # If it is 0x22 (") or 0x27 (')
        if ($char === "'" || $char === '"') {
            # Let b be the value of the byte at position.
            $b = $char;
            # Quote loop: Advance position to the next byte.
            while (($char = @$s[++$pos]) !== "") {
                # If the value of the byte at position is the value of b, 
                #   then advance position to the next byte and abort 
                #   the "get an attribute" algorithm. 
                # The attribute's name is the value of attribute name, 
                #   and its value is the value of attribute value.
                if ($char === $b) {
                    $pos++;
                    return ['name' => $name, 'value' => $value];
                }
                # Otherwise, append a code point to attribute value whose 
                #   value is the same as the value of the byte at position.

                // OPTIMIZATION: Also handle uppercase characters
                $value .= strtolower($char);
            }
            // Out of bytes
            return [];
        }
        # If it is 0x3E (>)
        elseif ($char === ">") {
            # Abort the get an attribute algorithm.
            # The attribute's name is the value of attribute name, 
            #   its value is the empty string.
            return ['name' => $name, 'value' => $value];
        }
        # Anything else
        else {
            # Append a code point with the same value as the byte at position to attribute value.
            # Advance position to the next byte.

            // OPTIMIZATION: Also handle uppercase characters
            $value .= strtolower($char);
            
            while (($char = @$s[++$pos]) !== "") {
                # Process the byte at position as follows:
                # If it is 0x09 (HT), 0x0A (LF), 0x0C (FF), 0x0D (CR), 0x20 (SP), or 0x3E (>)
                if (in_array($char, ["\x09", "\x0A", "\x0C", "\x0D", " ", ">"])) {
                    # Abort the get an attribute algorithm.
                    # The attribute's name is the value of attribute name 
                    #   and its value is the value of attribute value.
                    return ['name' => $name, 'value' => $value];
                }
                # If it is in the range 0x41 (A) to 0x5A (Z)
                # Anything else
                else {
                    # Append a code point with the same value as 
                    #   the byte at position to attribute value.
                    $value .= strtolower($char);
                }
            }
            // Out of bytes
            return [];
        }
    }

    protected static function fromMeta(string $s): ?string {
        # The algorithm for extracting a character encoding from a meta element, 
        #   given a string s, is as follows.
        # It either returns a character encoding or nothing.

        # Let position be a pointer into s, initially pointing at the start of the string.
        $pos = 0;
        $end = strlen($s);

        # Loop:
        while ($pos < $end) {
            # Find the first seven characters in s after position 
            #   that are an ASCII case-insensitive match for the word "charset".
            # If no such match is found, return nothing.
            $found = stripos($s, "charset", $pos);
            if ($found === false) {
                return null;
            }
            $pos = $found + 7;
            # Skip any ASCII whitespace that immediately follow the word "charset" 
            #   (there might not be any).
            while (in_array(@$s[$pos], ["\x09", "\x0A", "\x0C", "\x0D", " "])) {
                $pos++;
            }
            # If the next character is not a U+003D EQUALS SIGN (=), 
            #   then move position to point just before that next 
            #   character, and jump back to the step labeled loop.
            if (@$s[$pos] !== "=") {
                continue;
            }
            # Skip any ASCII whitespace that immediately follow the equals sign 
            #   (there might not be any).
            while (in_array(@$s[++$pos], ["\x09", "\x0A", "\x0C", "\x0D", " "]));

            # Process the next character as follows:
            $char = @$s[$pos];

            # If it is a U+0022 QUOTATION MARK character (")...
            # If it is a U+0027 APOSTROPHE character (')...
            if ($char === '"' || $char === "'") {
                # ... and there is a later U+0022 QUOTATION MARK character (") in s
                # ... and there is a later U+0027 APOSTROPHE character (') in s
                if (($end = strpos($s, $char, $pos + 1)) !== false) {
                    $pos++;
                    return self::fromCharset(substr($s, $pos, $end - $pos));
                }
                # If it is an unmatched U+0022 QUOTATION MARK character (")
                # If it is an unmatched U+0027 APOSTROPHE character (')
                else {
                    # Return nothing
                    return null;
                }
            }
            # There is no next character
            elseif ($char === "") {
                # Return nothing
                return null;
            }
            # Anything else
            else {
                # Return the result of getting an encoding from the substring 
                #   that consists of this character up to but not including 
                #   the first ASCII whitespace or U+003B SEMICOLON (;) 
                #   character, or the end of s, whichever comes first.
                $size = -1;
                while (!in_array(@$s[$pos + (++$size)], ["\x09", "\x0A", "\x0C", "\x0D", " ", ";", ""]));
                return self::fromCharset(substr($s, $pos, $size));
            }
        }
    }
}
