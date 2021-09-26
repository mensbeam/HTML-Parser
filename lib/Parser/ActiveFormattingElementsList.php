<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

# 8.2.3.3. The list of active formatting elements
# Initially, the list of active formatting elements is empty. It is used to
# handle mis-nested formatting element tags.
#
# The list contains elements in the formatting category, and markers. The
# markers are inserted when entering applet, object, marquee, template, td, th,
# and caption elements, and are used to prevent formatting from "leaking" into
# applet, object, marquee, template, td, th, and caption elements.
#
# In addition, each element in the list of active formatting elements is
# associated with the token for which it was created, so that further elements
# can be created for that token if necessary.
class ActiveFormattingElementsList extends Stack {
    protected $_storage = [];

    public function offsetSet($offset, $value) {
        $count = $this->count;
        assert($offset >= 0 && $offset <= $count, new Exception(Exception::STACK_INVALID_INDEX, $offset));
        assert($value instanceof ActiveFormattingElementsMarker || (
            is_array($value)
            && count($value) === 2
            && isset($value['token'])
            && isset($value['element'])
            && $value['token'] instanceof StartTagToken
            && $value['element'] instanceof \DOMElement
        ), new Exception(Exception::STACK_INVALID_VALUE));
        if ($value instanceof ActiveFormattingElementsMarker) {
            $this->_storage[$offset ?? $count] = $value;
        } elseif ($count && ($offset ?? $count) === $count) {
            # When the steps below require the UA to push onto the list of active formatting
            # elements an element element, the UA must perform the following steps:
            // First find the position of the last marker, if any
            $lastMarker = -1;
            foreach ($this as $pos => $item) {
                if ($item instanceof ActiveFormattingElementsMarker) {
                    $lastMarker = $pos;
                    break;
                }
            }
            # If there are already three elements in the list of active formatting
            #   elements after the last marker, if any, or anywhere in the list if there are
            #   no markers, that have the same tag name, namespace, and attributes as element,
            #   then remove the earliest such element from the list of active formatting
            #   elements.
            $pos = $count - 1;
            $matches = 0;
            if ($pos > $lastMarker) {
                do {
                    $matches += (int) $this->matchElement($value['element'], $this->_storage[$pos]['element']);
                    // Stop once there are three matches or the marker is reached
                } while ($matches < 3 && (--$pos) > $lastMarker);
            }
            if ($matches === 3) {
                $this->offsetUnset($pos);
            }
            # Add element to the list of active formatting elements.
            $this->_storage[] = $value;
        } else {
            $this->_storage[$offset ?? $count] = $value;
        }
        $this->count = count($this->_storage);
    }

    protected function matchElement(\DOMElement $a, \DOMElement $b): bool {
        // Compare elements as part of pushing an element onto the stack
        # 1. If there are already three elements in the list of active formatting
        #   elements after the last marker, if any, or anywhere in the list if there are
        #   no markers, that have the same tag name, namespace, and attributes as element,
        #   then remove the earliest such element from the list of active formatting
        #   elements.
        # For these purposes, the attributes must be compared as they were
        #   when the elements were created by the parser; two elements have the same
        #   attributes if all their parsed attributes can be paired such that the two
        #   attributes in each pair have identical names, namespaces, and values (the
        #   order of the attributes does not matter).
        if (
            $a->nodeName !== $b->nodeName
            || $a->namespaceURI !== $b->namespaceURI
            || $a->attributes->length !== $b->attributes->length
        ) {
            return false;
        }
        foreach ($a->attributes as $attr) {
            if (!$b->hasAttributeNS($attr->namespaceURI, $attr->nodeName) || $b->getAttributeNS($attr->namespaceURI, $attr->nodeName) !== $attr->value) {
                return false;
            }
        }
        return true;
    }

    public function insert(StartTagToken $token, \DOMElement $element, ?int $at = null): void  {
        assert($at === null || ($at >= 0 && $at <= $this->count), new Exception(Exception::STACK_INVALID_INDEX, $at));
        if ($at === null) {
            $this[] = [
                'token' => $token,
                'element' => $element
            ];
        } else {
            array_splice($this->_storage, $at, 0, [[
                'token' => $token,
                'element' => $element,
            ]]);
            $this->count = count($this->_storage);
        }
    }

    public function insertMarker(): void {
        $this[] = new ActiveFormattingElementsMarker;
    }

    public function clearToTheLastMarker(): void {
        # When the steps below require the UA to clear the list of active formatting
        # elements up to the last marker, the UA must perform the following steps:
        # 1. Let entry be the last (most recently added) entry in the list of active
        # formatting elements.
        # 2. Remove entry from the list of active formatting elements.
        # 3. If entry was a marker, then stop the algorithm at this point. The list has
        # been cleared up to the last marker.
        # 4. Go to step 1.
        while ($this->_storage) {
            $popped = array_pop($this->_storage);
            if ($popped instanceof ActiveFormattingElementsMarker) {
                break;
            }
        }
        $this->count = count($this->_storage);
    }

    public function findSame(\DOMElement $target): int {
        foreach ($this as $k => $entry) {
            if (!$entry instanceof ActiveFormattingElementsMarker && $entry['element']->isSameNode($target)) {
                return $k;
            }
        }
        return -1;
    }

    public function findToMarker(string ...$name): int {
        foreach ($this as $k => $entry) {
            if ($entry instanceof ActiveFormattingElementsMarker) {
                return -1;
            }
            if (in_array($entry['element']->nodeName, $name)) {
                return $k;
            }
        }
        return -1;
    }

    public function removeSame(\DOMElement $target): void {
        $pos = $this->findSame($target);
        if ($pos > -1) {
            unset($this[$pos]);
        }
    }

    /** @codeCoverageIgnore */
    public function __toString(): string {
        $out = [];
        foreach ($this as $entry) {
            if ($entry instanceof ActiveFormattingElementsMarker) {
                $out[] = "|";
            } else {
                $node = $entry['element'];
                $ns = $node->namespaceURI ?? Parser::HTML_NAMESPACE;
                $prefix = Parser::NAMESPACE_MAP[$ns] ?? "?";
                $prefix .= $prefix ? " " : "";
                $out[] = $prefix.$node->nodeName;
            }
        }
        return implode(" - ", $out);
    }
}

class ActiveFormattingElementsMarker {
}
