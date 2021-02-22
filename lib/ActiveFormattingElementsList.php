<?php
declare(strict_types=1);
namespace dW\HTML5;

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
    protected $stack;
    protected $tree;

    public function __construct(TreeBuilder $tree, OpenElementsStack $stack) {
        $this->tree = $tree;
        $this->stack = $stack;
    }

    public function offsetSet($offset, $value) {
        $count = count($this->_storage);
        assert($offset >= 0 && $offset <= $count, new Exception(Exception::STACK_INVALID_INDEX, $offset));
        assert($value instanceof ActiveFormattingElementsMarker || (
            is_array($value) 
            && sizeof($value) === 2 
            && isset($value['token']) 
            && isset($value['element'])
            && $value['token'] instanceof StartTagToken
            && $value['element'] instanceof Element
        ), new \Exception("Active formatting element value is invalid"));
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
    }

    protected function matchElement(Element $a, Element $b): bool {
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

    public function insert(StartTagToken $token, Element $element): void  {
        $this[] = [
            'token' => $token,
            'element' => $element
        ];
    }

    public function insertMarker(): void {
        $this[] = new ActiveFormattingElementsMarker;
    }

    public function reconstruct(): void {
        # When the steps below require the UA to reconstruct the active formatting
        #   elements, the UA must perform the following steps:
        # 1. If there are no entries in the list of active formatting elements, then
        #   there is nothing to reconstruct; stop this algorithm.
        if (!$this->_storage) {
            return;
        }
        $last = count($this->_storage) - 1;
        # 2. If the last (most recently added) entry in the list of active formatting
        #   elements is a marker, or if it is an element that is in the stack of open
        #   elements, then there is nothing to reconstruct; stop this algorithm.
        $pos = $last;
        $entry = $this[$pos];
        if ($entry instanceof ActiveFormattingElementsMarker || $this->stack->findSame($entry['element']) > -1) {
            return;
        }
        # 3. Let entry be the last (most recently added) element in the list of 
        #   active formatting elements.
        // Already done
        while ($pos >= 0) {
            # 4. Rewind: If there are no entries before entry in the list of active
            #   formatting elements, then jump to the step labeled Create.
            if ($pos === 0) {
                // DEVIATION: Instead don't increment position before breaking, unlike below
                break;
            }
            # 5. Let entry be the entry one earlier than entry in the list of active
            #   formatting elements.
            $entry = $this[--$pos];
            # 6. If entry is neither a marker nor an element that is also in the stack of
            #   open elements, go to the step labeled Rewind.
            // Instead break if it is a marker or present in the stack
            if ($entry instanceof ActiveFormattingElementsMarker || $this->stack->findSame($entry['element']) > -1) {
                // DEVIATION: We increment before breaking to avoid having two loop exit points
                $pos++;
                break;
            }
        }
        while ($pos <= $last) {
            # 7. Advance: Let entry be the element one later than entry in the list of
            # active formatting elements.
            // DEVIATION: We increment at the end of the loop since we incremented when necessary before breaking out of the earlier loop
            $entry = $this[$pos];
            # 8. Create: Insert an HTML element for the token for which the element entry
            # was created, to obtain new element.
            $element = $this->tree->insertStartTagToken($entry['token']);
            # 9. Replace the entry for entry in the list with an entry for new element.
            $this->_storage[$pos]['element'] = $element;
            # 10. If the entry for new element in the list of active formatting elements is
            # not the last entry in the list, return to the step labeled Advance.
            $pos++;
        }
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
    }

    public function findSame(Element $target): int {
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

    public function removeSame(Element $target): void {
        $pos = $this->findSame($target);
        if ($pos > -1) {
            unset($this[$pos]);
        }
    }
}

class ActiveFormattingElementsMarker {}
