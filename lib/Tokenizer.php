<?php
declare(strict_types=1);
namespace dW\HTML5;

class Tokenizer {
    public $state;

    protected $data;
    protected $stack;

    const DATA_STATE = 0;
    const RCDATA_STATE = 1;
    const RAWTEXT_STATE = 2;
    const SCRIPT_DATA_STATE = 3;
    const PLAINTEXT_STATE = 4;
    const TAG_OPEN_STATE = 5;
    const END_TAG_OPEN_STATE = 6;
    const TAG_NAME_STATE = 7;
    const RCDATA_LESS_THAN_SIGN_STATE = 8;
    const RCDATA_END_TAG_OPEN_STATE = 9;
    const RCDATA_END_TAG_NAME_STATE = 10;
    const RAWTEXT_LESS_THAN_SIGN_STATE = 11;
    const RAWTEXT_END_TAG_OPEN_STATE = 12;
    const RAWTEXT_END_TAG_NAME_STATE = 13;
    const SCRIPT_DATA_LESS_THAN_SIGN_STATE = 14;
    const SCRIPT_DATA_END_TAG_OPEN_STATE = 15;
    const SCRIPT_DATA_END_TAG_NAME_STATE = 16;
    const SCRIPT_DATA_ESCAPE_START_STATE = 17;
    const SCRIPT_DATA_ESCAPE_START_DASH_STATE = 18;
    const SCRIPT_DATA_ESCAPED_STATE = 19;
    const SCRIPT_DATA_ESCAPED_DASH_STATE = 20;
    const SCRIPT_DATA_ESCAPED_DASH_DASH_STATE = 21;
    const SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE = 22;
    const SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE = 23;
    const SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE = 24;
    const SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE = 25;
    const SCRIPT_DATA_DOUBLE_ESCAPED_STATE = 26;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE = 27;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE = 28;
    const SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE = 29;
    const SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE = 30;
    const BEFORE_ATTRIBUTE_NAME_STATE = 31;
    const ATTRIBUTE_NAME_STATE = 32;
    const AFTER_ATTRIBUTE_NAME_STATE = 33;
    const BEFORE_ATTRIBUTE_VALUE_STATE = 34;
    const ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE = 35;
    const ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE = 36;
    const ATTRIBUTE_VALUE_UNQUOTED_STATE = 37;
    const AFTER_ATTRIBUTE_VALUE_QUOTED_STATE = 38;
    const SELF_CLOSING_START_TAG_STATE = 39;
    const BOGUS_COMMENT_STATE = 40;
    const MARKUP_DECLARATION_OPEN_STATE = 41;
    const COMMENT_START_STATE = 42;
    const COMMENT_START_DASH_STATE = 43;
    const COMMENT_STATE = 44;
    const COMMENT_END_DASH_STATE = 45;
    const COMMENT_END_STATE = 46;
    const COMMENT_END_BANG_STATE = 47;
    const DOCTYPE_STATE = 48;
    const BEFORE_DOCTYPE_NAME_STATE = 49;
    const DOCTYPE_NAME_STATE = 50;
    const AFTER_DOCTYPE_NAME_STATE = 51;
    const AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE = 52;
    const BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 53;
    const DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE = 54;
    const DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE = 55;
    const AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 56;
    const BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE = 57;
    const AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE = 58;
    const BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 59;
    const DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE = 60;
    const DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE = 61;
    const AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 62;
    const BOGUS_DOCTYPE_STATE = 63;
    const CDATA_SECTION_STATE = 64;

    // Ctype constants
    const CTYPE_ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const CTYPE_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(DataStream $data, OpenElementsStack $stack) {
        $this->state = self::DATA_STATE;
        $this->data = $data;
        $this->stack = $stack;
    }

    public function createToken(): Token {
        while (true) {
            if (Parser::$debug) {
                switch ($this->state) {
                    case self::DATA_STATE: $state = "Data";
                    break;
                    case self::RCDATA_STATE: $state = "RCDATA";
                    break;
                    case self::RAWTEXT_STATE: $state = "RAWTEXT";
                    break;
                    case self::SCRIPT_DATA_STATE: $state = "Script data";
                    break;
                    case self::PLAINTEXT_STATE: $state = "PLAINTEXT";
                    break;
                    case self::TAG_OPEN_STATE: $state = "Tag open";
                    break;
                    case self::END_TAG_OPEN_STATE: $state = "End tag open";
                    break;
                    case self::TAG_NAME_STATE: $state = "Tag name";
                    break;
                    case self::RCDATA_LESS_THAN_SIGN_STATE: $state = "RCDATA less-than sign";
                    break;
                    case self::RCDATA_END_TAG_OPEN_STATE: $state = "RCDATA end tag open";
                    break;
                    case self::RCDATA_END_TAG_NAME_STATE: $state = "RCDATA end tag name";
                    break;
                    case self::RAWTEXT_LESS_THAN_SIGN_STATE: $state = "RAWTEXT less than sign";
                    break;
                    case self::RAWTEXT_END_TAG_OPEN_STATE: $state = "RAWTEXT end tag open";
                    break;
                    case self::RAWTEXT_END_TAG_NAME_STATE: $state = "RAWTEXT end tag name";
                    break;
                    case self::SCRIPT_DATA_LESS_THAN_SIGN_STATE: $state = "Script data less-than sign";
                    break;
                    case self::SCRIPT_DATA_END_TAG_OPEN_STATE: $state = "Script data end tag open";
                    break;
                    case self::SCRIPT_DATA_END_TAG_NAME_STATE: $state = "Script data end tag name";
                    break;
                    case self::SCRIPT_DATA_ESCAPE_START_STATE: $state = "Script data escape start";
                    break;
                    case self::SCRIPT_DATA_ESCAPE_START_DASH_STATE: $state = "Script data escape start dash";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_STATE: $state = "Script data escaped";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_DASH_STATE: $state = "Script data escaped dash";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE: $state = "Script data escaped dash dash";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE: $state = "Script data escaped less-than sign";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE: $state = "Script data escaped end tag open";
                    break;
                    case self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE: $state = "Script data escaped end tag name";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE: $state = "Script data double escape start";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE: $state = "Script data double escaped";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE: $state = "Script data double escaped dash";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE: $state = "Script data double escaped dash dash";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE: $state = "Script data double escaped less-than sign";
                    break;
                    case self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE: $state = "Script data double escape end";
                    break;
                    case self::BEFORE_ATTRIBUTE_NAME_STATE: $state = "Before attribute";
                    break;
                    case self::ATTRIBUTE_NAME_STATE: $state = "Attribute name";
                    break;
                    case self::AFTER_ATTRIBUTE_NAME_STATE: $state = "After attribute name";
                    break;
                    case self::BEFORE_ATTRIBUTE_VALUE_STATE: $state = "Before attribute value";
                    break;
                    case self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE: $state = "Attribute value (double quoted)";
                    break;
                    case self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE: $state = "Attribute value (single quoted)";
                    break;
                    case self::ATTRIBUTE_VALUE_UNQUOTED_STATE: $state = "Attribute value (unquoted)";
                    break;
                    case self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE: $state = "After attribute value (quoted)";
                    break;
                    case self::SELF_CLOSING_START_TAG_STATE: $state = "Self-closing start tag";
                    break;
                    case self::BOGUS_COMMENT_STATE: $state = "Bogus comment";
                    break;
                    case self::MARKUP_DECLARATION_OPEN_STATE: $state = "Markup declaration open";
                    break;
                    case self::COMMENT_START_STATE: $state = "Comment start";
                    break;
                    case self::COMMENT_START_DASH_STATE: $state = "Comment start dash";
                    break;
                    case self::COMMENT_STATE: $state = "Comment";
                    break;
                    case self::COMMENT_END_DASH_STATE: $state = "Comment end dash";
                    break;
                    case self::COMMENT_END_STATE: $state = "Comment end";
                    break;
                    case self::COMMENT_END_BANG_STATE: $state = "Comment end bang";
                    break;
                    case self::DOCTYPE_STATE: $state = "DOCTYPE";
                    break;
                    case self::BEFORE_DOCTYPE_NAME_STATE: $state = "Before DOCTYPE name";
                    break;
                    case self::DOCTYPE_NAME_STATE: $state = "DOCTYPE name";
                    break;
                    case self::AFTER_DOCTYPE_NAME_STATE: $state = "After DOCTYPE name";
                    break;
                    case self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE: $state = "After DOCTYPE public keyword";
                    break;
                    case self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE: $state = "Before DOCTYPE public identifier";
                    break;
                    case self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE: $state = "DOCTYPE public identifier (double quoted)";
                    break;
                    case self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE: $state = "DOCTYPE public identifier (single quoted)";
                    break;
                    case self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE: $state = "After DOCTYPE public identifier";
                    break;
                    case self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE: $state = "Between DOCTYPE public and system identifiers";
                    break;
                    case self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE: $state = "After DOCTYPE system keyword";
                    break;
                    case self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE: $state = "Before DOCTYPE system identifier";
                    break;
                    case self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE: $state = "DOCTYPE system identifier (double-quoted)";
                    break;
                    case self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE: $state = "DOCTYPE system identifier (single-quoted)";
                    break;
                    case self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE: $state = "After DOCTYPE system identifier";
                    break;
                    case self::BOGUS_DOCTYPE_STATE: $state = "Bogus comment";
                    break;
                    case self::CDATA_SECTION_STATE: $state = "CDATA section";
                    break;
                    default: throw new Exception(Exception::UNKNOWN_ERROR);
                }

                echo "State: $state\n";
            }

            # 12.2.4.1 Data state
            if ($this->state === self::DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Switch to the character reference in data state.

                    # 8.2.4.2 Character reference in data state:
                    # Switch to the data state.
                    # Attempt to consume a character reference, with no additional allowed character.
                    # If nothing is returned, emit a U+0026 AMPERSAND character (&) token.
                    # Otherwise, emit the character tokens that were returned.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    return new CharacterToken($this->data->consumeCharacterReference());
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the tag open state.
                    $this->state = self::TAG_OPEN_STATE;
                    continue;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    return new CharacterToken($char.$this->data->consumeUntil('&<'));
                }
            }

            # 12.2.4.2 Character reference in data state
            // OPTIMIZATION: This is instead done in the block above.

            # 12.2.4.3 RCDATA state
            elseif ($this->state === self::RCDATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Switch to the character reference in RCDATA state.

                    # 8.2.4.4 Character reference in RCDATA state:
                    # Switch to the RCDATA state.
                    # Attempt to consume a character reference, with no additional allowed character.
                    # If nothing is returned, emit a U+0026 AMPERSAND character (&) token.
                    # Otherwise, emit the character tokens that were returned.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    return new CharacterToken($this->data->consumeCharacterReference());
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the RCDATA less-than sign state.
                    $this->state = self::RCDATA_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    return new CharacterToken($char.$this->data->consumeUntil('&<'));
                }

                continue;
            }

            # 12.2.4.4 Character reference in RCDATA state
            // OPTIMIZATION: This is instead done in the block above.

            # 12.2.4.5 RAWTEXT state
            elseif ($this->state === self::RAWTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the RAWTEXT less-than sign state.
                    $this->state = self::RAWTEXT_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    return new CharacterToken($char.$this->data->consumeUntil('<'));
                }

                continue;
            }

            # 12.2.4.6 Script data state
            elseif ($this->state === self::SCRIPT_DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the script data less-than sign state.
                    $this->state = self::SCRIPT_DATA_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    return new CharacterToken($char.$this->data->consumeUntil('<'));
                }

                continue;
            }

            # 12.2.4.7 PLAINTEXT state
            elseif ($this->state === self::PLAINTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # EOF
                if ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    return new CharacterToken($char.$this->data->consumeUntil(''));
                }
            }

            # 12.2.4.8 Tag open state
            elseif ($this->state === self::TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0021 EXCLAMATION MARK (!)
                if ($char === '!') {
                    # Switch to the markup declaration open state.
                    $this->state = self::MARKUP_DECLARATION_OPEN_STATE;
                }
                # U+002F SOLIDUS (/)
                elseif ($char === '/') {
                    # Switch to the end tag open state.
                    $this->state = self::END_TAG_OPEN_STATE;
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new start tag token, set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point), then switch
                    # to the tag name state. (Don't emit the token yet; further details will be filled
                    # in before it is emitted.)
                    # Lowercase:
                    # Create a new start tag token, set its tag name to the current input character,
                    # then switch to the tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new StartTagToken(strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA)));
                    $this->state = self::TAG_NAME_STATE;
                }
                # U+003F QUESTION MARK (?)
                elseif ($char === '?') {
                    # Parse error. Switch to the bogus comment state.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, 'tag name');
                    }

                    $this->state = self::BOGUS_COMMENT_STATE;
                }
                # Anything else
                else {
                    # Parse error. Switch to the data state. Emit a U+003C LESS-THAN SIGN character
                    # token. Reconsume the current input character.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, 'tag name');
                    }

                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.9 End tag open state
            elseif ($this->state === self::END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point), then switch
                    # to the tag name state. (Don't emit the token yet; further details will be filled
                    # in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, set its tag name to the current input character,
                    # then switch to the tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA)));
                    $this->state = self::TAG_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state.
                    ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $char);
                    $this->state = self::DATA_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit a U+003C LESS-THAN SIGN character
                    # token and a U+002F SOLIDUS character token. Reconsume the EOF character.
                    // Making errors more expressive.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'tag name');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }
                # Anything else
                else {
                   # Parse error. Switch to the bogus comment state.
                   ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $char);
                   $this->state = self::BOGUS_COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.10 Tag name state
            elseif ($this->state === self::TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before attribute name state.
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->state = self::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name.

                    // OPTIMIZATION: Consume all characters that are Uppercase ASCII characters to
                    // prevent having to loop back through here every single time.
                    $token->name = $token->name.strtolower($char.$this->data->consumeWhile(self::CTYPE_UPPER));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, 'tag name');
                    }

                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current tag token's tag name.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name = $token->name.$char.$this->data->consumeUntil("\t\n\x0c />".self::CTYPE_UPPER);
                }

                continue;
            }

            # 8.2.4.11 RCDATA less-than sign state
            elseif ($this->state === self::RCDATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the RCDATA end tag open
                    # state.
                    $temporaryBuffer = '';
                    $this->state = self::RCDATA_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->state = self::RCDATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }

                continue;
            }

            # 8.2.4.12 RCDATA end tag open state
            elseif ($this->state === self::RCDATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the RCDATA
                    # end tag name state. (Don't emit the token yet; further details will be filled in
                    # before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the RCDATA end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->state = self::RCDATA_END_TAG_NAME_STATE;
                    continue;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token and a
                    # U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->state = self::RCDATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }
            }

            # 8.2.4.13 RCDATA end tag name state
            elseif ($this->state === self::RCDATA_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->state = self::RCDATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->state = self::RCDATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        $this->state = self::RCDATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->state = self::RCDATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }

                continue;
            }

            # 8.2.4.14 RAWTEXT less-than sign state
            elseif ($this->state === self::RAWTEXT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the RAWTEXT end tag open
                    # state.
                    $temporaryBuffer = '';
                    $this->state = self::RAWTEXT_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }

                continue;
            }

            # 8.2.4.15 RAWTEXT end tag open state
            elseif ($this->state === self::RAWTEXT_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the RAWTEXT
                    # end tag name state. (Don't emit the token yet; further details will be filled in
                    # before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the RAWTEXT end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->state = self::RAWTEXT_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token and a
                    # U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }

                continue;
            }

            # 8.2.4.16 RAWTEXT end tag name state
            elseif ($this->state === self::RAWTEXT_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->state = self::RAWTEXT_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }

                    continue;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->state = self::RAWTEXT_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }

                    continue;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        $this->state = self::RAWTEXT_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }

                    continue;
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);

                    continue;
                }
            }

            # 8.2.4.17 Script data less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data end tag
                    # open state.
                    $temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_END_TAG_OPEN_STATE;

                    continue;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Switch to the script data escape start state. Emit a U+003C LESS-THAN SIGN
                    # character token and a U+0021 EXCLAMATION MARK character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_STATE;
                    return new CharacterToken('<!');
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');

                    continue;
                }
            }

            # 8.2.4.18 Script data end tag open state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the script
                    # data end tag name state. (Don't emit the token yet; further details will be
                    # filled in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the script data end tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->state = self::SCRIPT_DATA_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token
                    # and a U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }

                continue;
            }

            # 8.2.4.19 Script data end tag name state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        $this->state = self::SCRIPT_DATA_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }

                continue;
            }

            # 8.2.4.20 Script data escape start state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escape start dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_DASH_STATE;
                    return new CharacterToken('-');
                }
                # Anything else
                else {
                    # Switch to the script data state. Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.21 Script data escape start dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # Anything else
                else {
                    # Switch to the script data state. Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.22 Script data escaped state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->state = self::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil('-<'));
                }

                continue;
            }

            # 8.2.4.23 Script data escaped dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->state = self::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit the current input character as a
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken($char);
                }

                continue;
            }

            # 8.2.4.24 Script data escaped dash dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Emit a U+002D HYPHEN-MINUS character token.
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state. Emit a U+003E GREATER-THAN SIGN character
                    # token.
                    $this->state = self::SCRIPT_DATA_STATE;
                    return new CharacterToken('>');
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->state = self::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit the current input character as a
                    # character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken($char);
                }

                continue;
            }

            # 8.2.4.25 Script data escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data escaped
                    # end tag open state.
                    $temporaryBuffer .= '';
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE;
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Set the temporary buffer to the empty string. Append the lowercase version of
                    # the current input character (add 0x0020 to the character's code point) to the
                    # temporary buffer. Switch to the script data double escape start state. Emit a
                    # U+003C LESS-THAN SIGN character token and the current input character as a
                    # character token.
                    # Lowercase:
                    # Set the temporary buffer to the empty string. Append the current input character
                    # to the temporary buffer. Switch to the script data double escape start state.
                    # Emit a U+003C LESS-THAN SIGN character token and the current input character as
                    # a character token.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $temporaryBuffer = strtolower($char);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE;
                    return new CharacterToken('<'.$char);
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit a U+003C LESS-THAN SIGN character
                    # token. Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken($char);
                }

                continue;
            }

            # 8.2.4.26 Script data escaped end tag open state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the script
                    # data escaped end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the script data escaped end tag name state. (Don't emit the token yet; further
                    # details will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit a U+003C LESS-THAN SIGN character
                    # token and a U+002F SOLIDUS character token. Reconsume the current input
                    # character.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }

                continue;
            }

            # 8.2.4.27 Script data escaped end tag name state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                        $this->data->unconsume();
                        return new CharacterToken('</'.$temporaryBuffer);
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }

                continue;
            }

            # 8.2.4.29 Script data double escaped state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash dash state. Emit a U+002D
                    # HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state. Emit a U+003C
                    # LESS-THAN SIGN character token.
                    $this->state = self::DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    return new CharacterToken('<');
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state. Emit a U+003E GREATER-THAN SIGN character
                    # token.
                    $this->state = self::SCRIPT_DATA_STATE;
                    return new CharacterToken('>');
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'script data');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Emit the current input character
                    # as a character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    return new CharacterToken($char);
                }

                continue;
            }

            # 8.2.4.32 Script data double escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data double
                    # escape end state. Emit a U+002F SOLIDUS character token.
                    $temporaryBuffer = '';
                    $this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE;
                    return new CharacterToken('/');
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Reconsume the current input
                    # character.
                    $this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.33 Script data double escape end state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                # "/" (U+002F)
                # ">" (U+003E)
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ' || $char === '/' || $char === '>') {
                    # If the temporary buffer is the string "script", then switch to the script data
                    # escaped state. Otherwise, switch to the script data double escaped state. Emit
                    # the current input character as a character token.
                    if ($temporaryBuffer === 'script') {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                        return new CharacterToken($char);
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the temporary buffer. Emit the current input
                    # character as a character token.
                    # Lowercase:
                    # Append the current input character to the temporary buffer. Emit the current
                    # input character as a character token.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char = $char.$this->data->consumeWhile(self::CTYPE_ALPHA);
                    $temporaryBuffer .= strtolower(strtolower($char));
                    return new CharacterToken($char);
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Reconsume the current input
                    # character.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.34 Before attribute name state
            elseif ($this->state === self::BEFORE_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->state = self::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # lowercase version of the current input character (add 0x0020 to the character's
                    # code point), and its value to the empty string. Switch to the attribute name
                    # state.

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    $attribute = new TokenAttr(strtolower($char), '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute name');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # Anything else
                else {
                    # Quotes, less than sign, equals:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # current input character, and its value to the empty string. Switch to the
                    # attribute name state.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute name');
                    }

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    $attribute = new TokenAttr($char, '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                }

                continue;
            }

            # 8.2.4.35 Attribute name state
            elseif ($this->state === self::ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                # "/" (U+002F)
                # U+003E GREATER-THAN SIGN (>)
                # EOF
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ' || $char === '/' || $char === '>' || $char === '') {
                    if ($token->hasAttribute($attribute->name)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $attribute->name);
                    }

                    # Reconsume in the after attribute name state.
                    $this->data->unconsume();
                    $this->state = self::AFTER_ATTRIBUTE_NAME_STATE;
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    if ($token->hasAttribute($attribute->name)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $attribute->name);
                    }

                    # Switch to the before attribute value state.
                    $this->state = self::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current attribute's name.

                    // OPTIMIZATION: Consume all characters that are uppercase ASCII letters to prevent
                    // having to loop back through here every single time.
                    $attribute->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_UPPER));
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # Anything else
                else {
                    # Quotes, less than sign:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's name.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute name');
                    }

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->name .= $char.$this->data->consumeUntil("\t\n\x0c /=>\"'<".self::CTYPE_UPPER);
                }

                # When the user agent leaves the attribute name state (and before emitting the tag
                # token, if appropriate), the complete attribute's name must be compared to the
                # other attributes on the same token; if there is already an attribute on the
                # token with the exact same name, then this is a parse error and the new attribute
                # must be removed from the token.

                // DEVIATION: Because this implementation uses a buffer to hold the attribute name
                // it is only added if it is valid. The result is the same, though.

                continue;
            }

            # 8.2.4.36 After attribute name state
            elseif ($this->state === self::AFTER_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # U+002F SOLIDUS (/)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->state = self::SELF_CLOSING_START_TAG_STATE;
                }
                # U+003D EQUALS SIGN (=)
                elseif ($char === '=') {
                    # Switch to the before attribute value state.
                    $this->state = self::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # U+003E GREATER-THAN SIGN (>)
                elseif ($char === '>') {
                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # lowercase version of the current input character (add 0x0020 to the character's
                    # code point), and its value to the empty string. Switch to the attribute name
                    # state.

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    $attribute = new TokenAttr(strtolower($char), '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute name, attribute value, or tag end');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # Anything else
                else {
                    # Quotes, less than sign, equals:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # current input character, and its value to the empty string. Switch to the
                    # attribute name state.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute name, attribute value, or tag end');
                    }

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    $attribute = new TokenAttr($char, '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                }

                continue;
            }

            # 8.2.4.37 Before attribute value state
            elseif ($this->state === self::BEFORE_ATTRIBUTE_VALUE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Switch to the attribute value (double-quoted) state.
                    $this->state = self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the attribute value (unquoted) state. Reconsume the current input
                    # character.
                    $this->state = self::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                    $this->data->unconsume();
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Switch to the attribute value (single-quoted) state.
                    $this->state = self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the current tag token.
                    ParseError::trigger(ParseError::UNEXPECTED_END_OF_TAG, 'attribute value');
                    $this->state = self::DATA_STATE;

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute value');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # "<" (U+003C)
                # "=" (U+003D)
                # "`" (U+0060)
                # Anything else
                else {
                    # less than sign, equals, tick:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's value. Switch to
                    # the attribute value (unquoted) state.

                    if ($char === '<' || $char === '=' || $char === '`') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute value');
                    }

                    $attribute->value .= $char;
                    $this->state = self::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                }

                continue;
            }

            # 8.2.4.38 Attribute value (double-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being U+0022 QUOTATION MARK (").

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference('"', true);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute value');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil('"&');
                }

                continue;
            }

            # 8.2.4.39 Attribute value (single-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after attribute value (quoted) state.
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being "'" (U+0027).

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    # DEVIATION: This implementation does the character reference consuming in a
                    # function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference("'", true);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute value');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil("'&");
                }

                continue;
            }


            # 8.2.4.40 Attribute value (unquoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_UNQUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    $this->state = self::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being ">" (U+003E).

                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being "'" (U+0027).

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference('>', true);
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    return $token;
                }
                # Parse error. Switch to the data state. Reconsume the EOF character.
                elseif ($char === '') {
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute value');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # "`" (U+0060)
                # Anything else
                else {
                    # Quotes, less than sign, equals, tick:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's value.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=' || $char === '`') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute value');
                    }

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil("\t\n\x0c &>\"'<=`");
                }

                continue;
            }

            # 8.2.4.42 After attribute value (quoted) state
            elseif ($this->state === self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before attribute name state.
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->state = self::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'attribute name or tag end');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Switch to the before attribute name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'attribute name or tag end');
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.43 Self-closing start tag state
            elseif ($this->state === self::SELF_CLOSING_START_TAG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Set the self-closing flag of the current tag token. Switch to the data state.
                    # Emit the current tag token.
                    $token->selfClosing = true;
                    $this->state = self::DATA_STATE;

                    // Need to add the current attribute to the token, if necessary.
                    if ($attribute) {
                        $token->attributes[] = $attribute;
                        $attribute = null;
                    }

                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'tag end');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Switch to the before attribute name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'tag end');
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.44 Bogus comment state
            elseif ($this->state === self::BOGUS_COMMENT_STATE) {
                # Consume every character up to and including the first ">" (U+003E) character or
                # the end of the file (EOF), whichever comes first. Emit a comment token whose
                # data is the concatenation of all the characters starting from and including the
                # character that caused the state machine to switch into the bogus comment state,
                # up to and including the character immediately before the last consumed character
                # (i.e. up to the character just before the U+003E or EOF character), but with any
                # U+0000 NULL characters replaced by U+FFFD REPLACEMENT CHARACTER characters. (If
                # the comment was started by the end of the file (EOF), the token is empty.
                # Similarly, the token is empty if it was generated by the string "<!>".)

                $char = $char.$this->data->consumeUntil('>');
                $nextChar = $this->data->consume();

                # Switch to the data state.
                $this->state = self::DATA_STATE;

                # If the end of the file was reached, reconsume the EOF character.
                if ($nextChar === '') {
                    $this->data->unconsume();
                }

                return new CommentToken($char);
            }

            # 8.2.4.45 Markup declaration open state
            elseif ($this->state === self::MARKUP_DECLARATION_OPEN_STATE) {
                # If the next two characters are both "-" (U+002D) characters, consume those two
                # characters, create a comment token whose data is the empty string, and switch to
                # the comment start state.
                if ($this->data->peek(2) === '--') {
                    $this->data->consume(2);
                    $token = new CommentToken();
                    $this->state = self::COMMENT_START_STATE;
                }
                # Otherwise, if the next seven characters are an ASCII case-insensitive match for
                # the word "DOCTYPE", then consume those characters and switch to the DOCTYPE
                # state.
                elseif (strtolower($this->data->peek(7)) === 'doctype') {
                    $this->data->consume(7);
                    $this->state = self::DOCTYPE_STATE;
                }
                # Otherwise, if there is an adjusted current node and it is not an element in the
                # HTML namespace and the next seven characters are a case-sensitive match for the
                # string "[CDATA[" (the five uppercase letters "CDATA" with a U+005B LEFT SQUARE
                # BRACKET character before and after), then consume those characters and switch to
                # the CDATA section state.
                else {
                    $adjustedCurrentNode = $this->stack->adjustedCurrentNode;
                    if ($adjustedCurrentNode && $adjustedCurrentNode->namespace !== self::HTML_NAMESPACE && $this->data->peek(7) === '[CDATA[') {
                        $this->data->consume(7);
                        $this->state = self::CDATA_SECTION_STATE;
                    }
                    # Otherwise, this is a parse error. Switch to the bogus comment state. The next
                    # character that is consumed, if any, is the first character that will be in the
                    # comment.
                    else {
                        $char = $this->data->consume();
                        if ($char !== '') {
                            ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'markup declaration');
                        } else {
                            ParseError::trigger(ParseError::UNEXPECTED_EOF, 'markup declaration');
                        }

                        $this->state = self::BOGUS_COMMENT_STATE;
                    }
                }

                continue;
            }

            # 8.2.4.46 Comment start state
            elseif ($this->state === self::COMMENT_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment start dash state.
                    $this->state = self::COMMENT_START_DASH_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the comment token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'comment');
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data. Switch to the
                    # comment state.
                    $token->data .= $char;
                    $this->state = self::COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.47 Comment start dash state
            elseif ($this->state === self::COMMENT_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment start dash state.
                    $this->state = self::COMMENT_END_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the comment token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'comment');
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append a "-" (U+002D) character and the current input character to the comment
                    # token's data. Switch to the comment state.
                    $token->data .= '-'.$char;
                    $this->state = self::COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.48 Comment state
            elseif ($this->state === self::COMMENT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment end dash state
                    $this->state = self::COMMENT_END_DASH_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->data .= $char.$this->data->consumeUntil('-');
                }

                continue;
            }

            # 8.2.4.49 Comment end dash state
            elseif ($this->state === self::COMMENT_END_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment end state
                    $this->state = self::COMMENT_END_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                   # Append a "-" (U+002D) character and the current input character to the comment
                   # token's data. Switch to the comment state.
                   $token->data .= '-'.$char;
                   $this->state = self::COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.50 Comment end state
            elseif ($this->state === self::COMMENT_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state. Emit the comment token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Parse error. Switch to the comment end bang state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '!', 'comment end');
                    $this->state = self::COMMENT_END_BANG_STATE;
                }
                # "-" (U+002D)
                elseif ($char === '-') {
                    # Parse error. Append a "-" (U+002D) character to the comment token's data.

                    // OPTIMIZATION: Consume all '-' characters to prevent having to loop back through
                    // here every single time.
                    $char .= $this->data->consumeWhile('-');
                    for ($i = 0; $i < strlen($char); $i++) {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '-', 'comment end');
                    }

                    $token->data .= $char;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment end');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Append two "-" (U+002D) characters and the current input character
                    # to the comment token's data. Switch to the comment state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'comment end');
                    $token->data .= '--'.$char;
                    $this->state = self::COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.51 Comment end bang state
            elseif ($this->state === self::COMMENT_END_BANG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Append two "-" (U+002D) characters and a "!" (U+0021) character to the comment
                    # token's data. Switch to the comment end dash state.
                    $token->data .= '--!';
                    $this->state = self::COMMENT_END_DASH_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the comment token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'comment end');
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append two "-" (U+002D) characters, a "!" (U+0021) character, and the current
                    # input character to the comment token's data. Switch to the comment state.
                    $token->data .= '--!'.$char;
                    $this->state = self::COMMENT_STATE;
                }

                continue;
            }

            # 8.2.4.52 DOCTYPE state
            elseif ($this->state === self::DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE name state.

                    // Spec doesn't say to create a token here, but if you don't it leads to a
                    // situation where a token doesn't exist.
                    $token = new DOCTYPEToken();
                    $this->state = self::DOCTYPE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Create a new DOCTYPE token. Set its
                    # force-quirks flag to on. Emit the token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE');
                    $this->state = self::DATA_STATE;
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Switch to the before DOCTYPE name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE');
                    $this->state = self::DOCTYPE_NAME_STATE;
                    $this->data->unconsume();
                }

                continue;
            }

            # 8.2.4.53 Before DOCTYPE name state
            elseif ($this->state === self::BEFORE_DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Create a new DOCTYPE token. Set the token's name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Switch to
                    # the DOCTYPE name state.
                    $token = new DOCTYPEToken($char);
                    $token->tokenizerState = self::DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Create a new DOCTYPE token. Set its force-quirks flag to on. Switch
                    # to the data state. Emit the token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE');
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Create a new DOCTYPE token. Set its
                    # force-quirks flag to on. Emit the token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE');
                    $this->state = self::DATA_STATE;
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Create a new DOCTYPE token. Set the token's name to the current input character.
                    # Switch to the DOCTYPE name state.
                    $token = new DOCTYPEToken($char);
                    $token->tokenizerState = self::DOCTYPE_NAME_STATE;
                }

                continue;
            }

            # 8.2.4.54 DOCTYPE name state
            elseif ($this->state === self::DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the after DOCTYPE name state.
                    $this->state = self::AFTER_DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current DOCTYPE token's name.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's name.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name .= $char.$this->data->consumeUntil("\t\n\x0c >".self::CTYPE_ALPHA);
                }

                continue;
            }

            # 8.2.4.55 After DOCTYPE name state
            elseif ($this->state === self::AFTER_DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the after DOCTYPE name state.
                    $this->state = self::AFTER_DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE name');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # If the six characters starting from the current input character are an ASCII
                    # case-insensitive match for the word "PUBLIC", then consume those characters and
                    # switch to the after DOCTYPE public keyword state.
                    // Simpler to just consume and then unconsume if they're not needed.
                    $char .= $this->data->consume(5);
                    if (strtolower($char) === 'public') {
                        $this->state = self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE;
                    }
                    # Otherwise, if the six characters starting from the current input character are
                    # an ASCII case-insensitive match for the word "SYSTEM", then consume those
                    # characters and switch to the after DOCTYPE system keyword state.
                    elseif (strtolower($char) === 'system') {
                        $this->state = self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE;
                    }
                    # Otherwise, this is a parse error. Set the DOCTYPE token's force-quirks flag to
                    # on. Switch to the bogus DOCTYPE state.
                    else {
                        // Need to unconsume what was consumed earlier.
                        $this->data->unconsume(5);
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char[0], 'DOCTYPE name');
                        $token->forceQuirks = true;
                        $this->state = self::BOGUS_DOCTYPE_STATE;
                    }
                }

                continue;
            }

            # 8.2.4.56 After DOCTYPE public keyword state
            elseif ($this->state === self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE public identifier state.
                    $this->state = self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Parse error. Set the DOCTYPE token's public identifier to the empty string (not
                    # missing), then switch to the DOCTYPE public identifier (double-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '"', 'DOCTYPE public keyword');
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Parse error. Set the DOCTYPE token's public identifier to the empty string (not
                    # missing), then switch to the DOCTYPE public identifier (single-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, "'", 'DOCTYPE public keyword');
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE public keyword');
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public keyword');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE public keyword');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.57 Before DOCTYPE public identifier state
            elseif ($this->state === self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    # then switch to the DOCTYPE public identifier (double-quoted) state.
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    # then switch to the DOCTYPE public identifier (single-quoted) state.
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.58 DOCTYPE public identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->state = self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's public identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil('">');
                }

                continue;
            }

            # 8.2.4.59 DOCTYPE public identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->state = self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's public identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil("'>");
                }

                continue;
            }

            # 8.2.4.60 After DOCTYPE public identifier state
            elseif ($this->state === self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the between DOCTYPE public and system identifiers state.
                    $this->state = self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.61 Between DOCTYPE public and system identifiers state
            elseif ($this->state === self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE public identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.62 After DOCTYPE system keyword state
            elseif ($this->state === self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE system identifier state.
                    $this->state = self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Parse error. Set the DOCTYPE token's system identifier to the empty string (not
                    # missing), then switch to the DOCTYPE system identifier (double-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '"', 'DOCTYPE system keyword');
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Parse error. Set the DOCTYPE token's system identifier to the empty string (not
                    # missing), then switch to the DOCTYPE system identifier (single-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, "'", 'DOCTYPE system keyword');
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE system keyword');
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE system keyword');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE system keyword');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.63 Before DOCTYPE system identifier state
            elseif ($this->state === self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.64 DOCTYPE system identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->state = self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil('">');
                }

                continue;
            }

            # 8.2.4.65 DOCTYPE system identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->state = self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, '>', 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil("'>");
                }

                continue;
            }

            # 8.2.4.66 After DOCTYPE system identifier state
            elseif ($this->state === self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the between DOCTYPE system and system identifiers state.
                    $this->state = self::BETWEEN_DOCTYPE_SYSTEM_AND_SYSTEM_IDENTIFIERS_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, 'DOCTYPE system identifier');
                    $this->state = self::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $char, 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }

                continue;
            }

            # 8.2.4.67 Bogus DOCTYPE state
            elseif ($this->state === self::BOGUS_DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state. Emit the DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Emit the DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                # Ignore the character.

                continue;
            }

            # 8.2.4.68 CDATA section state
            elseif ($this->state === self::CDATA_SECTION_STATE) {
                # Switch to the data state.
                $this->state = self::DATA_STATE;

                # Consume every character up to the next occurrence of the three character
                # sequence U+005D RIGHT SQUARE BRACKET U+005D RIGHT SQUARE BRACKET U+003E
                # GREATER-THAN SIGN (]]>), or the end of the file (EOF), whichever comes first.
                # Emit a series of character tokens consisting of all the characters consumed
                # except the matching three character sequence at the end (if one was found before
                # the end of the file).
                $char = '';
                while (true) {
                    $char .= $this->data->consumeUntil(']');
                    $peek = $this->data->peek(3);
                    $peeklen = strlen($peek);

                    if ($peek === ']]>') {
                        $this->data->consume(3);
                        return new CharacterToken($char);
                    } elseif ($peek === '') {
                        # If the end of the file was reached, reconsume the EOF character.
                        $this->data->unconsume();
                        return new CharacterToken($char);
                    } elseif ($peeklen < 3) {
                        $char .= $this->data->consume($peeklen);
                        # If the end of the file was reached, reconsume the EOF character.
                        $this->data->unconsume();
                        return new CharacterToken($char);
                    } else {
                        $char .= $this->data->consume();
                    }
                }

                continue;
            }

            // If this is reached then we've fucked up. The tokenizer is in an infinite loop
            // and should exit immediately.
            throw new Exception(Exception::TOKENIZER_INVALID_STATE);
        }
    }
}
