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

abstract class TagToken extends Token {
    public $name;

    public function __construct(string $name) {
        $this->name = $name;
    }
}

class EOFToken extends Token {}

class DOCTYPEToken extends Token {
    public $forceQuirks = false;
    public $public;
    public $system;

    public function __construct(string $name = null, string $public = '', string $system = '') {
        $this->name = $name;

        $this->public = $public;
        $this->system = $system;
    }
}

class CharacterToken extends DataToken {}

class CommentToken extends DataToken {
    public function __construct(string $data = '') {
        parent::__construct($data);
    }
}

class StartTagToken extends TagToken {
    public $namespace;
    public $selfClosing;
    public $attributes = [];

    public function __construct(string $name, bool $selfClosing = false, string $namespace = Parser::HTML_NAMESPACE) {
        $this->selfClosing = $selfClosing;
        $this->namespace = $namespace;
        parent::__construct($name);
    }

     public function getAttribute(string $name) {
         $key = $this->getAttributeKey($name);

         return (isset($this->attributes[$key])) ? $this->attributes[$key] : null;
     }

     public function hasAttribute(string $name): bool {
         return (!is_null($this->_getAttributeKey($name)));
     }

     public function removeAttribute(string $name) {
         unset($this->attributes[$this->getAttributeKey($name)]);
     }

     public function setAttribute(string $name, string $value, string $namespace = Parser::HTML_NAMESPACE) {
         $key = $this->_getAttributeKey($name);
         $attribute = new TokenAttr($name, $value, $namespace);

         if (is_null($key)) {
             $this->attributes[] = $attribute;
         } else {
             $this->attributes[$key] = $attribute;
         }
     }

     private function _getAttributeKey(string $name) {
         $key = null;
         foreach ($this->attributes as $key => $a) {
             if ($a->name === $name) {
                 break;
             }
         }

         return $key;
     }
}

class EndTagToken extends TagToken {}

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