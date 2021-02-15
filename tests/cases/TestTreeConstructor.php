<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Data;
use dW\HTML5\Document;
use dW\HTML5\EOFToken;
use dW\HTML5\LoopException;
use dW\HTML5\NotImplementedException;
use dW\HTML5\OpenElementsStack;
use dW\HTML5\ParseError;
use dW\HTML5\Parser;
use dW\HTML5\TemplateInsertionModesStack;
use dW\HTML5\Tokenizer;
use dW\HTML5\TreeBuilder;

/** 
 * @covers \dW\HTML5\TreeBuilder
 */
class TestTreeConstructor extends \PHPUnit\Framework\TestCase {
    protected const NS = [
        Parser::HTML_NAMESPACE   => "",
        Parser::SVG_NAMESPACE    => "svg ",
        Parser::MATHML_NAMESPACE => "math ",
    ];

    protected $out;
    protected $depth;

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTests(string $data, array $exp, array $errors, $fragment, ?bool $scripted): void {
        if ($scripted) {
            $this->markTestIncomplete("Scripting is not supported");
        } elseif ($fragment) {
            $this->markTestSkipped("Fragment tests still to be implemented");
        }
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
        // initialize the classes we need
        $decoder = new Data($data, "STDIN", $errorHandler);
        $stack = new OpenElementsStack;
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $doc = new Document;
        $treeBuilder = new TreeBuilder($doc, null, false, null, $stack, new TemplateInsertionModesStack, $tokenizer, $errorHandler, $decoder);
        // run the tree builder
        try {
            do {
                $token = $tokenizer->createToken();
                $treeBuilder->emitToken($token);
            } while (!$token instanceof EOFToken);
        } catch (LoopException $e) {
            $act = $this->serializeTree($doc);
            $this->assertEquals($exp, $act, $e->getMessage()."\n".$treeBuilder->debugLog);
        } catch (NotImplementedException $e) {
            $this->markTestSkipped($e->getMessage());
            return;
        }
        $act = $this->serializeTree($doc);
        $this->assertEquals($exp, $act, $treeBuilder->debugLog);
        // TODO: evaluate errors
    }

    protected function push(string $data): void {
        $this->out[] = "| ".str_repeat("  ", $this->depth).$data;
    }

    protected function serializeTree(\DOMDocument $d): array {
        $this->out = [];
        $this->depth = 0;
        if ($d->doctype) {
            $dt = "<!DOCTYPE ".$d->doctype->name;
            $dt .= strlen($d->doctype->publicId) ? ' "'.$d->doctype->publicId.'"' : "";
            $dt .= strlen($d->doctype->systemId) ? ' "'.$d->doctype->systemId.'"' : "";
            $dt .= ">";
            $this->push($dt);
        }
        if ($d->documentElement) {
            $this->serializeElement($d->documentElement);
        }
        return $this->out;
    }

    protected function serializeElement(\DOMElement $e): void {
        if ($e->namespaceURI) {
            $prefix = $ns[$e->namespaceURI] ?? "";
            assert((bool) $prefix, new \Exception("Prefix for namespace {$e->namespaceURI} is not defined"));
        } else {
            $prefix = "";
        }
        $this->push("<".$prefix.$e->localName.">");
        $this->depth++;
        $attr = [];
        foreach ($e->attributes as $a) {
            $attr[$a->name] = $a->value;
        }
        ksort($attr);
        foreach ($attr as $k => $v) {
            $this->push($k.'="'.$v.'"');
        }
        if ($e->localName === "template") {
            $this->push("content");
            $this->depth++;
        }
        foreach ($e->childNodes as $n) {
            $this->serializeNode($n);
        }
        if ($e->localName === "template") {
            $this->depth--;
        }
        $this->depth--;
    }

    public function serializeNode(\DOMNode $n): void {
        if ($n instanceof \DOMElement) {
            $this->serializeElement($n);
        } elseif ($n instanceof \DOMProcessingInstruction) {
            $this->push("<?".$n->target." ".$n->data.">");
        } elseif ($n instanceof \DOMComment) {
            $this->push("<!-- ".$n->data." -->");
        } elseif ($n instanceof \DOMCharacterData) {
            $this->push('"'.$n->data.'"');
        } else {
            throw new \Exception("Node type ".get_class($n)." not handled");
        }
    }

    public function provideStandardTreeTests(): iterable {
        $blacklist = [];
        foreach (new \GlobIterator(\dW\HTML5\BASE."tests/html5lib-tests/tree-construction/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $file) {
            $index = 0;
            $l = 0;
            if (!in_array(basename($file), $blacklist)) {
                $lines = array_map("trim", file($file));
                while ($l < sizeof($lines)) {
                    $pos = $l + 1;
                    assert($lines[$l] === "#data", new \Exception("Test $file #$index does not start with #data tag at line ".($l + 1)));
                    // collect the test input
                    $data = [];
                    for (++$l; $l < sizeof($lines); $l++) {
                        if ($lines[$l] === "#errors") {
                            break;
                        }
                        $data[] = $lines[$l];
                    }
                    $data = implode("\n", $data);
                    // collect the test errors
                    $errors = [];
                    assert(($lines[$l] ?? "") === "#errors", new \Exception("Test $file #$index does not list errors at line ".($l + 1)));
                    for (++$l; $l < sizeof($lines); $l++) {
                        if ($lines[$l] === "#new-errors") {
                            continue;
                        } elseif (preg_match('/^#(document(-fragment)?|script-(on|off)|)$/', $lines[$l])) {
                            break;
                        }
                        $errors[] = $lines[$l];
                    }
                    // set the script mode, if present
                    assert(preg_match('/^#(script-(on|off)|document(-fragment)?)$/', $lines[$l]) === 1, new \Exception("Test $file #$index follows errors with something other than script flag, document fragment, or document at line ".($l + 1)));
                    $script = null;
                    if ($lines[$l] === "#script-off") {
                        $script = false;
                        $l++;
                    } elseif ($lines[$l] === "#script-on") {
                        $script = true;
                        $l++;
                    }
                    // collect the document fragment, if present
                    assert(preg_match('/^#document(-fragment)?$/', $lines[$l]) === 1, new \Exception("Test $file #$index follows script flag with something other than document fragment or document at line ".($l + 1)));
                    $fragment = null;
                    if ($lines[$l] === "#document-fragment") {
                        $fragment = $lines[++$l];
                        $l++;
                    }
                    // collect the output tree
                    $exp = [];
                    assert($lines[$l] === "#document", new \Exception("Test $file #$index follows dociument fragment with something other than document at line ".($l + 1)));
                    for (++$l; $l < sizeof($lines); $l++) {
                        if ($lines[$l] === "" && ($lines[$l + 1] ?? "") === "#data") {
                            break;
                        } elseif (($lines[$l][0] ?? "") !== "|") {
                            // apend the data to the previous token
                            $exp[sizeof($exp) - 1] .= "\n".$lines[$l];
                            continue;
                        }
                        assert(preg_match('/^[^#]/', $lines[$l]) === 1, new \Exception("Test $file #$index contains unrecognized data after document at line ".($l + 1)));
                        $exp[] = $lines[$l];
                    }
                    yield "$file #$index (line $pos)" => [$data, $exp, $errors, $fragment, $script];
                    $l++;
                    $index++;
                }
            }
        }
    }
}