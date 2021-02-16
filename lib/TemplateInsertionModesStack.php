<?php
declare(strict_types=1);
namespace dW\HTML5;

class TemplateInsertionModesStack extends \SplStack {
    public function __get($property) {
        switch ($property) {
            case 'currentMode':
                return $this->isEmpty() ? null : $this->top();
            default: 
                return null;
        }
    }
}
