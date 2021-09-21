<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class DocumentFragment extends \DOMDocumentFragment {
    use ContainerNode, MoonwalkShallow, ToString, Walk, WalkShallow;
}
