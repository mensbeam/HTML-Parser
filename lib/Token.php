<?php
declare(strict_types=1);
namespace dW\HTML5;

abstract class Token {}

abstract class DataToken extends Token {
    public $data;

    public function __construct($data) {
        $this->data = (string)$data;
    }
}

abstract class TagToken extends Token {
    public $name;

    public function __construct($name) {
        $this->name = (string)$name;
    }
}

class EOFToken extends Token {}

class DOCTYPEToken extends Token {
    public $forceQuirks = false;
    public $public;
    public $system;

    public function __construct($name = null, $public = null, $system = null) {
        $this->name = (string)$name;

        $this->public = (string)$public;
        $this->system = (string)$system;
    }
}

class CharacterToken extends DataToken {}

class CommentToken extends DataToken {
    public function __construct($data = '') {
        parent::__construct($data);
    }
}

class StartTagToken extends TagToken {
    public $namespace;
    public $selfClosing;

    protected $_attributes;

    public function __construct($name, bool $selfClosing = false, string $namespace = Parser::HTML_NAMESPACE) {
        $this->selfClosing = $selfClosing;
        $this->namespace = $namespace;
        parent::__construct($name);
    }

    public function getAttribute(string $name): \DOMAttr {
         return ($this->_attributes[$name]) ? $this->_attributes[$name] : null;
     }

     public function hasAttribute(string $name): bool {
         return (isset($this->_attributes[$name]));
     }

     public function removeAttribute(string $name) {
         unset($this->_attributes[$name]);
     }

     public function setAttribute($name, $value) {
         $this->_attributes[(string)$name] = (string)$value;
     }

     public function __get($property) {
         if ($property === 'attributes') {
             return $this->_attributes;
         }

         return null;
     }
}

class EndTagToken extends TagToken {}