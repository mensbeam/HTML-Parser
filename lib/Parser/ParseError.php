<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class ParseError {
    // tokenization parse errors; these have been standardized
    public const ENCODING_ERROR                                                    = 100;
    public const UNEXPECTED_NULL_CHARACTER                                         = 101;
    public const UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME                      = 102;
    public const EOF_BEFORE_TAG_NAME                                               = 103;
    public const INVALID_FIRST_CHARACTER_OF_TAG_NAME                               = 104;
    public const MISSING_END_TAG_NAME                                              = 105;
    public const EOF_IN_TAG                                                        = 106;
    public const EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT                              = 107;
    public const UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME                      = 108;
    public const DUPLICATE_ATTRIBUTE                                               = 109;
    public const UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME                            = 110;
    public const MISSING_ATTRIBUTE_VALUE                                           = 111;
    public const UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE                  = 112;
    public const MISSING_WHITESPACE_BETWEEN_ATTRIBUTES                             = 113;
    public const UNEXPECTED_SOLIDUS_IN_TAG                                         = 114;
    public const CDATA_IN_HTML_CONTENT                                             = 115;
    public const INCORRECTLY_OPENED_COMMENT                                        = 116;
    public const ABRUPT_CLOSING_OF_EMPTY_COMMENT                                   = 117;
    public const EOF_IN_COMMENT                                                    = 118;
    public const NESTED_COMMENT                                                    = 119;
    public const INCORRECTLY_CLOSED_COMMENT                                        = 120;
    public const EOF_IN_DOCTYPE                                                    = 121;
    public const MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME                            = 122;
    public const MISSING_DOCTYPE_NAME                                              = 123;
    public const INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME                     = 124;
    public const MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD                   = 125;
    public const MISSING_DOCTYPE_PUBLIC_IDENTIFIER                                 = 126;
    public const MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER                    = 127;
    public const ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER                                  = 128;
    public const MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS  = 129;
    public const MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD                   = 130;
    public const MISSING_DOCTYPE_SYSTEM_IDENTIFIER                                 = 131;
    public const MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER                    = 132;
    public const ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER                                  = 133;
    public const UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER              = 134;
    public const EOF_IN_CDATA                                                      = 135;
    public const END_TAG_WITH_ATTRIBUTES                                           = 136;
    public const END_TAG_WITH_TRAILING_SOLIDUS                                     = 137;
    public const MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE                       = 138;
    public const UNKNOWN_NAMED_CHARACTER_REFERENCE                                 = 139;
    public const ABSENCE_OF_DIGITS_IN_NUMERIC_CHARACTER_REFERENCE                  = 140;
    public const NULL_CHARACTER_REFERENCE                                          = 141;
    public const CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE                         = 142;
    public const SURROGATE_CHARACTER_REFERENCE                                     = 143;
    public const NONCHARACTER_CHARACTER_REFERENCE                                  = 144;
    public const CONTROL_CHARACTER_REFERENCE                                       = 145;
    public const SURROGATE_IN_INPUT_STREAM                                         = 146;
    public const NONCHARACTER_IN_INPUT_STREAM                                      = 147;
    public const CONTROL_CHARACTER_IN_INPUT_STREAM                                 = 148;
    // tree construction parse errors; these have not been standardized, but html5lib's error names are likely to become standard in future
    public const EXPECTED_DOCTYPE_BUT_GOT_START_TAG                                = 200;
    public const EXPECTED_DOCTYPE_BUT_GOT_END_TAG                                  = 201;
    public const EXPECTED_DOCTYPE_BUT_GOT_CHARS                                    = 202;
    public const EXPECTED_DOCTYPE_BUT_GOT_EOF                                      = 203;
    public const UNKNOWN_DOCTYPE                                                   = 204;
    public const UNEXPECTED_DOCTYPE                                                = 205;
    public const UNEXPECTED_START_TAG                                              = 206;
    public const UNEXPECTED_END_TAG                                                = 207; // html5lib also uses 'adoption-agency-1.2' and 'adoption-agency-1.3' for this
    public const NON_VOID_HTML_ELEMENT_START_TAG_WITH_TRAILING_SOLIDUS             = 208;
    public const UNEXPECTED_START_TAG_IMPLIES_END_TAG                              = 209;
    public const UNEXPECTED_START_TAG_ALIAS                                        = 210; // html5lib uses 'unexpected-start-tag-treated-as'
    public const UNEXPECTED_CHAR                                                   = 211;
    public const UNEXPECTED_EOF                                                    = 212;
    public const UNEXPECTED_PARENT                                                 = 213;
    public const INVALID_NAMESPACE_ATTRIBUTE_VALUE                                 = 214;
    public const FOSTERED_START_TAG                                                = 215;
    public const FOSTERED_END_TAG                                                  = 216;
    public const FOSTERED_CHAR                                                     = 217;

    public const MESSAGES = [
        self::EXPECTED_DOCTYPE_BUT_GOT_START_TAG                                => 'Expected DOCTYPE but got start tag <%s>',
        self::EXPECTED_DOCTYPE_BUT_GOT_END_TAG                                  => 'Expected DOCTYPE but got end tag </%s>',
        self::EXPECTED_DOCTYPE_BUT_GOT_CHARS                                    => 'Expected DOCTYPE but got characters',
        self::EXPECTED_DOCTYPE_BUT_GOT_EOF                                      => 'Expected DOCTYPE but got end-of-file',
        self::UNKNOWN_DOCTYPE                                                   => 'Unknown DOCTYPE',
        self::UNEXPECTED_DOCTYPE                                                => 'Unexpected DOCTYPE',
        self::UNEXPECTED_START_TAG                                              => 'Unexpected start tag <%s>',
        self::UNEXPECTED_END_TAG                                                => 'Unexpected end tag </%s>',
        self::NON_VOID_HTML_ELEMENT_START_TAG_WITH_TRAILING_SOLIDUS             => 'Trailing solidus in non-void HTML element start tag <%s>',
        self::UNEXPECTED_START_TAG_IMPLIES_END_TAG                              => 'Unexpcted non-nesting start tag <%s> in nested context',
        self::UNEXPECTED_START_TAG_ALIAS                                        => 'Start tag <%s> should be <%s>',
        self::UNEXPECTED_CHAR                                                   => 'Unexpected character data',
        self::UNEXPECTED_EOF                                                    => 'Unexpected end of file',
        self::UNEXPECTED_PARENT                                                 => 'Start tag <%s> not valid in parent <%s>',
        self::INVALID_NAMESPACE_ATTRIBUTE_VALUE                                 => 'Invalid value for attribute "%s"; it must have value "%s" or be omitted',
        self::FOSTERED_START_TAG                                                => 'Start tag <%s> moved to before table',
        self::FOSTERED_END_TAG                                                  => 'End tag </%s> moved to before table',
        self::FOSTERED_CHAR                                                     => 'Character moved to before table',

        self::ENCODING_ERROR                                                    => 'Corrupt encoding near byte position %s',
        self::UNEXPECTED_NULL_CHARACTER                                         => 'Unexpected null character',
        self::UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME                      => 'Unexpected "?" character instead of tag name',
        self::EOF_BEFORE_TAG_NAME                                               => 'End-of-file before tag name',
        self::INVALID_FIRST_CHARACTER_OF_TAG_NAME                               => 'Invalid first character "%s" of tag name',
        self::MISSING_END_TAG_NAME                                              => 'Missing end-tag name',
        self::EOF_IN_TAG                                                        => 'End-of-file in tag',
        self::EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT                              => 'End-of-file in script (HTML comment-like) text',
        self::UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME                      => 'Unexpected equals sign before attribute name',
        self::DUPLICATE_ATTRIBUTE                                               => 'Duplicate attribute "%s" in start tag',
        self::UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME                            => 'Unexpected character "%s" in attribute name',
        self::MISSING_ATTRIBUTE_VALUE                                           => 'Missing attribute value',
        self::UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE                  => 'Unexpected character "%s" in unquoted attribute value',
        self::MISSING_WHITESPACE_BETWEEN_ATTRIBUTES                             => 'Missing whitespace between attributes',
        self::UNEXPECTED_SOLIDUS_IN_TAG                                         => 'Unexpected solidus in tag',
        self::CDATA_IN_HTML_CONTENT                                             => 'CDATA in HTML content',
        self::INCORRECTLY_OPENED_COMMENT                                        => 'Incorrectly opened comment',
        self::ABRUPT_CLOSING_OF_EMPTY_COMMENT                                   => 'Abrupt closing of empty comment',
        self::EOF_IN_COMMENT                                                    => 'End-of-file in comment',
        self::NESTED_COMMENT                                                    => 'Nested comment',
        self::INCORRECTLY_CLOSED_COMMENT                                        => 'Incorrectly closed comment',
        self::EOF_IN_DOCTYPE                                                    => 'End-of-file in DOCTYPE',
        self::MISSING_WHITESPACE_BEFORE_DOCTYPE_NAME                            => 'Missing whitespace before DOCTYPE name',
        self::MISSING_DOCTYPE_NAME                                              => 'Missing DOCTYPE name',
        self::INVALID_CHARACTER_SEQUENCE_AFTER_DOCTYPE_NAME                     => 'Invalid character sequence after DOCTYPE name',
        self::MISSING_WHITESPACE_AFTER_DOCTYPE_PUBLIC_KEYWORD                   => 'Missing whitespace after DOCTYPE "PUBLIC" keyword',
        self::MISSING_DOCTYPE_PUBLIC_IDENTIFIER                                 => 'Missing DOCTYPE "PUBLIC" identifier',
        self::MISSING_QUOTE_BEFORE_DOCTYPE_PUBLIC_IDENTIFIER                    => 'Missing quote before DOCTYPE "PUBLIC" identifier',
        self::ABRUPT_DOCTYPE_PUBLIC_IDENTIFIER                                  => 'Abrupt DOCTYPE "PUBLIC" identifier',
        self::MISSING_WHITESPACE_BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS  => 'Missing whitespace between DOCTYPE "PUBLIC" and "SYSTEM" identifiers',
        self::MISSING_WHITESPACE_AFTER_DOCTYPE_SYSTEM_KEYWORD                   => 'Missing whitespace after DOCTYPE "SYSTEM" keyword',
        self::MISSING_DOCTYPE_SYSTEM_IDENTIFIER                                 => 'Missing DOCTYPE "SYSTEM" identifier',
        self::MISSING_QUOTE_BEFORE_DOCTYPE_SYSTEM_IDENTIFIER                    => 'Missing quote before DOCTYPE "SYSTEM" identifier',
        self::ABRUPT_DOCTYPE_SYSTEM_IDENTIFIER                                  => 'Abrupt DOCTYPE "SYSTEM" identifier',
        self::UNEXPECTED_CHARACTER_AFTER_DOCTYPE_SYSTEM_IDENTIFIER              => 'Unexpected character "%s" after DOCTYPE "SYSTEM" identifier',
        self::EOF_IN_CDATA                                                      => 'End-of-file in CDATA section',
        self::END_TAG_WITH_ATTRIBUTES                                           => 'End-tag with attributes',
        self::END_TAG_WITH_TRAILING_SOLIDUS                                     => 'End-tag with trailing solidus',
        self::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE                       => 'Missing semicolon after character reference',
        self::UNKNOWN_NAMED_CHARACTER_REFERENCE                                 => 'Unknown named character reference "%s"',
        self::ABSENCE_OF_DIGITS_IN_NUMERIC_CHARACTER_REFERENCE                  => 'Absence of digits in character reference',
        self::NULL_CHARACTER_REFERENCE                                          => 'Null character reference',
        self::CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE                         => 'Character reference outside Unicode range',
        self::SURROGATE_CHARACTER_REFERENCE                                     => 'Surrogate character rereference',
        self::NONCHARACTER_CHARACTER_REFERENCE                                  => 'Non-character character reference',
        self::CONTROL_CHARACTER_REFERENCE                                       => 'Control-character character reference',
        self::SURROGATE_IN_INPUT_STREAM                                         => 'Surrogate character in input stream',
        self::NONCHARACTER_IN_INPUT_STREAM                                      => 'Non-character character in input stream',
        self::CONTROL_CHARACTER_IN_INPUT_STREAM                                 => 'Control character in input stream',
    ];

    public const REPORT_OFFSETS = [
        self::INCORRECTLY_OPENED_COMMENT                       => 1,
        self::SURROGATE_CHARACTER_REFERENCE                    => 1,
        self::CHARACTER_REFERENCE_OUTSIDE_UNICODE_RANGE        => 1,
        self::NONCHARACTER_CHARACTER_REFERENCE                 => 1,
        self::ABSENCE_OF_DIGITS_IN_NUMERIC_CHARACTER_REFERENCE => 1,
        self::NULL_CHARACTER_REFERENCE                         => 1,
        self::MISSING_SEMICOLON_AFTER_CHARACTER_REFERENCE      => 1,
        self::CONTROL_CHARACTER_REFERENCE                      => 1,
        self::UNKNOWN_NAMED_CHARACTER_REFERENCE                => 1,
    ];

    public $errors = [];
}
