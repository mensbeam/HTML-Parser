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
use MensBeam\HTML\Parser\Config;
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
    public function testSerializeADecoratedTemplate(?string $ns, string $exp): void {
        $d = new \DOMDocument;
        $t = $d->createElementNS($ns, "template");
        $t->appendChild($d->createTextNode("EEK"));
        $exp1 = $exp;
        $exp2 = "<template>$exp</template>";
        $this->assertSame($exp1, Serializer::serializeInner($t));
        $this->assertSame($exp2, Serializer::serialize($t));
    }

    public function provideTemplateTests(): iterable {
        return [
            [null,                   "EEK"],
            [Parser::HTML_NAMESPACE, "EEK"],
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

    /** @dataProvider provideCustomSerializations */
    public function testSerializeWithOptions(bool $fragment, ?string $fragmentContext, int $indentStep, bool $indentWithSpaces, bool $processingInstructions, bool $reformatWhitespace, bool $boolAttr, bool $foreignVoid, string $in, string $exp): void {
        $config = new Config;
        $config->indentStep = $indentStep;
        $config->indentWithSpaces = $indentWithSpaces;
        $config->processingInstructions = $processingInstructions;
        $config->reformatWhitespace = $reformatWhitespace;
        $config->serializeBooleanAttributeValues = $boolAttr;
        $config->serializeForeignVoidEndTags = $foreignVoid;

        if (!$fragment) {
            $d = Parser::parse($in, "UTF-8", $config)->document;
            $act = Parser::serialize($d, $config);
        } else {
            $d = new \DOMDocument();
            $act = Parser::serialize(Parser::parseFragment($d->createElement($fragmentContext), 0, $in, 'UTF-8', $config), $config);
        }

        $this->assertSame($exp, $act);
    }

    public function provideCustomSerializations(): iterable {
        return [
            // Boolean attribute values serialized
            [false, null, 0, false, false, false, true, true,
                <<<HTML
                <a hidden="hidden"></a><b hidden=""></b><c hidden="HIDDEN"></c><d hidden="true"></d>
                HTML,

                <<<HTML
                <html><head></head><body><a hidden="hidden"></a><b hidden=""></b><c hidden="HIDDEN"></c><d hidden="true"></d></body></html>
                HTML
            ],

            // Boolean attribute values not serialized
            [false, null, 0, false, false, false, false, true,
                <<<HTML
                <a hidden="hidden"></a><b hidden=""></b><c hidden="HIDDEN"></c><d hidden="true"></d>
                HTML,

                <<<HTML
                <html><head></head><body><a hidden></a><b hidden></b><c hidden></c><d hidden="true"></d></body></html>
                HTML
            ],

            // Boolean attribute values serialized, foreign void end tags serialized
            [false, null, 0, false, false, false, true, true,
                <<<HTML
                <br><svg/><svg>blah</svg><math/><math>blah</math><input>
                HTML,

                <<<HTML
                <html><head></head><body><br><svg></svg><svg>blah</svg><math></math><math>blah</math><input></body></html>
                HTML
            ],

            // Boolean attribute values serialized, foreign void end tags not serialized
            [false, null, 0, false, false, false, true, false,
                <<<HTML
                <br><svg/><svg>blah</svg><math/><math>blah</math><input>
                HTML,

                <<<HTML
                <html><head></head><body><br><svg/><svg>blah</svg><math/><math>blah</math><input></body></html>
                HTML
            ],

            // Neither attribute values nor foreign void end tags serialized
            [false, null, 0, false, false, false, false, false,
                <<<HTML
                <audio loop hidden></audio><svg/>
                HTML,

                <<<HTML
                <html><head></head><body><audio loop hidden></audio><svg/></body></html>
                HTML
            ],

            // Reformat whitespace, empty document
            [false, null, 1, true, false, true, false, false,
                <<<HTML
                <html></html>
                HTML,

                <<<HTML
                <html>
                 <head></head>

                 <body></body>
                </html>
                HTML
            ],

            // Reformat whitespace, comment before doctype
            [false, null, 1, true, false, true, false, false,
                <<<HTML
                <!--data-->
                <!DOCTYPE html>
                <html></html>
                HTML,

                <<<HTML
                <!--data-->
                <!DOCTYPE html>
                <html>
                 <head></head>

                 <body></body>
                </html>
                HTML
            ],

            // Reformat whitespace, preformatted element
            [false, null, 1, true, false, true, false, false,
                <<<HTML
                <pre><code></code></pre>
                HTML,

                <<<HTML
                <html>
                 <head></head>

                 <body>
                  <pre><code></code></pre>
                 </body>
                </html>
                HTML
            ],

            // Reformat whitespace, element grouping, foreign "block" content, & foreign
            // void end tags not serialized
            [false, null, 1, true, false, true, false, false,
                <<<HTML
                <div></div><svg><g id="ook"></g></svg>
                HTML,

                <<<HTML
                <html>
                 <head></head>

                 <body>
                  <div></div>

                  <svg>
                   <g id="ook"/>
                  </svg>
                 </body>
                </html>
                HTML
            ],

            // Inline serialized comments and processing instructions, parsing of processing instructions off
            [false, null, 1, true, false, true, false, false,
                <<<HTML
                <html>
                 <head></head>
                 <body>
                  <!--ook-->
                  <?ook eeeeek ?>
                 </body>
                </html>
                HTML,

                <<<HTML
                <html>
                 <head></head>

                 <body><!--ook--><!--?ook eeeeek ?--></body>
                </html>
                HTML
            ],

            // Block serialized comments and processing instructions, parsing of processing instructions on
            [false, null, 1, true, true, true, false, false,
                <<<HTML
                <html>
                 <head></head>
                 <body>
                  <div></div>
                  <!--ook-->
                  <?ook eeeeek ?>
                  <div></div>
                 </body>
                </html>
                HTML,

                <<<HTML
                <html>
                 <head></head>

                 <body>
                  <div></div>

                  <!--ook-->

                  <?ook eeeeek ?>

                  <div></div>
                 </body>
                </html>
                HTML
            ],

            // Reformat whitespace, whitespace collapsing, custom indentions
            [false, null, 4, true, false, true, false, false,
                <<<HTML
                <!DOCTYPE html>
                <html>



                <head>

                </head>
                          <body>
                    ook     eek
                                        <pre>
                    This should be ignored

                                also this
                         </pre>
                                    <div></div>
                 <p>   Ook
                <span> Eek!</span>     </p>
                </body>
                   </html>
                HTML,

                <<<HTML
                <!DOCTYPE html>
                <html>
                    <head></head>

                    <body>ook eek
                        <pre>    This should be ignored

                                also this
                         </pre>

                        <div></div>

                        <p>Ook <span>Eek!</span></p>
                    </body>
                </html>
                HTML
            ],

            // Fragment, html elements
            [true, 'div', 1, true, false, true, false, false,
                <<<HTML
                <span> <span> Ook!</span></span>
                HTML,

                <<<HTML
                <span><span>Ook!</span></span>
                HTML
            ],

            // Fragment, foreign elements
            [true, 'div', 1, true, false, true, false, false,
                <<<HTML
                <svg> <g><path d=""/></g></svg>
                HTML,

                <<<HTML
                <svg>
                 <g>
                  <path d=""/>
                 </g>
                </svg>
                HTML
            ],

            // Fragment, foreign elements
            [true, 'div', 1, true, false, true, false, false,
                <<<HTML
                <svg> <g><path d=""/></g></svg>
                HTML,

                <<<HTML
                <svg>
                 <g>
                  <path d=""/>
                 </g>
                </svg>
                HTML
            ],
        ];
    }

    /** @dataProvider provideCustomSerializationsForNodes */
    public function testSerializeNodesWithOptions(int $indentStep, bool $indentWithSpaces, bool $processingInstructions, bool $reformatWhitespace, bool $boolAttr, bool $foreignVoid, \Closure $in, string $exp): void {
        $config = new Config;
        $config->indentStep = $indentStep;
        $config->indentWithSpaces = $indentWithSpaces;
        $config->processingInstructions = $processingInstructions;
        $config->reformatWhitespace = $reformatWhitespace;
        $config->serializeBooleanAttributeValues = $boolAttr;
        $config->serializeForeignVoidEndTags = $foreignVoid;

        $act = $in($config);
        $this->assertSame($exp, $act);
    }

    public function provideCustomSerializationsForNodes(): iterable {
        return [
            // Solo html element with context
            [1, true, false, true, false, false,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      <p> Ook! </p>
                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    return Parser::serialize($d->getElementsByTagName('p')->item(0), $config);
                },

                <<<HTML
                <p>Ook!</p>
                HTML
            ],

            // Solo html element without context
            [1, true, false, true, false, false,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      <p> Ook! </p>
                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    $p = $d->getElementsByTagName('p')->item(0);
                    $p->parentNode->removeChild($p);

                    return Parser::serialize($p, $config);
                },

                <<<HTML
                <p>Ook!</p>
                HTML
            ],

            // Solo svg element serializing as inline with context
            [1, true, false, true, false, true,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      <svg role="img" viewBox="0 0 26 26"><title>Ook</title>
                          <rect id="eek--a" width="5" height="5"/></svg>
                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    $svg = $d->getElementsByTagName('svg')->item(0);

                    return Parser::serialize($svg, $config);
                },

                <<<HTML
                <svg role="img" viewBox="0 0 26 26"><title>Ook</title> <rect id="eek--a" width="5" height="5"></rect></svg>
                HTML
            ],

            // Solo svg element serializing as block with context
            [1, true, false, true, false, false,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      <svg><g><g><rect id="eek--a" width="5" height="5"/></g></g></svg>
                      <div></div>
                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    $svg = $d->getElementsByTagName('svg')->item(0);
                    $g = $svg->firstChild->firstChild;

                    return Parser::serialize($g, $config);
                },

                <<<HTML
                <g>
                 <rect id="eek--a" width="5" height="5"/>
                </g>
                HTML
            ],

            // Solo svg element without context
            [1, true, false, true, false, true,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      <svg role="img" viewBox="0 0 26 26"><title>Ook</title>
                          <rect id="eek--a" width="5" height="5"/></svg>
                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    $svg = $d->getElementsByTagName('svg')->item(0);
                    $svg->parentNode->removeChild($svg);

                    return Parser::serialize($svg, $config);
                },

                <<<HTML
                <svg role="img" viewBox="0 0 26 26">
                 <title>Ook</title>

                 <rect id="eek--a" width="5" height="5"></rect>
                </svg>
                HTML
            ],

            // Solo text node without context
            [1, true, false, true, false, true,
                function (Config $config): string {
                    $html = <<<HTML
                    <!DOCTYPE html>
                    <html>
                     <body>
                      OOK eeek ooooooook     ook

                     </body>
                    </html>
                    HTML;

                    $d = Parser::parse($html, "UTF-8")->document;
                    $text = $d->getElementsByTagName('body')->item(0)->firstChild;
                    $text->parentNode->removeChild($text);

                    return Parser::serialize($text, $config);
                },

                <<<HTML
                OOK eeek ooooooook ook
                HTML
            ],
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
