<?php
declare(strict_types=1);
namespace dW\HTML5;

trait Ancestor {
    use Compare;

    public function getAncestor($needle): Element {
        return $this->ancestor($needle, true);
    }

    public static function hasAncestor($needle): bool {
        return $this->ancestor($needle, false);
    }

    protected function ancestor($needle, bool $returnNode = true) {
        $context = $this->parentNode;
        do {
            $result = self::compare($needle, $context);
            if (!is_null($result)) {
                return ($returnNode === true) ? $result : true;
            }
        } while ($context = $context->parentNode);

        return ($returnNode === true) ? null : false;
    }
}