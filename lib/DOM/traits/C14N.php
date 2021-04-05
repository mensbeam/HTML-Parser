<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Disables C14N in extended DOM classes
trait C14N {
    public function C14N($exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
    }

    public function C14NFile($uri, $exclusive = null, $with_comments = null, ?array $xpath = null, ?array $ns_prefixes = null): bool {
        return false;
    }
}
