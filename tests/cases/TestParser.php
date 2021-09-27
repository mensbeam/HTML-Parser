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
 */
class TestParser extends \PHPUnit\Framework\TestCase {
    public function testParseADocument(): void {
        $in = "hello world!";
        $out = Parser::parse($in, "tex/html; charset=utf8");
        $this->assertInstanceOf(Output::class, $out);
        $this->assertInstanceOf(\DOMDocument::class, $out->document);
        $this->assertSame("UTF-8", $out->encoding);
        $this->assertSame(Parser::QUIRKS_MODE, $out->quirksMode);
        $this->assertNull($out->errors);
    }

    public function testParseAFragment(): void {
        $doc = new \DOMDocument();
        $context = $doc->createElement("div");
        $in = "hello world!";
        $out = Parser::parseFragment($context, 0, $in, "tex/html; charset=utf8");
        $this->assertInstanceOf(\DOMDocumentFragment::class, $out);
    }

    public function testParseADocumentReportingErrors(): void {
        $in = "hello world!";
        $conf = new Config;
        $conf->errorCollection = true;
        $out = Parser::parse($in, "tex/html; charset=utf8", null, null, null, $conf);
        $this->assertInstanceOf(Output::class, $out);
        $this->assertInstanceOf(\DOMDocument::class, $out->document);
        $this->assertSame("UTF-8", $out->encoding);
        $this->assertSame(Parser::QUIRKS_MODE, $out->quirksMode);
        $this->assertIsArray($out->errors);
    }
}