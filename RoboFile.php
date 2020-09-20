<?php

use Robo\Result;

const BASE = __DIR__.\DIRECTORY_SEPARATOR;
const BASE_TEST = BASE."tests".\DIRECTORY_SEPARATOR;
define("IS_WIN", defined("PHP_WINDOWS_VERSION_MAJOR"));
define("IS_MAC", php_uname("s") === "Darwin");

function norm(string $path): string {
    $out = realpath($path);
    if (!$out) {
        $out = str_replace(["/", "\\"], \DIRECTORY_SEPARATOR, $path);
    }
    return $out;
}

class RoboFile extends \Robo\Tasks {
    /** Runs the typical test suite
     *
     * Arguments passed to the task are passed on to PHPUnit. Thus one may, for
     * example, run the following command and get the expected results:
     *
     * ./robo test --testsuite Tokenizer --exclude-group slow --testdox
     *
     * Please see the PHPUnit documentation for available options.
    */
    public function test(array $args): Result {
        return $this->runTests(escapeshellarg(\PHP_BINARY), "typical", $args);
    }

    /** Runs the full test suite
     *
     * This includes pedantic tests which may help to identify problems.
     * See help for the "test" task for more details.
    */
    public function testFull(array $args): Result {
        return $this->runTests(escapeshellarg(\PHP_BINARY), "full", $args);
    }

    /**
     * Runs a quick subset of the test suite
     *
     * See help for the "test" task for more details.
    */
    public function testQuick(array $args): Result {
        return $this->runTests(escapeshellarg(\PHP_BINARY), "quick", $args);
    }

    /** Manually updates the imported html5lib test suite */
    public function testUpdate(): Result {
        $dir = BASE_TEST."html5lib-tests";
        if (is_dir($dir)) {
            return $this->taskGitStack()->dir($dir)->pull()->run();
        } else {
            return $this->taskGitStack()->cloneRepo("https://github.com/html5lib/html5lib-tests", $dir)->run();
        }
    }

    /** Produces a code coverage report
     *
     * By default this task produces an HTML-format coverage report in
     * tests/coverage/. Additional reports may be produced by passing
     * arguments to this task as one would to PHPUnit.
    */
    public function coverage(array $args): Result {
        // run tests with code coverage reporting enabled
        $exec = $this->findCoverageEngine();
        return $this->runTests($exec, "coverage", array_merge(["--coverage-html", BASE_TEST."coverage"], $args));
    }

    /** Produces a code coverage report, with redundant tests
     *
     * Depending on the environment, some tests that normally provide
     * coverage may be skipped, while working alternatives are normally
     * suppressed for reasons of time. This coverage report will try to
     * run all tests which may cover code.
     *
     * See also help for the "coverage" task for more details.
    */
    public function coverageFull(array $args): Result {
        // run tests with code coverage reporting enabled
        $exec = $this->findCoverageEngine();
        return $this->runTests($exec, "typical", array_merge(["--coverage-html", BASE_TEST."coverage"], $args));
    }

    protected function findCoverageEngine(): string {
        $dir = rtrim(ini_get("extension_dir"), "/").\DIRECTORY_SEPARATOR;
        $ext = IS_WIN ? "dll" : (IS_MAC ? "dylib" : "so");
        $php = escapeshellarg(\PHP_BINARY);
        $code = escapeshellarg(BASE."lib");
        if (extension_loaded("pcov")) {
            return "$php -d pcov.enabled=1 -d pcov.directory=$code";
        } elseif (extension_loaded("xdebug")) {
            return $php;
        } elseif (file_exists($dir."pcov.$ext")) {
            return "$php -d extension=pcov.$ext -d pcov.enabled=1 -d pcov.directory=$code";
        } elseif (file_exists($dir."pcov.$ext")) {
            return "$php -d zend_extension=xdebug.$ext";
        } else {
            if (IS_WIN) {
                $dbg = dirname(\PHP_BINARY)."\\phpdbg.exe";
                $dbg = file_exists($dbg) ? $dbg : "";
            } else {
                $dbg = trim(`which phpdbg 2>/dev/null`);
            }
            if ($dbg) {
                return escapeshellarg($dbg)." -qrr";
            } else {
                return $php;
            }
        }
    }

    protected function blackhole(bool $all = false): string {
        $hole = IS_WIN ? "nul" : "/dev/null";
        return $all ? ">$hole 2>&1" : "2>$hole";
    }

    protected function runTests(string $executor, string $set, array $args) : Result {
        switch ($set) {
            case "typical":
                $set = ["--exclude-group", "optional"];
                break;
            case "quick":
                $set = ["--exclude-group", "optional,slow"];
                break;
            case "coverage":
                $set = ["--exclude-group", "optional,coverageOptional"];
                break;
            case "full":
                $set = [];
                break;
            default:
                throw new \Exception;
        }
        $execpath = norm(BASE."vendor-bin/phpunit/vendor/phpunit/phpunit/phpunit");
        $confpath = realpath(BASE_TEST."phpunit.dist.xml") ?: norm(BASE_TEST."phpunit.xml");
        // clone the html5lib test suite if it's not already present
        if (!is_dir(BASE_TEST."html5lib-tests")) {
            $this->testUpdate();
        }
        return $this->taskExec($executor)->option("-d", "zend.assertions=1")->arg($execpath)->option("-c", $confpath)->args(array_merge($set, $args))->run();
    }

    /** Runs the coding standards fixer */
    public function clean($opts = ['demo|d' => false]): Result {
        $t = $this->taskExec(norm(BASE."vendor/bin/php-cs-fixer"));
        $t->arg("fix");
        if ($opts['demo']) {
            $t->args("--dry-run", "--diff")->option("--diff-format", "udiff");
        }
        return $t->run();
    }

    /** Produces the CharacterReference class file */
    public function charref() {
        $template = <<<'FILE'
<?php
declare(strict_types=1);
namespace dW\HTML5;

// This file is machine-generated
// DO NOT MODIFY

// To update, run ./robo charref

class CharacterReference {
    const LONGEST_NAME = %LONGEST%;
    const PREFIX_PATTERN = %NAMED_PATTERN%;
    const NAMES = [
        %NAMED_REFERENCES%
    ];
    const C1_TABLE = [
        %C1_SUBSTITUTIONS%
    ];
}

FILE;
        $input = @json_decode(@file_get_contents("https://html.spec.whatwg.org/entities.json"), true);
        if (!is_array($input)) {
            throw new \Exception("Could not retrieve character reference table.");
        }
        $list = [];
        $terms = [];
        foreach ($input as $entity => $data) {
            // strip the ampersand from the entity name
            $entity = substr($entity, 1);
            // add the entity name to an array of regular expression terms
            // if the entry exists in unterminated form, compress it into one, skiping the unterminated version
            if (substr($entity, -1) === ';') {
                if (isset($input['&'.substr($entity, 0, strlen($entity) -1)])) {
                    $terms[] = "$entity?";
                } else {
                    $terms[] = $entity;
                }
            }
            // add a PHP-code representation of the entity name and its characters to another array
            $chars = $data['codepoints'];
            for ($a = 0; $a < sizeof($chars); $a++) {
                $chars[$a] = '\u{'.dechex($chars[$a]).'}';
            }
            $chars = implode('', $chars);
            $list[] = "'$entity'=>\"$chars\"";
        }
        // concatenate the list of entities and substitute them into the template
        $list = implode(",", $list);
        $template = str_replace('%NAMED_REFERENCES%', $list, $template);
        // prepare the list of terms as a regular expression
        // sort longest terms first
        usort($terms, function($a, $b) {
            return -1 * (strlen(preg_replace("/\W/", "", $a)) <=> strlen(preg_replace("/\W/", "", $b)));
        });
        // note the longest term
        $longest = strlen(preg_replace("/\W/", "", $terms[0]));
        $template = str_replace('%LONGEST%', $longest, $template);
        // concatenate the terms into a case-sensitive non-capturing prefix search
        $regexp = '/^(?:'.implode('|', $terms).')/';
        $template = str_replace('%NAMED_PATTERN%', var_export($regexp, true), $template);
        // Compile the C1 control substitution table
        // See https://html.spec.whatwg.org/multipage/parsing.html#numeric-character-reference-end-state
        $list = [];
        $c1table = [
            0x80 => 0x20AC, // EURO SIGN (€)
            0x82 => 0x201A, // SINGLE LOW-9 QUOTATION MARK (‚)
            0x83 => 0x0192, // LATIN SMALL LETTER F WITH HOOK (ƒ)
            0x84 => 0x201E, // DOUBLE LOW-9 QUOTATION MARK („)
            0x85 => 0x2026, // HORIZONTAL ELLIPSIS (…)
            0x86 => 0x2020, // DAGGER (†)
            0x87 => 0x2021, // DOUBLE DAGGER (‡)
            0x88 => 0x02C6, // MODIFIER LETTER CIRCUMFLEX ACCENT (ˆ)
            0x89 => 0x2030, // PER MILLE SIGN (‰)
            0x8A => 0x0160, // LATIN CAPITAL LETTER S WITH CARON (Š)
            0x8B => 0x2039, // SINGLE LEFT-POINTING ANGLE QUOTATION MARK (‹)
            0x8C => 0x0152, // LATIN CAPITAL LIGATURE OE (Œ)
            0x8E => 0x017D, // LATIN CAPITAL LETTER Z WITH CARON (Ž)
            0x91 => 0x2018, // LEFT SINGLE QUOTATION MARK (‘)
            0x92 => 0x2019, // RIGHT SINGLE QUOTATION MARK (’)
            0x93 => 0x201C, // LEFT DOUBLE QUOTATION MARK (“)
            0x94 => 0x201D, // RIGHT DOUBLE QUOTATION MARK (”)
            0x95 => 0x2022, // BULLET (•)
            0x96 => 0x2013, // EN DASH (–)
            0x97 => 0x2014, // EM DASH (—)
            0x98 => 0x02DC, // SMALL TILDE (˜)
            0x99 => 0x2122, // TRADE MARK SIGN (™)
            0x9A => 0x0161, // LATIN SMALL LETTER S WITH CARON (š)
            0x9B => 0x203A, // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK (›)
            0x9C => 0x0153, // LATIN SMALL LIGATURE OE (œ)
            0x9E => 0x017E, // LATIN SMALL LETTER Z WITH CARON (ž)
            0x9F => 0x0178, // LATIN CAPITAL LETTER Y WITH DIAERESIS (Ÿ)
        ];
        foreach ($c1table as $c1 => $code) {
            $list[] = "$c1=>$code";
        }
        $list = implode(",", $list);
        $template = str_replace('%C1_SUBSTITUTIONS%', $list, $template);
        // output the file itself
        file_put_contents(BASE."lib/CharacterReference.php", $template);
    }
}
