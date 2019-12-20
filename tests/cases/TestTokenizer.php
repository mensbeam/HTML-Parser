<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Data;
use dW\HTML5\EOFToken;
use dW\HTML5\OpenElementsStack;
use dW\HTML5\ParseError;
use dW\HTML5\Tokenizer;

/** 
 * @covers \dW\HTML5\Tokenizer
 * @covers \dW\HTML5\Data
 * @covers \dW\HTML5\CharacterToken
 * @covers \dW\HTML5\CommentToken
 * @covers \dW\HTML5\DataToken
 * @covers \dW\HTML5\TagToken
 * @covers \dW\HTML5\DOCTYPEToken
 * @covers \dW\HTML5\TokenAttr
 */
class TestTokenizer extends \dW\HTML5\Test\StandardTest {
    const DEBUG = false;

    public function setUp(): void {
        if (self::DEBUG) {
            ob_end_clean();
            Tokenizer::$debug = true;
        }
    }
    /** @dataProvider provideStandardTokenizerTests */
    public function testStandardTokenizerTests(string $input, array $expected, int $state, string $open = null, array $expErrors) {
        // convert parse error constants into standard symbols in specification
        $errorMap = array_map(function($str) {
            return strtolower(str_replace("_", "-", $str));
        }, array_flip(array_filter((new \ReflectionClass(ParseError::class))->getConstants(), function($v) {
            return is_int($v);
        })));
        // create a stub error handler which collects parse errors
        $errors = [];
        $errorHandler = $this->createStub(ParseError::class);
        $errorHandler->method("emit")->willReturnCallback(function($file, $line, $col, $code) use (&$errors, $errorMap) {
            $errors[] = ['code' => $errorMap[$code], 'line' => $line, 'col' => $col];
            return true;
        });
        // initialize a stack of open elements, possibly with an open element
        $stack = new OpenElementsStack();
        if ($open) {
            $stack[] = (new \DOMDocument)->createElement($open);
        }
        // initialize the data stream and tokenizer
        $data = new Data($input, "STDIN", $errorHandler);
        $tokenizer = new Tokenizer($data, $stack, $errorHandler);
        $tokenizer->state = $state;
        // perform the test
        $actual = [];
        try {
            do {
                $t = $tokenizer->createToken();
                if (!($t instanceof EOFToken)) {
                    $actual[] = $t;
                }
            } while (!($t instanceof EOFToken));
        } finally {
            //$expErrors = $expErrors ? array_column($expErrors, "code") : [];
            //$errors = $errors ? array_column($errors, "code") : [];
            $actual = $this->combineCharacterTokens($actual);
            $this->assertEquals($expected, $actual, $tokenizer->debugLog);
            $this->assertEquals($expErrors, $errors, $tokenizer->debugLog);
        }
    }

    public function provideStandardTokenizerTests() {
        $tests = [];
        $blacklist = ["xmlViolation.test"];
        foreach (new \GlobIterator(\dW\HTML5\BASE."tests/html5lib-tests/tokenizer/*.test", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $file) {
            if (!in_array(basename($file), $blacklist)) {
                $tests[] = $file;
            }
        }
        return $this->makeTokenTests(...$tests);
    }
}
