<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// Exists so Element can extend methods from its traits.
abstract class AbstractElement extends \DOMElement {
    use ContainerNode, EscapeString, Moonwalk, ToString, Walk;
}
