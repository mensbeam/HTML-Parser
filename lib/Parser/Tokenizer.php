<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;
use MensBeam\Intl\Encoding\UTF8;

class Tokenizer {
    use ParseErrorEmitter;

    public $state;
    public $debugLog = '';
    public $debugCount = 0;

    protected $data;
    protected $stack;
    protected $temporaryBuffer = "";

    const DATA_STATE = 1;
    const RCDATA_STATE = 2;
    const RAWTEXT_STATE = 3;
    const SCRIPT_DATA_STATE = 4;
    const PLAINTEXT_STATE = 5;
    const TAG_OPEN_STATE = 6;
    const END_TAG_OPEN_STATE = 7;
    const TAG_NAME_STATE = 8;
    const RCDATA_LESS_THAN_SIGN_STATE = 9;
    const RCDATA_END_TAG_OPEN_STATE = 10;
    const RCDATA_END_TAG_NAME_STATE = 11;
    const RAWTEXT_LESS_THAN_SIGN_STATE = 12;
    const RAWTEXT_END_TAG_OPEN_STATE = 13;
    const RAWTEXT_END_TAG_NAME_STATE = 14;
    const SCRIPT_DATA_LESS_THAN_SIGN_STATE = 15;
    const SCRIPT_DATA_END_TAG_OPEN_STATE = 16;
    const SCRIPT_DATA_END_TAG_NAME_STATE = 17;
    const SCRIPT_DATA_ESCAPE_START_STATE = 18;
    const SCRIPT_DATA_ESCAPE_START_DASH_STATE = 19;
    const SCRIPT_DATA_ESCAPED_STATE = 20;
    const SCRIPT_DATA_ESCAPED_DASH_STATE = 21;
    const SCRIPT_DATA_ESCAPED_DASH_DASH_STATE = 22;
    const SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE = 23;
    const SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE = 24;
    const SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE = 25;
    const SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE = 26;
    const SCRIPT_DATA_DOUBLE_ESCAPED_STATE = 27;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE = 28;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE = 29;
    const SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE = 30;
    const SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE = 31;
    const BEFORE_ATTRIBUTE_NAME_STATE = 32;
    const ATTRIBUTE_NAME_STATE = 33;
    const AFTER_ATTRIBUTE_NAME_STATE = 34;
    const BEFORE_ATTRIBUTE_VALUE_STATE = 35;
    const ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE = 36;
    const ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE = 37;
    const ATTRIBUTE_VALUE_UNQUOTED_STATE = 38;
    const AFTER_ATTRIBUTE_VALUE_QUOTED_STATE = 39;
    const SELF_CLOSING_START_TAG_STATE = 40;
    const BOGUS_COMMENT_STATE = 41;
    const MARKUP_DECLARATION_OPEN_STATE = 42;
    const COMMENT_START_STATE = 43;
    const COMMENT_START_DASH_STATE = 44;
    const COMMENT_STATE = 45;
    const COMMENT_LESS_THAN_SIGN_STATE = 46;
    const COMMENT_LESS_THAN_SIGN_BANG_STATE = 47;
    const COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE = 48;
    const COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE = 49;
    const COMMENT_END_DASH_STATE = 50;
    const COMMENT_END_STATE = 51;
    const COMMENT_END_BANG_STATE = 52;
    const DOCTYPE_STATE = 53;
    const BEFORE_DOCTYPE_NAME_STATE = 54;
    const DOCTYPE_NAME_STATE = 55;
    const AFTER_DOCTYPE_NAME_STATE = 56;
    const AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE = 57;
    const BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 58;
    const DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE = 59;
    const DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE = 60;
    const AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 61;
    const BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE = 62;
    const AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE = 63;
    const BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 64;
    const DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE = 65;
    const DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE = 66;
    const AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 67;
    const BOGUS_DOCTYPE_STATE = 68;
    const CDATA_SECTION_STATE = 69;
    const CDATA_SECTION_BRACKET_STATE = 70;
    const CDATA_SECTION_END_STATE = 71;
    const CHARACTER_REFERENCE_STATE = 72;
    const NAMED_CHARACTER_REFERENCE_STATE = 73;
    const AMBIGUOUS_AMPERSAND_STATE = 74;
    const NUMERIC_CHARACTER_REFERENCE_STATE = 75;
    const HEXADECIMAL_CHARACTER_REFERENCE_START_STATE = 76;
    const DECIMAL_CHARACTER_REFERENCE_START_STATE = 77;
    const HEXADECIMAL_CHARACTER_REFERENCE_STATE = 78;
    const DECIMAL_CHARACTER_REFERENCE_STATE = 79;
    const NUMERIC_CHARACTER_REFERENCE_END_STATE = 80;

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
        self::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE         => "Comment less-than sign bang dash dash",
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
        self::BOGUS_DOCTYPE_STATE                                 => "Bogus DOCTYPE",
        self::CDATA_SECTION_STATE                                 => "CDATA section",
        self::CDATA_SECTION_BRACKET_STATE                         => "CDATA section bracket",
        self::CDATA_SECTION_END_STATE                             => "CDATA section end",
        self::CHARACTER_REFERENCE_STATE                           => "Character reference",
        self::NAMED_CHARACTER_REFERENCE_STATE                     => "Named character reference",
        self::AMBIGUOUS_AMPERSAND_STATE                           => "Ambiguous ampersand",
        self::NUMERIC_CHARACTER_REFERENCE_STATE                   => "Numeric character reference",
        self::HEXADECIMAL_CHARACTER_REFERENCE_START_STATE         => "Hexadecimal character reference start",
        self::DECIMAL_CHARACTER_REFERENCE_START_STATE             => "Decimal character reference start",
        self::HEXADECIMAL_CHARACTER_REFERENCE_STATE               => "Hexadecimal character reference",
        self::DECIMAL_CHARACTER_REFERENCE_STATE                   => "Decimal character reference",
        self::NUMERIC_CHARACTER_REFERENCE_END_STATE               => "Numeric character reference",
    ];

    const ATTRIBUTE_VALUE_STATE_SET = [
        # A character reference is said to be consumed as part of an attribute
        #   if the return state is either attribute value (double-quoted) state,
        #   attribute value (single-quoted) state or attribute value (unquoted) state.
        self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE,
        self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE,
        self::ATTRIBUTE_VALUE_UNQUOTED_STATE
    ];

    // Ctype constants
    const CTYPE_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CTYPE_ALPHA = self::CTYPE_UPPER.'abcdefghijklmnopqrstuvwxyz';
    const CTYPE_NUM   = '0123456789';
    const CTYPE_ALNUM = self::CTYPE_ALPHA.self::CTYPE_NUM;
    const CTYPE_HEX   = self::CTYPE_NUM.'ABCDEFabcdef';

    public function __construct(Data $data, OpenElementsStack $stack, ?ParseError $errorHandler) {
        $this->state = self::DATA_STATE;
        $this->data = $data;
        $this->stack = $stack;
        $this->errorHandler = $errorHandler;
    }

    protected function sanitizeTag(TagToken $token): void {
        if ($token instanceof EndTagToken) {
            # When an end tag token is emitted with attributes,
            #   that is an end-tag-with-attributes parse error.
            if ($token->attributes) {
                $this->error(ParseError::END_TAG_WITH_ATTRIBUTES);
                $token->attributes = [];
            }
            # When an end tag token is emitted with its self-closing
            #   flag set, that is an end-tag-with-trailing-solidus parse error.
            if ($token->selfClosing) {
                $this->error(ParseError::END_TAG_WITH_TRAILING_SOLIDUS);
                $token->selfClosing = false;
            }
        }

    }

    protected function keepOrDiscardAttribute(TagToken $token, TokenAttr $attribute): void {
        // See 13.2.5.33 Attribute name state

        # When the user agent leaves the attribute name state
        #   (and before emitting the tag token, if appropriate),
        #   the complete attribute's name must be compared to the
        #   other attributes on the same token; if there is already
        #   an attribute on the token with the exact same name,
        #   then this is a duplicate-attribute parse error and the
        #   new attribute must be removed from the token.
        if ($token->hasAttribute($attribute->name)) {
            $this->error(ParseError::DUPLICATE_ATTRIBUTE, $attribute->name);
        } else {
            $token->attributes[] = $attribute;
        }
    }

    public function tokenize(): \Generator {
        Consume:
        assert((function() {
            $this->debugLog .= "TOKEN ".++$this->debugCount."\n";
            return true;
        })());

        while (true) {
            // OPTIMIZATION: All but one state consumes; we instead do so
            //   here unless the state is the exception; this allows us to
            //   reconsume more efficiently when needed
            if ($this->state !== self::MARKUP_DECLARATION_OPEN_STATE) {
                $char = $this->data->consume();
            }
            Reconsume:

            assert((function() use ($char) {
                $state = self::STATE_NAMES[$this->state] ?? $this->state;
                $this->debugLog .= "    State: $state ($char)\n";
                return true;
            })());

            # 13.2.5.1 Data state
            if ($this->state === self::DATA_STATE) {
                # Consume the next input character

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Set the return state to the data state.
                    # Switch to the character reference state.

                    // DEVIATION: Character reference consumption implemented as a function
                    $outChar = $this->switchToCharacterReferenceState(self::DATA_STATE);
                    if (strspn($outChar, Data::WHITESPACE)) {
                        yield new WhitespaceToken($outChar); // a character reference is either all whitespace is no whitespace
                    } else {
                        yield new CharacterToken($outChar);
                    }
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
                    yield new NullCharacterToken($char);
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("&<\0"));
                    }
                }
            }

            # 13.2.5.2 RCDATA state
            elseif ($this->state === self::RCDATA_STATE) {
                # Consume the next input character

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Set the return state to the RCDATA state.
                    # Switch to the character reference state.

                    // DEVIATION: Character reference consumption implemented as a function
                    $outChar = $this->switchToCharacterReferenceState(self::RCDATA_STATE);
                    if (strspn($outChar, Data::WHITESPACE)) {
                        yield new WhitespaceToken($outChar); // a character reference is either all whitespace is no whitespace
                    } else {
                        yield new CharacterToken($outChar);
                    }
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
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("&<\0"));
                    }
                }
            }

            # 13.2.5.3 RAWTEXT state
            elseif ($this->state === self::RAWTEXT_STATE) {
                # Consume the next input character

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
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("<\0"));
                    }
                }
            }

            # 13.2.5.4 Script data state
            elseif ($this->state === self::SCRIPT_DATA_STATE) {
                # Consume the next input character

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
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("<\0"));
                    }
                }
            }

            # 13.2.5.5 PLAINTEXT state
            elseif ($this->state === self::PLAINTEXT_STATE) {
                # Consume the next input character

                # U+0000 NULL
                if ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back
                    // through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("\0"));
                    }
                }
            }

            # 13.2.5.6 Tag open state
            elseif ($this->state === self::TAG_OPEN_STATE) {
                # Consume the next input character

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
                    $token = new ProcessingInstructionToken("");
                    $this->state = self::BOGUS_COMMENT_STATE;
                    goto Reconsume;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-before-tag-name parse error.
                    # Emit a U+003C LESS-THAN SIGN character token and an end-of-file token.
                    $this->error(ParseError::EOF_BEFORE_TAG_NAME);
                    yield new CharacterToken('<');
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is an invalid-first-character-of-tag-name parse error.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the data state.
                    $this->error(ParseError::INVALID_FIRST_CHARACTER_OF_TAG_NAME, $char);
                    $this->state = self::DATA_STATE;
                    yield new CharacterToken('<');
                    goto Reconsume;
                }
            }

            # 13.2.5.7 End tag open state
            elseif ($this->state === self::END_TAG_OPEN_STATE) {
                # Consume the next input character

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
                    yield new CharacterToken('</');
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                   # This is an invalid-first-character-of-tag-name parse error.
                   # Create a comment token whose data is the empty string.
                   # Reconsume in the bogus comment state.
                   $this->error(ParseError::INVALID_FIRST_CHARACTER_OF_TAG_NAME, $char);
                   $token = new CommentToken();
                   $this->state = self::BOGUS_COMMENT_STATE;
                   goto Reconsume;
                }
            }

            # 13.2.5.8 Tag name state
            elseif ($this->state === self::TAG_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
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
                    $this->sanitizeTag($token);
                    yield $token;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the current
                    #   tag token's tag name.

                    // OPTIMIZATION:
                    // Consume all characters that are Uppercase ASCII characters to
                    // prevent having to loop back through here every single time.
                    $token->name .= strtolower($char.$this->data->consumeWhile(self::CTYPE_UPPER));
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to
                    #   the current tag token's tag name.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $token->name .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the current tag token's tag name.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name .= $char.$this->data->consumeUntil("\0\t\n\x0c />".self::CTYPE_UPPER);
                }
            }

            # 13.2.5.9 RCDATA less-than sign state
            elseif ($this->state === self::RCDATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the RCDATA end tag open state.
                    $this->temporaryBuffer = '';
                    $this->state = self::RCDATA_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the RCDATA state.
                    $this->state = self::RCDATA_STATE;
                    yield new CharacterToken('<');
                    goto Reconsume;
                }
            }

            # 13.2.5.10 RCDATA end tag open state
            elseif ($this->state === self::RCDATA_END_TAG_OPEN_STATE) {
                # Consume the next input character

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the RCDATA end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::RCDATA_END_TAG_NAME_STATE;
                    goto Reconsume;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the RCDATA state.
                    $this->state = self::RCDATA_STATE;
                    yield new CharacterToken('</');
                    goto Reconsume;
                }
            }

            # 13.2.5.11 RCDATA end tag name state
            elseif ($this->state === self::RCDATA_END_TAG_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
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
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        $this->sanitizeTag($token);
                        yield $token;
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
                    $char .= $this->data->consumeWhile(self::CTYPE_ALPHA);
                    $token->name .= strtolower($char);
                    $this->temporaryBuffer .= $char;
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
                    yield new CharacterToken('</'.$this->temporaryBuffer);
                    goto Reconsume;
                }
            }

            # 13.2.5.12 RAWTEXT less-than sign state
            elseif ($this->state === self::RAWTEXT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the RAWTEXT end tag open state.
                    $this->temporaryBuffer = '';
                    $this->state = self::RAWTEXT_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the RAWTEXT state.
                    $this->state = self::RAWTEXT_STATE;
                    yield new CharacterToken('<');
                    goto Reconsume;
                }
            }

            # 13.2.5.13 RAWTEXT end tag open state
            elseif ($this->state === self::RAWTEXT_END_TAG_OPEN_STATE) {
                # Consume the next input character

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the RAWTEXT end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::RAWTEXT_END_TAG_NAME_STATE;
                    goto Reconsume;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the RAWTEXT state.
                    $this->state = self::RAWTEXT_STATE;
                    yield new CharacterToken('</');
                    goto Reconsume;
                }
            }

            # 13.2.5.14 RAWTEXT end tag name state
            elseif ($this->state === self::RAWTEXT_END_TAG_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
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
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        $this->sanitizeTag($token);
                        yield $token;
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
                    $char .= $this->data->consumeWhile(self::CTYPE_ALPHA);
                    $token->name .= strtolower($char);
                    $this->temporaryBuffer .= $char;
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
                    yield new CharacterToken('</'.$this->temporaryBuffer);
                    goto Reconsume;
                }
            }

            # 13.2.5.15 Script data less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data end tag open state.
                    $this->temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_END_TAG_OPEN_STATE;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Switch to the script data escape start state.
                    # Emit a U+003C LESS-THAN SIGN character token
                    #   and a U+0021 EXCLAMATION MARK character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_STATE;
                    yield new CharacterToken('<!');
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    yield new CharacterToken('<');
                    goto Reconsume;
                }
            }

            # 13.2.5.16 Script data end tag open state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_OPEN_STATE) {
                # Consume the next input character

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the script data end tag name state.
                    $token = new EndTagToken("");
                    $this->state = self::SCRIPT_DATA_END_TAG_NAME_STATE;
                    goto Reconsume;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    yield new CharacterToken('</');
                    goto Reconsume;
                }
            }

            # 13.2.5.17 Script data end tag name state
            elseif ($this->state === self::SCRIPT_DATA_END_TAG_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
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
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        $this->sanitizeTag($token);
                        yield $token;
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
                    $char = $char.$this->data->consumeWhile(self::CTYPE_ALPHA);
                    $token->name .= strtolower($char);
                    $this->temporaryBuffer .= $char;
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
                    yield new CharacterToken('</'.$this->temporaryBuffer);
                    goto Reconsume;
                }
            }

            # 13.2.5.18 Script data escape start state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escape start dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPE_START_DASH_STATE;
                    yield new CharacterToken('-');
                }
                # Anything else
                else {
                    # Switch to the script data state.
                    # Reconsume the current input character.
                    $this->state = self::SCRIPT_DATA_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.19 Script data escape start dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPE_START_DASH_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    yield new CharacterToken('-');
                }
                # Anything else
                else {
                    # Reconsume in the script data state.
                    $this->state = self::SCRIPT_DATA_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.20 Script data escaped state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_STATE;
                    yield new CharacterToken('-');
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
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("-<\0"));
                    }
                }
            }

            # 13.2.5.21 Script data escaped dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_DASH_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    yield new CharacterToken('-');
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
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
                    }
                }
            }

            # 13.2.5.22 Script data escaped dash dash state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Emit a U+002D HYPHEN-MINUS character token.
                    yield new CharacterToken('-');
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
                    yield new CharacterToken('>');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
                    }
                }
            }

            # 13.2.5.23 Script data escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data escaped end tag open state.
                    $this->temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE;
                }
                # ASCII alpha
                elseif (ctype_alpha($char)) {
                    # Set the temporary buffer to the empty string.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data double escape start state.

                    $this->temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE;
                    yield new CharacterToken('<');
                    goto Reconsume;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    yield new CharacterToken("<");
                    goto Reconsume;
                }
            }

            # 13.2.5.24 Script data escaped end tag open state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE) {
                # Consume the next input character

                # ASCII alpha
                if (ctype_alpha($char)) {
                    # Create a new end tag token, set its tag name to the empty string.
                    # Reconsume in the script data escaped end tag name state.

                    // OPTIMIZATION: Avoid reconsuming
                    // Set the tag name to the lowercase
                    // Append the original to the temporary buffer
                    $token = new EndTagToken(strtolower($char));
                    $this->temporaryBuffer = $char;
                    $this->state = self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Emit a U+003C LESS-THAN SIGN character token and a U+002F SOLIDUS character token.
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    yield new CharacterToken('</');
                    goto Reconsume;
                }
            }

            # 13.2.5.25 Script data escaped end tag name state
            elseif ($this->state === self::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # If the current end tag token is an appropriate end tag token,
                    #   then switch to the before attribute name state.
                    # Otherwise, treat it as per the "anything else" entry below.
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
                    if ($token->name === $this->stack->currentNodeName) {
                        $this->state = self::DATA_STATE;
                        $this->sanitizeTag($token);
                        yield $token;
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
                    $token->name .= strtolower($char);
                    $this->temporaryBuffer .= $char;
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
                    yield new CharacterToken('</'.$this->temporaryBuffer);
                    goto Reconsume;
                }
            }

            # 13.2.5.26 Script data double escape start state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE) {
                # Consume the next input character

                # U+0009 CHARACTER TABULATION (tab)
                # U+000A LINE FEED (LF)
                # U+000C FORM FEED (FF)
                # U+0020 SPACE
                # U+002F SOLIDUS (/)
                # U+003E GREATER-THAN SIGN (>)
                if (strspn($char, " />\t\n\x0C")) {
                    # If the temporary buffer is the string "script",
                    #   then switch to the script data double escaped state.
                    # Otherwise, switch to the script data escaped state.
                    #   Emit the current input character as a character token.
                    if ($this->temporaryBuffer === 'script') {
                        $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    }
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
                    }
                }
                # ASCII upper alpha
                # ASCII lower alpha
                elseif (ctype_alpha($char)) {
                    # Append the lowercase version of the current input character
                    #   (add 0x0020 to the character's code point) to the temporary buffer.
                    # Emit the current input character as a character token.

                    // OPTIMIZATION: Combine upper and lower alpha
                    // OPTIMIZATION:
                    // Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char = $char.$this->data->consumeWhile(self::CTYPE_ALPHA);
                    $this->temporaryBuffer .= strtolower($char);
                    yield new CharacterToken($char);
                }
                # Anything else
                else {
                    # Reconsume in the script data escaped state.
                    $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.27 Script data double escaped state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE;
                    yield new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    yield new CharacterToken('<');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("-<\0"));
                    }
                }
            }

            # 13.2.5.28 Script data double escaped dash state
            elseif ($this->state == self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash dash state.
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE;
                    yield new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    yield new CharacterToken('<');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data double escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
                    }
                }
            }

            # 13.2.5.29 Script data double escaped dash dash state
            elseif ($this->state == self::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Emit a U+002D HYPHEN-MINUS character token.
                    yield new CharacterToken('-');
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state.
                    # Emit a U+003C LESS-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    yield new CharacterToken('<');
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state.
                    # Emit a U+003E GREATER-THAN SIGN character token.
                    $this->state = self::SCRIPT_DATA_STATE;
                    yield new CharacterToken('>');
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Switch to the script data double escaped state.
                    # Emit a U+FFFD REPLACEMENT CHARACTER character token.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    yield new CharacterToken("\u{FFFD}");
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-script-html-comment-like-text parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state.
                    # Emit the current input character as a character token.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
                    }
                }
            }

            # 13.2.5.30 Script data double escaped less-than sign state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string.
                    # Switch to the script data double escape end state.
                    # Emit a U+002F SOLIDUS character token.
                    $this->temporaryBuffer = '';
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE;
                    yield new CharacterToken('/');
                }
                # Anything else
                else {
                    # Reconsume in the script data double escaped state.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.31 Script data double escape end state
            elseif ($this->state === self::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                # "/" (U+002F)
                # ">" (U+003E)
                if (strspn($char, " />\t\n\x0C")) {
                    # If the temporary buffer is the string "script",
                    #   then switch to the script data escaped state.
                    # Otherwise, switch to the script data double escaped state.
                    #   Emit the current input character as a character token.
                    if ($this->temporaryBuffer === 'script') {
                        $this->state = self::SCRIPT_DATA_ESCAPED_STATE;
                    } else {
                        $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    }
                    if (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char);
                    } else {
                        yield new CharacterToken($char);
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
                    $this->temporaryBuffer .= strtolower($char);
                    yield new CharacterToken($char);
                }
                # Anything else
                else {
                    # Reconsume in the script data double escaped state.
                    $this->state = self::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.32 Before attribute name state
            elseif ($this->state === self::BEFORE_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # Ignore the character.
                }
                # "/" (U+002F)
                # ">" (U+003E)
                # EOF
                elseif ($char === '/' || $char === '>' || $char === '') {
                    # Reconsume in the after attribute name state.
                    $this->state = self::AFTER_ATTRIBUTE_NAME_STATE;
                    goto Reconsume;
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
                    goto Reconsume;
                }
            }

            # 13.2.5.33 Attribute name state
            elseif ($this->state === self::ATTRIBUTE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                # "/" (U+002F)
                # U+003E GREATER-THAN SIGN (>)
                # EOF
                if (strspn($char, " />\t\n\x0C") || $char === '') {
                    # Reconsume in the after attribute name state.
                    $this->keepOrDiscardAttribute($token, $attribute);
                    $this->state = self::AFTER_ATTRIBUTE_NAME_STATE;
                    goto Reconsume;
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    # Switch to the before attribute value state.
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

            # 13.2.5.34 After attribute name state
            elseif ($this->state === self::AFTER_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
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
                    $this->sanitizeTag($token);
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Start a new attribute in the current tag token.
                    # Set that attribute name and value to the empty string.
                    # Reconsume in the attribute name state.
                    $attribute = new TokenAttr('', '');
                    $this->state = self::ATTRIBUTE_NAME_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.35 Before attribute value state
            elseif ($this->state === self::BEFORE_ATTRIBUTE_VALUE_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
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
                    $this->sanitizeTag($token);
                    yield $token;
                }
                # Anything else
                else {
                    # Reconsume in the attribute value (unquoted) state.
                    $this->state = self::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.36 Attribute value (double-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE) {
                # Consume the next input character

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after attribute value (quoted) state.
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (double-quoted) state.
                    # Switch to the character reference state.

                    // DEVIATION: Character reference consumption implemented as a function
                    $attribute->value .= $this->switchToCharacterReferenceState(self::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE);
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
                    yield new EOFToken;
                    return;
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

            # 13.2.5.37 Attribute value (single-quoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE) {
                # Consume the next input character

                # U+0027 APOSTROPHE (')
                if ($char === "'") {
                    # Switch to the after attribute value (quoted) state.
                    $this->state = self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (single-quoted) state.
                    # Switch to the character reference state.

                    // DEVIATION: Character reference consumption implemented as a function
                    $attribute->value .= $this->switchToCharacterReferenceState(self::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE);
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
                    yield new EOFToken;
                    return;
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


            # 13.2.5.38 Attribute value (unquoted) state
            elseif ($this->state === self::ATTRIBUTE_VALUE_UNQUOTED_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
                    # Switch to the before attribute name state.
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Set the return state to the attribute value (unquoted) state.
                    # Switch to the character reference state.

                    // DEVIATION: Character reference consumption implemented as a function
                    $attribute->value .= $this->switchToCharacterReferenceState(self::ATTRIBUTE_VALUE_UNQUOTED_STATE);
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->state = self::DATA_STATE;
                    $this->sanitizeTag($token);
                    yield $token;
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
                elseif (strspn($char,"\"'<=`")) {
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
                    yield new EOFToken;
                    return;
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

            # 13.2.5.39 After attribute value (quoted) state
            elseif ($this->state === self::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, " \t\n\x0C")) {
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
                    $this->sanitizeTag($token);
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-whitespace-between-attributes parse error.
                    # Reconsume in the before attribute name state.
                    $this->error(ParseError::MISSING_WHITESPACE_BETWEEN_ATTRIBUTES);
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.40 Self-closing start tag state
            elseif ($this->state === self::SELF_CLOSING_START_TAG_STATE) {
                # Consume the next input character

                # ">" (U+003E)
                if ($char === '>') {
                    # Set the self-closing flag of the current tag token.
                    # Switch to the data state.
                    # Emit the current tag token.
                    $token->selfClosing = true;
                    $this->state = self::DATA_STATE;
                    $this->sanitizeTag($token);
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-tag parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_TAG);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is an unexpected-solidus-in-tag parse error.
                    # Reconsume in the before attribute name state.
                    $this->error(ParseError::UNEXPECTED_SOLIDUS_IN_TAG);
                    $this->state = self::BEFORE_ATTRIBUTE_NAME_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.44 Bogus comment state
            elseif ($this->state === self::BOGUS_COMMENT_STATE) {
                # Consume the next input character

                # U+003E GREATER-THAN SIGN (>)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # Emit the comment.
                    # Emit an end-of-file token.
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character to the comment token's data.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $token->data .= "\u{FFFD}";
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->data .= $char.$this->data->consumeUntil(">\0");
                }
            }

            # 13.2.5.42 Markup declaration open state
            elseif ($this->state === self::MARKUP_DECLARATION_OPEN_STATE) {
                # If the next few characters are:

                # Two U+002D HYPHEN-MINUS characters (-)
                if ($this->data->peek(2) === '--') {
                    # Consume those two characters,
                    #   create a comment token whose data is the empty string,
                    #   and switch to the comment start state.
                    $this->data->consumeWhile("-", 2);
                    $token = new CommentToken('');
                    $this->state = self::COMMENT_START_STATE;
                }
                //OPTIMIZATION: Peek seven characters only once
                else {
                    $peek = $this->data->peek(7);
                    # ASCII case-insensitive match for the word "DOCTYPE"
                    if (strtoupper($peek) === 'DOCTYPE') {
                        # Consume those characters and switch to the DOCTYPE state.
                        $this->data->consumeWhile(self::CTYPE_ALPHA, 7);
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
                        $this->data->consumeWhile(self::CTYPE_ALPHA."[", 7);
                        if ($this->stack->adjustedCurrentNode && ($this->stack->adjustedCurrentNode->namespaceURI ?? Parser::HTML_NAMESPACE) !== Parser::HTML_NAMESPACE) {
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

            # 13.2.5.43 Comment start state
            elseif ($this->state === self::COMMENT_START_STATE) {
                # Consume the next input character

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
                    yield $token;
                }
                # Anything else
                else {
                    # Reconsume in the comment state.
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.44 Comment start dash state
            elseif ($this->state === self::COMMENT_START_DASH_STATE) {
                # Consume the next input character

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
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append a U+002D HYPHEN-MINUS character (-) to the comment token's data.
                    # Reconsume in the comment state.
                    $token->data .= '-';
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.45 Comment state
            elseif ($this->state === self::COMMENT_STATE) {
                # Consume the next input character

                # "<" (U+003C)
                if ($char === '<') {
                    # Append the current input character to the comment token's data.
                    # Switch to the comment less-than sign state.
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
                    $token->data .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->data .= $char.$this->data->consumeUntil("<-\0");
                }
            }

            # 13.2.5.46 Comment less-than sign state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character

                # U+0021 EXCLAMATION MARK (!)
                if ($char === '!') {
                    # Append the current input character to the comment token's data.
                    # Switch to the comment less-than sign bang state.
                    $token->data .= $char;
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_STATE;
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char ==='<') {
                    # Append the current input character to the comment token's data.
                    $token->data .= $char;
                }
                # Anything else
                else {
                    # Reconsume in the comment state
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.47 Comment less-than sign bang state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_STATE) {
                # Consume the next input character

                # U+002D HYPHEN-MINUS (-)
                if ($char === '-') {
                    # Switch to the comment less-than sign bang dash state.
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE;
                }
                # Anything else
                else {
                    # Reconsume in the comment state
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.48 Comment less-than sign bang dash state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_DASH_STATE) {
                # Consume the next input character

                # U+002D HYPHEN-MINUS (-)
                if ($char === '-') {
                    # Switch to the comment less-than sign bang dash dash state.
                    $this->state = self::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE;
                }
                # Anything else
                else {
                    # Reconsume in the comment end dash state
                    $this->state = self::COMMENT_END_DASH_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.49 Comment less-than sign bang dash dash state
            elseif ($this->state === self::COMMENT_LESS_THAN_SIGN_BANG_DASH_DASH_STATE) {
                # Consume the next input character

                # U+003E GREATER-THAN SIGN (>)
                # EOF
                if ($char === '>' || $char === '') {
                    # Reconsume in the comment end state.
                    $this->state = self::COMMENT_END_STATE;
                    goto Reconsume;
                }
                # Anything else
                else {
                    # This is a nested-comment parse error.
                    # Reconsume in the comment end state.
                    $this->error(ParseError::NESTED_COMMENT);
                    $this->state = self::COMMENT_END_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.50 Comment end dash state
            elseif ($this->state === self::COMMENT_END_DASH_STATE) {
                # Consume the next input character

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
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append a "-" (U+002D) character to the comment token's data.
                    # Reconsume in the comment state.
                    $token->data .= '-';
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.50 Comment end state
            elseif ($this->state === self::COMMENT_END_STATE) {
                # Consume the next input character

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the comment token.
                    $this->state = self::DATA_STATE;
                    yield $token;
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
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append two U+002D HYPHEN-MINUS characters (-) to the comment token's data.
                    # Reconsume in the comment state.
                    $token->data .= '--';
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.52 Comment end bang state
            elseif ($this->state === self::COMMENT_END_BANG_STATE) {
                # Consume the next input character

                # "-" (U+002D)
                if ($char === '-') {
                    # Append two U+002D HYPHEN-MINUS characters (-)
                    #   and a U+0021 EXCLAMATION MARK character (!)
                    #   to the comment token's data.
                    # Switch to the comment end dash state.
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
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-comment parse error.
                    # Emit the comment token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_COMMENT);
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append two U+002D HYPHEN-MINUS characters (-)
                    #   and a U+0021 EXCLAMATION MARK character (!)
                    #   to the comment token's data.
                    # Reconsume in the comment state.
                    $token->data .= '--!';
                    $this->state = self::COMMENT_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.53 DOCTYPE state
            elseif ($this->state === self::DOCTYPE_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Switch to the before DOCTYPE name state.
                    $this->state = self::BEFORE_DOCTYPE_NAME_STATE;
                }
                # U+003E GREATER-THAN SIGN (>)
                elseif ($char === '>') {
                    # Reconsume in the before DOCTYPE name state.
                    $this->state = self::BEFORE_DOCTYPE_NAME_STATE;
                    goto Reconsume;
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
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-whitespace-before-doctype-name parse error.
                    # Reconsume in the before DOCTYPE name state.
                    $this->error(ParseError::MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME);
                    $this->state = self::BEFORE_DOCTYPE_NAME_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.54 Before DOCTYPE name state
            elseif ($this->state === self::BEFORE_DOCTYPE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
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
                    yield $token;
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
                    yield $token;
                    yield new EOFToken;
                    return;
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

            # 13.2.5.55 DOCTYPE name state
            elseif ($this->state === self::DOCTYPE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Switch to the after DOCTYPE name state.
                    $this->state = self::AFTER_DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                // See below for ASCII upper alpha
                # U+0000 NULL
                elseif ($char === "\0") {
                    # This is an unexpected-null-character parse error.
                    # Append a U+FFFD REPLACEMENT CHARACTER character
                    #   to the current DOCTYPE token's name.
                    $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
                    $token->name .= "\u{FFFD}";
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # ASCII upper alpha
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's name.

                    // OPTIMIZATION: Also handle ASCII upper alpha
                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name .= strtolower($char.$this->data->consumeUntil("\t\n\x0c >\0"));
                }
            }

            # 13.2.5.56 After DOCTYPE name state
            elseif ($this->state === self::AFTER_DOCTYPE_NAME_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Ignore the character
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
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
                        $this->data->consumeWhile(self::CTYPE_ALPHA, 5);
                        $this->state = self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE;
                    }
                    # Otherwise, if the six characters starting from the current input
                    #   character are an ASCII case-insensitive match for the
                    #   word "SYSTEM", then consume those characters and
                    #   switch to the after DOCTYPE system keyword state.
                    elseif ($peek === 'SYSTEM') {
                        $this->data->consumeWhile(self::CTYPE_ALPHA, 5);
                        $this->state = self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE;
                    }
                    # Otherwise, this is an
                    #   invalid-character-sequence-after-doctype-name
                    #   parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    else {
                        $this->error(ParseError::INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME);
                        $token->forceQuirks = true;
                        $this->state = self::BOGUS_DOCTYPE_STATE;
                        goto Reconsume;
                    }
                }
            }

            # 13.2.5.57 After DOCTYPE public keyword state
            elseif ($this->state === self::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Switch to the before DOCTYPE public identifier state.
                    $this->state = self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # This is a missing-whitespace-after-doctype-public-keyword parse error.
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE public identifier (double-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD);
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # This is a missing-whitespace-after-doctype-public-keyword parse error.
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE public identifier (single-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD);
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
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.58 Before DOCTYPE public identifier state
            elseif ($this->state === self::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Ignore the character.
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE public identifier (double-quoted) state.
                    $token->public = '';
                    $this->state = self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE public identifier (single-quoted) state.
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
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.59 DOCTYPE public identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character

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
                    $token->public .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the
                    #   current DOCTYPE token's public identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil("\">\0");
                }
            }

            # 13.2.5.60 DOCTYPE public identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character

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
                    $token->public .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-public-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the
                    #   current DOCTYPE token's public identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil("'>\0");
                }
            }

            # 13.2.5.60 After DOCTYPE public identifier state
            elseif ($this->state === self::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Switch to the between DOCTYPE public and system identifiers state.
                    $this->state = self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
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
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.62 Between DOCTYPE public and system identifiers state
            elseif ($this->state === self::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Ignore the character.
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
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
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.63 After DOCTYPE system keyword state
            elseif ($this->state === self::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Switch to the before DOCTYPE system identifier state.
                    $this->state = self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # This is a missing-whitespace-after-doctype-system-keyword parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD);
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # This is a missing-whitespace-after-doctype-system-keyword parse error.
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    #   then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->error(ParseError::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD);
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
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.64 Before DOCTYPE system identifier state
            elseif ($this->state === self::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Ignore the character.
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the
                    #   empty string (not missing), then switch to the
                    #   DOCTYPE system identifier (double-quoted) state.
                    $token->system = '';
                    $this->state = self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the
                    #   empty string (not missing), then switch to the
                    #   DOCTYPE system identifier (single-quoted) state.
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
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is a missing-quote-before-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Reconsume in the bogus DOCTYPE state.
                    $this->error(ParseError::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.64 DOCTYPE system identifier (double-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character

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
                    $token->system .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil("\"\0>");
                }
            }

            # 13.2.5.66 DOCTYPE system identifier (single-quoted) state
            elseif ($this->state === self::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character

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
                    $token->system .= "\u{FFFD}";
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # This is an abrupt-doctype-system-identifier parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Switch to the data state.
                    # Emit that DOCTYPE token.
                    $this->error(ParseError::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER);
                    $token->forceQuirks = true;
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil("'\0>");
                }
            }

            # 13.2.5.67 After DOCTYPE system identifier state
            elseif ($this->state === self::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if (strspn($char, "\t\n\x0C ")) {
                    # Ignore the character
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state.
                    # Emit the current DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-doctype parse error.
                    # Set the DOCTYPE token's force-quirks flag to on.
                    # Emit that DOCTYPE token.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_DOCTYPE);
                    $token->forceQuirks = true;
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # This is an unexpected-character-after-doctype-system-identifier parse error.
                    # Reconsume in the bogus DOCTYPE state.
                    # (This does not set the DOCTYPE token's force-quirks flag to on.)
                    $this->error(ParseError::UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER, $char);
                    $this->state = self::BOGUS_DOCTYPE_STATE;
                    goto Reconsume;
                }
            }

            # 13.2.5.67 Bogus DOCTYPE state
            elseif ($this->state === self::BOGUS_DOCTYPE_STATE) {
                # Consume the next input character

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state.
                    # Emit the DOCTYPE token.
                    $this->state = self::DATA_STATE;
                    yield $token;
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
                    yield $token;
                    yield new EOFToken;
                    return;
                }
                # Anything else
                # Ignore the character.
            }

            # 13.2.5.69 CDATA section state
            elseif ($this->state === self::CDATA_SECTION_STATE) {
                # Consume the next input character

                # U+005D RIGHT SQUARE BRACKET (])
                if ($char === ']') {
                    # Switch to the CDATA section bracket state.
                    $this->state = self::CDATA_SECTION_BRACKET_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # This is an eof-in-cdata parse error.
                    # Emit an end-of-file token.
                    $this->error(ParseError::EOF_IN_CDATA);
                    yield new EOFToken;
                    return;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.

                    // OPTIMIZATION:
                    // Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time; only null characters
                    // are emitted singly
                    if ($char === "\0") {
                        yield new NullCharacterToken($char);
                    } elseif (strspn($char, Data::WHITESPACE)) {
                        yield new WhitespaceToken($char.$this->data->consumeWhile(Data::WHITESPACE_SAFE));
                    } else {
                        yield new CharacterToken($char.$this->data->consumeUntil("]\0"));
                    }
                }
            }

            # 13.2.5.70 CDATA section bracket state
            elseif ($this->state === self::CDATA_SECTION_BRACKET_STATE) {
                # Consume the next input character

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
                    yield new CharacterToken(']');
                    goto Reconsume;
                }
            }

            # 13.2.5.71 CDATA section end state
            elseif ($this->state === self::CDATA_SECTION_END_STATE) {
                # Consume the next input character

                # U+005D RIGHT SQUARE BRACKET (])
                if ($char === ']') {
                    # Emit a U+005D RIGHT SQUARE BRACKET character token.

                    // OTPIMIZATION: Consume any additional right square brackets
                    yield new CharacterToken(']'.$this->data->consumeWhile(']'));
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
                    yield new CharacterToken(']]');
                    goto Reconsume;
                }
            }

            # Not a valid state, unimplemented, or implemented elsewhere
            else {
                throw new Exception(Exception::TOKENIZER_INVALID_STATE, (self::STATE_NAMES[$this->state] ?? $this->state)); // @codeCoverageIgnore
            }
        }
    } // @codeCoverageIgnore

    protected function switchToCharacterReferenceState(int $returnState): string {
        // This function implements states 72 through 80,
        // "Character reference" through "Numeric character reference end" states
        $this->state = self::CHARACTER_REFERENCE_STATE;
        $charRefCode = 0;

        while (true) {
            assert((function() {
                $state = self::STATE_NAMES[$this->state] ?? $this->state;
                $char = bin2hex($this->data->peek(1));
                $this->debugLog .= "    State: $state ($char)\n";
                return true;
            })());

            # 13.2.5.72 Character reference state
            if ($this->state === self::CHARACTER_REFERENCE_STATE) {
                # Set the temporary buffer to the empty string.
                # Append a U+0026 AMPERSAND (&) character to the temporary buffer.
                # Consume the next input character.
                $this->temporaryBuffer = '&';
                $char = $this->data->consume();

                # ASCII alphanumeric
                if (ctype_alnum($char)) {
                    # Reconsume in the named character reference state.
                    $this->state = self::NAMED_CHARACTER_REFERENCE_STATE;
                    $this->data->unconsume();
                }
                # U+0023 NUMBER SIGN (#)
                elseif ($char === '#') {
                    # Append the current input character to the temporary buffer.
                    # Switch to the numeric character reference state.
                    $this->temporaryBuffer .= $char;
                    $this->state = self::NUMERIC_CHARACTER_REFERENCE_STATE;
                }
                # Anything else
                else {
                    # Flush code points consumed as a character reference.
                    # Reconsume in the return state.
                    $this->state = $returnState;
                    $this->data->unconsume();
                    return $this->temporaryBuffer;
                }
            }

            # 13.2.5.73 Named character reference state
            elseif ($this->state === self::NAMED_CHARACTER_REFERENCE_STATE) {
                # Consume the maximum number of characters possible,
                #   with the consumed characters matching one of the
                #   identifiers in the first column of the named character
                #   references table (in a case-sensitive manner).

                // DEVIATION:
                // We consume all possible alphanumeric characters,
                // up to the length of the longest in the table
                $candidate = $this->data->consumeWhile(self::CTYPE_ALNUM, CharacterReference::LONGEST_NAME);
                // Keep a record of the terminating character, which is used later
                $next = $this->data->peek(1);
                if ($next === ';') {
                    // consume the following character if it is a proper terminator
                    $candidate .= $this->data->consume();
                }
                // Look for an exact match; if not found look for a prefix match
                $match = CharacterReference::NAMES[$candidate] ?? null;
                if ($match === null) {
                    $match = (preg_match(CharacterReference::PREFIX_PATTERN, $candidate, $match)) ? $match[0] : null;
                    // If a prefix match is found, unconsume to the end of the prefix and look up the entry in the table
                    if ($match !== null) {
                        $this->data->unconsume(strlen($candidate) - strlen($match));
                        $next = $candidate[strlen($match)];
                        $candidate = $match;
                        $match = CharacterReference::NAMES[$match];
                    }
                }

                # Append each character to the temporary buffer when it's consumed.
                $this->temporaryBuffer .= $candidate;

                # If there is a match
                if ($match !== null) {
                    # If the character reference was consumed as part of an attribute,
                    #   and the last character matched is not a U+003B SEMICOLON character (;),
                    #   and the next input character is either a U+003D EQUALS SIGN character (=)
                    #   or an ASCII alphanumeric...
                    if (in_array($returnState, self::ATTRIBUTE_VALUE_STATE_SET) && $next !== ';' && ($next === '=' || ctype_alnum($next))) {
                        # ... then, for historical reasons, flush code points consumed
                        #   as a character reference and switch to the return state.
                        $this->state = $returnState;
                        return $this->temporaryBuffer;
                    }
                    # Otherwise:
                    else {
                        # If the last character matched is not a U+003B SEMICOLON character (;),
                        #   then this is a missing-semicolon-after-character-reference parse error.
                        if ($next !== ';') {
                            $this->error(ParseError::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE);
                        }
                        # Set the temporary buffer to the empty string.
                        # Append one or two characters corresponding to the
                        #   character reference name (as given by the second
                        #   column of the named character references table)
                        #   to the temporary buffer.
                        # Flush code points consumed as a character reference.
                        # Switch to the return state.

                        // In other words: return the match
                        $this->state = $returnState;
                        return $match;
                    }
                }
                # Otherwise:
                else {
                    # Flush code points consumed as a character reference.
                    # Switch to the ambiguous ampersand state.

                    // DEVIATION: We flush only when switching to the return state
                    $this->state = self::AMBIGUOUS_AMPERSAND_STATE;
                    // If we consumed a semicolon earlier we need to undo this
                    if ($next === ';') {
                        $this->data->unconsume();
                        $this->temporaryBuffer = substr($this->temporaryBuffer, 0, -1);
                    }
                }
            }

            # 13.2.5.74 Ambiguous ampersand state
            elseif ($this->state === self::AMBIGUOUS_AMPERSAND_STATE) {
                # Consume the next input character.
                $char = $this->data->consume();

                # ASCII alphanumeric
                if (ctype_alnum($char)) {
                    # If the character reference was consumed as part of an attribute,
                    #   then append the current input character to the current attribute's value.
                    # Otherwise, emit the current input character as a character token.

                    // DEVIATION: We just continue to buffer characters until it's time to return
                    $this->temporaryBuffer .= $char.$this->data->consumeWhile(self::CTYPE_ALNUM);
                }
                # U+003B SEMICOLON (;)
                elseif ($char === ';') {
                    # This is an unknown-named-character-reference parse error.
                    # Reconsume in the return state.
                    $this->data->unconsume();
                    $this->error(ParseError::UNKNOWN_NAMED_CHARACTER_REFERENCE, $this->temporaryBuffer.';');
                    $this->state = $returnState;
                    return $this->temporaryBuffer;
                }
                # Anything else
                else {
                    # Reconsume in the return state.
                    $this->state = $returnState;
                    $this->data->unconsume();
                    return $this->temporaryBuffer;
                }
            }

            # 13.2.5.75 Numeric character reference state
            elseif ($this->state === self::NUMERIC_CHARACTER_REFERENCE_STATE) {
                # Set the character reference code to zero (0).
                $charRefCode = 0;
                # Consume the next input character.
                $char = $this->data->consume();

                # U+0078 LATIN SMALL LETTER X
                #U+0058 LATIN CAPITAL LETTER X
                if ($char === 'x' || $char === 'X') {
                    # Append the current input character to the temporary buffer.
                    # Switch to the hexadecimal character reference start state.
                    $this->temporaryBuffer .= $char;
                    $this->state = self::HEXADECIMAL_CHARACTER_REFERENCE_START_STATE;
                }
                # Anything else
                else {
                    # Reconsume in the decimal character reference start state.
                    $this->state = self::DECIMAL_CHARACTER_REFERENCE_START_STATE;
                    $this->data->unconsume();
                }
            }

            # 13.2.5.76 Hexadecimal character reference start state
            elseif ($this->state === self::HEXADECIMAL_CHARACTER_REFERENCE_START_STATE) {
                # Consume the next input character.
                $char = $this->data->consume();

                # ASCII hex digit
                if (ctype_xdigit($char)) {
                    # Reconsume in the hexadecimal character reference state.

                    // OPTIMIZATION:
                    // Just consume the digits here
                    $charRefCode = hexdec($char.$this->data->consumeWhile(self::CTYPE_HEX));
                    $this->state = self::HEXADECIMAL_CHARACTER_REFERENCE_STATE;
                }
                # Anything else
                else {
                    # This is an absence-of-digits-in-numeric-character-reference parse error.
                    # Flush code points consumed as a character reference.
                    # Reconsume in the return state.
                    $this->data->unconsume();
                    $this->error(ParseError::ABSENCE_OF_DIGITS_IN_NUMERIC_CHARACTER_REFERENCE);
                    $this->state = $returnState;
                    return $this->temporaryBuffer;
                }
            }

            # 13.2.5.77 Decimal character reference start state
            elseif ($this->state === self::DECIMAL_CHARACTER_REFERENCE_START_STATE) {
                # Consume the next input character.
                $char = $this->data->consume();

                # ASCII digit
                if (ctype_digit($char)) {
                    # Reconsume in the decimal character reference state.

                    // OPTIMIZATION:
                    // Just consume the digits here
                    $charRefCode = (int) ($char.$this->data->consumeWhile(self::CTYPE_NUM));
                    $this->state = self::DECIMAL_CHARACTER_REFERENCE_STATE;
                }
                # Anything else
                else {
                    # This is an absence-of-digits-in-numeric-character-reference parse error.
                    # Flush code points consumed as a character reference.
                    # Reconsume in the return state.
                    $this->data->unconsume();
                    $this->error(ParseError::ABSENCE_OF_DIGITS_IN_NUMERIC_CHARACTER_REFERENCE);
                    $this->state = $returnState;
                    return $this->temporaryBuffer;
                }
            }

            # 13.2.5.78 Hexadecimal character reference state
            elseif ($this->state === self::HEXADECIMAL_CHARACTER_REFERENCE_STATE) {
                # Consume the next input character.
                $char = $this->data->consume();

                # ASCII digit
                # ASCII upper hex digit
                # ASCII lower hex digit
                if (ctype_xdigit($char)) {
                    # Multiply the character reference code by 16.
                    # Add a numeric version of the current input
                    #   character to the character reference code.

                    // OPTIMIZATION: Combine all digit types
                    // NOTE: This branch should never be reached
                    $charRefCode = ($charRefCode * 16) + hexdec($char); // @codeCoverageIgnore
                }
                # U+003B SEMICOLON
                elseif ($char === ';') {
                    # Switch to the numeric character reference end state.
                    $this->state = self::NUMERIC_CHARACTER_REFERENCE_END_STATE;
                }
                # Anything else
                else {
                    # This is a missing-semicolon-after-character-reference parse error.
                    # Reconsume in the numeric character reference end state.
                    $this->data->unconsume();
                    $this->error(ParseError::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE);
                    $this->state = self::NUMERIC_CHARACTER_REFERENCE_END_STATE;
                }
            }

            # 13.2.5.79 Decimal character reference state
            elseif ($this->state === self::DECIMAL_CHARACTER_REFERENCE_STATE) {
                # Consume the next input character.
                $char = $this->data->consume();

                # ASCII digit
                if (ctype_digit($char)) {
                    # Multiply the character reference code by 10.
                    # Add a numeric version of the current input
                    #   character to the character reference code.

                    // OPTIMIZATION: Combine all digit types
                    // NOTE: This branch should never be reached
                    $charRefCode = ($charRefCode * 10) + ((int) ($char)); // @codeCoverageIgnore
                }
                # U+003B SEMICOLON
                elseif ($char === ';') {
                    # Switch to the numeric character reference end state.
                    $this->state = self::NUMERIC_CHARACTER_REFERENCE_END_STATE;
                }
                # Anything else
                else {
                    # This is a missing-semicolon-after-character-reference parse error.
                    # Reconsume in the numeric character reference end state.
                    $this->data->unconsume();
                    $this->error(ParseError::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE);
                    $this->state = self::NUMERIC_CHARACTER_REFERENCE_END_STATE;
                }
            }

            # 13.2.5.80 Numeric character reference end state
            elseif ($this->state === self::NUMERIC_CHARACTER_REFERENCE_END_STATE) {
                # Check the character reference code:

                # If the number is 0x00, then this is a null-character-reference parse error.
                # Set the character reference code to 0xFFFD.
                if ($charRefCode === 0) {
                    $this->error(ParseError::NULL_CHARACTER_REFERENCE);
                    $charRefCode = 0xFFFD;
                }
                # If the number is greater than 0x10FFFF, then this is a
                #   character-reference-outside-unicode-range parse error.
                # Set the character reference code to 0xFFFD.
                elseif ($charRefCode > 0x10FFFF) {
                    $this->error(ParseError::CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE);
                    $charRefCode = 0xFFFD;
                }
                # If the number is a surrogate, then this is a
                #   surrogate-character-reference parse error.
                # Set the character reference code to 0xFFFD.
                elseif ($charRefCode >= 0xD800 && $charRefCode <= 0xDFFF) {
                    $this->error(ParseError::SURROGATE_CHARACTER_REFERENCE);
                    $charRefCode = 0xFFFD;
                }
                # If the number is a noncharacter, then this is a
                #   noncharacter-character-reference parse error.
                elseif (($charRefCode >= 0xFDD0 && $charRefCode <= 0xFDEF) || ($charRefCode % 0x10000 & 0xFFFE) === 0xFFFE) {
                    $this->error(ParseError::NONCHARACTER_CHARACTER_REFERENCE);
                }
                # If the number is 0x0D, or a control that's not ASCII whitespace, then
                #   this is a control-character-reference parse error.
                # If the number is one of the numbers in the first column of the following
                #   table, then find the row with that number in the first column, and set
                #   the character reference code to the number in the second column of that row.
                elseif (($charRefCode < 0x20 && !in_array($charRefCode, [0x9, 0xA, 0xC])) || ($charRefCode >= 0x7F && $charRefCode <= 0x9F)) {
                    // NOTE: Table elided
                    $this->error(ParseError::CONTROL_CHARACTER_REFERENCE);
                    $charRefCode = CharacterReference::C1_TABLE[$charRefCode] ?? $charRefCode;
                }
                $this->temporaryBuffer = UTF8::encode($charRefCode);
                $this->state = $returnState;
                return $this->temporaryBuffer;
            }

            # Not a valid state, unimplemented, or implemented elsewhere
            else {
                throw new Exception(Exception::TOKENIZER_INVALID_CHARACTER_REFERENCE_STATE, (self::STATE_NAMES[$this->state] ?? $this->state)); // @codeCoverageIgnore
            }
        }
    } // @codeCoverageIgnore
}
