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
    public function testParseADocument(string $input, string $type, string $exp): void {
        $p = new DOMParser;
        $document = $p->parseFromString($input, $type);
        $this->assertSame($exp, $document->documentElement->textContent);
        $this->assertSame("html", $document->documentElement->tagName);
    }

    public function provideDocuments(): iterable {
        $mkUtf16 = function(string $s, bool $le) {
            $replacement = $le ? "$0\x00" : "\x00$0";
            return preg_replace("/[\x{01}-\x{7F}]/s", $replacement, $s);
        };
        return [
            ["Test",                                                                                   "text/html",                     "Test"],
            ["Ol\xE9",                                                                                 "text/html",                     "Ol\u{E9}"],
            ["Ol\u{E9}",                                                                               "text/html;charset=utf8",        "Ol\u{E9}"],
            ["<meta charset=utf8>Ol\u{E9}",                                                            "text/html",                     "Ol\u{E9}"],
            ["<html>Test</html>",                                                                      "text/xml",                      "Test"],
            ["<html>Ol\u{E9}</html>",                                                                  "text/xml",                      "Ol\u{E9}"],
            ["<html>Ol\xE9</html>",                                                                    "text/xml;charset=windows-1252", "Ol\u{E9}"],
            ["\u{FEFF}<html>Ol\u{E9}</html>",                                                          "text/xml;charset=windows-1252", "Ol\u{E9}"],
            ["<?xml version='1.0' encoding='windows-1252'?><html>Ol\xE9</html>",                       "text/xml",                      "Ol\u{E9}"],
            ["<html>Ol\xE9</html>",                                                                    "text/xml;charset=windows-1252", "Ol\u{E9}"],
            ["<html>Ol\u{E9}</html>",                                                                  "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.1' encoding='windows-1252'?><html>Ol\u{E9}</html>",                     "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.1' encoding='utf8'?><html>Ol\u{E9}</html>",                             "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.1'?><html>Ol\u{E9}</html>",                                             "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.1' ?><html>Ol\u{E9}</html>",                                            "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.0' standalone='yes'?><html>Ol\u{E9}</html>",                            "text/xml;charset=UTF-8",        "Ol\u{E9}"],
            ["<?xml version='1.0' standalone='yes'?><html>Ol\xE9</html>",                              "text/xml;charset=windows-1252", "Ol\u{E9}"],
            [$mkUtf16("\xFE\xFF<html>Ol\x00\xE9</html>", false),                                       "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("\xFF\xFE<html>Ol\xE9\x00</html>", true),                                        "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("<?xml version='1.0' encoding='UTF-16'?><html>Ol\x00\xE9</html>", false),        "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("<?xml version='1.0' encoding='UTF-16'?><html>Ol\xE9\x00</html>", true),         "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("\xFE\xFF<?xml version='1.0' encoding='UTF-8'?><html>Ol\x00\xE9</html>", false), "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("\xFF\xFE<?xml version='1.0' encoding='UTF-8'?><html>Ol\xE9\x00</html>", true),  "text/xml",                      "Ol\u{E9}"],
            [$mkUtf16("<?xml version='1.0' encoding='UTF-8'?><html>Ol\x00\xE9</html>", false),         "text/xml;charset=utf-16be",     "Ol\u{E9}"],
            [$mkUtf16("<?xml version='1.0' encoding='UTF-8'?><html>Ol\xE9\x00</html>", true),          "text/xml;charset=utf-16le",     "Ol\u{E9}"],
        ];
    }

    public function testFailToParseADocument(): void {
        $in = "<html>Test</html><!--Test-->Test";
        $p = new DOMParser;
        $d = $p->parseFromString($in, "text/xml");
        $this->assertSame("parsererror", $d->documentElement->tagName);
        $this->assertSame("http://www.mozilla.org/newlayout/xml/parsererror.xml", $d->documentElement->namespaceURI);
        $this->assertNotSame("", trim($d->documentElement->textContent));
    }

    public function testParseWithIncorrectType(): void {
        $in = "<html>Ol\u{E9}</html>";
        $p = new DOMParser;
        $this->expectException(\InvalidArgumentException::class);
        $p->parseFromString($in, "text/plain");
    }
}
