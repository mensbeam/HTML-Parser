<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Parser\Data;
use MensBeam\HTML\Parser\EOFToken;
use MensBeam\HTML\Parser\OpenElementsStack;
use MensBeam\HTML\Parser\ParseError;
use MensBeam\HTML\Parser\Tokenizer;
use MensBeam\HTML\Parser\CharacterToken;
use MensBeam\HTML\Parser\CommentToken;
use MensBeam\HTML\Parser\DOCTYPEToken;
use MensBeam\HTML\Parser\EndTagToken;
use MensBeam\HTML\Parser\NullCharacterToken;
use MensBeam\HTML\Parser\StartTagToken;
use MensBeam\HTML\Parser\TokenAttr;
use MensBeam\HTML\Parser\WhitespaceToken;
use MensBeam\Intl\Encoding\UTF8;

/** 
 * @covers \MensBeam\HTML\Parser\Data
 * @covers \MensBeam\HTML\Parser\Tokenizer
 * @covers \MensBeam\HTML\Parser\CharacterToken
 * @covers \MensBeam\HTML\Parser\CommentToken
 * @covers \MensBeam\HTML\Parser\DataToken
 * @covers \MensBeam\HTML\Parser\TagToken
 * @covers \MensBeam\HTML\Parser\DOCTYPEToken
 * @covers \MensBeam\HTML\Parser\TokenAttr
 */
class TestTokenizer extends \PHPUnit\Framework\TestCase {
    const STATE_MAP = [
        'Data state'          => Tokenizer::DATA_STATE,
        'PLAINTEXT state'     => Tokenizer::PLAINTEXT_STATE,
        'RCDATA state'        => Tokenizer::RCDATA_STATE,
        'RAWTEXT state'       => Tokenizer::RAWTEXT_STATE,
        'Script data state'   => Tokenizer::SCRIPT_DATA_STATE,
        'CDATA section state' => Tokenizer::CDATA_SECTION_STATE,
    ];

    /** @dataProvider provideStandardTokenizerTests */
    public function testStandardTokenizerTests(string $input, array $expected, int $state, string $open = null, array $expErrors) {
        $errorHandler = new ParseError;
        // initialize a stack of open elements, possibly with an open element
        $stack = new OpenElementsStack();
        if ($open) {
            $stack[] = (new \DOMDocument)->createElement($open);
        }
        // initialize the data stream and tokenizer
        $data = new Data($input, "UTF-8", $errorHandler, "UTF-8");
        $tokenizer = new Tokenizer($data, $stack, $errorHandler);
        $tokenizer->state = $state;
        // perform the test
        $actual = [];
        try {
            foreach ($tokenizer->tokenize() as $t) {
                assert(
                    (!$t instanceof CharacterToken)
                    || ($t instanceof NullCharacterToken && $t->data === "\0")
                    || ($t instanceof WhitespaceToken && strspn($t->data, Data::WHITESPACE) === strlen($t->data))
                    || ($t->data !== "\0" && strspn($t->data, Data::WHITESPACE) === 0)
                , new \Exception("Character token must either consist of a single null character, consist only of whitespace, or start with other than whitespace: ".get_class($t)." ".var_export($t->data ?? "''", true)));
                $actual[] = $t;
            }
        } finally {
            $actual = $this->combineCharacterTokens($actual);
            $errors = $this->formatErrors($errorHandler->errors);
            $this->assertEquals($expected, $actual, $tokenizer->debugLog);
            $this->assertEquals($expErrors, $errors, $tokenizer->debugLog);
        }
    }

    public function provideStandardTokenizerTests() {
        $tests = [];
        $blacklist = ["xmlViolation.test"];
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/html5lib-tests/tokenizer/*.test", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/cases/tokenizer/*.test", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        foreach ($files as $file) {
            if (!in_array(basename($file), $blacklist)) {
                $tests[] = $file;
            }
        }
        return $this->makeTokenTests(...$tests);
    }

    protected function reverseDoubleEscape(string $str): string {
        if (preg_match_all("/\\\\u([0-9a-f]{4})/i", $str, $matches)) {
            for ($a = 0; $a < sizeof($matches[0]); $a++) {
                $esc = $matches[0][$a];
                $chr = UTF8::encode(hexdec($matches[1][$a]));
                $str = str_replace($esc, $chr, $str);
            }
        }
        return $str;
    }

    protected function combineCharacterTokens(array $tokens) : array {
        $out = [];
        $pending = null;
        foreach ($tokens as $t) {
            if ($t instanceof CharacterToken) {
                if (!$pending) {
                    if ($t instanceof WhitespaceToken || $t instanceof NullCharacterToken) {
                        $t = new CharacterToken($t->data);
                    }
                    $pending = $t;
                } else {
                    $pending->data .= $t->data;
                }
            } else {
                if ($pending) {
                    $out[] = $pending;
                    $pending = null;
                }
                $out[] = $t;
            }
        }
        if ($pending) {
            $out[] = $pending;
        }
        return $out;
    }

    protected function makeTokenTests(string ...$file): iterable {
        foreach ($file as $path) {
            $f = basename($path);
            $testSet = json_decode(file_get_contents($path), true);
            foreach ($testSet['tests'] ?? $testSet['xmlViolationTests'] as $index => $test) {
                $testId = "$f #$index";
                if ($test['doubleEscaped'] ?? false) {
                    $test['input'] = $this->reverseDoubleEscape($test['input']);
                    for ($a = 0; $a < sizeof($test['output']); $a++) {
                        for ($b = 0; $b < sizeof($test['output'][$a]); $b++) {
                            if (is_string($test['output'][$a][$b])) {
                                $test['output'][$a][$b] = $this->reverseDoubleEscape($test['output'][$a][$b]);
                            }
                        }
                    }
                }
                $test['initialStates'] = $test['initialStates'] ?? ["Data state"];
                // check if a test needs a patch due to trivial differences in implementation
                $this->patchTest($test);
                for ($a = 0; $a < sizeof($test['initialStates']); $a++) {
                    $tokens = [];
                    foreach ($test['output'] as $token) {
                        switch ($token[0]) {
                            case "DOCTYPE":
                                $t = new DOCTYPEToken((string) $token[1], (string) $token[2], (string) $token[3]);
                                $t->forceQuirks = !$token[4];
                                $tokens[] = $t;
                                break;
                            case "StartTag":
                                $t = new StartTagToken($token[1], $token[3] ?? false);
                                foreach ($token[2] ?? [] as $name => $value) {
                                    $t->attributes[] = new TokenAttr((string) $name, $value);
                                }
                                $tokens[] = $t;
                                break;
                            case "EndTag":
                                $tokens[] = new EndTagToken($token[1]);
                                break;
                            case "Character":
                                $tokens[] = new CharacterToken($token[1]);
                                break;
                            case "Comment":
                                $tokens[] = new CommentToken($token[1]);
                                break;
                            default:
                                throw new \Exception("Token type '{$token[0]}' not implemented in standard test interpreter");
                        }
                        unset($t);
                    }
                    $tokens[] = new EOFToken;
                    yield "$testId: {$test['description']} ({$test['initialStates'][$a]})" => [
                        $test['input'],                                 // input
                        $tokens,                                        // output
                        self::STATE_MAP[$test['initialStates'][$a]],    // initial state
                        $test['lastStartTag'] ?? null,                  // open element, if any
                        $test['errors'] ?? [],                          // errors, if any
                    ];
                }
            }
        }
    }

    protected function formatErrors(array $errors): array {
        $errorMap = array_map(function($str) {
            return strtolower(str_replace("_", "-", $str));
        }, array_flip(array_filter((new \ReflectionClass(ParseError::class))->getConstants(), function($v) {
            return is_int($v);
        })));
        $out = [];
        foreach ($errors as list($line, $col, $code)) {
            $out[] = ['code' => $errorMap[$code], 'line' => $line, 'col' => $col];
        }
        return $out;
    }

    protected function patchTest(&$test): void {
        $id = [$test['input'], $test['initialStates']];
        switch ($id) {
            // test emits input stream error first despite peeking 
            case ["<!\u{B}", ["Data state"]]:
                $test['errors'] = array_reverse($test['errors']);
                break;
            // eof-in-<whatever> positions in some tests don't make sense
            // https://github.com/html5lib/html5lib-tests/issues/125
            case ["", ["CDATA section state"]]:
                // there is no position 2
                $test['errors'][0]['col']--;
                break;
            case ["\u{A}", ["CDATA section state"]]:
                // the line break is, for some reason, not counted in the test
                $test['errors'][0]['line']++;
                $test['errors'][0]['col'] = 1;
                break;
            case ["<!----!\r\n>", ["Data state"]]:
            case ["<!----!\n>", ["Data state"]]:
            case ["<!----!\r>", ["Data state"]]:
                // the line break is, for some reason, not counted in the test
                $test['errors'][0]['line']++;
                $test['errors'][0]['col'] = 2;
                break;
            case ["<!----! >", ["Data state"]]:
                $test['errors'][0]['col']++;
                break;
            case [hex2bin("f4808080"), ["CDATA section state"]]:
            case [hex2bin("3bf4808080"), ["CDATA section state"]]:
                // malpaired surrogates count as two characters
                $test['errors'][0]['col']++;
                break;
        }
    }
}
