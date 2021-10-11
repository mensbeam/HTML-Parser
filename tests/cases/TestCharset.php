<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\Parser;
use MensBeam\HTML\Parser\Charset;
use MensBeam\HTML\Parser\Config;

/** 
 * @covers \MensBeam\HTML\Parser\Charset
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
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/html5lib-tests/encoding/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/cases/encoding/*.dat", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
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

    /** @dataProvider provideStandardDeclarationTests */
    public function testStandardDeclarationTests(string $file, ?string $charset, string $exp): void {
        $config = new Config;
        $config->encodingPrescanBytes = 2048;
        $file = \MensBeam\HTML\Parser\BASE."tests/platform-tests/html/syntax/xmldecl/support/".$file;
        $data = file_get_contents($file);
        $act = Parser::parse($data, $charset, $config);
        $this->assertSame($exp, $act->encoding);
    }

    public function provideStandardDeclarationTests() {
        $tests = [];
        $blacklist = ["xmldecl-3.html"];
        $files = new \AppendIterator();
        $files->append(new \GlobIterator(\MensBeam\HTML\Parser\BASE."tests/platform-tests/html/syntax/xmldecl/*.htm*", \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME));
        foreach ($files as $file) {
            if (!in_array(basename($file), $blacklist)) {
                $tests[] = $file;
            }
        }
        return $this->makeDeclarationTests(...$tests);
    }

    protected function makeDeclarationTests(string ...$file): iterable {
        foreach ($file as $f) {
            $d = new \DOMDocument;
            @$d->loadHTMLFile($f);
            foreach ($d->getElementsByTagName("div") as $div) {
                $exp = $div->getAttribute("class");
                foreach ($div->getElementsByTagName("iframe") as $frame) {
                    $test = \MensBeam\HTML\Parser\BASE."tests/platform-tests/html/syntax/xmldecl/".$frame->getAttribute("src");
                    if (file_exists($test.".headers")) {
                        $h = file_get_contents($test.".headers");
                        if (preg_match('/^Content-Type:\s*text\/html;\s*charset=(\S+)\s*$/Dis', $h, $m)) {
                            $charset = $m[1];
                        }
                        assert(isset($charset), new \Exception("Header file associated with $test has no charset"));
                    } else {
                        $charset = null;
                    }
                    yield [basename($test), $charset, $exp];
                }
            }
        }
    }
}
