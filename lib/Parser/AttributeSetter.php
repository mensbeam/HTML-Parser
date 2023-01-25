<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

trait AttributeSetter {
    protected $mangledAttributes = false;

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
            // @codeCoverageIgnoreStart
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML 1.0 Second Edition
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                // NOTE: This case is never encountered by the parser
                $qualifiedName = self::coerceName($qualifiedName, true);
                $a = $d->createAttributeNS($namespaceURI, $qualifiedName);
            }
            // @codeCoverageIgnoreEnd
            $a->value = self::escapeString($value, true);
            $element->setAttributeNodeNS($element->ownerDocument->importNode($a));
        } elseif ($namespaceURI !== null || strpos($qualifiedName, "xml:") === 0) {
            try {
                $element->setAttributeNS($namespaceURI, $qualifiedName, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML 1.0 Second Edition
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $qualifiedName = self::coerceName($qualifiedName, ($namespaceURI !== null));
                $element->setAttributeNS($namespaceURI, $qualifiedName, $value);
                $this->mangledAttributes = true;
            }
        } elseif ($namespaceURI === null && $qualifiedName === 'xmlns') {
            // There are even more bugs with xmlns attributes. Xmlns attributes on html
            // elements are parsed in the null namespace per the specification. PHP still
            // goes a bit screwy when trying to access them afterwards. Attempt to work
            // around that.
            $a = $element->ownerDocument->createAttribute('xmlns');
            $a->value = $value;
            $element->setAttributeNode($a);
        } else {
            try {
                $element->setAttribute($qualifiedName, $value);
            } catch (\DOMException $e) {
                // The attribute name is invalid for XML 1.0 Second Edition
                // Replace any offending characters with "UHHHHHH" where H are the
                //   uppercase hexadecimal digits of the character's code point
                $qualifiedName = self::coerceName($qualifiedName, false);
                $element->setAttribute($qualifiedName, $value);
                $this->mangledAttributes = true;
            }
            if ($qualifiedName === "id") {
                $element->setIdAttribute($qualifiedName, true);
            }
        }
    }
}
