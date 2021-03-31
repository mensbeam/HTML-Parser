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
    public function testCreateNamespacedElements(?string $ns, string $name, string $local, string $prefix): void {
        $d = new Document;
        $e = $d->createElementNS($ns, $name);
        $this->assertSame($ns, $e->namespaceURI);
        $this->assertSame($local, $e->localName);
        $this->assertSame($prefix, $e->prefix);
    }

    public function provideNamespacedElements(): iterable {
        return [
            [null,                         "test",           "test",            ""],
            [null,                         "test:test",      "testU00003Atest", ""],
            ["http://www.w3.org/2000/svg", "svg",            "svg",             ""],
            ["http://www.w3.org/2000/svg", "svg:svg",        "svg",             "svg"],
            ["fake_ns",                    "test:test",      "test",            "test"],
            ["fake_ns",                    "test:test:test", "testU00003Atest", "test"],
            ["fake_ns",                    "te st:test",     "test",            "teU000020st"],
            [null,                         "9",              "U000039",         ""],
        ];
    }
}
