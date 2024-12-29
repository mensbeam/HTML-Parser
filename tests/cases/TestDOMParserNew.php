<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\TestCase;

use MensBeam\HTML\DOMParser;

/**
 * @covers \MensBeam\HTML\DOMParser
 * @requires PHP >= 8.4
 */
class TestDOMParserNew extends TestDOMParser {
    protected $p;

    public function setUp(): void {
        $this->p = \Phake::partialMock(DOMParser::class);
        \Phake::when($this->p)->useNewParsers->thenReturn(true); 
    }
}
