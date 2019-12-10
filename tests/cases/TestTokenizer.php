<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Data;
use dW\HTML5\EOFToken;
use dW\HTML5\OpenElementsStack;
use dW\HTML5\Tokenizer;

class TestTokenizer extends \dW\HTML5\Test\StandardTest {
    /** @dataProvider provideStandardTokenizerTests */
    public function testStandardTokenizerTests(string $input, array $expected, int $state, string $open = null, array $errors) {
        $data = new Data($input);
        $stack = new OpenElementsStack();
        if ($open) {
            $stack[] = $open;
        }
        $tokenizer = new Tokenizer($data, $stack);
        $tokenizer->state = $state;
        $actual = [];
        while (!($t = $tokenizer->createToken()) instanceof EOFToken) {
            $actual[] = $t;
        }
        $this->assertEquals($expected, $actual);
    }

    public function provideStandardTokenizerTests() {
        $out = $this->makeTokenTests(__DIR__."/../html5lib-tests/tokenizer/test1.test");
        return array_slice(iterator_to_array($out), 0, 3);
    }
}
