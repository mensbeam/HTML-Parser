<?php
declare(strict_types=1);
namespace dW\HTML5;

class Tokenizer {
    use ParseErrorEmitter;

    public $state;

    protected $data;
    protected $stack;

    public static $debug = false;

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
    const COMMENT_LESS_THAN_SIGN_STATE = 45;
    const COMMENT_LESS_THAN_SIGN_BANG_STATE = 46;
    const COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE = 47;
    const COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE = 48;
    const COMMENT_END_DASH_STATE = 49;
    const COMMENT_END_STATE = 50;
    const COMMENT_END_BANG_STATE = 51;
    const DOCTYPE_STATE = 52;
    const BEFORE_DOCTYPE_NAME_STATE = 53;
    const DOCTYPE_NAME_STATE = 54;
    const AFTER_DOCTYPE_NAME_STATE = 55;
    const AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE = 56;
    const BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 57;
    const DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE = 58;
    const DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE = 59;
    const AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 60;
    const BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE = 61;
    const AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE = 62;
    const BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 63;
    const DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE = 64;
    const DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE = 65;
    const AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 66;
    const BOGUS_DOCTYPE_STATE = 67;
    const CDATA_SECTION_STATE = 68;
    const CDATA_SECTION_BRACKET_STATE = 69;
    const CDATA_SECTION_END_STATE = 70;

    const STATE_NAMES = [
        self::DATA_STATE                                          => "Data",
        self::RCDATA_STATE                                        => "RCDATA",
        self::RAWTEXT_STATE                                       => "RAWTEXT",
        self::SCRIPT_DATA_STATE                                   => "Script data",
        self::PLAINTEXT_STATE                                     => "PLAINTEXT",
        self::TAG_OPEN_STATE                                      => "Tag open",
        self::END_TAG_OPEN_STATE                                  => "End tag open",
        self::TAG_NAME_STATE                                      => "Tag name",
        self::RCDATA_LESS_THAN_SIGN_STATE                         => "RCDATA less-than sign",
        self::RCDATA_END_TAG_OPEN_STATE                           => "RCDATA end tag open",
        self::RCDATA_END_TAG_NAME_STATE                           => "RCDATA end tag name",
        self::RAWTEXT_LESS_THAN_SIGN_STATE                        => "RAWTEXT less than sign",
        self::RAWTEXT_END_TAG_OPEN_STATE                          => "RAWTEXT end tag open",
        self::RAWTEXT_END_TAG_NAME_STATE                          => "RAWTEXT end tag name",
        self::SCRIPT_DATA_LESS_THAN_SIGN_STATE                    => "Script data less-than sign",
        self::SCRIPT_DATA_END_TAG_OPEN_STATE                      => "Script data end tag open",
        self::SCRIPT_DATA_END_TAG_NAME_STATE                      => "Script data end tag name",
        self::SCRIPT_DATA_ESCAPE_START_STATE                      => "Script data escape start",
        self::SCRIPT_DATA_ESCAPE_START_DASH_STATE                 => "Script data escape start dash",
        self::SCRIPT_DATA_ESCAPED_STATE                           => "Script data escaped",
        self::SCRIPT_DATA_ESCAPED_DASH_STATE                      => "Script data escaped dash",
        self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE                 => "Script data escaped dash dash",
        self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE            => "Script data escaped less-than sign",
        self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE              => "Script data escaped end tag open",
        self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE              => "Script data escaped end tag name",
        self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE               => "Script data double escape start",
        self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE                    => "Script data double escaped",
        self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE               => "Script data double escaped dash",
        self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE          => "Script data double escaped dash dash",
        self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE     => "Script data double escaped less-than sign",
        self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE                 => "Script data double escape end",
        self::BEFORE_ATTRIBUTE_NAME_STATE                         => "Before attribute",
        self::ATTRIBUTE_NAME_STATE                                => "Attribute name",
        self::AFTER_ATTRIBUTE_NAME_STATE                          => "After attribute name",
        self::BEFORE_ATTRIBUTE_VALUE_STATE                        => "Before attribute value",
        self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE                 => "Attribute value (double quoted)",
        self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE                 => "Attribute value (single quoted)",
        self::ATTRIBUTE_VALUE_UNQUOTED_STATE                      => "Attribute value (unquoted)",
        self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE                  => "After attribute value (quoted)",
        self::SELF_CLOSING_START_TAG_STATE                        => "Self-closing start tag",
        self::BOGUS_COMMENT_STATE                                 => "Bogus comment",
        self::MARKUP_DECLARATION_OPEN_STATE                       => "Markup declaration open",
        self::COMMENT_START_STATE                                 => "Comment start",
        self::COMMENT_START_DASH_STATE                            => "Comment start dash",
        self::COMMENT_STATE                                       => "Comment",
        self::COMMENT_LESS_THAN_SIGN_STATE                        => "Comment less-than sign",
        self::COMMENT_LESS_THAN_SIGN_BANG_STATE                   => "Comment less-than sign bang",
        self::COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE              => "Comment less-than sign bang dash",
        self::COMMENT_END_DASH_STATE                              => "Comment end dash",
        self::COMMENT_END_STATE                                   => "Comment end",
        self::COMMENT_END_BANG_STATE                              => "Comment end bang",
        self::DOCTYPE_STATE                                       => "DOCTYPE",
        self::BEFORE_DOCTYPE_NAME_STATE                           => "Before DOCTYPE name",
        self::DOCTYPE_NAME_STATE                                  => "DOCTYPE name",
        self::AFTER_DOCTYPE_NAME_STATE                            => "After DOCTYPE name",
        self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE                  => "After DOCTYPE public keyword",
        self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE              => "Before DOCTYPE public identifier",
        self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE       => "DOCTYPE public identifier (double quoted)",
        self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE       => "DOCTYPE public identifier (single quoted)",
        self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE               => "After DOCTYPE public identifier",
        self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE => "Between DOCTYPE public and system identifiers",
        self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE                  => "After DOCTYPE system keyword",
        self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE              => "Before DOCTYPE system identifier",
        self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE       => "DOCTYPE system identifier (double-quoted)",
        self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE       => "DOCTYPE system identifier (single-quoted)",
        self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE               => "After DOCTYPE system identifier",
        self::BOGUS_DOCTYPE_STATE                                 => "Bogus comment",
        self::CDATA_SECTION_STATE                                 => "CDATA section",
    ];

    // Ctype constants
    const CTYPE_ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const CTYPE_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(Data $data, OpenElementsStack $stack, ParseError $errorHandler) {
        $this->state = self::DATA_STATE;
        $this->data = $data;
        $this->stack = $stack;
        $this->errorHandler = $errorHandler;
    }

    protected function keepOrDiscardAttribute(TagToken $token, TokenAttr $attribute): void {
        // See 12.2.5.33 Attribute name state

        # When the user agent leaves the attribute name state
        #   (and before emitting the tag token, if appropriate),
        #   the complete attribute's name must be compared to the
        #   other attributes on the same token; if there is already
        #   an attribute on the token with the exact same name,
        #   then this is a duplicate-attribute parse error and the
        #   new attribute must be removed from the token.


        // DEVIATION:
        // Because this implementation uses a buffer to hold the
        // attribute name it is only added if it is valid.
        // The result is the same, though.
        if ($token->hasAttribute($attribute->name)) {
            $this->error(ParseError::DUPLICATE_ATTRIBUTE, $attribute->name);
        } else {
            $token->attributes[] = $attribute;
        }
    }

    public function createToken(): Token {
        while (true) {
            if (self::$debug) {
                $state = self::STATE_NAMES[$this->state] ?? "";
                assert(strlen($state) > 0);
                echo "State: $state\n";
                unset($state);
            }

            # 12.2.5.1 Data state
            if ($this->state === self::DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Set the return state to the data state.
                    # Switch to the character reference state.

                    // DEVIATION:
                    // This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    return new CharacterToken($this->data->consumeCharacterReference());
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the tag open state.
                    $this->state = self::TAG_OPEN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit the current input character as a character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken($char);
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("&<\0"));
                }
            }

            # 12.2.5.2 RCDATA state
            elseif ($this->state === self::RCDATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Set the return state to the RCDATA state.
                    # Switch to the character reference state.

                    // DEVIATION:
                    // This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    return new CharacterToken($this->data->consumeCharacterReference());
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the RCDATA less-than sign state.
                    $this->state = self::RCDATA_LESS_THAN_SIGN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("&<\0"));
                }
            }

            # 12.2.5.3 RAWTEXT state
            elseif ($this->state === self::RAWTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the RAWTEXT less-than sign state.
                    $this->state = self::RAWTEXT_LESS_THAN_SIGN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("<\0"));
                }
            }

            # 12.2.5.4 Script data state
            elseif ($this->state === self::SCRIPT_DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the script data less-than sign state.
                    $this->state = self::SCRIPT_DATA_LESS_THAN_SIGN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("<\0"));
                }
            }

            # 12.2.5.5 PLAINTEXT state
            elseif ($this->state === self::PLAINTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0000 NULL
                if ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("\0"));
                }
            }

            # 12.2.5.6 Tag open state
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
                # ASCII alpha
                elseif (ctype_alpha($char)) {
                    # Create a new start tag token, set its tag name to the empty string.
                    # Reconsume in the tag name state.

                    // OPTIMIZATION:
                    // Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new StartTagToken(strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA)));
                    $this->state = self::TAG_NAME_STATE;
                }
                # U+003F QUESTION MARK (?)
                elseif ($char === '?') {
                    # This is an unexpected-question-mark-instead-of-tag-name parse error.
                    # Create a comment token whose data is the empty string.
                    # Reconsume in the bogus comment state.
                    $this->error(ParseError::UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME);
                    // OPTIMIZATION: Not necessary to reconsume
                    $token = new CommentToken('?');
                    $this->state = self::BOGUS_COMMENT_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-before-tag-name parse error.
                    # Emit a U+003C LESS-THAN SIGN character token and an end-of-file token.
                    $this->error(ParseError::EOF_BEFORE_TAG_NAME);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    return new CharacterToken('<');
                }
                # Anything else
                else {
                    # This is an invalid-first-character-of-tag-name parse error.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the data state.
                    $this->error(ParseError::INVALID_FIRST_CHARACTER_OF_TAG_NAME, $char);
                    // DEVIATION: unconsume and change state before emitting
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }
            }

            # 12.2.5.7 End tag open state
            elseif ($this->state === self::END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the tag name state.

                    // OPTIMIZATION:
                    // Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA)));
                    $this->state = self::TAG_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-end-tag-name parse error.
                    # Switch to the data state.
                    $this->error(ParseError::MISSING_END_TAG_NAME);
                    $this->state = self::DATA_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-before-tag-name parse error.
                    # Emit a U+003C LESS-THAN SIGN character token, a U+002F SOLIDUS character token and an end-of-file token.
                    // Making errors more expressive.
                    $this->error(ParseError::EOF_BEFORE_TAG_NAME);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    return new CharacterToken('</');
                }
                # Anything else
                else {
                   # This is an invalid-first-character-of-tag-name parse error.
                   # Create a comment token whose data is the empty string.
                   # Reconsume in the bogus comment state.
                   $this->error(ParseError::INVALID_FIRST_CHARACTER_OF_TAG_NAME, $char);
                   $token = new CommentToken();
                   $this->data->unconsume();
                   $this->state = self::BOGUS_COMMENT_STATE;
                }
            }

            # 12.2.5.8 Tag name state
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
                    assert(isset($token) && $token instanceof TagToken);
                    return $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.

                    // OPTIMIZATION:
                    // Consume all characters that are Uppercase ASCII characters to
                    // prevent having to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_UPPER));
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Append the current input character to the current tag token's tag name.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->name .= $char.$this->data->consumeUntil("\0\t\n\x0c />".self::CTYPE_UPPER);
                }
            }

            # 12.2.5.9 RCDATA less-than sign state
            elseif ($this->state === self::RCDATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the RCDATA end tag open state.
                    $temporaryBuffer = '';
                    $this->state = self::RCDATA_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the RCDATA state.
                    $this->state = self::RCDATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }
            }

            # 12.2.5.10 RCDATA end tag open state
            elseif ($this->state === self::RCDATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the RCDATA end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::RCDATA_END_TAG_NAME_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the RCDATA state.

                    $this->data->unconsume();
                    $this->state = self::RCDATA_STATE;
                    return new CharacterToken('</');
                }
            }

            # 12.2.5.11 RCDATA end tag name state
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
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        goto RCDATA_end_tag_name_state_anything_else;
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        goto RCDATA_end_tag_name_state_anything_else;
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        goto RCDATA_end_tag_name_state_anything_else;
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    assert(isset($temporaryBuffer));
                    $token->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    RCDATA_end_tag_name_state_anything_else:
                    # Emit a U+003C LESS-THAN SIGN character token,
                    #   a U+002F SOLIDUS character token, and a character
                    #   token for each of the characters in the temporary
                    #   buffer (in the order they were added to the buffer).
                    # Reconsume in the RCDATA state.
                    $this->state = self::RCDATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }
            }

            # 12.2.5.12 RAWTEXT less-than sign state
            elseif ($this->state === self::RAWTEXT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the RAWTEXT end tag open state.
                    $temporaryBuffer = '';
                    $this->state = self::RAWTEXT_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the RAWTEXT state.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }
            }

            # 12.2.5.13 RAWTEXT end tag open state
            elseif ($this->state === self::RAWTEXT_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the RAWTEXT end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::RAWTEXT_END_TAG_NAME_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the RAWTEXT state.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }
            }

            # 12.2.5.14 RAWTEXT end tag name state
            elseif ($this->state === self::RAWTEXT_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        goto RAWTEXT_end_tag_name_state_anything_else;
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the self-closing start tag state.
                    # Otherwise, treat it as per the "anything else"
                    # entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        goto RAWTEXT_end_tag_name_state_anything_else;
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the data state and emit the current tag token.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        goto RAWTEXT_end_tag_name_state_anything_else;
                    }
                }
                # ASCII upper alpha
                # ASCII lower apha
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    assert(isset($temporaryBuffer));
                    $token->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    RAWTEXT_end_tag_name_state_anything_else:
                    # Emit a U+003C LESS-THAN SIGN character token,
                    #   a U+002F SOLIDUS character token, and a character
                    #   token for each of the characters in the temporary
                    #   buffer (in the order they were added to the buffer).
                    # Reconsume in the RAWTEXT state.
                    $this->state = self::RAWTEXT_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }
            }

            # 12.2.5.15 Script data less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data end tag open state.
                    $temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_END_TAG_OPEN_STATE;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Switch to the script data escape start state.
                    # Emit a U+003C LESS-THAN SIGN character token
                    #   and a U+0021 EXCLAMATION MARK character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_STATE;
                    return new CharacterToken('<!');
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('<');
                }
            }

            # 12.2.5.16 Script data end tag open state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the script data end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::SCRIPT_DATA_END_TAG_NAME_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }
            }

            # 12.2.5.17 Script data end tag name state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        goto script_data_end_tag_name_state_anything_else;
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the self-closing start tag state.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        goto script_data_end_tag_name_state_anything_else;
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the data state and emit the current tag token.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        goto script_data_end_tag_name_state_anything_else;
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    assert(isset($temporaryBuffer));
                    $token->name .= strtolower($char.strtolower($this->data->consumeWhile(self::CTYPE_ALPHA)));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    script_data_end_tag_name_state_anything_else:
                    # Emit a U+003C LESS-THAN SIGN character token,
                    #   a U+002F SOLIDUS character token, and a character
                    #   token for each of the characters in the temporary
                    #   buffer (in the order they were added to the buffer).
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }
            }

            # 12.2.5.18 Script data escape start state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escape start dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_DASH_STATE;
                    return new CharacterToken('-');
                }
                # Anything else
                else {
                    # Switch to the script data state. Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.19 Script data escape start dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # Anything else
                else {
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.20 Script data escaped state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("-<\0"));
                }
            }

            # 12.2.5.21 Script data escaped dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken($char);
                }
            }

            # 12.2.5.22 Script data escaped dash dash state
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
                    # Switch to the script data state.
                    # Emit a U+003E GREATER-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_STATE;
                    return new CharacterToken('>');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    return new CharacterToken($char);
                }
            }

            # 12.2.5.23 Script data escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data escaped end tag open state.
                    $temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE;
                }
                # ASCII alpha
                elseif (ctype_alpha($char)) {
                    # Set the temporary buffer to the empty string.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data double escape start state.

                    // OPTIMIZATION: Avoid reconsuming
                    // Set the temporary buffer to the lowercase of the character
                    // Emit a less-than sign and the character without changing case
                    $temporaryBuffer = strtolower($char);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE;
                    return new CharacterToken('<'.$char);
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken($char);
                }
            }

            # 12.2.5.24 Script data escaped end tag open state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the script data escaped end tag name state.

                    // OPTIMIZATION: Avoid reconsuming
                    // Set the tag name to the lowercase
                    // Append the original to the temporary buffer
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer = $char;
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</');
                }
            }

            # 12.2.5.25 Script data escaped end tag name state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        goto script_data_escaped_end_tag_name_state_anything_else;
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the self-closing start tag state.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        goto script_data_escaped_end_tag_name_state_anything_else;
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the data state and emit the current tag token.
                    # Otherwise, treat it as per the "anything else" entry below.
                    assert(isset($token) && $token instanceof Token);
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        return $token;
                    } else {
                        goto script_data_escaped_end_tag_name_state_anything_else;
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.
                    # Append the current input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag
                    #   token's tag name.
                    # Append the current input character to the temporary buffer.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char .= $this->data->consumeWhile(self::CTYPE_ALPHA);
                    assert(isset($token) && $token instanceof Token);
                    assert(isset($temporaryBuffer));
                    $token->name .= strtolower($char);
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    script_data_escaped_end_tag_name_state_anything_else:
                    # Emit a U+003C LESS-THAN SIGN character token,
                    #   a U+002F SOLIDUS character token, and a character token
                    #   for each of the characters in the temporary buffer
                    #   (in the order they were added to the buffer).
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                    return new CharacterToken('</'.$temporaryBuffer);
                }
            }

            # 12.2.5.26 Script data double escape start state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0009 CHARACTER TABULATION (tab)
                # U+000A LINE FEED (LF)
                # U+000C FORM FEED (FF)
                # U+0020 SPACE
                # U+002F SOLIDUS (/)
                # U+003E GREATER-THAN SIGN (>)
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ' || $char === '/' || $char === '>') {
                    # If the temporary buffer is the string "script", 
                    #   then switch to the script data double escaped state. 
                    # Otherwise, switch to the script data escaped state. 
                    #   Emit the current input character as a character token.
                    if ($temporaryBuffer === 'script') {
                        $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                        return new CharacterToken($char);
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                if (ctype_alpha($char)) {
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the temporary buffer.
                    # Emit the current input character as a character token.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: 
                    // Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char = $char.$this->data->consumeWhile(self::CTYPE_ALPHA);
                    assert(isset($temporaryBuffer));
                    $temporaryBuffer .= strtolower($char);
                    return new CharacterToken($char);
                }
                # Anything else
                else {
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.27 Script data double escaped state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    return new CharacterToken('<');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil("-<\0"));
                }
            }

            # 12.2.5.28 Script data double escaped dash state
            elseif ($this->state == self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE;
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    return new CharacterToken('<');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data double escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    return new CharacterToken($char);
                }
            }

            # 12.2.5.29 Script data double escaped dash dash state
            elseif ($this->state == self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Emit a U+002D HYPHEN-MINUS character token.
                    return new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    return new CharacterToken('<');
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state.
                    # Emit a U+003E GREATER-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_STATE;
                    return new CharacterToken('>');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data double escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    return new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    return new CharacterToken($char);
                }
            }

            # 12.2.5.30 Script data double escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data double escape end state.
                    # Emit a U+002F SOLIDUS character token.
                    $temporaryBuffer = '';
                    $this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE;
                    return new CharacterToken('/');
                }
                # Anything else
                else {
                    # Reconsume in the script data double escaped state.
                    $this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.31 Script data double escape end state
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
                    # If the temporary buffer is the string "script",
                    #   then switch to the script data escaped state.
                    # Otherwise, switch to the script data double escaped state.
                    #   Emit the current input character as a character token.
                    if ($temporaryBuffer === 'script') {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                        return new CharacterToken($char);
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the temporary buffer.
                    # Emit the current input character as a character token.
                    # Lowercase:
                    # Append the current input character to the temporary buffer.
                    # Emit the current input character as a character token.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char = $char.$this->data->consumeWhile(self::CTYPE_ALPHA);
                    assert(isset($temporaryBuffer));
                    $temporaryBuffer .= strtolower($char);
                    return new CharacterToken($char);
                }
                # Anything else
                else {
                    # Reconsume in the script data double escaped state.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.32 Before attribute name state
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
                # ">" (U+003E)
                # EOF
                elseif ($char === '/' || $char === '>' || $char === '') {
                    # Reconsume in the after attribute name state.
                    $this->state = self::AFTER_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    # This is an unexpected-equals-sign-before-attribute-name parse error.
                    # Start a new attribute in the current tag token.
                    # Set that attribute's name to the current input character,
                    #   and its value to the empty string.
                    # Switch to the attribute name state.
                    $this->error(ParseError::UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME);
                    $attribute = new TokenAttr($char, '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                }
                # Anything else
                else {
                    # Start a new attribute in the current tag token.
                    # Set that attribute name and value to the empty string.
                    # Reconsume in the attribute name state.
                    $attribute = new TokenAttr('', '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.33 Attribute name state
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
                    # Reconsume in the after attribute name state.
                    assert(isset($token) && $token instanceof Token);
                    $this->keepOrDiscardAttribute($token, $attribute);
                    $this->data->unconsume();
                    $this->state = self::AFTER_ATTRIBUTE_NAME_STATE;
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    # Switch to the before attribute value state.
                    assert(isset($token) && $token instanceof Token);
                    $this->keepOrDiscardAttribute($token, $attribute);
                    $this->state = self::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # ASCII upper alpha
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the
                    #   current attribute's name.

                    // OPTIMIZATION:
                    // Consume all characters that are uppercase ASCII letters to prevent
                    // having to loop back through here every single time.
                    $attribute->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_UPPER));
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the current attribute's name.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $attribute->name .= "\u{FFFD}";
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                elseif ($char === '"' || $char === "'" || $char === '<') {
                    # This is an unexpected-character-in-attribute-name parse error.
                    # Treat it as per the "anything else" entry below.
                    $this->error(ParseError::UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME, $char);
                    goto attribute_name_state_anything_else;
                }
                # Anything else
                else {
                    attribute_name_state_anything_else:
                    # Append the current input character to the current attribute's name.
                    $attribute->name .= $char.$this->data->consumeUntil("\t\n\x0c /=>\0\"'<".self::CTYPE_UPPER);
                }
            }

            # 12.2.5.34 After attribute name state
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
                    # Switch to the data state.
                    # Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Start a new attribute in the current tag token.
                    # Set that attribute name and value to the empty string.
                    # Reconsume in the attribute name state.
                    $attribute = new TokenAttr('', '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.35 Before attribute value state
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
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Switch to the attribute value (single-quoted) state.
                    $this->state = self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-attribute-value parse error.
                    # Switch to the data state.
                    # Emit the current tag token.
                    $this->error(ParseError::MISSING_ATTRIBUTE_VALUE);
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Reconsume in the attribute value (unquoted) state.
                    $this->state = self::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.36 Attribute value (double-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after attribute value (quoted) state.
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (double-quoted) state.
                    # Switch to the character reference state.

                    // DEVIATION:
                    // This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference('"', true);
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the current attribute's value.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $attribute->value .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil("\"&\0");
                }
            }

            # 12.2.5.37 Attribute value (single-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0027 APOSTROPHE (')
                if ($char === "'") {
                    # Switch to the after attribute value (quoted) state.
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (single-quoted) state.
                    # Switch to the character reference state.

                    // DEVIATION:
                    // This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference("'", true);
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the current attribute's value.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $attribute->value .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil("'&\0");
                }
            }


            # 12.2.5.38 Attribute value (unquoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_UNQUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before attribute name state.
                    $this->state = self::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (unquoted) state.
                    # Switch to the character reference state.

                    // DEVIATION:
                    // This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attribute->value .= $this->data->consumeCharacterReference('>', true);
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the current attribute's value.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $attribute->value .= "\u{FFFD}";
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # "`" (U+0060)
                elseif ($char === '"' || $char === "'" || $char === '<' || $char === '=' || $char === '`') {
                    # This is an unexpected-character-in-unquoted-attribute-value parse error.
                    # Treat it as per the "anything else" entry below.
                    $this->error(ParseError::UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE, $char);
                    goto attribute_value_unquoted_state_anything_else;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    attribute_value_unquoted_state_anything_else:
                    # Append the current input character to the current attribute's value.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attribute->value .= $char.$this->data->consumeUntil("\t\n\x0c &>\0\"'<=`");
                }
            }

            # 12.2.5.39 After attribute value (quoted) state
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
                    # Switch to the data state.
                    # Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # This is a missing-whitespace-between-attributes parse error.
                    # Reconsume in the before attribute name state.
                    $this->error(ParseError::MISSING_WHITESPACE_BETWEEN_ATTRIBUTES);
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.40 Self-closing start tag state
            elseif ($this->state === self::SELF_CLOSING_START_TAG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Set the self-closing flag of the current tag token.
                    # Switch to the data state.
                    # Emit the current tag token.
                    assert(isset($token) && $token instanceof Token);
                    $token->selfClosing = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # This is an unexpected-solidus-in-tag parse error.
                    # Reconsume in the before attribute name state.
                    $this->error(ParseError::UNEXPECTED_SOLIDUS_IN_TAG);
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.44 Bogus comment state
            elseif ($this->state === self::BOGUS_COMMENT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003E GREATER-THAN SIGN (>)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # Emit the comment.
                    # Emit an end-of-file token.

                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the comment token's data.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= "\u{FFFD}";
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= $char.$this->data->consumeUntil(">\0");
                }
            }

            # 12.2.5.42 Markup declaration open state
            elseif ($this->state === self::MARKUP_DECLARATION_OPEN_STATE) {
                # If the next few characters are:

                # Two U+002D HYPHEN-MINUS characters (-)
                if ($this->data->peek(2) === '--') {
                    # Consume those two characters,
                    #   create a comment token whose data is the empty string,
                    #   and switch to the comment start state.
                    $this->data->consume(2);
                    $token = new CommentToken('');
                    $this->state = self::COMMENT_START_STATE;
                }
                //OPTIMIZATION: Peek seven characters only once
                else {
                    $peek = $this->data->peek(7);
                    # ASCII case-insensitive match for the word "DOCTYPE"
                    if (strtoupper($peek) === 'DOCTYPE') {
                        # Consume those characters and switch to the DOCTYPE state.
                        $this->data->consume(7);
                        $this->state = self::DOCTYPE_STATE;
                    }
                    # Case-sensitive match for the string "[CDATA["
                    elseif ($peek === '[CDATA[') {
                        # Consume those characters.
                        # If there is an adjusted current node and it is not an
                        #   element in the HTML namespace, then switch to the
                        #   CDATA section state.
                        # Otherwise, this is a cdata-in-html-content parse error.
                        #   Create a comment token whose data is the "[CDATA[" string.
                        #   Switch to the bogus comment state.
                        $this->data->consume(7);
                        if ($this->stack->adjustedCurrentNode && $this->stack->adjustedCurrentNode->namespace !== Parser::HTML_NAMESPACE) {
                            $this->state = self::CDATA_SECTION_STATE;
                        } else {
                            $this->error(ParseError::CDATA_IN_HTML_CONTENT);
                            $token = new CommentToken('[CDATA[');
                            $this->state = self::BOGUS_COMMENT_STATE;
                        }
                    }
                    # Anything else
                    else {
                        # This is an incorrectly-opened-comment parse error.
                        # Create a comment token whose data is the empty string.
                        # Switch to the bogus comment state
                        #   (don't consume anything in the current state).
                        $this->error(ParseError::INCORRECTLY_OPENED_COMMENT);
                        $token = new CommentToken('');
                        $this->state = self::BOGUS_COMMENT_STATE;
                    }
                }
            }

            # 12.2.5.43 Comment start state
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
                    # This is an abrupt-closing-of-empty-comment parse error.
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->error(ParseError::ABRUPT_CLOSING_OF_EMPTY_COMMENT);
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Reconsume in the comment state.
                    $this->data->unconsume();
                    $this->state = self::COMMENT_STATE;
                }
            }

            # 12.2.5.44 Comment start dash state
            elseif ($this->state === self::COMMENT_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment end state.
                    $this->state = self::COMMENT_END_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-closing-of-empty-comment parse error.
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->error(ParseError::ABRUPT_CLOSING_OF_EMPTY_COMMENT);
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Append a U+002D HYPHEN-MINUS character (-) to the comment token's data.
                    # Reconsume in the comment state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= '-';
                    $this->data->unconsume();
                    $this->state = self::COMMENT_STATE;
                }
            }

            # 12.2.5.45 Comment state
            elseif ($this->state === self::COMMENT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "<" (U+003C)
                if ($char === '<') {
                    # Append the current input character to the comment token's data.
                    # Switch to the comment less-than sign state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= $char;
                    $this->state = self::COMMENT_LESS_THAN_SIGN_STATE;
                }
                # "-" (U+002D)
                elseif ($char === '-') {
                    # Switch to the comment end dash state
                    $this->state = self::COMMENT_END_DASH_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the comment token's data.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= $char.$this->data->consumeUntil("<-\0");
                }
            }

            # 12.2.5.46 Comment less-than sign state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0021 EXCLAMATION MARK (!)
                if ($char === '!') {
                    # Append the current input character to the comment token's data.
                    # Switch to the comment less-than sign bang state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= $char;
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_STATE;
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char ==='<') {
                    # Append the current input character to the comment token's data.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= $char;
                }
                # Anything else
                else {
                    # Reconsume in the comment state
                    $this->state = self::COMMENT_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.47 Comment less-than sign bang state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+002D HYPHEN-MINUS (-)
                if ($char === '-') {
                    # Switch to the comment less-than sign bang dash state.
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE;
                }
                # Anything else
                else {
                    # Reconsume in the comment state
                    $this->state = self::COMMENT_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.48 Comment less-than sign bang dash state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+002D HYPHEN-MINUS (-)
                if ($char === '-') {
                    # Switch to the comment less-than sign bang dash dash state.
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE;
                }
                # Anything else
                else {
                    # Reconsume in the comment end dash state
                    $this->state = self::COMMENT_END_DASH_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.49 Comment less-than sign bang dash dash state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003E GREATER-THAN SIGN (>)
                # EOF
                if ($char === '>' || $char === '') {
                    # Reconsume in the comment end state.
                    $this->state = self::COMMENT_END_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # This is a nested-comment parse error.
                    # Reconsume in the comment end state.
                    $this->error(ParseError::NESTED_COMMENT);
                    $this->state = self::COMMENT_END_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.50 Comment end dash state
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
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Append a "-" (U+002D) character to the comment token's data.
                    # Reconsume in the comment state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= '-';
                    $this->state = self::COMMENT_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.50 Comment end state
            elseif ($this->state === self::COMMENT_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Switch to the comment end bang state.
                    $this->state = self::COMMENT_END_BANG_STATE;
                }
                # "-" (U+002D)
                elseif ($char === '-') {
                    # Append a U+002D HYPHEN-MINUS character (-) to the comment token's data.

                    // OPTIMIZATION:
                    // Consume all '-' characters to prevent having to loop back through
                    // here every single time.
                    $token->data .= $char.$this->data->consumeWhile('-');
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Append two U+002D HYPHEN-MINUS characters (-) to the comment token's data.
                    # Reconsume in the comment state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= '--'.$char;
                    $this->state = self::COMMENT_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.52 Comment end bang state
            elseif ($this->state === self::COMMENT_END_BANG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Append two U+002D HYPHEN-MINUS characters (-)
                    #   and a U+0021 EXCLAMATION MARK character (!)
                    #   to the comment token's data.
                    # Switch to the comment end dash state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= '--!';
                    $this->state = self::COMMENT_END_DASH_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an incorrectly-closed-comment parse error.
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->error(ParseError::INCORRECTLY_CLOSED_COMMENT);
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # Append two U+002D HYPHEN-MINUS characters (-)
                    #   and a U+0021 EXCLAMATION MARK character (!)
                    #   to the comment token's data.
                    # Reconsume in the comment state.
                    assert(isset($token) && $token instanceof Token);
                    $token->data .= '--!'.$char;
                    $this->state = self::COMMENT_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.53 DOCTYPE state
            elseif ($this->state === self::DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE name state.
                    $this->state = self::DOCTYPE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Create a new DOCTYPE token.
                    # Set its force-quirks flag to on.
                    # Emit the token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-whitespace-before-doctype-name parse error.
                    # Reconsume in the before DOCTYPE name state.
                    $this->error(ParseError::MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME);
                    $this->state = self::DOCTYPE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.54 Before DOCTYPE name state
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
                // See below for ASCII upper alpha
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Create a new DOCTYPE token.
                    # Set the token's name to a U+FFFD REPLACEMENT CHARACTER character.
                    # Switch to the DOCTYPE name state.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $token = new DOCTYPEToken("\u{FFFD}");
                    $this->state = self::DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-doctype-name parse error.
                    # Create a new DOCTYPE token.
                    # Set its force-quirks flag to on.
                    # Switch to the data state.
                    # Emit the token.
                    $this->error(ParseError::MISSING_DOCTYPE_NAME);
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Create a new DOCTYPE token.
                    # Set its force-quirks flag to on.
                    # Emit the token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # ASCII upper alpha
                # Anything else
                else {
                    # Create a new DOCTYPE token.
                    # Set the token's name to the current input character.
                    # Switch to the DOCTYPE name state.

                    // OPTIMIZATION: Also handle ASCII upper alpha
                    // OPTIMIZATION: Consume characters not explicitly handled by the "DOCTYPE name" state
                    $token = new DOCTYPEToken(strtolower($char.$this->data->consumeUntil("\t\n\x0c >\0")));
                    $this->state = self::DOCTYPE_NAME_STATE;
                }
            }

            # 12.2.5.55 DOCTYPE name state
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
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                // See below for ASCII upper alpha
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's name.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->name .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # ASCII upper alpha
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's name.

                    // OPTIMIZATION: Also handle ASCII upper alpha
                    // OPTIMIZATION: 
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->name .= strtolower($char.$this->data->consumeUntil("\t\n\x0c >\0"));
                }
            }

            # 12.2.5.56 After DOCTYPE name state
            elseif ($this->state === self::AFTER_DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. 
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    // OPTIMIZATION: Peek only once; we peek because consuming could alter the order of errors
                    $peek = strtoupper($char.$this->data->peek(5));
                    # If the six characters starting from the current input 
                    #   character are an ASCII case-insensitive match for the 
                    #   word "PUBLIC", then consume those characters and 
                    #   switch to the after DOCTYPE public keyword state.
                    if($peek === 'PUBLIC') {
                        $this->data->consume(5);
                        $this->state = self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE;
                    }
                    # Otherwise, if the six characters starting from the current input 
                    #   character are an ASCII case-insensitive match for the 
                    #   word "SYSTEM", then consume those characters and 
                    #   switch to the after DOCTYPE system keyword state.
                    elseif ($peek === 'SYSTEM') {
                        $this->data->consume(5);
                        $this->state = self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE;
                    }
                    # Otherwise, this is an 
                    #   invalid-character-sequence-after-doctype-name 
                    #   parse error. 
                    # Set the DOCTYPE token's force-quirks flag to on. 
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.57 After DOCTYPE public keyword state
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
                    # This is a missing-whitespace-after-doctype-public-keyword parse error. 
                    # Set the DOCTYPE token's public identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE public identifier (double-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD);
                    assert(isset($token) && $token instanceof Token);
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # This is a missing-whitespace-after-doctype-public-keyword parse error. 
                    # Set the DOCTYPE token's public identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE public identifier (single-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD);
                    assert(isset($token) && $token instanceof Token);
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::MISSING_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.58 Before DOCTYPE public identifier state
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
                    #   then switch to the DOCTYPE public identifier (double-quoted) state.
                    assert(isset($token) && $token instanceof Token);
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE public identifier (single-quoted) state.
                    assert(isset($token) && $token instanceof Token);
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::MISSING_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.59 DOCTYPE public identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->state = self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's public identifier.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->public .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the 
                    #   current DOCTYPE token's public identifier.

                    // OPTIMIZATION: 
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->public .= $char.$this->data->consumeUntil("\">\0");
                }
            }

            # 12.2.5.60 DOCTYPE public identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->state = self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's public identifier.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->public .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the 
                    #   current DOCTYPE token's public identifier.

                    // OPTIMIZATION: 
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->public .= $char.$this->data->consumeUntil("'>\0");
                }
            }

            # 12.2.5.60 After DOCTYPE public identifier state
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
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # This is a missing-whitespace-between-doctype-public-and-system-identifiers parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS);
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # This is a missing-whitespace-between-doctype-public-and-system-identifiers parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS);
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.62 Between DOCTYPE public and system identifiers state
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
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the 
                    #   empty string (not missing), then switch to the 
                    #   DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the 
                    #   empty string (not missing), then switch to the 
                    #   DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                }
            }

            # 12.2.5.63 After DOCTYPE system keyword state
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
                    # This is a missing-whitespace-after-doctype-system-keyword parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD);
                    assert(isset($token) && $token instanceof Token);
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # This is a missing-whitespace-after-doctype-system-keyword parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing), 
                    #   then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD);
                    assert(isset($token) && $token instanceof Token);
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::MISSING_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.64 Before DOCTYPE system identifier state
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
                    # Set the DOCTYPE token's system identifier to the 
                    #   empty string (not missing), then switch to the 
                    #   DOCTYPE system identifier (double-quoted) state.
                    assert(isset($token) && $token instanceof Token);
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the 
                    #   empty string (not missing), then switch to the 
                    #   DOCTYPE system identifier (single-quoted) state.
                    assert(isset($token) && $token instanceof Token);
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is a missing-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::MISSING_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.64 DOCTYPE system identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->state = self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's system identifier.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->system .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->system .= $char.$this->data->consumeUntil("\"\0>");
                }
            }

            # 12.2.5.66 DOCTYPE system identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->state = self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's system identifier.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    assert(isset($token) && $token instanceof Token);
                    $token->system .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    assert(isset($token) && $token instanceof Token);
                    $token->system .= $char.$this->data->consumeUntil("'\0>");
                }
            }

            # 12.2.5.67 After DOCTYPE system identifier state
            elseif ($this->state === self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    assert(isset($token) && $token instanceof Token);
                    $token->forceQuirks = true;
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    return $token;
                }
                # Anything else
                else {
                    # This is an unexpected-character-after-doctype-system-identifier parse error.
                    # Reconsume in the bogus DOCTYPE state.
                    # (This does not set the DOCTYPE token's force-quirks flag to on.)
                    $this->error(ParseError::UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER, $char);
                    assert(isset($token) && $token instanceof Token);
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    $this->data->unconsume();
                }
            }

            # 12.2.5.67 Bogus DOCTYPE state
            elseif ($this->state === self::BOGUS_DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Ignore the character.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                }
                # EOF
                elseif ($char === '') {
                    # Emit the DOCTYPE token.
                    # Emit an end-of-file token.
                    
                    // DEVIATION:
                    // We cannot emit two tokens, so we switch to
                    // the data state, which will emit the EOF token
                    $this->state = self::DATA_STATE;
                    $this->data->unconsume();
                    assert(isset($token) && $token instanceof Token);
                    return $token;
                }
                # Anything else
                # Ignore the character.
            }

            # 12.2.5.69 CDATA section state
            elseif ($this->state === self::CDATA_SECTION_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+005D RIGHT SQUARE BRACKET (])
                if ($char === ']') {
                    # Switch to the CDATA section bracket state.
                    $this->state = self::CDATA_SECTION_BRACKET_STATE;
                }
                # EOF
                elseif ($char ==='') {
                    # This is an eof-in-cdata parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_CDATA);
                    return new EOFToken;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    return new CharacterToken($char.$this->data->consumeUntil(']'));
                }
            }

            # 12.2.5.70 CDATA section bracket state
            elseif ($this->state === self::CDATA_SECTION_BRACKET_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+005D RIGHT SQUARE BRACKET (])
                if ($char === ']') {
                    # Switch to the CDATA section end state.
                    $this->state = self::CDATA_SECTION_END_STATE;
                }
                # Anything else
                else {
                    # Emit a U+005D RIGHT SQUARE BRACKET character token.
                    # Reconsume in the CDATA section state.
                    $this->state = self::CDATA_SECTION_STATE;
                    // OPTIMIZATION: Not necessary to reconsume
                    return new CharacterToken(']'.$char);
                }
            }

            # 12.2.5.71 CDATA section end state
            elseif ($this->state === self::CDATA_SECTION_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+005D RIGHT SQUARE BRACKET (])
                if ($char === ']') {
                    # Emit a U+005D RIGHT SQUARE BRACKET character token.
                    
                    // OTPIMIZATION: Consume any additional right square brackets
                    return new CharacterToken($char.$this->data->consumeWhile(']'));
                }
                # U+003E GREATER-THAN SIGN character
                elseif ($char === '>') {
                    # Switch to the data state.
                    $this->state = self::DATA_STATE;
                }
                # Anything else
                else {
                    # Emit two U+005D RIGHT SQUARE BRACKET character tokens.
                    # Reconsume in the CDATA section state.
                    $this->state = self::CDATA_SECTION_STATE;
                    // OPTIMIZATION: Not necessary to reconsume
                    return new CharacterToken(']'.$char);
                }
            } 
            
            # Not a valid state 
            else {
                throw new \Exception("Tokenizer state: ".$this->state);
            }
        }
    }
}
