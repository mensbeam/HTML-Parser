<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Data;
use MensBeam\HTML\Document;
use MensBeam\HTML\LoopException;
use MensBeam\HTML\NotImplementedException;
use MensBeam\HTML\OpenElementsStack;
use MensBeam\HTML\ParseError;
use MensBeam\HTML\Parser;
use MensBeam\HTML\TemplateInsertionModesStack;
use MensBeam\HTML\Tokenizer;
use MensBeam\HTML\TreeBuilder;

/** 
 * @covers \MensBeam\HTML\Document
 * @covers \MensBeam\HTML\Element
 * @covers \MensBeam\HTML\Tokenizer
 * @covers \MensBeam\HTML\TreeBuilder
 * @covers \MensBeam\HTML\ActiveFormattingElementsList
 * @covers \MensBeam\HTML\TemplateInsertionModesStack
 * @covers \MensBeam\HTML\OpenElementsStack
 * @covers \MensBeam\HTML\Stack
 * @covers \MensBeam\HTML\TagToken
 */
class TestTreeConstructor extends \PHPUnit\Framework\TestCase {
    use \MensBeam\HTML\EscapeString;

    protected $out;
    protected $depth;

    /** @dataProvider provideStandardTreeTests */
    public function testStandardTreeTests(string $data, array $exp, array $errors, $fragment): void {
        // certain tests need to be patched to ignore unavoidable limitations of PHP's DOM
        [$exp, $errors, $patched,  $skip] = $this->patchTest($data, $fragment, $errors, $exp);
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
        $actualErrors = [];
        $errorHandler = $this->createStub(ParseError::class);
        $errorHandler->method("emit")->willReturnCallback(function($file, $line, $col, $code) use (&$actualErrors, $errorMap) {
            $actualErrors[] = ['code' => $errorMap[$code], 'line' => $line, 'col' => $col];
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
        }
        // initialize the other classes we need
        $decoder = new Data($data, "STDIN", $errorHandler, "UTF-8");
        $stack = new OpenElementsStack($fragmentContext);
        $tokenizer = new Tokenizer($decoder, $stack, $errorHandler);
        $tokenList = $tokenizer->tokenize();
        $treeBuilder = new TreeBuilder($doc, $decoder, $tokenizer, $tokenList, $errorHandler, $stack, new TemplateInsertionModesStack, $fragmentContext);
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
        if ($errors !== false) {
            // If $errors is false, the test does not include errors when there are in fact errors
            $this->assertCount(sizeof($errors), $actualErrors, var_export($errors, true).var_export($actualErrors, true));
        }
    }

    protected function patchTest(string $data, $fragment, array $errors, array $exp): array {
        $patched = false;
        $skip = "";
        // comments outside the root element are silently dropped by the PHP DOM
        if (!$fragment) {
            for ($a = 0; $a < sizeof($exp); $a++) {
                if (strpos($exp[$a], "| <!--") === 0) {
                    array_splice($exp, $a--, 1);
                    $patched = true;
                }
            }
        }
        // some tests don't document errors when they should
        if (!$errors && in_array($data, [
            // math.dat
            '<math><tr><td><mo><tr>',
            '<math><thead><mo><tbody>',
            '<math><tfoot><mo><tbody>',
            '<math><tbody><mo><tfoot>',
            '<math><tbody><mo></table>',
            '<math><thead><mo></table>',
            '<math><tfoot><mo></table>',
            // namespace-sensitivity.dat
            '<body><table><tr><td><svg><td><foreignObject><span></td>Foo',
            // svg.dat
            '<svg><tr><td><title><tr>',
            '<svg><thead><title><tbody>',
            '<svg><tfoot><title><tbody>',
            '<svg><tbody><title><tfoot>',
            '<svg><tbody><title></table>',
            '<svg><thead><title></table>',
            '<svg><tfoot><title></table>',
            // template.dat
            '<template><a><table><a>',
            // tests6.dat
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"><html></html>',
            // tests8.dat
            '<table><li><li></table>',
            // webkit01.dat
            '<table><tr><td><svg><desc><td></desc><circle>',
            // webkit02.dat
            '<legend>test</legend>',
            '<table><input>',
            '<b><em><foo><foo><aside></b>',
            '<b><em><foo><foo><aside></b></em>',
            '<b><em><foo><foo><foo><aside></b>',
            '<b><em><foo><foo><foo><aside></b></em>',
            '<b><em><foo><foo><foo><foo><foo><foo><foo><foo><foo><foo><aside></b></em>',
            '<b><em><foo><foob><foob><foob><foob><fooc><fooc><fooc><fooc><food><aside></b></em>',
            '<option><XH<optgroup></optgroup>',
            '<svg><foreignObject><div>foo</div><plaintext></foreignObject></svg><div>bar</div>',
            '<svg><foreignObject></foreignObject><title></svg>foo',
            '</foreignObject><plaintext><div>foo</div>',
        ])) {
            $errors = false;
        }
        // other tests do list errors, but they are plainly incorrect in missing some
        if (in_array($data, [
            // doctype01.dat"
            "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n   \"http://www.w3.org/TR/html4/strict.dtd\">Hello",
            '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN""http://www.w3.org/TR/html4/strict.dtd">',
            '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"\'http://www.w3.org/TR/html4/strict.dtd\'>',
            '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.01//EN"\'http://www.w3.org/TR/html4/strict.dtd\'>',
            "<!DOCTYPE HTML PUBLIC'-//W3C//DTD HTML 4.01//EN''http://www.w3.org/TR/html4/strict.dtd'>",
            // entities02.dat
            "<div>ZZ&prod=23</div>",
            "<div>ZZ&AElig=</div>",
            // foreign-fragment.dat
            "<body><foo>",
            "<p><foo>",
            "<p></p><foo>",
            // ruby.dat
            "<html><ruby>a<rtc>b<span></ruby></html>",
            // test1.dat
            "<!-----><font><div>hello<table>excite!<b>me!<th><i>please!</tr><!--X-->", // this one is pretty hairy with buffered characters
        ])) {
            $errors = false;
        }        
        if ($errors) {
            // some "old" errors are made redundant by "new" errors
            $obsoleteSymbolList = implode("|", [
                "illegal-codepoint-for-numeric-entity",
                "eof-in-attribute-value-double-quote",
                "non-void-element-with-trailing-solidus",
                "invalid-character-in-attribute-name",
                "attributes-in-end-tag",
                "expected-tag-name",
                "unexpected-character-after-solidus-in-tag",
                "expected-closing-tag-but-got-char",
                "eof-in-tag-name",
                "need-space-after-doctype",
                "expected-doctype-name-but-got-right-bracket",
                "expected-dashes-or-doctype",
                "expected-space-or-right-bracket-in-doctype",
                "unexpected-char-in-comment",
                "eof-in-comment-double-dash",
                "expected-named-entity",
                "named-entity-without-semicolon",
                "numeric-entity-without-semicolon",
                "expected-numeric-entity",
                "eof-in-attribute-name",
                "unexpected-eof-in-text-mode",
                "unexpected-EOF-after-solidus-in-tag",
                "expected-attribute-name-but-got-eof",
                "eof-in-script-in-script",
                "expected-script-data-but-got-eof",
                "unexpected-EOF-in-text-mode",
                "expected-tag-name-but-got-question-mark",
                "incorrect-comment",
                "self-closing-flag-on-end-tag",
                "invalid-codepoint",
                "invalid-codepoint-in-body",
                "invalid-codepoint-in-foreign-content",
                "end-table-tag-in-caption",
                "equals-in-unquoted-attribute-value",
                "eof-in-numeric-entity",
                "unexpected-char-in-doctype",
                "unexpected-end-of-doctype",
                "unexpected-dash-after-double-dash-in-comment",
                "unexpected-bang-after-double-dash-in-comment",
            ]);
            for ($a = 0, $stop = sizeof($errors); $a < $stop; $a++) {
                if (preg_match("/^\(\d+,\d+\):? ($obsoleteSymbolList)$/", $errors[$a])) {
                    // these errors are redundant with "new" errors
                    unset($errors[$a]);
                }
            }
            $errors = array_values($errors);
            // some other errors appear to document implementation details
            //   rather than what the specificatioon dictates, or are
            //   simple duplicates
            for ($a = 0, $stop = sizeof($errors); $a < $stop; $a++) {
                if (
                    preg_match("/^\(\d+,\d+\): unexpected-end-tag-in-special-element$/", $errors[$a])
                    || preg_match('/^\d+: Unclosed element “[^”]+”\.$/u', $errors[$a])
                    || ($data === '<!---x' && $errors[$a] === "(1:7) eof-in-comment")
                    || ($data === "<!DOCTYPE html><body><table><caption><math><mi>foo</mi><mi>bar</mi>baz</table><p>quux" && $errors[$a] === "(1,78) expected-one-end-tag-but-got-another")
                    || ($data === "<!DOCTYPE html><!-- XXX - XXX" && $errors[$a] === "(1,29): eof-in-comment")
                    || ($data === "<!DOCTYPE html><!-- X" && $errors[$a] === "(1,21): eof-in-comment")
                    || ($data === "<!doctype html><math></html>" && $errors[$a] === "(1,28): expected-one-end-tag-but-got-another")
                    || ($data === "</" && $errors[$a] === "(1,2): expected-closing-tag-but-got-eof")
                    || ($data === "<div foo=`bar`>" && $errors[$a] === "(1,14): unexpected-character-in-unquoted-attribute-value")
                    || (
                        $errors[$a] === "51: Self-closing syntax (“/>”) used on a non-void HTML element. Ignoring the slash and treating as a start tag."
                        && (
                            $data === "<b></b><mglyph/><i></i><malignmark/><u></u><mtext/>X"
                            || $data === "<b></b><mglyph/><i></i><malignmark/><u></u><mi/>X"
                            || $data === "<b></b><mglyph/><i></i><malignmark/><u></u><mo/>X"
                            || $data === "<b></b><mglyph/><i></i><malignmark/><u></u><mn/>X"
                            || $data === "<b></b><mglyph/><i></i><malignmark/><u></u><ms/>X"
                        )
                    )
                    || ($data === "&ammmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmp;" && $errors[$a] === "(1,950): unknown-named-character-reference")
                    || ($data === "&ammmp;" && $errors[$a] === "(1,7): unknown-named-character-reference")
                    || ($data === "FOO<!-- BAR -- <QUX> -- MUX -- >BAZ" && $errors[$a] === "(1,35): eof-in-comment")
                    || ($data === "FOO<!-- BAR --   >BAZ" && $errors[$a] === "(1,21): eof-in-comment")
                ) {
                    // these errors seems to simply be redundant
                    unset($errors[$a]);
                }
            }
            $errors = array_values($errors);
            // other errors are spurious, or are for runs of character tokens
            for ($a = 0, $stop = sizeof($errors); $a < $stop; $a++) {
                if (preg_match("/^\((\d+),(\d+)\):? (foster-parenting-character(?:-in-table)?|unexpected-character-in-colgroup|unexpected-char-after-frameset|unexpected-char-in-frameset|expected-eof-but-got-char)$/", $errors[$a], $m1) && preg_match("/^\((\d+),(\d+)\):? $m1[3]$/", $errors[$a + 1] ?? "", $m2)) {
                    // if the next error is also a character error at the next or same character position, this implies a run of characters where we only have one token
                    // technically we should be reporting each one, so this is properly a FIXME
                    if ($m1[1] == $m2[1] && ($m1[2] + 1 == $m2[2] || $m1[2] == $m2[2])) {
                        unset($errors[$a]);
                        $patched = true;
                    }
                } elseif (preg_match("/^foster-parenting text /", $errors[$a]) && preg_match("/^foster-parenting text /", $errors[$a + 1] ?? "")) {
                    // template tests have a different format of error message
                    unset($errors[$a]);
                    $patched = true;
                } elseif (preg_match("/^\((\d+,\d+)\):? unexpected-end-tag$/", $errors[$a], $m) && preg_match("/^\($m[1]\):? (unexpected-end-tag|end-tag-too-early|expected-one-end-tag-but-got-another|adoption-agency-1.3)$/", $errors[$a + 1] ?? "")) {
                    // unexpected-end-tag errors should only be reported once for a given tag
                    unset($errors[$a]);
                }
            }
            $errors = array_values($errors);
        }
        return [$exp, $errors, $patched, $skip];
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
        if ($e->localName === "template" && $e->namespaceURI === null) {
            $this->push("content");
            $this->depth++;
            foreach ($e->content->childNodes as $n) {
                $this->serializeNode($n);
            }
            $this->depth--;
        }
        foreach ($e->childNodes as $n) {
            $this->serializeNode($n);
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
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\BASE."tests/html5lib-tests/tree-construction/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        $files->append(new \GlobIterator(\MensBeam\HTML\BASE."tests/cases/tree-construction/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
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
                        yield basename($file)." #$index (line $pos)" => [$data, $exp, $errors, $fragment];
                    }
                    $l++;
                    $index++;
                }
            }
        }
    }
}
