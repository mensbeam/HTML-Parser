<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// This is a write-only map of elements which need to be kept in memory; it
// exists because values of properties on derived DOM classes are lost unless at
// least one PHP reference is kept for the element somewhere in userspace. This
// is that somewhere. It is at present only used for template elements.
class ElementMap {
    protected static $_storage = [];

    public static function delete(Element $element) {
        foreach (self::$_storage as $k => $v) {
            if ($v->isSameNode($element)) {
                unset(self::$_storage[$k]);
                self::$_storage = array_values(self::$_storage);
                return true;
            }
        }

        return false;
    }

    public static function destroy(Document $document) {
        $changed = false;
        foreach (self::$_storage as $k => $v) {
            if ($v->ownerDocument->isSameNode($document)) {
                unset(self::$_storage[$k]);
                $changed = true;
            }
        }

        if ($changed) {
            self::$_storage = array_values(self::$_storage);
            return true;
        }

        return false;
    }

    public static function has(Element $element) {
        foreach (self::$_storage as $v) {
            if ($v->isSameNode($element)) {
                return true;
            }
        }

        return false;
    }

    public static function set(Element $element) {
        if (!self::has($element)) {
            self::$_storage[] = $element;
            return true;
        }

        return false;
    }
}
