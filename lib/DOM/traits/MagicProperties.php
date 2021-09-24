<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details
 */

declare(strict_types=1);
namespace MensBeam\HTML;

/**
 * Getters and setters in PHP sucks. Instead of having getter and setter
 * function types for classes we instead have the __get and __set magic methods
 * to handle all properties. Not only are they unwieldy to use when you have
 * many properties they also become difficult to handle when inheriting where
 * traits are involved. This trait attempts to create hackish getter and setter
 * functions that can be extended by simple inheritance.
 */
trait MagicProperties {
    public function __get(string $name) {
        // If a getter method exists return it. Otherwise, trigger a property does not
        // exist fatal error.
        $methodName = $this->getMagicPropertyMethodName($name);
        if (!method_exists($this, $methodName)) {
            trigger_error("Property \"$name\" does not exist", \E_USER_ERROR);
        }
        return call_user_func([ $this, $methodName ]);
    }

    public function __isset(string $name): bool {
        return (method_exists($this, $this->getMagicPropertyMethodName($name)));
    }

    public function __set(string $name, $value) {
        // If a setter method exists return that.
        $methodName = $this->getMagicPropertyMethodName($name, false);
        if (method_exists($this, $methodName)) {
            call_user_func([ $this, $methodName ], $value);
            return;
        }

        // Otherwise, if a getter exists then trigger a readonly property fatal error.
        // Finally, if a getter doesn't exist trigger a property does not exist fatal
        // error.
        if (method_exists($this, $this->getMagicPropertyMethodName($name))) {
            trigger_error("Cannot write readonly property \"$name\"", \E_USER_ERROR);
        } else {
            trigger_error("Property \"$name\" does not exist", \E_USER_ERROR);
        }
    }

    public function __unset(string $name) {
        $methodName = $this->getMagicPropertyMethodName($name, false);
        if (!method_exists($this, $methodName)) {
            trigger_error("Cannot write readonly property \"$name\"", \E_USER_ERROR);
        }

        call_user_func([ $this, $methodName ], null);
    }


    private function getMagicPropertyMethodName(string $name, bool $get = true): string {
        return "__" . (($get) ? 'get' : 'set') . "_{$name}";
    }
}