<?php
declare(strict_types=1);
namespace dW\HTML5;

class Data {
    use ParseErrorEmitter;

    // Used to get the file path for error reporting.
    public $filePath;

    // Internal storage for the Intl data object.
    protected $data;
    // Used for error reporting to display line number.
    protected $_line = 1;
    // Used for error reporting to display column number.
    protected $_column = 0;
    // Used for error reporting when unconsuming to calculate column number from
    // last newline.
    protected $newlines = [];


    // Used for debugging to print out information as data is consumed.
    public static $debug = false;


    const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const DIGIT = '0123456789';
    const HEX = '0123456789ABCDEFabcdef';
    const WHITESPACE = "\t\n\x0c\x0d ";


    public function __construct(string $data, string $filePath = 'STDIN', ParseError $errorHandler = null) {
        $this->errorHandler = $errorHandler ?? new ParseError;
        if ($filePath !== 'STDIN') {
            $this->filePath = realpath($filePath);
            $data = file_get_contents($this->filePath);
        } else {
            $this->filePath = $filePath;
        }

        // DEVIATION: The spec has steps for parsing and determining the character
        // encoding. At this moment this implementation won't determine a character
        // encoding and will just assume UTF-8.

        # One leading U+FEFF BYTE ORDER MARK character must be ignored if any are present
        # in the input stream.

        # Note: The handling of U+0000 NULL characters varies based on where the
        # characters are found. In general, they are ignored except where doing so could
        # plausibly introduce an attack vector. This handling is, by necessity, spread
        # across both the tokenization stage and the tree construction stage.

        // DEVIATION: Just going to remove NULL characters. There is no scripting involved
        // in this implementation and therefore no attack vector possible due to it.
        $data = preg_replace(['/^\xEF\xBB\xBF/','/\x00/'], '', $data);

        // Won't provide line or column counts for this as it's done before that
        // information is available. It will be rare that this is triggered.
        $data = preg_replace_callback('/(?:[\x01-\x08\x0B\x0E-\x1F\x7F]|\xC2[\x80-\x9F]|\xED(?:\xA0[\x80-\xFF]|[\xA1-\xBE][\x00-\xFF]|\xBF[\x00-\xBF])|\xEF\xB7[\x90-\xAF]|\xEF\xBF[\xBE\xBF]|[\xF0-\xF4][\x8F-\xBF]\xBF[\xBE\xBF])/u', function($matches) {
            $this->error(ParseError::INVALID_CONTROL_OR_NONCHARACTERS);
            return '';
        }, $data);


        // Normalize line breaks. Convert CRLF and CR to LF.
        // Break the string up into a traversable object.
        $this->data = new \MensBeam\Intl\Encoding\UTF8(str_replace(["\r\n", "\r"], "\n", $data));
    }

    public function consume(int $length = 1): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        for ($i = 0, $string = ''; $i < $length; $i++) {
            $char = $this->data->nextChar();

            if ($char === "\n") {
                $this->newlines[] = $this->data->posChar();
                $this->_column = 1;
                $this->_line++;
            } else {
                $this->_column++;
            }

            $string .= $char;
        }

        if (self::$debug) {
            echo "\nConsume\n==========\n";
            echo "Length: $length\n";
            echo "Data: ";
            var_export($string);
            echo "\n";
            echo "Pointer: {$this->data->posChar()}\n";
            echo "==========\n\n";
        }

        return $string;
    }

    public function unconsume(int $length = 1) {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        if ($this->data->peekChar($length) !== '') {
            $this->data->seek(0 - $length);

            $string = $this->data->peekChar($length);
            $numOfNewlines = substr_count($string, "\n");

            if ($numOfNewlines > 0) {
                $this->_line -= $numOfNewlines;

                $count = $this->newlines;
                $index = count($this->newlines) - ($numOfNewlines - 1);
                $this->_column = 1 + (($count > 0 && isset($this->newlines[$index])) ? $this->data->posChar() - $this->newlines[$index] : $this->data->posChar());
            } else {
                $this->_column -= $length;
            }
        }

        if (self::$debug) {
            echo "\nUnconsume\n==========\n";
            echo "Pointer: {$this->data->posChar()}\n";
            echo "==========\n\n";
        }
    }

    public function consumeWhile(string $match, int $limit = 0): string {
        return $this->span($match, true, true, $limit);
    }

    public function consumeUntil(string $match, int $limit = 0): string {
        return $this->span($match, false, true, $limit);
    }

    public function peek(int $length = 1): string {
        assert($length > 0, new Exception(Exception::DATA_INVALID_DATA_CONSUMPTION_LENGTH, $length));

        $string = $this->data->peekChar($length);

        if (self::$debug) {
            echo "\nPeek\n==========\n";
            echo "Data: ";
            var_export($string);
            echo "\n";
            echo "Pointer: {$this->data->posChar()}\n";
            echo "==========\n\n";
        }

        return $string;
    }

    public function peekWhile(string $match, int $limit = 0): string {
        return $this->span($match, true, false, $limit);
    }

    public function peekUntil(string $match, int $limit = 0): string {
        return $this->span($match, false, false, $limit);
    }


    public function consumeCharacterReference(string $allowedCharacter = null, bool $inAttribute = false): string {
        $char = $this->peek();

        // OPTIMIZATION: When this spec states to return a character token of any kind this
        // method will just return the character. The token will be emitted from
        // Parser::parse() instead. Likewise, if the spec states to return nothing this
        // method will instead return '&' because every single use of "tokenizing a
        // character reference" in the spec this emits a '&' character token upon failure.

        # The behavior depends on the identity of the next character (the one immediately
        # after the U+0026 AMPERSAND character), as follows: U+0009 CHARACTER TABULATION
        # (tab), U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+0020 SPACE, U+003C
        # LESS-THAN SIGN, U+0026 AMPERSAND, EOF, The additional allowed character, if
        # there is one. Not a character reference. No characters are consumed, and nothing
        # is returned. (This is not an error, either.)
        if ($char === "\x09" || $char === "x0A" || $char === "\x0C" || $char === ' ' || $char === '<' || $char === '&' || $char === '' || (!is_null($allowedCharacter) && $char === $allowedCharacter)) {
            return '&';
        }
        # U+0023 NUMBER SIGN (#)
        if ($char === '#') {
            # Consume the U+0023 NUMBER SIGN.
            $this->consume();

            $char = $this->peek();
            # The behavior further depends on the character after the U+0023 NUMBER SIGN:
            # U+0078 LATIN SMALL LETTER X, U+0058 LATIN CAPITAL LETTER X
            if ($char === 'x' || $char === 'X') {
                # Consume the X.
                $this->consume();

                # Consume as many characters as match the range of ASCII hex digits.
                $number = $this->consumeWhile(self::HEX);

                # If no characters match the range, then don't consume any characters (and
                # unconsume the U+0023 NUMBER SIGN character and, if appropriate, the X
                # character). This is a parse error; nothing is returned.
                if (!$number) {
                    $this->error(ParseError::ENTITY_UNEXPECTED_CHARACTER, $this->peek(), 'hexadecimal digit');
                    $this->unconsume(2);
                    return '&';
                }
            } else {
                # Consume as many characters as match the range of ASCII digits.
                $number = $this->consumeWhile(self::DIGIT);

                # If no characters match the range, then don't consume any characters (and
                # unconsume the U+0023 NUMBER SIGN character and, if appropriate, the X
                # character). This is a parse error; nothing is returned.
                if (!$number) {
                    $peek = $this->peek();
                    if ($peek !== '') {
                        $this->error(ParseError::ENTITY_UNEXPECTED_CHARACTER, $this->peek(), 'decimal digit');
                    } else {
                        $this->error(ParseError::UNEXPECTED_EOF);
                    }

                    $this->unconsume();
                    return '&';
                }
            }

            # Otherwise, if the next character is a U+003B SEMICOLON, consume that too. If it
            # isn't, there is a parse error.
            $char = $this->peek();
            if ($char === ';') {
                $this->consume();
            } elseif ($char === '') {
                $this->error(ParseError::UNEXPECTED_EOF);
            } else {
                $this->error(ParseError::ENTITY_UNEXPECTED_CHARACTER, $char, 'semicolon terminator');
            }

            # If one or more characters match the range, then take them all and interpret the
            # string of characters as a number (either hexadecimal or decimal as appropriate).
            # If that number is one of the numbers in the first column of the following table,
            # then this is a parse error. Find the row with that number in the first column,
            # and return a character token for the Unicode character given in the second
            # column of that row.

            // DEVIATION: Because NULL characters are stripped from the document there's no
            // sense in checking for them here.

            switch ($number) {
                # 0x80	U+20AC	EURO SIGN (€)
                case 0x80: $returnValue = '€';
                break;
                # 0x80	U+20AC	EURO SIGN (€)
                case 0x82: $returnValue = '‚';
                break;
                # 0x83	U+0192	LATIN SMALL LETTER F WITH HOOK (ƒ)
                case 0x83: $returnValue = 'ƒ';
                break;
                # 0x84	U+201E	DOUBLE LOW-9 QUOTATION MARK (&ldquor;)
                case 0x84: $returnValue = '„';
                break;
                # 0x85	U+2026	HORIZONTAL ELLIPSIS (&mldr;)
                case 0x85: $returnValue = '…';
                break;
                # 0x86	U+2020	DAGGER (†)
                case 0x86: $returnValue = '†';
                break;
                # 0x87	U+2021	DOUBLE DAGGER (‡)
                case 0x87: $returnValue = '‡';
                break;
                # 0x88	U+02C6	MODIFIER LETTER CIRCUMFLEX ACCENT (ˆ)
                case 0x88: $returnValue = 'ˆ';
                break;
                # 0x89	U+2030	PER MILLE SIGN (‰)
                case 0x89: $returnValue = '‰';
                break;
                # 0x8A	U+0160	LATIN CAPITAL LETTER S WITH CARON (Š)
                case 0x8A: $returnValue = 'Š';
                break;
                # 0x8B	U+2039	SINGLE LEFT-POINTING ANGLE QUOTATION MARK (‹)
                case 0x8B: $returnValue = '‹';
                break;
                # 0x8C	U+0152	LATIN CAPITAL LIGATURE OE (Œ)
                case 0x8C: $returnValue = 'Œ';
                break;
                # 0x8E	U+017D	LATIN CAPITAL LETTER Z WITH CARON (&Zcaron;)
                case 0x8E: $returnValue = 'Ž';
                break;
                # 0x91	U+2018	LEFT SINGLE QUOTATION MARK (‘)
                case 0x91: $returnValue = '‘';
                break;
                # 0x92	U+2019	RIGHT SINGLE QUOTATION MARK (&rsquor;)
                case 0x92: $returnValue = '’';
                break;
                # 0x93	U+201C	LEFT DOUBLE QUOTATION MARK (“)
                case 0x93: $returnValue = '“';
                break;
                # 0x94	U+201D	RIGHT DOUBLE QUOTATION MARK (”)
                case 0x94: $returnValue = '”';
                break;
                # 0x95	U+2022	BULLET (&bullet;)
                case 0x95: $returnValue = '•';
                break;
                # 0x96	U+2013	EN DASH (–)
                case 0x96: $returnValue = '–';
                break;
                # 0x97	U+2014	EM DASH (—)
                case 0x97: $returnValue = '—';
                break;
                # 0x98	U+02DC	SMALL TILDE (˜)
                case 0x98: $returnValue = '˜';
                break;
                # 0x99	U+2122	TRADE MARK SIGN (™)
                case 0x99: $returnValue = '™';
                break;
                # 0x9A	U+0161	LATIN SMALL LETTER S WITH CARON (š)
                case 0x9A: $returnValue = 'š';
                break;
                # 0x9B	U+203A	SINGLE RIGHT-POINTING ANGLE QUOTATION MARK (›)
                case 0x9B: $returnValue = '›';
                break;
                # 0x9C	U+0153	LATIN SMALL LIGATURE OE (œ)
                case 0x9C: $returnValue = 'œ';
                break;
                # 0x9E	U+017E	LATIN SMALL LETTER Z WITH CARON (&zcaron;)
                case 0x9E: $returnValue = 'ž';
                break;
                # 0x9F	U+0178	LATIN CAPITAL LETTER Y WITH DIAERESIS (Ÿ)
                case 0x9F: $returnValue = 'Ÿ';
                break;
                default : $returnValue = null;
            }

            if ($returnValue) {
                $this->error(ParseError::INVALID_NUMERIC_ENTITY, $number);
                // Consume the ampersand but return the value instead.
                $this->consume();
                return $returnValue;
            }

            # Otherwise, if the number is in the range 0xD800 to 0xDFFF or is greater than
            # 0x10FFFF, then this is a parse error. Return a U+FFFD REPLACEMENT CHARACTER
            # character token.
            if (($number >= 0xD800 && $number <= 0xDFFF) || $number > 0x10FFFF) {
                $this->error(ParseError::INVALID_CODEPOINT, $number);
                return '�';
            }

            # Additionally, if the number is in the range 0x0001 to 0x0008, 0x000D to 0x001F,
            # 0x007F to 0x009F, 0xFDD0 to 0xFDEF, or is one of 0x000B, 0xFFFE, 0xFFFF,
            # 0x1FFFE, 0x1FFFF, 0x2FFFE, 0x2FFFF, 0x3FFFE, 0x3FFFF, 0x4FFFE, 0x4FFFF, 0x5FFFE,
            # 0x5FFFF, 0x6FFFE, 0x6FFFF, 0x7FFFE, 0x7FFFF, 0x8FFFE, 0x8FFFF, 0x9FFFE, 0x9FFFF,
            # 0xAFFFE, 0xAFFFF, 0xBFFFE, 0xBFFFF, 0xCFFFE, 0xCFFFF, 0xDFFFE, 0xDFFFF, 0xEFFFE,
            # 0xEFFFF, 0xFFFFE, 0xFFFFF, 0x10FFFE, or 0x10FFFF, then this is a parse error.
            if (($number >= 0x0001 && $number <= 0x0008) || ($number >= 0x000D && $number <= 0x001F) ||
                ($number >= 0x007F && $number <= 0x009F) || ($number >= 0xFDD0 && $number <= 0xFDEF) ||
                 $number === 0x000B || $number === 0xFFFE || $number === 0xFFFF || $number === 0x1FFFE ||
                 $number === 0x1FFFF || $number === 0x2FFFE || $number === 0x2FFFF || $number === 0x3FFFE ||
                 $number === 0x3FFFF || $number === 0x4FFFE || $number === 0x4FFFF || $number === 0x5FFFE ||
                 $number === 0x5FFFF || $number === 0x6FFFE || $number === 0x6FFFF || $number === 0x7FFFE ||
                 $number === 0x7FFFF || $number === 0x8FFFE || $number === 0x8FFFF || $number === 0x9FFFE ||
                 $number === 0x9FFFF || $number === 0xAFFFE || $number === 0xAFFFF || $number === 0xBFFFE ||
                 $number === 0xBFFFF || $number === 0xCFFFE || $number === 0xCFFFF || $number === 0xDFFFE ||
                 $number === 0xDFFFF || $number === 0xEFFFE || $number === 0xEFFFF || $number === 0xFFFFE ||
                 $number === 0xFFFFF || $number === 0x10FFFE || $number === 0x10FFFF) {
                $this->error(ParseError::INVALID_CODEPOINT, $number);
                // Consume the ampersand.
                $this->consume();
                return '&';
            }

            # Otherwise, return a character token for the Unicode character whose code point
            # is that number.
            return \MensBeam\Intl\Encoding\UTF8::encode((int) $number);
        }

        # Consume the maximum number of characters possible, with the consumed characters
        # matching one of the identifiers in the first column of the named character
        # references table (in a case-sensitive manner).

        // Implementing this by peeking ahead 33 characters that match 0-9, A-Z, a-z, and
        // ';'. 33 is the string length of the longest named character reference
        // (calculated using `max(array_map('mb_strlen', array_keys($referenceTable)));`).
        // It then checks the sequence of characters by checking them against a regular
        // expression which is generated by a script that grabs the JSON of the character
        // reference table from the spec and creates a somewhat optimized regular
        // expression.

        $sequence = static::peekWhile(self::DIGIT.self::ALPHA.';', 33);

        if ($sequence !== '' && preg_match('/^(?:[Aa]acute(?:;)?|[Aa]breve;|acd;|acE;|[Aa]circ(?:;)?|acute(?:;)?|[Aa]cy;|[Aa][Ee]lig(?:;)?|[Aa]fr;|af;|[Aa]grave(?:;)?|alefsym;|aleph;|[Aa]lpha;|[Aa]macr;|amalg;|[aA][mM][pP](?:;)?|andand;|andd;|andslope;|andv;|[Aa]nd;|ange;|angmsdaa;|angmsdab;|angmsdac;|angmsdad;|angmsdae;|angmsdaf;|angmsdag;|angmsdah;|angmsd;|angrtvbd;|angrtvb;|angrt;|angsph;|angst;|angzarr;|[Aa]ogon;|[Aa]opf;|apacir;|ap[Ee];|apid;|apos;|ApplyFunction;|approxeq;|[Aa]ring(?:;)?|[Aa]scr;|Assign;|ast;|asympeq;|asymp;|[Aa]tilde(?:;)?|[Aa]uml(?:;)?|awconint;|awint;|backcong;|backepsilon;|backprime;|backsimeq;|backsim;|Backslash;|barvee;|Barv;|barwedge;|[bB]arwed;|bbrktbrk;|bbrk;|bcong;|[Bb]cy;|bdquo;|[bB]ecause;|becaus;|bemptyv;|bepsi;|Bernoullis;|bernou;|[Bb]eta;|beth;|between;|[Bb]fr;|bigcap;|bigcirc;|bigcup;|bigodot;|bigoplus;|bigotimes;|bigsqcup;|bigstar;|bigtriangledown;|bigtriangleup;|biguplus;|bigvee;|bigwedge;|bkarow;|blacklozenge;|blacksquare;|blacktriangledown;|blacktriangleleft;|blacktriangleright;|blacktriangle;|ac;|angle;|ang;|blank;|blk12;|blk14;|blk34;|block;|bnequiv;|bne;|b[Nn]ot;|[Bb]opf;|bottom;|bot;|bowtie;|boxbox;|box[dD][lL];|box[dD][rR];|box[hH][dD];|box[hH][uU];|box[hH];|boxminus;|boxplus;|boxtimes;|box[uU][lL];|box[uU][rR];|box[vV][hH];|box[vV][lL];|box[vV][rR];|box[vV];|bprime;|[bB]reve;|brvbar(?:;)?|[bB]scr;|bsemi;|bsime;|bsim;|bsolb;|bsolhsub;|bsol;|bullet;|bull;|[Bb]umpeq;|bump[Ee];|bump;|[Cc]acute;|capand;|capbrcup;|capcap;|capcup;|capdot;|CapitalDifferentialD;|caps;|[cC]ap;|caret;|[Cc]caron;|caron;|Cayleys;|ccaps;|[Cc]cedil(?:;)?|[Cc]circ;|Cconint;|ccupssm;|ccups;|[Cc]dot;|Cedilla;|cedil(?:;)?|cemptyv;|[cC]enter[dD]ot;|cent(?:;)?|[cC]fr;|[Cc][Hh]cy;|checkmark;|check;|[Cc]hi;|circeq;|circlearrowleft;|circlearrowright;|circledast;|circledcirc;|circleddash;|CircleDot;|circledR;|circledS;|CircleMinus;|CirclePlus;|CircleTimes;|circ;|cir[Ee];|cirfnint;|cirmid;|cirscir;|cir;|ClockwiseContourIntegral;|CloseCurlyDoubleQuote;|CloseCurlyQuote;|clubsuit;|clubs;|coloneq;|[Cc]olone;|[cC]olon;|commat;|comma;|compfn;|complement;|complexes;|comp;|congdot;|Congruent;|cong;|cwconint;|[cC]onint;|ContourIntegral;|[cC]opf;|Coproduct;|coprod;|copysr;|[cC][oO][pP][yY](?:;)?|CounterClockwiseContourIntegral;|crarr;|[cC]ross;|[Cc]scr;|csube;|csub;|csupe;|csup;|ctdot;|cudarrl;|cudarrr;|cuepr;|cuesc;|cularrp;|cularr;|cupbrcap;|[cC]up[cC]ap;|cupcup;|cupdot;|cupor;|cups;|[cC]up;|curarrm;|curarr;|curlyeqprec;|curlyeqsucc;|curlyvee;|curlywedge;|curren(?:;)?|curvearrowleft;|curvearrowright;|cuvee;|cuwed;|cwint;|cylcty;|[dD]agger;|daleth;|[dD][aA]rr;|[Dd]ashv;|dash;|dbkarow;|dblac;|[Dd]caron;|[Dd]cy;|ddagger;|ddarr;|DDotrahd;|ddotseq;|[Dd][Dd];|deg(?:;)?|[Dd]elta;|Del;|demptyv;|dfisht;|[Dd]fr;|dharl;|dharr;|dHar;|DiacriticalAcute;|DiacriticalDot;|DiacriticalDoubleAcute;|DiacriticalGrave;|DiacriticalTilde;|diamondsuit;|[dD]iamond;|diams;|diam;|die;|DifferentialD;|digamma;|disin;|divideontimes;|divide(?:;)?|divonx;|div;|[Dd][Jj]cy;|dlcorn;|dlcrop;|dollar;|[Dd]opf;|DotDot;|doteqdot;|DotEqual;|doteq;|dotminus;|dotplus;|dotsquare;|doublebarwedge;|DoubleContourIntegral;|DoubleDot;|[Dd]ot;|DoubleDownArrow;|DoubleLeftArrow;|DoubleLeftRightArrow;|DoubleLeftTee;|DoubleLongLeftArrow;|DoubleLongLeftRightArrow;|DoubleLongRightArrow;|DoubleRightArrow;|DoubleRightTee;|DoubleUpArrow;|DoubleUpDownArrow;|DoubleVerticalBar;|DownArrowBar;|DownArrowUpArrow;|downdownarrows;|[dD]own[aA]rrow;|DownBreve;|downharpoonleft;|downharpoonright;|DownLeftRightVector;|DownLeftTeeVector;|DownLeftVectorBar;|DownLeftVector;|DownRightTeeVector;|DownRightVectorBar;|DownRightVector;|DownTeeArrow;|DownTee;|drbkarow;|drcorn;|drcrop;|[Dd]scr;|[Dd][Ss]cy;|dsol;|[Dd]strok;|dtdot;|dtrif;|dtri;|duarr;|duhar;|dwangle;|[Dd][Zz]cy;|dzigrarr;|[Ee]acute(?:;)?|easter;|[Ee]caron;|[Ee]circ(?:;)?|ecir;|ecolon;|[Ee]cy;|eDDot;|[Ee][dD]ot;|ee;|efDot;|[Ee]fr;|[Ee]grave(?:;)?|egsdot;|egs;|eg;|Element;|elinters;|ell;|elsdot;|els;|el;|[Ee]macr;|emptyset;|EmptySmallSquare;|EmptyVerySmallSquare;|emptyv;|empty;|emsp13;|emsp14;|emsp;|[Ee][Nn][Gg];|ensp;|[Ee]ogon;|[Ee]opf;|eparsl;|epar;|eplus;|[Ee]psilon;|epsiv;|epsi;|eqcirc;|eqcolon;|eqsim;|eqslantgtr;|eqslantless;|equals;|EqualTilde;|Equal;|equest;|Equilibrium;|equivDD;|equiv;|eqvparsl;|erarr;|erDot;|[eE]scr;|esdot;|[Ee]sim;|[Ee]ta;|[Ee][Tt][Hh](?:;)?|[Ee]uml(?:;)?|euro;|excl;|Exists;|exist;|expectation;|[eE]xponential[eE];|fallingdotseq;|[Ff]cy;|female;|ffilig;|fflig;|ffllig;|[Ff]fr;|filig;|FilledSmallSquare;|FilledVerySmallSquare;|fjlig;|flat;|fllig;|fltns;|fnof;|[Ff]opf;|[fF]or[aA]ll;|forkv;|fork;|Fouriertrf;|fpartint;|frac12(?:;)?|frac13;|frac14(?:;)?|frac15;|frac16;|frac18;|frac23;|frac25;|frac34(?:;)?|frac35;|frac38;|frac45;|frac56;|frac58;|frac78;|frasl;|frown;|[fF]scr;|gacute;|[Gg]ammad;|[Gg]amma;|gap;|[Gg]breve;|Gcedil;|[Gg]circ;|[Gg]cy;|[Gg]dot;|g[Ee]l;|geqq;|geqslant;|geq;|gescc;|gesdotol;|gesdoto;|gesdot;|gesles;|gesl;|ges;|g[eE];|[Gg]fr;|ggg;|[gG]g;|gimel;|[Gg][Jj]cy;|gla;|glE;|glj;|gl;|gnapprox;|gnap;|gneqq;|gneq;|gn[eE];|gnsim;|[Gg]opf;|grave;|GreaterEqualLess;|GreaterEqual;|GreaterFullEqual;|GreaterGreater;|GreaterLess;|GreaterSlantEqual;|GreaterTilde;|[Gg]scr;|gsime;|gsiml;|gsim;|gtcc;|gtcir;|gtdot;|gtlPar;|gtquest;|gtrapprox;|gtrarr;|gtrdot;|gtreqless;|gtreqqless;|gtrless;|gtrsim;|[gG][tT](?:;)?|gvertneqq;|gvnE;|Hacek;|hairsp;|half;|hamilt;|[Hh][Aa][Rr][Dd]cy;|harrcir;|harrw;|h[aA]rr;|Hat;|hbar;|[Hh]circ;|heartsuit;|hearts;|hellip;|hercon;|[hH]fr;|HilbertSpace;|hksearow;|hkswarow;|hoarr;|homtht;|hookleftarrow;|hookrightarrow;|[hH]opf;|horbar;|HorizontalLine;|[hH]scr;|hslash;|[Hh]strok;|HumpDownHump;|HumpEqual;|hybull;|hyphen;|[Ii]acute(?:;)?|[Ii]circ(?:;)?|[Ii]cy;|ic;|Idot;|[Ii][Ee]cy;|iexcl(?:;)?|iff;|[iI]fr;|[Ii]grave(?:;)?|iiiint;|iiint;|iinfin;|iiota;|ii;|[Ii][Jj]lig;|[Ii]macr;|image;|ImaginaryI;|imagline;|imagpart;|imath;|imof;|imped;|Implies;|Im;|incare;|infintie;|infin;|inodot;|intcal;|integers;|Integral;|intercal;|Intersection;|intlarhk;|intprod;|[iI]nt;|InvisibleComma;|InvisibleTimes;|in;|[Ii][Oo]cy;|[Ii]ogon;|[Ii]opf;|[Ii]ota;|iprod;|iquest(?:;)?|[iI]scr;|isindot;|isinE;|isinsv;|isins;|isinv;|isin;|[Ii]tilde;|it;|[Ii]ukcy;|[Ii]uml(?:;)?|[Jj]circ;|[Jj]cy;|[Jj]fr;|jmath;|[Jj]opf;|[Jj]scr;|[Jj]sercy;|[Jj]ukcy;|kappav;|[Kk]appa;|[Kk]cedil;|[Kk]cy;|[Kk]fr;|kgreen;|[Kk][Hh]cy;|[Kk][Jj]cy;|[Kk]opf;|[Kk]scr;|lAarr;|[Ll]acute;|laemptyv;|lagran;|[Ll]ambda;|langd;|langle;|[lL]ang;|Laplacetrf;|lap;|laquo(?:;)?|larrbfs;|larrb;|larrfs;|larrhk;|larrlp;|larrpl;|larrsim;|larrtl;|[lL][aA]rr;|l[aA]tail;|lates;|late;|lat;|l[bB]arr;|lbbrk;|lbrace;|lbrack;|lbrke;|lbrksld;|lbrkslu;|[Ll]caron;|[Ll]cedil;|lceil;|lcub;|[Ll]cy;|ldca;|ldquor;|ldquo;|ldrdhar;|ldrushar;|ldsh;|LeftAngleBracket;|LeftArrowBar;|LeftArrowRightArrow;|leftarrowtail;|[lL]eft[aA]rrow;|LeftCeiling;|LeftDoubleBracket;|LeftDownTeeVector;|LeftDownVectorBar;|LeftDownVector;|LeftFloor;|leftharpoondown;|leftharpoonup;|leftleftarrows;|leftrightarrows;|[lL]eft[rR]ight[aA]rrow;|leftrightharpoons;|leftrightsquigarrow;|LeftRightVector;|LeftTeeArrow;|LeftTeeVector;|LeftTee;|leftthreetimes;|LeftTriangleBar;|LeftTriangleEqual;|LeftTriangle;|LeftUpDownVector;|LeftUpTeeVector;|LeftUpVectorBar;|LeftUpVector;|LeftVectorBar;|LeftVector;|l[Ee]g;|leqq;|leqslant;|leq;|lescc;|lesdotor;|lesdoto;|lesdot;|lesges;|lesg;|lessapprox;|lessdot;|lesseqgtr;|lesseqqgtr;|LessEqualGreater;|LessFullEqual;|LessGreater;|lessgtr;|LessLess;|lesssim;|LessSlantEqual;|LessTilde;|les;|l[eE];|lfisht;|lfloor;|[Ll]fr;|lgE;|lg;|lhard;|lharul;|lharu;|lHar;|lhblk;|[Ll][Jj]cy;|llarr;|llcorner;|Lleftarrow;|llhard;|lltri;|[lL]l;|[Ll]midot;|lmoustache;|lmoust;|lnapprox;|lnap;|lneqq;|lneq;|ln[eE];|lnsim;|loang;|loarr;|lobrk;|[lL]ong[lL]eft[aA]rrow;|[lL]ong[lL]eft[rR]ight[aA]rrow;|longmapsto;|[lL]ong[rR]ight[aA]rrow;|looparrowleft;|looparrowright;|lopar;|[Ll]opf;|loplus;|lotimes;|lowast;|lowbar;|LowerLeftArrow;|LowerRightArrow;|lozenge;|lozf;|loz;|lparlt;|lpar;|lrarr;|lrcorner;|lrhard;|lrhar;|lrm;|lrtri;|lsaquo;|[lL]scr;|[lL]sh;|lsime;|lsimg;|lsim;|lsqb;|lsquor;|lsquo;|[Ll]strok;|ltcc;|ltcir;|ltdot;|lthree;|ltimes;|ltlarr;|ltquest;|ltrie;|ltrif;|ltri;|ltrPar;|[lL][tT](?:;)?|lurdshar;|luruhar;|lvertneqq;|lvnE;|macr(?:;)?|male;|maltese;|malt;|mapstodown;|mapstoleft;|mapstoup;|mapsto;|[Mm]ap;|marker;|mcomma;|[Mm]cy;|mdash;|mDDot;|measuredangle;|MediumSpace;|Mellintrf;|[Mm]fr;|mho;|micro(?:;)?|midast;|midcir;|middot(?:;)?|mid;|minusb;|minusdu;|minusd;|MinusPlus;|minus;|mlcp;|mldr;|mnplus;|models;|[Mm]opf;|mp;|[Mm]scr;|mstpos;|multimap;|mumap;|[mM]u;|nabla;|[nN]acute;|nang;|napE;|napid;|napos;|precnapprox;|succnapprox;|napprox;|approx;|nap;|naturals;|natural;|natur;|nbsp(?:;)?|nbumpe;|nbump;|ncap;|ap;|[nN]caron;|[nN]cedil;|ncongdot;|ncong;|ncup;|[nN]cy;|ndash;|nearhk;|nearrow;|ne[Aa]rr;|nedot;|NegativeMediumSpace;|NegativeThickSpace;|NegativeThinSpace;|NegativeVeryThinSpace;|nequiv;|nesear;|nesim;|NestedGreaterGreater;|NestedLessLess;|NewLine;|nexists;|nexist;|ne;|[nN]fr;|ngeqq;|ngeqslant;|ngeq;|nges;|ng[eE];|nGg;|ngsim;|ngtr;|nGtv;|n[gG]t;|nh[Aa]rr;|nhpar;|nisd;|nis;|[nN][jJ]cy;|nl[Aa]rr;|nldr;|n[Ll]eftarrow;|n[Ll]eftrightarrow;|nleqq;|nleqslant;|nleq;|nless;|nles;|nl[eE];|nLl;|nlsim;|nltrie;|nltri;|nLtv;|n[lL]t;|nmid;|NoBreak;|NonBreakingSpace;|[Nn]opf;|NotCongruent;|NotCupCap;|NotDoubleVerticalBar;|NotElement;|NotEqualTilde;|NotEqual;|NotExists;|NotGreaterEqual;|NotGreaterFullEqual;|NotGreaterGreater;|NotGreaterLess;|NotGreaterSlantEqual;|NotGreaterTilde;|NotGreater;|NotHumpDownHump;|NotHumpEqual;|notindot;|notinE;|notinva;|notinvb;|notinvc;|notin;|NotLeftTriangleBar;|NotLeftTriangleEqual;|NotLeftTriangle;|NotLessEqual;|NotLessGreater;|NotLessLess;|NotLessSlantEqual;|NotLessTilde;|NotLess;|NotNestedGreaterGreater;|NotNestedLessLess;|notniva;|notnivb;|notnivc;|niv;|notni;|ni;|NotPrecedesEqual;|NotPrecedesSlantEqual;|NotPrecedes;|NotReverseElement;|NotRightTriangleBar;|NotRightTriangleEqual;|NotRightTriangle;|NotSquareSubsetEqual;|NotSquareSubset;|NotSquareSupersetEqual;|NotSquareSuperset;|NotSubsetEqual;|NotSubset;|NotSucceedsEqual;|NotSucceedsSlantEqual;|NotSucceedsTilde;|NotSucceeds;|NotSupersetEqual;|NotSuperset;|NotTildeEqual;|NotTildeFullEqual;|NotTildeTilde;|NotTilde;|NotVerticalBar;|[nN]ot(?:;)?|nparallel;|nparsl;|npart;|npar;|npolint;|nprcue;|npreceq;|nprec;|npre;|npr;|nrarrc;|nrarrw;|nr[Aa]rr;|n[Rr]ightarrow;|nrtrie;|nrtri;|nsccue;|nsce;|[nN]scr;|nsc;|nshortmid;|nshortparallel;|nsimeq;|nsime;|nsim;|nsmid;|nspar;|nsqsube;|nsqsupe;|nsub[eE];|nsubseteqq;|nsubseteq;|nsubset;|nsub;|nsucceq;|nsucc;|nsup[eE];|nsupseteqq;|nsupseteq;|nsupset;|nsup;|ntgl;|[nN]tilde(?:;)?|ntlg;|ntrianglelefteq;|ntriangleleft;|ntrianglerighteq;|ntriangleright;|numero;|numsp;|num;|[nN]u;|nvap;|n[Vv][Dd]ash;|nvge;|nvgt;|nvHarr;|nvinfin;|nvlArr;|nvle;|nvltrie;|nvlt;|nvrArr;|nvrtrie;|nvsim;|nwarhk;|nwarrow;|nw[Aa]rr;|nwnear;|[oO]acute(?:;)?|oast;|[oO]circ(?:;)?|ocir;|[oO]cy;|odash;|[oO]dblac;|odiv;|odot;|odsold;|[oO][eE]lig;|ofcir;|[oO]fr;|ogon;|[oO]grave(?:;)?|ogt;|ohbar;|ohm;|oint;|olarr;|olcir;|olcross;|oline;|olt;|[oO]macr;|[oO]mega;|[oO]micron;|omid;|ominus;|[oO]opf;|opar;|OpenCurlyDoubleQuote;|OpenCurlyQuote;|operp;|oplus;|orarr;|orderof;|order;|ordf(?:;)?|ordm(?:;)?|ord;|origof;|oror;|orslope;|orv;|[oO]scr;|[oO]slash(?:;)?|osol;|oS;|[oO]tilde(?:;)?|otimesas;|[oO]times;|[oO]uml(?:;)?|ovbar;|OverBar;|OverBrace;|OverBracket;|OverParenthesis;|parallel;|para(?:;)?|parsim;|parsl;|PartialD;|part;|par;|[pP]cy;|percnt;|period;|permil;|perp;|pertenk;|[pP]fr;|phiv;|[pP]hi;|phmmat;|phone;|pitchfork;|piv;|[pP]i;|planckh;|planck;|plankv;|plusacir;|plusb;|pluscir;|plusdo;|plusdu;|pluse;|PlusMinus;|plusmn(?:;)?|plussim;|plustwo;|plus;|pm;|Poincareplane;|pointint;|[Pp]opf;|pound(?:;)?|prap;|prcue;|precapprox;|preccurlyeq;|PrecedesEqual;|PrecedesSlantEqual;|PrecedesTilde;|Precedes;|preceq;|precneqq;|precnsim;|precsim;|prec;|pr[Ee];|primes;|[Pp]rime;|prnap;|prnE;|prnsim;|Product;|prod;|profalar;|profline;|profsurf;|Proportional;|Proportion;|propto;|prop;|prsim;|prurel;|[pP]r;|[pP]scr;|[pP]si;|puncsp;|[qQ]fr;|qint;|[Qq]opf;|qprime;|[qQ]scr;|quaternions;|quatint;|questeq;|quest;|[Qq][Uu][Oo][Tt](?:;)?|rAarr;|race;|[rR]acute;|radic;|raemptyv;|rangd;|range;|rangle;|[Rr]ang;|raquo(?:;)?|rarrap;|rarrbfs;|rarrb;|rarrc;|rarrfs;|rarrhk;|rarrlp;|rarrpl;|rarrsim;|[rR]arrtl;|rarrw;|[rR][Aa]rr;|r[Aa]tail;|rationals;|ratio;|[Rr][Bb]arr;|rbbrk;|rbrace;|rbrack;|rbrke;|rbrksld;|rbrkslu;|[rR]caron;|[rR]cedil;|rceil;|rcub;|[rR]cy;|rdca;|rdldhar;|rdquor;|rdquo;|rdsh;|realine;|realpart;|reals;|real;|rect;|[Rr][Ee][Gg](?:;)?|ReverseElement;|ReverseEquilibrium;|ReverseUpEquilibrium;|Re;|rfisht;|rfloor;|[Rr]fr;|rhard;|rharul;|rharu;|rHar;|rhov;|[rR]ho;|RightAngleBracket;|RightArrowBar;|RightArrowLeftArrow;|rightarrowtail;|[Rr]ight[aA]rrow;|RightCeiling;|RightDoubleBracket;|RightDownTeeVector;|RightDownVectorBar;|RightDownVector;|RightFloor;|rightharpoondown;|rightharpoonup;|rightleftarrows;|rightleftharpoons;|rightrightarrows;|rightsquigarrow;|RightTeeArrow;|RightTeeVector;|RightTee;|rightthreetimes;|RightTriangleBar;|RightTriangleEqual;|RightTriangle;|RightUpDownVector;|RightUpTeeVector;|RightUpVectorBar;|RightUpVector;|RightVectorBar;|RightVector;|[oO]r;|ring;|risingdotseq;|rlarr;|rlhar;|rlm;|rmoustache;|rmoust;|rnmid;|roang;|roarr;|robrk;|ropar;|[Rr]opf;|roplus;|rotimes;|RoundImplies;|rpargt;|rpar;|rppolint;|rrarr;|Rrightarrow;|rsaquo;|[Rr]scr;|[Rr]sh;|rsqb;|rsquor;|rsquo;|rthree;|rtimes;|rtrie;|rtrif;|rtriltri;|rtri;|RuleDelayed;|ruluhar;|rx;|[sS]acute;|sbquo;|scap;|[sS]caron;|sccue;|[sS]cedil;|sc[Ee];|[sS]circ;|scnap;|scnE;|scnsim;|scpolint;|scsim;|[sS]cy;|[sS]c;|sdotb;|sdote;|sdot;|searhk;|searrow;|se[Aa]rr;|sect(?:;)?|semi;|seswar;|setminus;|setmn;|sext;|sfrown;|[sS]fr;|sharp;|[sS][hH][cC][hH]cy;|[sS][hH]cy;|ShortDownArrow;|ShortLeftArrow;|shortmid;|shortparallel;|ShortRightArrow;|ShortUpArrow;|shy(?:;)?|sigmaf;|sigmav;|[sS]igma;|simdot;|simeq;|sime;|simgE;|simg;|simlE;|siml;|simne;|simplus;|simrarr;|sim;|slarr;|SmallCircle;|smallsetminus;|smashp;|smeparsl;|smid;|smile;|smtes;|smte;|smt;|[sS][oO][fF][tT]cy;|solbar;|solb;|sol;|[sS]opf;|spadesuit;|spades;|spar;|sqcaps;|sqcap;|sqcups;|sqcup;|Sqrt;|sqsube;|sqsubseteq;|sqsubset;|sqsub;|sqsupe;|sqsupseteq;|sqsupset;|sqsup;|SquareIntersection;|SquareSubsetEqual;|SquareSubset;|SquareSupersetEqual;|SquareSuperset;|SquareUnion;|[Ss]quare;|squarf;|squf;|squ;|srarr;|[sS]scr;|ssetmn;|ssmile;|sstarf;|starf;|[sS]tar;|straightepsilon;|straightphi;|strns;|subdot;|subedot;|sub[eE];|submult;|subn[eE];|subplus;|subrarr;|subseteqq;|SubsetEqual;|subseteq;|subsetneqq;|subsetneq;|[Ss]ubset;|subsim;|subsub;|subsup;|[Ss]ub;|succapprox;|succcurlyeq;|SucceedsEqual;|SucceedsSlantEqual;|SucceedsTilde;|Succeeds;|succeq;|succneqq;|succnsim;|succsim;|succ;|SuchThat;|[Ss]um;|sung;|sup1(?:;)?|sup2(?:;)?|sup3(?:;)?|supdot;|supdsub;|supedot;|SupersetEqual;|Superset;|sup[eE];|suphsol;|suphsub;|suplarr;|supmult;|supn[eE];|supplus;|supseteqq;|supseteq;|supsetneqq;|supsetneq;|[Ss]upset;|supsim;|supsub;|supsup;|[Ss]up;|swarhk;|swarrow;|sw[Aa]rr;|swnwar;|szlig(?:;)?|Tab;|target;|[tT]au;|tbrk;|[tT]caron;|[tT]cedil;|[tT]cy;|tdot;|telrec;|[tT]fr;|there4;|[Tt]herefore;|thetasym;|thetav;|[tT]heta;|thickapprox;|thicksim;|ThickSpace;|ThinSpace;|thinsp;|thkap;|thksim;|[tT][hH][oO][rR][nN](?:;)?|TildeEqual;|TildeFullEqual;|TildeTilde;|timesbar;|timesb;|timesd;|times(?:;)?|tint;|toea;|topbot;|topcir;|topfork;|[tT]opf;|top;|tosa;|tprime;|[Tt][Rr][Aa][Dd][Ee];|triangledown;|trianglelefteq;|triangleleft;|triangleq;|trianglerighteq;|triangleright;|triangle;|tridot;|trie;|triminus;|TripleDot;|triplus;|trisb;|tritime;|trpezium;|[tT]scr;|[tT][sS]cy;|[tT][sS][hH]cy;|[tT]strok;|twixt;|twoheadleftarrow;|twoheadrightarrow;|[uU]acute(?:;)?|Uarrocir;|[uU][Aa]rr;|[uU]brcy;|[uU]breve;|[uU]circ(?:;)?|[uU]cy;|udarr;|[uU]dblac;|udhar;|ufisht;|[uU]fr;|[uU]grave(?:;)?|uharl;|uharr;|uHar;|uhblk;|ulcorner;|ulcorn;|ulcrop;|ultri;|[uU]macr;|uml(?:;)?|UnderBar;|UnderBrace;|UnderBracket;|UnderParenthesis;|UnionPlus;|Union;|[uU]ogon;|[uU]opf;|UpArrowBar;|UpArrowDownArrow;|[Uu]p[aA]rrow;|[Uu]p[dD]own[aA]rrow;|UpEquilibrium;|upharpoonleft;|upharpoonright;|uplus;|UpperLeftArrow;|UpperRightArrow;|upsih;|[uU]psilon;|[Uu]psi;|UpTeeArrow;|UpTee;|upuparrows;|urcorner;|urcorn;|urcrop;|[uU]ring;|urtri;|[uU]scr;|utdot;|[uU]tilde;|[Tt]ilde;|utrif;|utri;|uuarr;|[uU]uml(?:;)?|uwangle;|vangrt;|varepsilon;|varkappa;|varnothing;|varphi;|varpi;|varpropto;|varrho;|v[Aa]rr;|varsigma;|varsubsetneqq;|varsubsetneq;|varsupsetneqq;|varsupsetneq;|vartheta;|vartriangleleft;|vartriangleright;|vBarv;|[Vv][bB]ar;|[vV]cy;|Vdashl;|[Vv][Dd]ash;|veebar;|veeeq;|[Vv]ee;|vellip;|[Vv]erbar;|VerticalBar;|VerticalLine;|VerticalSeparator;|VerticalTilde;|[Vv]ert;|VeryThinSpace;|[vV]fr;|vltri;|vnsub;|vnsup;|[vV]opf;|vprop;|vrtri;|[vV]scr;|vsubn[eE];|vsupn[eE];|Vvdash;|vzigzag;|[wW]circ;|wedbar;|wedgeq;|xwedge;|[Ww]edge;|weierp;|[wW]fr;|[wW]opf;|wp;|wreath;|wr;|[wW]scr;|xcap;|xcirc;|xcup;|xdtri;|[xX]fr;|xh[Aa]rr;|[xX]i;|xl[Aa]rr;|xmap;|xnis;|xodot;|[xX]opf;|xoplus;|xotime;|xr[Aa]rr;|[xX]scr;|xsqcup;|xuplus;|xutri;|xvee;|[yY]acute(?:;)?|[yY][aA]cy;|[yY]circ;|[yY]cy;|yen(?:;)?|[yY]fr;|[yY][iI]cy;|[yY]opf;|[yY]scr;|[yY][uU]cy;|[Yy]uml(?:;)?|[zZ]acute;|[zZ]caron;|[zZ]cy;|[zZ]dot;|zeetrf;|ZeroWidthSpace;|[zZ]eta;|[Zz]fr;|[zZ][hH]cy;|zigrarr;|[Zz]opf;|[zZ]scr;|zwj;|zwnj;)/', $sequence, $matches)) {
            $sequence = $matches[0];
            $lastChar = substr($sequence, -1);

            # If the character reference is being consumed as part of an attribute, and the
            # last character matched is not a U+003B SEMICOLON character (;), and the next
            # character is either a U+003D EQUALS SIGN character (=) or an alphanumeric ASCII
            # character, then, for historical reasons, all the characters that were matched
            # after the U+0026 AMPERSAND character (&) must be unconsumed, and nothing is
            # returned. However, if this next character is in fact a U+003D EQUALS SIGN
            # character (=), then this is a parse error, because some legacy user agents will
            # misinterpret the markup in those cases.

            // OPTIMIZATION: Not consuming here until this stuff is checked because there's no
            // sense in consuming characters and then turning right back around and unconsuming
            // them. Will consume after this step instead.
            $next = $this->peek();
            if ($inAttribute && $lastChar !== ';' && ($next === '=' || ctype_alnum($next))) {
                if ($next === '=') {
                    $this->error(ParseError::ENTITY_UNEXPECTED_CHARACTER, $next, 'semicolon terminator');
                }

                // Consume the ampersand.
                $this->consume();
                return '&';
            }

            // Add 1 to the string length because the & isn't included in the matched
            // sequence.
            $this->consume(strlen($sequence) + 1);

            if ($lastChar !== ';') {
                // Used for PHP's entity decoder. Described below.
                $sequence.=';';

                $this->error(ParseError::ENTITY_UNEXPECTED_CHARACTER, $lastChar, 'semicolon terminator');
            }

            # Return one or two character tokens for the character(s) corresponding to the
            # character reference name (as given by the second column of the named character
            # references table).

            // DEVIATION: Since the regular expression above checks the validity of the
            // regular expression there isn't a need for the table. Can use PHP's built in
            // decoder at least until there's entities in the table that aren't in the spec's.
            return html_entity_decode('&'.$sequence, ENT_HTML5);
        }

        # If no match can be made, then no characters are consumed, and nothing is
        # returned. In this case, if the characters after the U+0026 AMPERSAND character
        # (&) consist of a sequence of one or more alphanumeric ASCII characters followed
        # by a U+003B SEMICOLON character (;), then this is a parse error.
        if (preg_match('/^[A-Za-z0-9]+;/', $char)) {
            $this->error(ParseError::INVALID_NAMED_ENTITY, $char);
        }

        // Consume the ampersand.
        $this->consume();
        return '&';
    }

    protected function span(string $match, bool $while = true, bool $advancePointer = true, int $limit = 0): string {
        // Break the matching characters into an array of characters. Unicode friendly.
        $match = preg_split('/(?<!^)(?!$)/Su', $match);

        $count = 0;
        $string = '';
        while (true) {
            $char = $this->data->nextChar();

            if ($char === '') {
                break;
            }

            $inArray = in_array($char, $match);

            // strspn
            if ($while && !$inArray) {
                break;
            }
            // strcspn
            elseif (!$while && $inArray) {
                break;
            }

            if ($advancePointer) {
                if ($char === "\n") {
                    $this->newlines[] = $this->data->posChar();
                    $this->_column = 1;
                    $this->_line++;
                } else {
                    $this->_column++;
                }
            }

            $string .= $char;
            $count++;
            if ($count === $limit) {
                break;
            }
        }

        // If the end is reached the pointer isn't moved when the last character
        // is checked, so it only needs to be moved backwards if not wanting the
        // pointer to move.
        if ($char === '') {
            if (!$advancePointer) {
                $this->data->seek(0 - $count - 1);
            }
        } else {
            $this->data->seek(($advancePointer) ? -1 : 0 - $count - 2);
        }

        if (self::$debug) {
            echo ($advancePointer) ? "\nconsume" : "\npeek";
            echo ($while) ? 'While' : 'Until';
            echo "\n==========\nPattern: ";
            var_export(str_replace(["\t", "\n", "\x0c", "\x0d"], ['\t', '\n', '\x0c', '\x0d'], implode('', $match)));
            echo "\nData: ";
            var_export($string);
            echo "\nPointer: {$this->data->posChar()}\n==========\n\n";
        }

        return $string;
    }

    public function __get($property) {
        switch ($property) {
            case 'column': return $this->_column;
            break;
            case 'line': return $this->_line;
            break;
            case 'pointer': return $this->data->posChar();
            break;
            default: return null;
        }
    }
}
