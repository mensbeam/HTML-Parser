<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

abstract class Token {}

abstract class DataToken extends Token {
    public $data;

    public function __construct(string $data = "") {
        $this->data = $data;
    }
}

class DOCTYPEToken extends Token {
    public const NAME = "DOCTYPE token";

    # DOCTYPE tokens have a name, a public identifier,
    #   a system identifier, and a force-quirks flag.
    # When a DOCTYPE token is created, its name,
    #   public identifier, and system identifier must
    #   be marked as missing (which is a distinct state
    #   from the empty string), and the force-quirks flag
    #   must be set to off (its other state is on).
    public $forceQuirks = false;
    public $name;
    public $public;
    public $system;

    public function __construct(?string $name = null, ?string $public = null, ?string $system = null) {
        // null stands in for the distinct "missing" state
        $this->name = $name;
        $this->public = $public;
        $this->system = $system;
    }
}

class CharacterToken extends DataToken {
    public const NAME = "Character token";
}

class WhitespaceToken extends CharacterToken {}

class NullCharacterToken extends CharacterToken {}

class CommentToken extends DataToken {
    public const NAME = "Comment token";
}

class ProcessingInstructionToken extends CommentToken {
}

abstract class TagToken extends Token {
    # Start and end tag tokens have a tag name,
    #   a self-closing flag, and a list of attributes,
    #   each of which has a name and a value.
    # When a start or end tag token is created, its
    #   self-closing flag must be unset (its other state
    #   is that it be set), and its attributes list must be empty.
    public $name;
    public $namespace;
    public $selfClosing;
    public $selfClosingAcknowledged = false;
    public $attributes = [];

    public function __construct(string $name, bool $selfClosing = false, ?string $namespace = null) {
        $this->selfClosing = $selfClosing;
        $this->namespace = $namespace;
        $this->name = $name;
    }

    public function hasAttribute(string $name): bool {
        return ($this->_getAttributeKey($name) !== null);
    }

    public function getAttribute(string $name): ?TokenAttr {
        $key = $this->_getAttributeKey($name);
        return (isset($this->attributes[$key])) ? $this->attributes[$key] : null;
    }

    public function getAttributeValue(string $name): ?string {
        $attr = $this->getAttribute($name);
        if ($attr) {
            return $attr->value;
        }
        return null;
    }

    private function _getAttributeKey(string $name): ?int {
        foreach ($this->attributes as $key => $a) {
            if ($a->name === $name) {
                return $key;
            }
        }
        return null;
    }
}

class StartTagToken extends TagToken {
    public const NAME = "Start tag token";
}

class EndTagToken extends TagToken {
    public const NAME = "End tag token";
}

class EOFToken extends Token {
    public const NAME = "EOF token";
}

class TokenAttr {
    /** @var string The name of the attribute */
    public $name;
    /** @var string The attribute's value */
    public $value;
    /** @var string|null The attribute's namespace. This is normally null but may be set during tree construction */
    public $namespace = null;

    public function __construct(string $name, string $value) {
        $this->name = $name;
        $this->value = $value;
    }
}
