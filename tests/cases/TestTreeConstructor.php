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
    protected $out;
    protected $depth;

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTests(string $data, array $exp, array $errors, $fragment): void {
        // certain tests need to be patched to ignore unavoidable limitations of PHP's DOM
        [$exp, $patched, $skip] = $this->patchTest($data, $fragment, $exp);
        if (strlen($skip)) {
            $this->markTestSkipped($skip);
        } elseif ($patched) {
            $this->markAsRisky();
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
        // initialize the output document
        $doc = new Document;
        // prepare the fragment context, if any
        if ($fragment) {
            $fragment = explode(" ", $fragment);
            assert(sizeof($fragment) < 3);
            if (sizeof($fragment) === 1) {
                $fragmentContext = $doc->createElement($fragment[0]);
            } else {
                $ns = array_flip(Parser::NAMESPACE_MAP)[$fragment[0]] ?? null;
                assert(isset($ns));
                $fragmentContext = $doc->createElementNS($ns, $fragment[1]);
            }
        } else {
            $fragmentContext = null;
        }// initialize the other classes we need
        $decoder = new Data($data, "STDIN", $errorHandler, "UTF-8");
        $stack = new OpenElementsStack($fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $treeBuilder = new TreeBuilder($doc, $decoder, $tokenizer, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext);
        // run the tree builder
        try {
            do {
                $token = $tokenizer->createToken();
                $treeBuilder->emitToken($token);
            } while (!$token instanceof EOFToken);
        } catch (\DOMException $e) {
            $this->markTestSkipped('Requires implementation of the "Coercing an HTML DOM into an infoset" specification section');
            return;
        } catch (LoopException $e) {
            $act = $this->serializeTree($doc, (bool) $fragmentContext);
            $this->assertEquals($exp, $act, $e->getMessage()."\n".$treeBuilder->debugLog);
            throw $e;
        } catch (NotImplementedException $e) {
            $this->markTestSkipped($e->getMessage());
            return;
        }
        $act = $this->serializeTree($doc, (bool) $fragmentContext);
        $this->assertEquals($exp, $act, $treeBuilder->debugLog);
        // TODO: evaluate errors
    }

    protected function patchTest(string $data, $fragment, array $exp): array {
        $patched = false;
        $skip = "";
        // comments outside the root element are silently dropped by the PHP DOM
        for ($a = 0; $a < sizeof($exp); $a++) {
            if (strpos($exp[$a], "| <!--") === 0) {
                array_splice($exp, $a--, 1);
                $patched = true;
            }
        }
        if ($data === '<!DOCTYPE html><html xml:lang=bar><html xml:lang=foo>') {
            $skip = 'Requires implementation of the "Coercing an HTML DOM into an infoset" specification section';
        }
        return [$exp, $patched, $skip];
    }

    protected function push(string $data): void {
        $this->out[] = "| ".str_repeat("  ", $this->depth).$data;
    }

    protected function serializeTree(Document $d, bool $fragment): array {
        $this->out = [];
        $this->depth = 0;
        if ($fragment){
            foreach ($d->documentElement->childNodes as $n) {
                $this->serializeNode($n);
            }
        } else {
            if ($d->doctype) {
                $dt = "<!DOCTYPE ";
                $dt .= ($d->doctype->name !== " ") ? $d->doctype->name : "";
                if (strlen($d->doctype->publicId) || strlen($d->doctype->systemId)) {
                    $dt .= ' "'.$d->doctype->publicId.'"';
                    $dt .= ' "'.$d->doctype->systemId.'"';
                }
                $dt .= ">";
                $this->push($dt);
            }
            if ($d->documentElement) {
                $this->serializeElement($d->documentElement);
            }
        }
        return $this->out;
    }

    protected function serializeElement(\DOMElement $e): void {
        if ($e->namespaceURI) {
            $prefix = Parser::NAMESPACE_MAP[$e->namespaceURI];
            assert((bool) $prefix, new \Exception("Prefix for namespace {$e->namespaceURI} is not defined"));
            $prefix .= " ";
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

    protected function serializeNode(\DOMNode $n): void {
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
                $lines = array_map(function($v) {
                    return rtrim($v, "\n");
                }, file($file));
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
                    if (!$script) {
                        // scripting-dependent tests are skipped entirely since we will not support scripting
                        yield "$file #$index (line $pos)" => [$data, $exp, $errors, $fragment];
                    }
                    $l++;
                    $index++;
                }
            }
        }
    }
}