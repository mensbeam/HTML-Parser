<?php
declare(strict_types=1);
namespace dW\HTML5;

class TemplateInsertionModesStack extends Stack {
    public function __get($property) {
        $value = parent::__get($property);
        if (!is_null($value)) {
            return $value;
        }

        switch ($property) {
            case 'currentMode': return
                $currentMode = end($this->_storage);
                return ($currentMode) ? $currentMode : null;
            break;
            default: return null;
        }
    }
}
