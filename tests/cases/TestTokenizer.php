<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Data;
use dW\HTML5\EOFToken;
use dW\HTML5\OpenElementsStack;
use dW\HTML5\ParseError;
use dW\HTML5\Tokenizer;

/** @covers \dW\HTML5\Tokenizer */
class TestTokenizer extends \dW\HTML5\Test\StandardTest {
    const DEBUG = false;

    public function setUp(): void {
        if (self::DEBUG) {
            ob_end_clean();
            Tokenizer::$debug = true;
        }
    }
    /** @dataProvider provideStandardTokenizerTests */
    public function testStandardTokenizerTests(string $input, array $expected, int $state, string $open = null, array $errors) {
        // create a mock error handler which simply discards parse errors for now
        $errorHandler = $this->createMock(ParseError::class);
        $errorHandler->method("emit")->willReturn(true);
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
            $actual = $this->combineCharacterTokens($actual);
            $this->assertEquals($expected, $actual, $tokenizer->debugLog);
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
