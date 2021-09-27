<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class Config {
    /** @var ?string The fallback encoding used when no encoding is provided or can be detected for the document. See https://html.spec.whatwg.org/multipage/parsing.html#determining-the-character-encoding:implementation-defined for guidance */
    public $encodingFallback = null;
    /** @var ?bool Whether parse errors should be recorded. Recording parse errors incurs a performance penalty. */
    public $errorCollection = null;
}