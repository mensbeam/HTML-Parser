<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Document;
use MensBeam\HTML\Parser;

/** 
 * @covers \MensBeam\HTML\Document
 * @covers \MensBeam\HTML\DocumentFragment
 * @covers \MensBeam\HTML\Element
 * @covers \MensBeam\HTML\TemplateElement
 * @covers \MensBeam\HTML\Comment
 * @covers \MensBeam\HTML\Text
 */
class TestSerializer extends \PHPUnit\Framework\TestCase {
    use \MensBeam\HTML\EscapeString;

    protected $out;
    protected $depth;

    /** @dataProvider provideStandardSerializerTests */
    public function testStandardTreeTests(array $data, bool $fragment, string $exp): void {
        $node = $this->buildTree($data, $fragment);
        $this->assertSame($exp, (string) $node);
    }

    public function provideStandardSerializerTests(): iterable {
        $blacklist = [];
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\BASE."tests/cases/serializer/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        foreach ($files as $file) {
            $index = 0;
            $l = 0;
            if (!in_array(basename($file), $blacklist)) {
                $lines = array_map(function($v) {
                    return rtrim($v, "\n");
                }, file($file));
                while ($l < sizeof($lines)) {
                    $pos = $l + 1;
                    assert(in_array($lines[$l], ["#document", "#fragment"]), new \Exception("Test $file #$index does not start with #doocument or #fragment tag at line ".($l + 1)));
                    $fragment = $lines[$l] === "#fragment";
                    // collect the test input
                    $data = [];
                    for (++$l; $l < sizeof($lines); $l++) {
                        if (preg_match('/^#(script-(on|off)|output)$/', $lines[$l])) {
                            break;
                        }
                        $data[] = $lines[$l];
                    }
                    // set the script mode, if present
                    assert(preg_match('/^#(script-(on|off)|output)$/', $lines[$l]) === 1, new \Exception("Test $file #$index follows data with something other than script flag or output at line ".($l + 1)));
                    $script = null;
                    if ($lines[$l] === "#script-off") {
                        $script = false;
                        $l++;
                    } elseif ($lines[$l] === "#script-on") {
                        $script = true;
                        $l++;
                    }
                    // collect the output string
                    $exp = [];
                    assert($lines[$l] === "#output", new \Exception("Test $file #$index follows input with something other than output at line ".($l + 1)));
                    for (++$l; $l < sizeof($lines); $l++) {
                        if ($lines[$l] === "" && in_array(($lines[$l + 1] ?? ""), ["#ddocument", "#fragment"])) {
                            break;
                        }
                        assert(preg_match('/^[^#]/', $lines[$l]) === 1, new \Exception("Test $file #$index contains unrecognized data after document at line ".($l + 1)));
                        $exp[] = $lines[$l];
                    }
                    $exp = implode("\n", $exp);
                    if (!$script) {
                        yield basename($file)." #$index (line $pos)" => [$data, $fragment, $exp];
                    }
                    $l++;
                    $index++;
                }
            }
        }
    }

    protected function buildTree(array $data, bool $fragment): \DOMNode {
        $document = new Document;
        $document->appendChild($document->createElement("html"));
        $out = $fragment ? $document->createDocumentFragment() : $document;
        $cur = $out;
        $pad = 2;
        // process each line in turn
        for ($l = 0; $l < sizeof($data); $l++) {
            preg_match('/^(\|\s+)(.+)/', $data[$l], $m);
            // pop any parents as long as the padding of the line is less than the expected padding
            $p = strlen((string) $m[1]);
            assert($p >= 2 && $p <= $pad && !($p % 2), new \Exception("Input data is invalid on line ".($l + 1)));
            while ($p < $pad) {
                $pad -= 2;
                $cur = $cur->parentNode;
            }
            // act based upon what the rest of the line looks like
            $d = $m[2];
            if (preg_match('/^<!-- (.*?) -->$/', $d, $m)) {
                // comment
                $cur->appendChild($document->createComment($m[1]));
            } elseif (preg_match('/^<!DOCTYPE ([^ >]*)(?: "([^"]*)" "([^"]*)")?>$/', $d, $m)) {
                // doctype
                $name = strlen((string) $m[1]) ? $m[1] : null;
                $public = strlen((string) $m[2]) ? $m[2] : null;
                $system = strlen((string) $m[3]) ? $m[3] : null;
                $cur->appendChild($document->implementation->createDocumentType($name, $public, $system));
            } elseif (preg_match('/^<(?:([^ ]+) )?([^>]+)>$/', $d, $m)) {
                // element
                $ns = strlen((string) $m[1]) ? (array_flip(Parser::NAMESPACE_MAP)[$m[1]] ?? null) : null;
                $cur = $cur->appendChild($document->createElementNS($ns, $m[2]));
                $pad += 2;
            } elseif (preg_match('/^(?:([^" ]+) )?([^"=]+)="((?:[^"]|"(?!$))*)"$/', $d, $m)) {
                // attribute
                $ns = strlen((string) $m[1]) ? (array_flip(Parser::NAMESPACE_MAP)[$m[1]] ?? $m[1]) : "";
                $cur->setAttributeNS($ns, $m[2], $m[3]);
            } elseif (preg_match('/^"((?:[^"]|"(?!$))*)("?)$/', $d, $m)) {
                // text
                $t = $m[1];
                while (!strlen((string) $m[2])) {
                    preg_match('/^((?:[^"]|"(?!$))*)("?)$/', $data[++$l], $m);
                    $t .= "\n".$m[1];
                }
                $cur->appendChild($document->createTextNode($t));
            } else {
                throw new \Exception("Input data is invalid on line ".($l + 1));
            }
        }
        return $out;
    }
}
