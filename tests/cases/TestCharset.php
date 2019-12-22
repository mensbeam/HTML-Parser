<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

use dW\HTML5\Charset;

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
            ["UTF-8",                   "UTF-8"],
            ["  utf8  ",                "UTF-8"],
            ["ISO-8859-1",              "windows-1252"],
            ["text/html; charset=utf8", null],
        ];
    }

    /** @dataProvider provideContentTypes */
    public function testDetermineEncodingFromContentType(string $in, ?string $exp) {
        $this->assertSame($exp, Charset::fromTransport($in));
    }

    public function provideContentTypes() {
        return [
            ["UTF-8",                                             null],
            ["charset=utf8",                                      null],
            ["text/html",                                         null],
            ["text/html charset=utf8",                            null],
            ["text/html; charset=utf8",                           "UTF-8"],
            ["text/html;charset=utf8",                            "UTF-8"],
            ["text/html; charset=\"utf8\"",                       "UTF-8"],
            ["image/svg+xml; param=value; charset=utf8",          "UTF-8"],
            ["image/svg+xml; charset=utf8; charset=big5",         "UTF-8"],
            ["image/svg+xml; charset=utf8;charset=big5",          "UTF-8"],
            ["text/html; charset=not-valid; charset=big5",        null],
            ["text/html; charset=not-valid",                      null],
            ["text/html; charsaaet=\"a \\\"fancy\\\" encoding\"", null],
        ];
    }
}
