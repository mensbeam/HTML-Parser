<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Parser;
use MensBeam\HTML\Parser\Output;
use MensBeam\HTML\Parser\Config;

/**
 * @covers \MensBeam\HTML\Parser
 * @covers \MensBeam\HTML\Parser\TreeBuilder
 * @covers \MensBeam\HTML\Parser\Data::changeEncoding
 */
class TestEncodingChange extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideEncodingChanges */
    public function testChangeEncodingWithCharset(string $assumedEncoding, string $statedEncoding, string $actualEncoding, string $titleBytes, string $titleUTF8): void {
        $in = "<!DOCTYPE html><html><head>".str_repeat(" ", 1024)."<title>$titleBytes</title><meta charset=$statedEncoding></head><body></body></html>";
        // if the input is some form of UTF-16, add the null bytes in the correct places
        if ($assumedEncoding === "UTF-16BE") {
            $in = preg_replace("/(.)/s", "\0$1", $in);
        } else if ($assumedEncoding === "UTF16-LE") {
            $in = preg_replace("/(.)/s", "$1\0", $in);
        }
        // set up the test
        $conf = new Config;
        $conf->encodingFallback = $assumedEncoding;
        $out = Parser::parse($in, "", null, null, null, $conf);
        $this->assertInstanceOf(Output::class, $out);
        // check the output
        $this->assertSame($actualEncoding, $out->encoding);
        $this->assertSame($titleUTF8, $out->document->getElementsByTagName("title")[0]->textContent);
    }

    public function provideEncodingChanges(): iterable {
        return [
            ["windows-1252", "UTF-8",    "UTF-8", "ASCII title", "ASCII title"],
            ["windows-1252", "UTF-16BE", "UTF-8", "ASCII title", "ASCII title"],
            ["windows-1252", "UTF-16LE", "UTF-8", "ASCII title", "ASCII title"],
            ["windows-1252", "UTF-8",    "UTF-8", "H\xC3\xA9",   "H\u{E9}"],
        ];
    }
}