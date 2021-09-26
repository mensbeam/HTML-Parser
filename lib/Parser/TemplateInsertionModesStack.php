<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

class TemplateInsertionModesStack extends Stack {
    public function __get($property) {
        assert($property === "currentMode", new \Exception("Property $property is invalid"));
        switch ($property) {
            case 'currentMode':
                return $this->isEmpty() ? null : $this->top();
            default: 
                return null; // @codeCoverageIgnore
        }
    }
}
