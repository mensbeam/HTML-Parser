<?php
declare(strict_types=1);
namespace dW\HTML5\Test;

use dW\HTML5\CharacterToken;
use dW\HTML5\CommentToken;
use dW\HTML5\DOCTYPEToken;
use dW\HTML5\EndTagToken;
use dW\HTML5\StartTagToken;
use dW\HTML5\Tokenizer;

class StandardTest extends \PHPUnit\Framework\TestCase {
    const STATE_MAP = [
        'Data state'          => Tokenizer::DATA_STATE,
        'PLAINTEXT state'     => Tokenizer::PLAINTEXT_STATE,
        'RCDATA state'        => Tokenizer::RCDATA_STATE,
        'RAWTEXT state'       => Tokenizer::RAWTEXT_STATE,
        'Script data state'   => Tokenizer::SCRIPT_DATA_STATE,
        'CDATA section state' => Tokenizer::CDATA_SECTION_STATE,
    ];

    protected function reverseDoubleEscape(string $str): string {
        if (preg_match_all("/\\\\u([0-9a-f]{4})/i", $str, $matches)) {
            for ($a = 0; $a < sizeof($matches[0]); $a++) {
                $esc = $matches[0][$a];
                $chr = \MensBeam\Intl\Encoding\UTF8::encode(hexdec($matches[1][$a]));
                $str = str_replace($esc, $chr, $str);
            }
        }
        return $str;
    }

    protected function makeTokenTests(string ...$file): iterable {
        foreach ($file as $path) {
            $f = basename($path);
            $testSet = json_decode(file_get_contents($path), true);
            foreach ($testSet['tests'] ?? $testSet['xmlViolationTests'] as $index => $test) {
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
                                    $t->setAttribute((string) $name, $value);
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
                    yield "$f #$index: {$test['description']} ({$test['initialStates'][$a]})" => [
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
}
