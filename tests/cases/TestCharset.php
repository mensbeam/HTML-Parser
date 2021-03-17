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
    public function testDetermineEncodingFromContentType(string $input, ?string $exp) {
        $this->assertSame($exp, Charset::fromTransport($input));
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

    /** @dataProvider provideBOMs */
    public function testDetermineEncodingFromByteOrderMark(string $input, ?string $exp) {
        $this->assertSame($exp, Charset::fromBOM($input));
    }
    
    public function provideBOMs() {
        return [
            'UTF-8'                  => ["\u{FEFF}Hello world!", "UTF-8"],
            'UTF-16 (big-endian)'    => ["\xFE\xFF\0H\0e\0l\0l\0o\0 \0w\0o\0r\0l\0d\0!", "UTF-16BE"],
            'UTF-16 (little-endian)' => ["\xFF\xFEH\0e\0l\0l\0o\0 \0w\0o\0r\0l\0d\0!\0", "UTF-16LE"],
            'No byte order mark'     => ["Hello world!", null],
        ];
    }

    /** @dataProvider provideStandardEncodingTests */
    public function testStandardEncoderTests(string $input, string $exp) {
        $exp = strtolower($exp);
        $this->assertSame(strtolower($exp), strtolower(Charset::fromBOM($input)?? Charset::fromPrescan($input, \PHP_INT_MAX) ?? "Windows-1252"));
    }

    public function provideStandardEncodingTests() {
        $tests = [];
        $blacklist = [];
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\dW\HTML5\BASE."tests/html5lib-tests/encoding/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        $files->append(new \GlobIterator(\dW\HTML5\BASE."tests/cases/encoding/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        foreach ($files as $file) {
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
            $end = sizeof($test);
            $l = 0;
            $index = 0;
            while ($l < $end) {
                $testId = "$f #".$index++;
                $data = "";
                while ($l < $end && !preg_match("/^#data\s+$/", @$test[$l++]));
                while ($l < $end && !preg_match("/^#encoding\s+$/", ($line = @$test[$l++]))) {
                    $data .= $line;
                }
                if ($l >= $end) {
                    return;
                }
                yield $testId => [trim($data, "\r\n"), trim($test[$l++])];
            }
        }
    }
}
