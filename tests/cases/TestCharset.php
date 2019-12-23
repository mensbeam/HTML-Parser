<?php
declare(strict_types=1);
namespace dW\HTML5\TestCase;

/* Missing tests:

Pre-scan:

- UTF-16LE and UTF-16BE BOM tests
- Duplicate attributes
- x-user-defined substitution
- EOF after attribute name
- Greater-than sign after equals sign
- EOF after equals sign

Meta parsing:

- No equals sign after charset
- EOF after equals sign

*/

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
    
    /** @dataProvider provideStandardEncodingTests */
    public function testStandardEncoderTests(string $input, string $exp) {
        $exp = strtolower($exp);
        if (in_array($exp, ["euc-jp", "iso-2022-jp", "shift-jis"])) {
            $this->markTestIncomplete("Japanese encodings are not yet implemented");
        }
        $this->assertSame(strtolower($exp), strtolower(Charset::fromBOM($input)?? Charset::fromPrescan($input, \PHP_INT_MAX) ?? "Windows-1252"));
    }

    public function provideStandardEncodingTests() {
        $tests = [];
        $blacklist = [];
        foreach (new \GlobIterator(\dW\HTML5\BASE."tests/html5lib-tests/encoding/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $file) {
            if (!in_array(basename($file), $blacklist)) {
                $tests[] = $file;
            }
        }
        return $this->makeEncodingTests(...$tests);
    }

    protected function makeEncodingTests(string ...$file): iterable {
        foreach ($file as $path) {
            $f = basename($path);
            $test = file($path);
            $l = 0;
            $index = 0;
            while ($l < sizeof($test)) {
                $testId = "$f #".$index++;
                $data = "";
                while (!preg_match("/^#data\s+$/", $test[$l++]));
                while (!preg_match("/^#encoding\s+$/", ($line = $test[$l++]))) {
                    $data .= $line;
                }
                if (in_array($testId,["tests1.dat #54", "tests1.dat #55"])) {
                    continue;
                }
                yield $testId => [trim($data), trim($test[$l++])];
            }
        }
    }
}
