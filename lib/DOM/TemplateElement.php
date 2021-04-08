<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

/** Class specifically for template elements to handle its content property. */
class TemplateElement extends Element {
    public $content = null;

    public function __construct(Document $ownerDocument, string $qualifiedName, ?string $value = null, string $namespace = '') {
        parent::__construct($qualifiedName, $value, $namespace);

        // Elements that are created by their constructor in PHP aren't owned by any
        // document and are readonly until owned by one. Temporarily append to a
        // document fragment so the element will be owned by the supplied owner
        // document.
        $frag = $ownerDocument->createDocumentFragment();
        $frag->appendChild($this);
        $frag->removeChild($this);
        unset($frag);
    }

    public function __destruct() {
        ElementMap::delete($this);
    }
}
