<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

trait AttributeSetter {
    public function elementSetAttribute(\DOMElement $element, ?string $namespaceURI, string $qualifiedName, string $value): void {
        if ($namespaceURI === Parser::XMLNS_NAMESPACE) {
            // NOTE: We create attribute nodes so that xmlns attributes
            //   don't get lost; otherwise they cannot be serialized.
            //   Furthermore we create the attribute node in a temporary
            //   document to avoid some related PHP bugs
            $d = new \DOMDocument;
            $d->appendChild($d->createElement("html"));
            try {
                $a = $d->createAttributeNS($namespaceURI, $qualifiedName);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML 1.0 Second Edition
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
                $a = $d->createAttributeNS($namespaceURI, $qualifiedName);
            }
            $a->value = self::escapeString($value, true);
            $element->setAttributeNodeNS($element->ownerDocument->importNode($a));
        } else {
            try {
                $element->setAttributeNS($namespaceURI, $qualifiedName, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML 1.0 Second Edition
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                if ($namespaceURI !== null) {
                    $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
                } else {
                    $qualifiedName = self::coerceName($qualifiedName);
                }
                $element->setAttributeNS($namespaceURI, $qualifiedName, $value);
                $this->mangledAttributes = true;
            }
            if ($qualifiedName === "id" && $namespaceURI === null) {
                $element->setIdAttribute($qualifiedName, true);
            }
        }
    }
}