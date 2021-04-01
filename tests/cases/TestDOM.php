<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Document;

/** 
 * @covers \MensBeam\HTML\Document
 * @covers \MensBeam\HTML\DocumentFragment
 * @covers \MensBeam\HTML\Element
 * @covers \MensBeam\HTML\TemplateElement
 * @covers \MensBeam\HTML\Comment
 * @covers \MensBeam\HTML\Text
 */
class TestDOM extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideNamespacedElements */
    public function testCreateNamespacedElements(?string $nsIn, string $nameIn, ?string $nsOut, string $local, string $prefix): void {
        $d = new Document;
        $e = $d->createElementNS($nsIn, $nameIn);
        $this->assertSame($nsOut, $e->namespaceURI);
        $this->assertSame($local, $e->localName);
        $this->assertSame($prefix, $e->prefix);
    }

    public function provideNamespacedElements(): iterable {
        return [
            [null,                                 "test",           null,                                 "test",            ""],
            [null,                                 "test:test",      null,                                 "testU00003Atest", ""],
            ["http://www.w3.org/2000/svg",         "svg",            "http://www.w3.org/2000/svg",         "svg",             ""],
            ["http://www.w3.org/2000/svg",         "svg:svg",        "http://www.w3.org/2000/svg",         "svg",             "svg"],
            ["fake_ns",                            "test:test",      "fake_ns",                            "test",            "test"],
            ["fake_ns",                            "test:test:test", "fake_ns",                            "testU00003Atest", "test"],
            ["fake_ns",                            "te st:test",     "fake_ns",                            "test",            "teU000020st"],
            [null,                                 "9",              null,                                 "U000039",         ""],
            ["http://www.w3.org/1999/xhtml",       "test",           null,                                 "test",            ""],
            ["http://www.w3.org/1999/xhtml",       "TEST",           null,                                 "test",            ""],
            [null,                                 "TEST",           null,                                 "test",            ""],
            ["fake_ns",                            "TEST",           "fake_ns",                            "TEST",            ""],
            ["http://www.w3.org/2000/svg",         "TEST",           "http://www.w3.org/2000/svg",         "TEST",            ""],
            ["http://www.w3.org/1998/Math/MathML", "TEST",           "http://www.w3.org/1998/Math/MathML", "TEST",            ""],
        ];
    }
    /** @dataProvider provideBareElements */
    public function testCreateBareElements(string $nameIn, $nameOut): void {
        $d = new Document;
        $e = $d->createElement($nameIn);
        $this->assertNull($e->namespaceURI);
        $this->assertSame("", $e->prefix);
        $this->assertSame($nameOut, $e->localName);
    }

    public function provideBareElements(): iterable {
        return [
            ["test",      "test"],
            ["test:test", "testU00003Atest"],
            ["9",         "U000039"],
            ["TEST",      "test"],
        ];
    }
}
