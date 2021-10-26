<?php
/**
 * @license MIT
 * Copyright 2017, Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details
 */

declare(strict_types=1);
namespace MensBeam\HTML\DOM\TestCase;

use MensBeam\HTML\Parser\Exception;
use MensBeam\HTML\Parser;
use MensBeam\HTML\Parser\AttributeSetter;
use MensBeam\HTML\Parser\NameCoercion;
use MensBeam\HTML\Parser\Serializer;

/** @covers \MensBeam\HTML\Parser\Serializer */
class TestSerializer extends \PHPUnit\Framework\TestCase {
    use NameCoercion, AttributeSetter;

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTests(array $data, bool $fragment, string $exp): void {
        $node = $this->buildTree($data, $fragment);
        $this->assertSame($exp, Serializer::serialize($node));
    }

    public function provideStandardTreeTests(): iterable {
        $blacklist = [];
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/cases/serializer/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        foreach ($files as $file) {
            if (!in_array(basename($file), $blacklist)) {
                yield from $this->parseTreeTestFile($file);
            }
        }
    }

    /** @dataProvider provideTemplateTests */
    public function testSerializeADecoratedTemplate(?string $ns, bool $content, bool $fragment, bool $text, string $exp): void {
        $d = new \DOMDocument;
        $t = $d->createElementNS($ns, "template");
        $t->appendChild($d->createTextNode("EEK"));
        if ($content) {
            $t->content = null;
            if ($fragment) {
                $f = $d->createDocumentFragment();
                $t->content = $f;
                if ($text) {
                    $f->appendChild($d->createTextNode("OOK"));
                }
            }
        }
        $exp1 = $exp;
        $exp2 = "<template>$exp</template>";
        $this->assertSame($exp1, Serializer::serializeInner($t));
        $this->assertSame($exp2, Serializer::serialize($t));
    }

    public function provideTemplateTests(): iterable {
        return [
            [null,                   false, false, false, "EEK"],
            [null,                   true,  false, false, "EEK"],
            [null,                   true,  true,  false, ""],
            [null,                   true,  true,  true,  "OOK"],
            [Parser::HTML_NAMESPACE, false, false, false, "EEK"],
            [Parser::HTML_NAMESPACE, true,  false, false, "EEK"],
            [Parser::HTML_NAMESPACE, true,  true,  false, ""],
            [Parser::HTML_NAMESPACE, true,  true,  true,  "OOK"],
        ];
    }

    /** @dataProvider provideEmptyElementTests */
    public function testInnerSerializeEmptyElement(string $tagName, ?string $ns, string $exp): void {
        $d = new \DOMDocument;
        $e = $d->createElementNS($ns, $tagName);
        $e->appendChild($d->createTextNode("EEK"));
        $this->assertSame($exp, Serializer::serializeInner($e));
    }

    public function provideEmptyElementTests(): iterable {
        return [
            ["basefont", null,                   ""],
            ["bgsound",  null,                   ""],
            ["frame",    null,                   ""],
            ["keygen",   null,                   ""],
            ["area",     null,                   ""],
            ["base",     null,                   ""],
            ["br",       null,                   ""],
            ["col",      null,                   ""],
            ["embed",    null,                   ""],
            ["hr",       null,                   ""],
            ["img",      null,                   ""],
            ["input",    null,                   ""],
            ["link",     null,                   ""],
            ["meta",     null,                   ""],
            ["param",    null,                   ""],
            ["source",   null,                   ""],
            ["track",    null,                   ""],
            ["wbr",      null,                   ""],
            ["basefont", Parser::HTML_NAMESPACE, ""],
            ["bgsound",  Parser::HTML_NAMESPACE, ""],
            ["frame",    Parser::HTML_NAMESPACE, ""],
            ["keygen",   Parser::HTML_NAMESPACE, ""],
            ["area",     Parser::HTML_NAMESPACE, ""],
            ["base",     Parser::HTML_NAMESPACE, ""],
            ["br",       Parser::HTML_NAMESPACE, ""],
            ["col",      Parser::HTML_NAMESPACE, ""],
            ["embed",    Parser::HTML_NAMESPACE, ""],
            ["hr",       Parser::HTML_NAMESPACE, ""],
            ["img",      Parser::HTML_NAMESPACE, ""],
            ["input",    Parser::HTML_NAMESPACE, ""],
            ["link",     Parser::HTML_NAMESPACE, ""],
            ["meta",     Parser::HTML_NAMESPACE, ""],
            ["param",    Parser::HTML_NAMESPACE, ""],
            ["source",   Parser::HTML_NAMESPACE, ""],
            ["track",    Parser::HTML_NAMESPACE, ""],
            ["wbr",      Parser::HTML_NAMESPACE, ""],
            ["basefont", Parser::SVG_NAMESPACE,  "EEK"],
            ["bgsound",  Parser::SVG_NAMESPACE,  "EEK"],
            ["frame",    Parser::SVG_NAMESPACE,  "EEK"],
            ["keygen",   Parser::SVG_NAMESPACE,  "EEK"],
            ["area",     Parser::SVG_NAMESPACE,  "EEK"],
            ["base",     Parser::SVG_NAMESPACE,  "EEK"],
            ["br",       Parser::SVG_NAMESPACE,  "EEK"],
            ["col",      Parser::SVG_NAMESPACE,  "EEK"],
            ["embed",    Parser::SVG_NAMESPACE,  "EEK"],
            ["hr",       Parser::SVG_NAMESPACE,  "EEK"],
            ["img",      Parser::SVG_NAMESPACE,  "EEK"],
            ["input",    Parser::SVG_NAMESPACE,  "EEK"],
            ["link",     Parser::SVG_NAMESPACE,  "EEK"],
            ["meta",     Parser::SVG_NAMESPACE,  "EEK"],
            ["param",    Parser::SVG_NAMESPACE,  "EEK"],
            ["source",   Parser::SVG_NAMESPACE,  "EEK"],
            ["track",    Parser::SVG_NAMESPACE,  "EEK"],
            ["wbr",      Parser::SVG_NAMESPACE,  "EEK"],
        ];
    }

    public function testOuterSerializeAnInvalidNode(): void {
        $d = new \DOMDocument;
        $a = $d->createAttribute("oops");
        $this->expectExceptionObject(new Exception(Exception::UNSUPPORTED_NODE_TYPE, [\DOMAttr::class]));
        Serializer::serialize($a);
    }

    public function testInnerSerializeAnInvalidNode(): void {
        $d = new \DOMDocument;
        $t = $d->createTextNode("OOPS");
        $this->expectExceptionObject(new Exception(Exception::UNSUPPORTED_NODE_TYPE, [\DOMText::class]));
        Serializer::serializeInner($t);
    }

    protected function buildTree(array $data, bool $fragment, bool $formatOutput = false): \DOMNode {
        $document = new \DOMDocument;
        $document->formatOutput = $formatOutput;
        if ($fragment) {
            $document->appendChild($document->createElement("html"));
            $out = $document->createDocumentFragment();
        } else {
            $out = $document;
        }
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
            } elseif (preg_match('/^<!DOCTYPE(?: ([^ >]*)(?: "([^"]*)" "([^"]*)")?)?>$/', $d, $m)) {
                // doctype
                $name = strlen((string) ($m[1] ?? "")) ? $m[1] : " ";
                $public = strlen((string) ($m[2] ?? "")) ? $m[2] : "";
                $system = strlen((string) ($m[3] ?? "")) ? $m[3] : "";
                $cur->appendChild($document->implementation->createDocumentType($name, $public, $system));
            } elseif (preg_match('/^<\?([^ ]+) ([^>]*)>$/', $d, $m)) {
                // processing instruction
                $cur->appendChild($document->createProcessingInstruction($m[1], $m[2]));
            } elseif (preg_match('/^<(?:([^ ]+) )?([^>]+)>$/', $d, $m)) {
                // element
                $ns = strlen((string) $m[1]) ? (array_flip(Parser::NAMESPACE_MAP)[$m[1]] ?? $m[1]) : null;
                $cur = $cur->appendChild($document->createElementNS($ns, self::coerceName($m[2])));
                $pad += 2;
            } elseif (preg_match('/^(?:([^" ]+) )?([^"=]+)="((?:[^"]|"(?!$))*)"$/', $d, $m)) {
                // attribute
                $ns = strlen((string) $m[1]) ? (array_flip(Parser::NAMESPACE_MAP)[$m[1]] ?? $m[1]) : null;
                $this->elementSetAttribute($cur, $ns, $m[2], $m[3]);
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

    protected function parseTreeTestFile(string $file): \Generator {
        $index = 0;
        $l = 0;
        $lines = array_map(function($v) {
            return rtrim($v, "\n");
        }, file($file));
        while ($l < sizeof($lines)) {
            $pos = $l + 1;
            assert(in_array($lines[$l], ["#document", "#fragment"]), new \Exception("Test $file #$index does not start with #document or #fragment tag at line ".($l + 1)));
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
                if ($lines[$l] === "" && in_array(($lines[$l + 1] ?? ""), ["#document", "#fragment"])) {
                    break;
                }
                assert(preg_match('/^([^#]|$)/', $lines[$l]) === 1, new \Exception("Test $file #$index contains unrecognized data after output at line ".($l + 1)));
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
