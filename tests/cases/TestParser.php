<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Parser;
use MensBeam\HTML\Parser\Output;
use MensBeam\HTML\Parser\Config;
use MensBeam\HTML\Parser\Exception;

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
        $out = Parser::parse($in, "tex/html; charset=utf8", $conf);
        $this->assertInstanceOf(Output::class, $out);
        $this->assertInstanceOf(\DOMDocument::class, $out->document);
        $this->assertSame("UTF-8", $out->encoding);
        $this->assertSame(Parser::QUIRKS_MODE, $out->quirksMode);
        $this->assertIsArray($out->errors);
    }

    public function testParseADocumentWithFallbackEncoding(): void {
        $in = "hello world!";
        $conf = new Config;
        $conf->encodingFallback = "iso-2022-jp";
        $out = Parser::parse($in, "", $conf);
        $this->assertInstanceOf(Output::class, $out);
        $this->assertSame("ISO-2022-JP", $out->encoding);
    }

    public function testParseADocumentWithACustomClass(): void {
        $c = new class extends \DOMDocument {};
        $in = "hello world!";
        $conf = new Config;
        $conf->documentClass = get_class($c);
        $out = Parser::parse($in, "utf8", $conf);
        $this->assertInstanceOf(Output::class, $out);
        $this->assertInstanceOf(get_class($c), $out->document);
    }

    public function testParseADocumentWithAMissingCustomClass(): void {
        $in = "hello world!";
        $conf = new Config;
        $conf->documentClass = "MissingClass";
        $this->expectExceptionCode(Exception::FAILED_CREATING_DOCUMENT);
        Parser::parse($in, "utf8", $conf);
    }

    public function testParseADocumentWithAnIncompaibleCustomClass(): void {
        $c = new class {};
        $in = "hello world!";
        $conf = new Config;
        $conf->documentClass = get_class($c);
        $this->expectExceptionCode(Exception::INVALID_DOCUMENT_CLASS);
        Parser::parse($in, "utf8", $conf);
    }
}
