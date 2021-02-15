<?php
declare(strict_types=1);
namespace dW\HTML5;

abstract class Token {}

abstract class DataToken extends Token {
    public $data;

    public function __construct(string $data) {
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

class CommentToken extends DataToken {
    public const NAME = "Comment token";

    public function __construct(string $data = '') {
        parent::__construct($data);
    }
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

    public function __construct(string $name, bool $selfClosing = false, string $namespace = Parser::HTML_NAMESPACE) {
        $this->selfClosing = $selfClosing;
        $this->namespace = $namespace;
        $this->name = $name;
    }

     public function getAttribute(string $name) {
         $key = $this->_getAttributeKey($name);

         return (isset($this->attributes[$key])) ? $this->attributes[$key] : null;
     }

     public function hasAttribute(string $name): bool {
         return (!is_null($this->_getAttributeKey($name)));
     }

     public function removeAttribute(string $name) {
         unset($this->attributes[$this->_getAttributeKey($name)]);
     }

     public function setAttribute(string $name, string $value, string $namespace = Parser::HTML_NAMESPACE) {
         $key = $this->_getAttributeKey($name);

         if (is_null($key)) {
             $this->attributes[] = new TokenAttr($name, $value, $namespace);
         } else {
             $attribute = &$this->attributes[$key];
             $attribute->name = $name;
             $attribute->value = $value;
             $attribute->namespace = $namespace;
         }
     }

     private function _getAttributeKey(string $name) {
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
    public $name;
    public $value;
    public $namespace;

    public function __construct(string $name, string $value, string $namespace = Parser::HTML_NAMESPACE) {
        $this->name = $name;
        $this->value = $value;
        $this->namespace = $namespace;
    }
}
