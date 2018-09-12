<?php
declare(strict_types=1);
namespace dW\HTML5;

class DocumentFragment extends \DOMDocumentFragment {
    use Descendant, Printing;

    public function __toString() {
        return $this->serialize();
    }
}
