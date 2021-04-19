<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Document;
use MensBeam\HTML\Parser;
use MensBeam\HTML\TemplateElement;

class TestDOM extends \PHPUnit\Framework\TestCase {
    /**
     * @dataProvider provideNamespacedElements
     * @covers \MensBeam\HTML\Document::createElementNS
     */
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
    /**
     * @dataProvider provideBareElements
     * @covers \MensBeam\HTML\Document::createElement
     */
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

    /** @covers \MensBeam\HTML\Document::createElementNS */
    public function testCreateTemplateElements(): void {
        $d = new Document;
        $t = $d->createElement("template");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
        $t = $d->createElement("TEMPLATE");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
        $t = $d->createElementNS(null, "template");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
        $t = $d->createElementNS(null, "TEMPLATE");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
        $t = $d->createElementNS("http://www.w3.org/1999/xhtml", "template");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
        $t = $d->createElementNS("http://www.w3.org/1999/xhtml", "TEMPLATE");
        $this->assertInstanceOf(TemplateElement::class, $t);
        $this->assertNotNull($t->ownerDocument);
    }

    /**
     * @dataProvider provideNamespacedAttributeCreations
     * @covers \MensBeam\HTML\Document::createAttributeNS
     */
    public function testCreateNamespacedAttributes(?string $nsIn, string $nameIn, string $local, string $prefix): void {
        $d = new Document;
        $d->appendChild($d->createElement("html"));
        $a = $d->createAttributeNS($nsIn, $nameIn);
        $this->assertSame($local, $a->localName);
        $this->assertSame($nsIn, $a->namespaceURI);
        $this->assertSame($prefix, $a->prefix);
    }

    public function provideNamespacedAttributeCreations(): iterable {
        return [
            [null,      "test",           "test",            ""],
            [null,      "test:test",      "testU00003Atest", ""],
            [null,      "test",           "test",            ""],
            [null,      "TEST:TEST",      "TESTU00003ATEST", ""],
            ["fake_ns", "test",           "test",            ""],
            ["fake_ns", "test:test",      "test",            "test"],
            ["fake_ns", "TEST:TEST",      "TEST",            "TEST"],
            ["fake_ns", "test:test:test", "testU00003Atest", "test"],
            ["fake_ns", "TEST:TEST:TEST", "TESTU00003ATEST", "TEST"],
        ];
    }

    /**
     * @dataProvider provideBareAttributeCreations
     * @covers \MensBeam\HTML\Document::createAttribute
     */
    public function testCreateBareAttributes(string $nameIn, string $nameOut): void {
        $d = new Document;
        $d->appendChild($d->createElement("html"));
        $a = $d->createAttribute($nameIn);
        $this->assertSame($nameOut, $a->name);
        $this->assertNull($a->namespaceURI);
    }

    public function provideBareAttributeCreations(): iterable {
        return [
            ["test",      "test"],
            ["test:test", "testU00003Atest"],
            ["TEST",      "TEST"],
            ["TEST:TEST", "TESTU00003ATEST"],
        ];
    }

    /**
     * @dataProvider provideNamespacedAttributeSettings
     * @covers \MensBeam\HTML\Element::setAttributeNS
     */
    public function testSetNamespoacedAttributes(?string $elementNS, ?string $attrNS, string $nameIn, string $nameOut): void {
        $d = new Document;
        $e = $d->createElementNS($elementNS, "test");
        $this->assertSame(0, $e->attributes->length);
        $e->setAttributeNS($attrNS, $nameIn, "test");
        $this->assertSame(1, $e->attributes->length);
        $a = $e->attributes[0];
        $this->assertSame($nameOut, $a->nodeName);
        $this->assertSame($attrNS, $a->namespaceURI);
    }

    public function provideNamespacedAttributeSettings(): iterable {
        return [
            [null,                                 null,                            "test",           "test"],
            [null,                                 null,                            "TEST",           "test"],
            ["http://www.w3.org/1999/xhtml",       null,                            "test",           "test"],
            ["http://www.w3.org/1999/xhtml",       null,                            "TEST",           "test"],
            [null,                                 null,                            "test:test",      "testU00003Atest"],
            [null,                                 null,                            "TEST:TEST",      "testU00003Atest"],
            ["http://www.w3.org/1999/xhtml",       null,                            "test:test",      "testU00003Atest"],
            ["http://www.w3.org/1999/xhtml",       null,                            "TEST:TEST",      "testU00003Atest"],
            [null,                                 "http://www.w3.org/1999/xhtml",  "test:test",      "test:test"],
            [null,                                 "http://www.w3.org/1999/xhtml",  "TEST:TEST",      "TEST:TEST"],
            ["http://www.w3.org/1998/Math/MathML", null,                            "test",           "test"],
            ["http://www.w3.org/1998/Math/MathML", null,                            "TEST",           "TEST"],
            [null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:xlink",    "xmlns:xlink"],
            [null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:XLINK",    "xmlns:XLINK"],
            [null,                                 "fake_ns",                       "test:test:test", "test:testU00003Atest"],
            [null,                                 "fake_ns",                       "TEST:TEST:TEST", "TEST:TESTU00003ATEST"],
        ];
    }

    /**
     * @dataProvider provideBareAttributeSettings
     * @covers \MensBeam\HTML\Element::setAttribute
     */
    public function testSetBareAttributes(?string $elementNS, string $nameIn, string $nameOut): void {
        $d = new Document;
        $e = $d->createElementNS($elementNS, "test");
        $this->assertSame(0, $e->attributes->length);
        $e->setAttribute($nameIn, "test");
        $this->assertSame(1, $e->attributes->length);
        $a = $e->attributes[0];
        $this->assertSame($nameOut, $a->nodeName);
        $this->assertNull($a->namespaceURI);
    }

    public function provideBareAttributeSettings(): iterable {
        return [
            [null,                                 "test",           "test"],
            [null,                                 "TEST",           "test"],
            ["http://www.w3.org/1999/xhtml",       "test",           "test"],
            ["http://www.w3.org/1999/xhtml",       "TEST",           "test"],
            [null,                                 "test:test",      "testU00003Atest"],
            [null,                                 "TEST:TEST",      "testU00003Atest"],
            ["http://www.w3.org/1999/xhtml",       "test:test",      "testU00003Atest"],
            ["http://www.w3.org/1999/xhtml",       "TEST:TEST",      "testU00003Atest"],
            ["http://www.w3.org/1998/Math/MathML", "test",           "test"],
            ["http://www.w3.org/1998/Math/MathML", "TEST",           "TEST"],
        ];
    }

    /**
     * @dataProvider provideAttributeNodeSettings
     * @covers \MensBeam\HTML\Element::setAttributeNode
     * @covers \MensBeam\HTML\Element::setAttributeNodeNS
     */
    public function testSetAttributeNodes(bool $ns, ?string $elementNS, ?string $attrNS, string $name): void {
        $d = new Document;
        $e = $d->createElementNS($elementNS, "test");
        $d->appendChild($e);
        $this->assertSame(0, $e->attributes->length);
        $a = $d->createAttributeNS($attrNS, $name);
        if ($ns) {
            $e->setAttributeNodeNS($a);
        } else {
            $e->setAttributeNode($a);
        }
        $this->assertSame(1, $e->attributes->length);
        $a = $e->attributes[0];
        $this->assertSame($name, $a->nodeName);
        $this->assertSame($attrNS, $a->namespaceURI);
    }

    public function provideAttributeNodeSettings(): iterable {
        return [
            [true,  null,                                 null,                            "test"],
            [true,  null,                                 null,                            "TEST"],
            [true,  "http://www.w3.org/1999/xhtml",       null,                            "test"],
            [true,  "http://www.w3.org/1999/xhtml",       null,                            "TEST"],
            [true,  null,                                 null,                            "testU00003Atest"],
            [true,  null,                                 null,                            "TESTU00003ATEST"],
            [true,  "http://www.w3.org/1999/xhtml",       null,                            "testU00003Atest"],
            [true,  "http://www.w3.org/1999/xhtml",       null,                            "TESTU00003ATEST"],
            [true,  null,                                 "http://www.w3.org/1999/xhtml",  "test:test"],
            [true,  null,                                 "http://www.w3.org/1999/xhtml",  "TEST:TEST"],
            [true,  "http://www.w3.org/1998/Math/MathML", null,                            "test"],
            [true,  "http://www.w3.org/1998/Math/MathML", null,                            "TEST"],
            [true,  null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:xlink"],
            [true,  null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:XLINK"],
            [true,  null,                                 "fake_ns",                       "test:testU00003Atest"],
            [true,  null,                                 "fake_ns",                       "TEST:TESTU00003ATEST"],
            [false, null,                                 null,                            "test"],
            [false, null,                                 null,                            "TEST"],
            [false, "http://www.w3.org/1999/xhtml",       null,                            "test"],
            [false, "http://www.w3.org/1999/xhtml",       null,                            "TEST"],
            [false, null,                                 null,                            "testU00003Atest"],
            [false, null,                                 null,                            "TESTU00003ATEST"],
            [false, "http://www.w3.org/1999/xhtml",       null,                            "testU00003Atest"],
            [false, "http://www.w3.org/1999/xhtml",       null,                            "TESTU00003ATEST"],
            [false, null,                                 "http://www.w3.org/1999/xhtml",  "test:test"],
            [false, null,                                 "http://www.w3.org/1999/xhtml",  "TEST:TEST"],
            [false, "http://www.w3.org/1998/Math/MathML", null,                            "test"],
            [false, "http://www.w3.org/1998/Math/MathML", null,                            "TEST"],
            [false, null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:xlink"],
            [false, null,                                 "http://www.w3.org/2000/xmlns/", "xmlns:XLINK"],
            [false, null,                                 "fake_ns",                       "test:testU00003Atest"],
            [false, null,                                 "fake_ns",                       "TEST:TESTU00003ATEST"],
        ];
    }

    /**
     * @covers \MensBeam\HTML\Element::hasAttribute
     * @covers \MensBeam\HTML\Element::getAttribute
     * @covers \MensBeam\HTML\Element::getAttributeNS
     */
    public function testCheckForAttribute(): void {
        $d = new Document;
        $d->appendChild($d->createElement("html"));
        $e = $d->documentElement;
        $e->setAttribute("ook", "eek");
        $e->setAttributeNS(Parser::XML_NAMESPACE, "xml:base", "http://example.com/");
        $e->setAttributeNS(Parser::XMLNS_NAMESPACE, "xmlns:xlink", Parser::XLINK_NAMESPACE);
        $e->setAttributeNS("fake_ns", "ook:eek", "ack");
        // perform boolean tests
        $this->assertFalse($e->hasAttribute("blah"));
        $this->assertFalse($e->hasAttribute("OOK"));;
        $this->assertFalse($e->hasAttribute("eek"));
        $this->assertFalse($e->hasAttribute("ack"));
        $this->assertTrue($e->hasAttribute("ook"));
        $this->assertTrue($e->hasAttribute("xml:base"));
        $this->assertTrue($e->hasAttribute("xmlns:xlink"));
        $this->assertTrue($e->hasAttribute("ook:eek"));
        $this->assertFalse($e->hasAttributeNS(null, "blah"));
        $this->assertFalse($e->hasAttributeNS(null, "OOK"));
        $this->assertFalse($e->hasAttributeNS(null, "eek"));
        $this->assertTrue($e->hasAttributeNS(null, "ook"));
        $this->assertTrue($e->hasAttributeNS(Parser::XML_NAMESPACE, "base"));
        $this->assertTrue($e->hasAttributeNS(Parser::XMLNS_NAMESPACE, "xlink"));
        $this->assertTrue($e->hasAttributeNS("fake_ns", "eek"));
        // perform retrival tests
        $this->assertNull($e->getAttribute("blah"));
        $this->assertNull($e->getAttribute("OOK"));
        $this->assertNull($e->getAttribute("eek"));
        $this->assertNull($e->getAttribute("ack"));
        $this->assertSame("eek", $e->getAttribute("ook"));
        $this->assertSame("http://example.com/", $e->getAttribute("xml:base"));
        $this->assertSame(Parser::XLINK_NAMESPACE, $e->getAttribute("xmlns:xlink"));
        $this->assertSame("ack", $e->getAttribute("ook:eek"));
        $this->assertNull($e->getAttributeNS(null, "blah"));
        $this->assertNull($e->getAttributeNS(null, "OOK"));
        $this->assertNull($e->getAttributeNS(null, "ack"));
        $this->assertSame("eek", $e->getAttributeNS(null, "ook"));
        $this->assertSame("http://example.com/", $e->getAttributeNS(Parser::XML_NAMESPACE, "base"));
        $this->assertSame(Parser::XLINK_NAMESPACE, $e->getAttributeNS(Parser::XMLNS_NAMESPACE, "xlink"));
        $this->assertSame("ack", $e->getAttributeNS("fake_ns", "eek"));
    }

    /** @covers \MensBeam\HTML\Element::__get */
    public function testGetInnerAndOuterHtml(): void {
        $d = new Document;
        $d->appendChild($d->createElement("html"));
        $d->documentElement->appendChild($d->createTextNode("OOK"));
        $this->assertSame("OOK", $d->documentElement->innerHTML);
        $this->assertSame("<html>OOK</html>", $d->documentElement->outerHTML);
        $this->assertNull($d->documentElement->innerHtml);
        $this->assertNull($d->documentElement->outerHtml);
    }
}
