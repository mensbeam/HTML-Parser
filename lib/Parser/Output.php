<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class Output {
    /** @var \DOMDocument The parsed document */
    public $document;
    /** @var string The document's original encoding, as supplied by the user or detected during parsing */
    public $encoding;
    /** @var int The "quirks mode" property of the document, one of Parser::NO_QUIRKS_MODE, Parser::LIMITED_QUIRKS_MODE, or Parser::QUIRKS_MODE */
    public $quirksMode;
    /** @var array|null The list of parse errors emitted during processing if parse error reporting was turned on */
    public $errors = null;
}
