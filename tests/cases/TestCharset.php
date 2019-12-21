<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Charset;
use MensBeam\Intl\Encoding\UTF8;
use MensBeam\Intl\Encoding\Windows1252;

/** 
 * @covers \dW\HTML5\Charset
 */
class TestCharset extends \PHPUnit\Framework\TestCase {
    /** @dataProvider provideCharsets */
    public function testDetermineEncodingFromEncodingLabel(string $in, ?string $exp) {
        $this->assertSame($exp, Charset::fromCharset($in));
    }

    public function provideCharsets() {
        return [
            ["UTF-8",                   UTF8::class],
            ["  utf8  ",                UTF8::class],
            ["ISO-8859-1",              Windows1252::class],
            ["text/html; charset=utf8", null],
        ];
    }

    /** @dataProvider provideContentTypes */
    public function testDetermineEncodingFromContentType(string $in, ?string $exp) {
        $this->assertSame($exp, Charset::fromTransport($in));
    }

    public function provideContentTypes() {
        return [
            ["UTF-8",                                          null],
            ["charset=utf8",                                   null],
            ["text/html",                                      null],
            ["text/html charset=utf8",                         null],
            ["text/html; charset=utf8",                        UTF8::class],
            ["text/html;charset=utf8",                         UTF8::class],
            ["text/html; charset=\"utf8\"",                    UTF8::class],
            ["image/svg+xml; param=value; charset=utf8",       UTF8::class],
            ["image/svg+xml; charset=utf8; charset=big5",      UTF8::class],
            ["image/svg+xml; charset=utf8;charset=big5",       UTF8::class],
            ["text/html; charset=not-valid; charset=big5",     null],
            ["text/html; charset=not-valid",                   null],
        ];
    }
}
