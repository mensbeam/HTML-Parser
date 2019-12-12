<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Data;
use dW\HTML5\EOFToken;
use dW\HTML5\OpenElementsStack;
use dW\HTML5\Tokenizer;

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
        $data = new Data($input);
        $stack = new OpenElementsStack();
        if ($open) {
            $stack[] = (new \DOMDocument)->createElement($open);
        }
        $tokenizer = new Tokenizer($data, $stack);
        $tokenizer->state = $state;
        $actual = [];
        do {
            $t = $tokenizer->createToken();
            $actual[] = $t;
        } while (!($t instanceof EOFToken));
        array_pop($actual);
        $this->assertEquals($expected, $actual);       
    }

    public function provideStandardTokenizerTests() {
        $tests = [];
        $blacklist = ["pendingSpecChanges.test", "xmlViolation.test"];
        foreach (new \GlobIterator(\dW\HTML5\BASE."tests/html5lib-tests/tokenizer/*.test", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $file) {
            if (!in_array(basename($file), $blacklist)) {
                $tests[] = $file;
            }
        }
        return $this->makeTokenTests(...$tests);
    }
}
