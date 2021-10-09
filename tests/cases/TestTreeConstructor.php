<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Parser;
use MensBeam\HTML\Parser\Config;
use MensBeam\HTML\Parser\Data;
use MensBeam\HTML\Parser\LoopException;
use MensBeam\HTML\Parser\NotImplementedException;
use MensBeam\HTML\Parser\OpenElementsStack;
use MensBeam\HTML\Parser\ParseError;
use MensBeam\HTML\Parser\TemplateInsertionModesStack;
use MensBeam\HTML\Parser\Tokenizer;
use MensBeam\HTML\Parser\TreeBuilder;

/** 
 * @covers \MensBeam\HTML\Parser\Tokenizer
 * @covers \MensBeam\HTML\Parser\TreeBuilder
 * @covers \MensBeam\HTML\Parser\ActiveFormattingElementsList
 * @covers \MensBeam\HTML\Parser\TemplateInsertionModesStack
 * @covers \MensBeam\HTML\Parser\OpenElementsStack
 * @covers \MensBeam\HTML\Parser\Stack
 * @covers \MensBeam\HTML\Parser\TagToken
 * @covers \MensBeam\HTML\Parser\ProcessingInstructionToken
 */
class TestTreeConstructor extends \PHPUnit\Framework\TestCase {
    use \MensBeam\HTML\Parser\NameCoercion;

    protected $out;
    protected $depth;
    protected $ns;

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTests(string $data, array $exp, array $errors, $fragment): void {
        $this->runTreeTest($data, $exp, $errors, $fragment, null);
    }

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTestsWithHtmlNamespace(string $data, array $exp, array $errors, $fragment): void {
        $config = new Config;
        $config->htmlNamespace = true;
        $this->runTreeTest($data, $exp, $errors, $fragment, $config);
    }

    public function provideStandardTreeTests(): iterable {
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/html5lib-tests/tree-construction/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/cases/tree-construction/mensbeam*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        return $this->parseTreeTest($files);
    }

    /** @dataProvider provideProcessingInstructionTreeTests */
    public function testProcessingInstructionTreeTests(string $data, array $exp, array $errors, $fragment): void {
        $config = new Config;
        $config->processingInstructions = true;
        $this->runTreeTest($data, $exp, $errors, $fragment, $config);
    }

    public function provideProcessingInstructionTreeTests(): iterable {
        $files = new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/cases/tree-construction/pi*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);
        return $this->parseTreeTest($files);
    }

    protected function runTreeTest(string $data, array $exp, array $errors, ?string $fragment, ?Config $config): void {
        $this->ns = ($config && $config->htmlNamespace);
        $htmlNamespace = ($this->ns) ? Parser::HTML_NAMESPACE : null;
        // certain tests need to be patched to ignore unavoidable limitations of PHP's DOM
        [$exp, $errors] = $this->patchTest($data, $fragment, $errors, $exp);
        // initialize the output document
        $doc = new \DOMDocument;
        // prepare the fragment context, if any
        if ($fragment) {
            $fragment = explode(" ", $fragment);
            assert(sizeof($fragment) < 3);
            if (sizeof($fragment) === 1) {
                // an HTML element
                $fragmentContext = $doc->createElementNS($htmlNamespace, $fragment[0]);
            } else {
                $ns = array_flip(Parser::NAMESPACE_MAP)[$fragment[0]] ?? null;
                assert(isset($ns));
                $fragmentContext = $doc->createElementNS($ns, $fragment[1]);
            }
        } else {
            $fragmentContext = null;
        }
        // initialize the other classes we need
        $errorHandler = new ParseError;
        $decoder = new Data($data, "UTF-8", $errorHandler, "UTF-8");
        $stack = new OpenElementsStack($htmlNamespace, $fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $tokenList = $tokenizer->tokenize();
        $treeBuilder = new TreeBuilder($doc, $decoder, $tokenizer, $tokenList, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext, 0, $config);
        // run the tree builder
        try {
            $treeBuilder->constructTree();
        } catch (LoopException $e) {
            $act = $this->balanceTree($this->serializeTree($doc, (bool) $fragmentContext), $exp);
            $this->assertEquals($exp, $act, $e->getMessage()."\n".$treeBuilder->debugLog);
            throw $e;
        } catch (NotImplementedException $e) {
            $this->markTestSkipped($e->getMessage());
            return;
        }
        $act = $this->balanceTree($this->serializeTree($doc, (bool) $fragmentContext), $exp);
        $this->assertEquals($exp, $act, $treeBuilder->debugLog);
        // skip checking errors in some tests for now
        if (in_array($data, [
            "<!doctype html><math></html>", // emits an error I cannot account for
            "<head><noscript>XXX<!--foo--></noscript></head>", // number of character-related parse errors is wrong
        ])) {
            $this->markTestSkipped();
        }
        $actualErrors = $this->formatErrors($errorHandler->errors);
        $this->assertCount(sizeof($errors['old']), $actualErrors, $treeBuilder->debugLog."\n".var_export($errors['old'], true).var_export($actualErrors, true));
    }

    protected function formatErrors(array $errors): array {
        $errorMap = array_map(function($str) {
            return strtolower(str_replace("_", "-", $str));
        }, array_flip(array_filter((new \ReflectionClass(ParseError::class))->getConstants(), function($v) {
            return is_int($v);
        })));
        $out = [];
        foreach ($errors as list($line, $col, $code)) {
            $out[] = "($line:$col): ".$errorMap[$code];
        }
        return $out;
    }

    protected function patchTest(string $data, $fragment, array $errors, array $exp): array {
        // When using the HTML namespace, xmlns attribute cannot be inserted due to a PHP limitation
        if ($this->ns) {
            for ($a = 0; $a < sizeof($exp); $a++) {
                if (preg_match('/^\|\s+xmlns xmlns=/', $exp[$a])) {
                    array_splice($exp, $a--, 1);
                }
            }
        }
        return [$exp, $errors];
        // some "old" errors are made redundant by "new" errors
        $symbolMap = [
            'incorrectly-closed-comment'                                       => ["unexpected-bang-after-double-dash-in-comment"],
            'abrupt-closing-of-empty-comment'                                  => ["incorrect-comment"],
            'unexpected-question-mark-instead-of-tag-name'                     => ["expected-tag-name-but-got-question-mark"],
            'missing-whitespace-before-doctype-name'                           => ["need-space-after-doctype"],
            'missing-doctype-name'                                             => ["expected-doctype-name-but-got-right-bracket"],
            'invalid-character-sequence-after-doctype-name'                    => ["expected-space-or-right-bracket-in-doctype"],
            'missing-doctype-system-identifier'                                => ["unexpected-char-in-doctype"],
            'missing-quote-before-doctype-system-identifier'                   => ["unexpected-char-in-doctype"],
            'missing-doctype-public-identifier'                                => ["unexpected-end-of-doctype"],
            'missing-quote-before-doctype-public-identifier'                   => ["unexpected-char-in-doctype"],
            'missing-whitespace-between-doctype-public-and-system-identifiers' => ["unexpected-char-in-doctype"],
            'unexpected-null-character'                                        => ["invalid-codepoint"],
            'eof-in-script-html-comment-like-text'                             => ["expected-script-data-but-got-eof", "unexpected-eof-in-text-mode", "eof-in-script-in-script", "unexpected-EOF-in-text-mode"],
            'missing-semicolon-after-character-reference'                      => ["named-entity-without-semicolon", "expected-numeric-entity", "numeric-entity-without-semicolon", "eof-in-numeric-entity"],
            'absence-of-digits-in-numeric-character-reference'                 => ["expected-numeric-entity"],
            'null-character-reference'                                         => ["illegal-codepoint-for-numeric-entity"],
            'control-character-reference'                                      => ["illegal-codepoint-for-numeric-entity"],
            'surrogate-character-reference'                                    => ["illegal-codepoint-for-numeric-entity"],
            'noncharacter-character-reference'                                 => ["illegal-codepoint-for-numeric-entity"],
            'character-reference-outside-unicode-range'                        => ["illegal-codepoint-for-numeric-entity"],
            'non-void-html-element-start-tag-with-trailing-solidus'            => ["Ignoring the slash and treating as a start tag."],
            'unexpected-character-in-attribute-name'                           => ["invalid-character-in-attribute-name"],
            'unexpected-character-in-unquoted-attribute-value'                 => ["equals-in-unquoted-attribute-value"],
            'cdata-in-html-content'                                            => ["expected-dashes-or-doctype"],
            'incorrectly-opened-comment'                                       => ["expected-dashes-or-doctype"],
            'end-tag-with-trailing-solidus'                                    => ["self-closing-flag-on-end-tag"],
            'unexpected-solidus-in-tag'                                        => ["unexpected-character-after-solidus-in-tag"],
            'end-tag-with-attributes'                                          => ["attributes-in-end-tag"],
            'eof-before-tag-name'                                              => ["expected-tag-name", "expected-closing-tag-but-got-eof"],
            'invalid-first-character-of-tag-name'                              => ["expected-tag-name", "expected-closing-tag-but-got-char"],
            'eof-in-tag'                                                       => ["expected-attribute-name-but-got-eof", "unexpected-EOF-after-solidus-in-tag", "eof-in-attribute-name", "eof-in-tag-name", "eof-in-attribute-value-double-quote"],
            'unknown-named-character-reference'                                => ["expected-named-entity"],
            'eof-in-comment'                                                   => ["eof-in-comment-double-dash"],
            'nested-comment'                                                   => ["unexpected-char-in-comment"],
            'non-void-html-element-start-tag-with-trailing-solidus'            => ["non-void-element-with-trailing-solidus", "Ignoring the slash and treating as a start tag."],
        ];
        foreach ($errors['new'] as $new) {
            preg_match("/\s(\S+)$/", $new, $m);
            assert(is_array($m) && sizeof($m) === 2);
            $new = $m[1];
            $old = implode("|", [$new, ...$symbolMap[$new] ?? []]);
            for ($a = 0, $stop = sizeof($errors['old']); $a < $stop; $a++) {
                if (preg_match("/ ($old)$/", $errors['old'][$a])) {
                    unset($errors['old'][$a]);
                }
            }
            $errors['old'] = array_values($errors['old']);
        }
        $errors = [...$errors['old'], ...$errors['new']];
    }

    protected function balanceTree(array $act, array $exp): array {
        // makes sure that the actual tree contain the same number of lines as the expected tree
        // lines are inserted where the two trees diverge, until the end of the actual tree is reached
        // this usually results in cleaner PHPUnit comparison failure output
        for ($a = 0; $a < sizeof($act) && sizeof($act) < sizeof($exp); $a++) {
            if (!isset($act[$a]) || $exp[$a] !== $act[$a]) {
                array_splice($act, $a, 0, [""]);
            }
        }
        return $act;
    }

    protected function push(string $data): void {
        $this->out[] = "| ".str_repeat("  ", $this->depth).$data;
    }

    protected function serializeTree(\DOMDocument $d, bool $fragment): array {
        $this->out = [];
        $this->depth = 0;
        if ($fragment){
            foreach ($d->documentElement->childNodes as $n) {
                $this->serializeNode($n);
            }
        } else {
            foreach ($d->childNodes as $n) {
                $this->serializeNode($n);
            }
        }
        return $this->out;
    }

    protected function serializeElement(\DOMElement $e): void {
        if ($e->namespaceURI) {
            $prefix = Parser::NAMESPACE_MAP[$e->namespaceURI];
            assert((bool) $prefix, new \Exception("Prefix for namespace {$e->namespaceURI} is not defined"));
            $prefix .= " ";
            if ($this->ns && $prefix === "html ") {
                // if the parser is using the HTML namespace on purpose, the prefix should be omitted
                $prefix = "";
            }
        } else {
            $prefix = "";
            if ($this->ns) {
                // if the parser is using the HTML namespace, elements with the null namespace should be prefixed
                $prefix = "null ";
            }
        }
        $localName = $this->uncoerceName($e->localName);
        $this->push("<".$prefix.$localName.">");
        $this->depth++;
        $attr = [];
        foreach ($e->attributes as $a) {
            $prefix = "";
            if ($a->namespaceURI) {
                $prefix = Parser::NAMESPACE_MAP[$a->namespaceURI];
                assert((bool) $prefix, new \Exception("Prefix for namespace {$a->namespaceURI} is not defined"));
                $prefix .= " ";
            }
            $attr[$prefix.$this->uncoerceName($a->name)] = $a->value;
        }
        ksort($attr, \SORT_STRING);
        foreach ($attr as $k => $v) {
            $this->push($k.'="'.$v.'"');
        }
        if ($e->localName === "template" && $e->namespaceURI === ($this->ns ? Parser::HTML_NAMESPACE : null)) {
            $this->push("content");
            $this->depth++;
            foreach ($e->childNodes as $n) {
                $this->serializeNode($n);
            }
            $this->depth--;
        } else {
            foreach ($e->childNodes as $n) {
                $this->serializeNode($n);
            }
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
        } elseif ($n instanceof \DOMDocumentType) {
            $dt = "<!DOCTYPE ";
            $dt .= ($n->name !== " ") ? $n->name : "";
            if (strlen($n->publicId) || strlen($n->systemId)) {
                $dt .= ' "'.$n->publicId.'"';
                $dt .= ' "'.$n->systemId.'"';
            }
            $dt .= ">";
            $this->push($dt);
        } else {
            throw new \Exception("Node type ".get_class($n)." not handled");
        }
    }

    protected function parseTreeTest(iterable $files, array $blacklist = []): iterable {
        foreach ($files as $file) {
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
                        if (preg_match('/^#(document(-fragment)?|script-(on|off)|new-errors|)$/', $lines[$l])) {
                            break;
                        }
                        $errors[] = $lines[$l];
                    }
                    // collect new errors, if present
                    assert(preg_match('/^#(new-errors|script-(on|off)|document(-fragment)?)$/', $lines[$l]) === 1, new \Exception("Test $file #$index follows errors with something other than new errors, script flag, document fragment, or document at line ".($l + 1)));
                    $newErrors = [];
                    if ($lines[$l] === "#new-errors") {
                        for (++$l; $l < sizeof($lines); $l++) {
                            if (preg_match('/^#(document(-fragment)?|script-(on|off)|)$/', $lines[$l])) {
                                break;
                            }
                            $newErrors[] = $lines[$l];
                        }
                    }
                    // set the script mode, if present
                    assert(preg_match('/^#(script-(on|off)|document(-fragment)?)$/', $lines[$l] ?? "") === 1, new \Exception("Test $file #$index follows new errors with something other than script flag, document fragment, or document at line ".($l + 1)));
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
                    assert($lines[$l] === "#document", new \Exception("Test $file #$index follows document fragment with something other than document at line ".($l + 1)));
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
                        $errors = ['old' => $errors, 'new' => $newErrors];
                        yield basename($file)." #$index (line $pos)" => [$data, $exp, $errors, $fragment];
                    }
                    $l++;
                    $index++;
                }
            }
        }
    }
}
