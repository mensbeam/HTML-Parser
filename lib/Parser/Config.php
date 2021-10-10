<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class Config {
    /** @var ?string The fallback encoding used when no encoding is provided or can be detected for the document. See https://html.spec.whatwg.org/multipage/parsing.html#determining-the-character-encoding:implementation-defined for guidance */
    public $encodingFallback = null;
    /** @var ?int The number of bytes to examine during encoding pre-scan. 1024 is the default and recommended value */
    public $encodingPrescanBytes = null;
    /** @var ?bool Whether parse errors should be recorded. Recording parse errors incurs a performance penalty. */
    public $errorCollection = null;
    /** @var ?bool Whether to use the HTML namespace rather than the null namespace for HTML elements. Using the HTML namespace is the correct behaviour, but this has performance and compatibility implications for PHP */
    public $htmlNamespace = null;
    /** @var ?bool Whether to retain processing instructions rather than parsing them into comments as the HTML specification requires. Setting this true will yield non-standard documents */
    public $processingInstructions = null;
}