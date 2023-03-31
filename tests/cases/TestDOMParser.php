<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\DOMParser;

/**
 * @covers \MensBeam\HTML\DOMParser
 */
class TestDOMParser extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideDocuments */
    public function testParseADocument(string $input, string $type, bool $parseError, string $exp): void {
        $p = new DOMParser;
        $document = $p->parseFromString($input, $type);
        $root = $parseError ? "parserror" : "html";
        $this->assertSame($root, $document->documentElement->tagName);
        $this->assertSame($exp, $document->documentElement->textContent);
    }

    public function provideDocuments(): iterable {
        return [
            ["Test",                                                               "text/html",                     false, "Test"],
            ["Ol\xE9",                                                             "text/html",                     false, "Ol\u{E9}"],
            ["Ol\u{E9}",                                                           "text/html;charset=utf8",        false, "Ol\u{E9}"],
            ["<meta charset=utf8>Ol\u{E9}",                                        "text/html",                     false, "Ol\u{E9}"],
            ["<html>Test</html>",                                                  "text/xml",                      false, "Test"],
            ["<html>Ol\u{E9}</html>",                                              "text/xml",                      false, "Ol\u{E9}"],
            ["<html>Ol\xE9</html>",                                                "text/xml;charset=windows-1252", false, "Ol\u{E9}"],
            ["\u{FEFF}<html>Ol\u{E9}</html>",                                      "text/xml;charset=windows-1252", false, "Ol\u{E9}"],
            ["<?xml version='1.0' encoding='windows-1252'?><html>Ol\xE9</html>",   "text/xml",                      false, "Ol\u{E9}"],
            ["<html>Ol\xE9</html>",                                                "text/xml;charset=windows-1252", false, "Ol\u{E9}"],
            ["<?xml version='1.2' encoding='windows-1252'?><html>Ol\u{E9}</html>", "text/xml;charset=UTF-8",        false, "Ol\u{E9}"],
        ];
    }
}
