<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

trait ToString {
    public function __toString(): string {
        return $this->ownerDocument->serialize($this);
    }
}
