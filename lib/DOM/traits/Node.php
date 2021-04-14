<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Extensions to PHP's DOM cannot inherit from an extended Node parent, so a
// trait is the next best thing...
trait Node {
    // Disable C14N
    public function C14N($exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
    }

    // Disable C14NFile
    public function C14NFile($uri, $exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
    }
}
