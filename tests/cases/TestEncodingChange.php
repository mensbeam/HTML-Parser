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
            $in = preg_replace("/(.)/s", "\x00$1", $in);
        } elseif ($assumedEncoding === "UTF-16LE") {
            $in = preg_replace("/(.)/s", "$1\x00", $in);
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

    /** @dataProvider provideEncodingChanges */
    public function testChangeEncodingWithHttpEquiv(string $assumedEncoding, string $statedEncoding, string $actualEncoding, string $titleBytes, string $titleUTF8): void {
        $in = "<!DOCTYPE html><html><head>".str_repeat(" ", 1024)."<title>$titleBytes</title><meta http-equiv=CoNtenT-TYpe content='text/html;charset=$statedEncoding'></head><body></body></html>";
        // if the input is some form of UTF-16, add the null bytes in the correct places
        if ($assumedEncoding === "UTF-16BE") {
            $in = preg_replace("/(.)/s", "\x00$1", $in);
        } elseif ($assumedEncoding === "UTF-16LE") {
            $in = preg_replace("/(.)/s", "$1\x00", $in);
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
            ["windows-1252", "",               "windows-1252", "ASCII title",                  "ASCII title"],
            ["windows-1252", "UTF-8",          "UTF-8",        "ASCII title",                  "ASCII title"],
            ["windows-1252", "UTF-16BE",       "UTF-8",        "ASCII title",                  "ASCII title"],
            ["windows-1252", "UTF-16LE",       "UTF-8",        "ASCII title",                  "ASCII title"],
            ["UTF-8",        "x-user-defined", "windows-1252", "ASCII title",                  "ASCII title"],
            ["windows-1252", "UTF-8",          "UTF-8",        "H\xC3\xA9",                    "H\u{E9}"],
            ["UTF-8",        "UTF-8",          "UTF-8",        "H\xC3\xA9",                    "H\u{E9}"],
            ["UTF-16LE",     "UTF-8",          "UTF-16LE",     "ASCII title",                  "ASCII title"],
            ["UTF-16BE",     "UTF-8",          "UTF-16BE",     "ASCII title",                  "ASCII title"],
            ["windows-1252", "bogus",          "windows-1252", "H\xE9",                        "H\u{E9}"],
            ["ISO-2022-JP",  "ISO-2022-JP",    "ISO-2022-JP",  "\x1B\x28\x49\x56\x1B\x28\x42", "\u{FF96}"],
            ["ISO-2022-JP",  "UTF-8",          "UTF-8",        "\x1B\x28\x49\x56\x1B\x28\x42", "\u{1B}(IV\u{1B}(B"],
            ["UTF-8",        "ISO-2022-JP",    "ISO-2022-JP",  "ASCII title",                  "ASCII title"],
            ["UTF-8",        "UTF-8",          "UTF-8",        "\x0E",                         "\u{E}"],
            ["UTF-8",        "UTF-8",          "UTF-8",        "\x0F",                         "\u{F}"],
            ["UTF-8",        "UTF-8",          "UTF-8",        "\x1B",                         "\u{1B}"],
            ["UTF-8",        "ISO-2022-JP",    "ISO-2022-JP",  "\x0E",                         "\u{FFFD}"],
            ["UTF-8",        "ISO-2022-JP",    "ISO-2022-JP",  "\x0F",                         "\u{FFFD}"],
            ["UTF-8",        "ISO-2022-JP",    "ISO-2022-JP",  "\x1B",                         "\u{FFFD}"],
        ];
    }
}