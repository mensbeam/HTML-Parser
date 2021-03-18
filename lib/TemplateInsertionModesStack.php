<?php
declare(strict_types=1);
namespace dW\HTML5;

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
