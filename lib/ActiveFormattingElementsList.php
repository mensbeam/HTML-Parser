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
class ActiveFormattingElementsList implements \ArrayAccess {
    protected $storage = [];

    public function offsetSet($offset, $value) {
        if ($offset < 0) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        if (is_null($offset)) {
            # When the steps below require the UA to push onto the list of active formatting
            # elements an element element, the UA must perform the following steps:
            if ($value instanceof DOMElement) {
                # 1. If there are already three elements in the list of active formatting
                # elements after the last marker, if any, or anywhere in the list if there are
                # no markers, that have the same tag name, namespace, and attributes as element,
                # then remove the earliest such element from the list of active formatting
                # elements. For these purposes, the attributes must be compared as they were
                # when the elements were created by the parser; two elements have the same
                # attributes if all their parsed attributes can be paired such that the two
                # attributes in each pair have identical names, namespaces, and values (the
                # order of the attributes does not matter).
                $lastMarkerIndex = $this->lastMarker;
                $start = ($lastMarkerIndex !== false) ? $lastMarkerIndex + 1 : 0;
                $length = count($storage);
                if ($start < $length - 3) {
                    $count = 0;
                    for ($i = $length - 1; $i > $start; $i--) {
                        $cur = $storage[$i];
                        if ($cur->nodeName === $value->nodeName && $cur->namespaceURI === $value->namespaceURI && $cur->attributes->length === $value->attributes->length) {
                            $a = [];
                            for ($j = 0; $j < $cur->attributes->length; $cur++) {
                                $cur2 = $cur->attributes[$j];
                                $a[] = $cur2->name . $cur2->namespaceURI . $cur2->value;
                            }

                            $b = [];
                            for ($j = 0; $j < $value->attributes->length; $cur++) {
                                $cur2 = $value->attributes[$j];
                                $b[] = $cur2->name . $cur2->namespaceURI . $cur2->value;
                            }

                            sort($a);
                            sort($b);

                            if ($a === $b) {
                                $count++;
                                if ($count === 3) {
                                    $this->offsetUnset($i);
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            # 2. Add element to the list of active formatting elements.
            $this->storage[] = $value;
        } else {
            $this->storage[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        unset($this->storage[$offset]);
        // Reindex the array.
        $this->storage = array_values($this->storage);
    }

    public function offsetGet($offset) {
        if ($offset < 0 || $offset > count($this->$storage) - 1) {
            throw new Exception(Exception::STACK_INVALID_INDEX);
        }

        return $this->storage[$offset];
    }

    public function insertMarker() {
        $this->offsetSet(null, new ActiveFormattingElementMarker());
    }

    public function pop() {
        return array_pop($this->storage);
    }

    public function __get($property) {
        switch ($property) {
            case 'lastMarker':
                foreach (array_reverse($this->storage) as $key => $value) {
                    if ($value instanceof ActiveFormattingElementMarker) {
                        return $key;
                    }
                }

                return false;
            break;
            case 'length': return count($this->storage);
            break;
            default: return null;
        }
    }
}

class ActiveFormattingElementMarker {}