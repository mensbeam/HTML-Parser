<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

class Document extends AbstractDocument {
    // Quirks mode constants
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    public $documentEncoding = null;
    public $mangledAttributes = false;
    public $mangledElements = false;
    public $quirksMode = self::NO_QUIRKS_MODE;

    protected $_body = null;
    // List of elements that are treated as block elements for the purposes of
    // output formatting when serializing
    protected const BLOCK_ELEMENTS = [ 'address', 'article', 'aside', 'blockquote', 'base', 'body', 'details', 'dialog', 'dd', 'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'isindex', 'li', 'link', 'main', 'meta', 'nav', 'ol', 'p', 'picture', 'pre', 'section', 'script', 'source', 'style', 'table', 'template', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'ul' ];
    // List of h-elements used when determining extra spacing for the purposes of
    // output formatting when serializing
    protected const H_ELEMENTS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
    // List of preformatted elements where content is ignored for the purposes of
    // output formatting when serializing
    protected const PREFORMATTED_ELEMENTS = [ 'iframe', 'listing', 'noembed', 'noframes', 'noscript', 'plaintext', 'pre', 'style', 'script', 'textarea', 'title', 'xmp' ];
    // List of elements which are self-closing; used when serializing
    protected const VOID_ELEMENTS = [ 'area', 'base', 'basefont', 'bgsound', 'br', 'col', 'embed', 'frame', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr' ];


    public function __construct() {
        parent::__construct();

        $this->registerNodeClass('DOMComment', '\MensBeam\HTML\Comment');
        $this->registerNodeClass('DOMDocumentFragment', '\MensBeam\HTML\DocumentFragment');
        $this->registerNodeClass('DOMElement', '\MensBeam\HTML\Element');
        $this->registerNodeClass('DOMProcessingInstruction', '\MensBeam\HTML\ProcessingInstruction');
        $this->registerNodeClass('DOMText', '\MensBeam\HTML\Text');
    }


    public function createAttribute($name) {
        return $this->createAttributeNS(null, $name);
    }

    public function createAttributeNS($namespaceURI, $qualifiedName) {
        // Normalize the attribute name and namespace URI per modern DOM specifications.
        if ($namespaceURI !== null) {
            $namespaceURI = trim($namespaceURI);
        }
        $qualifiedName = trim($qualifiedName);

        try {
            return parent::createAttributeNS($namespaceURI, $qualifiedName);
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledAttributes = true;
            if ($namespaceURI !== null) {
                $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
            } else {
                $qualifiedName = $this->coerceName($qualifiedName);
            }
            return parent::createAttributeNS($namespaceURI, $qualifiedName);
        }
    }

    public function createElement($name, $value = "") {
        return $this->createElementNS(null, $name, $value);
    }

    public function createElementNS($namespaceURI, $qualifiedName, $value = "") {
        // Normalize the element name and namespace URI per modern DOM specifications.
        if ($namespaceURI !== null) {
            $namespaceURI = trim($namespaceURI);
            $namespaceURI = ($namespaceURI === Parser::HTML_NAMESPACE) ? null : $namespaceURI;
        }
        $qualifiedName = ($namespaceURI === null) ? strtolower(trim($qualifiedName)) : trim($qualifiedName);

        try {
            if ($qualifiedName !== 'template' || $namespaceURI !== null) {
                $e = parent::createElementNS($namespaceURI, $qualifiedName, $value);
            } else {
                $e = new TemplateElement($this, $qualifiedName, $value);
                // Template elements need to have a reference kept in userland
                ElementMap::set($e);
                $e->content = $this->createDocumentFragment();
            }

            return $e;
        } catch (\DOMException $e) {
            // The element name is invalid for XML
            // Replace any offending characters with "UHHHHHH" where H are the
            //   uppercase hexadecimal digits of the character's code point
            $this->mangledElements = true;
            if ($namespaceURI !== null) {
                $qualifiedName = implode(":", array_map([$this, "coerceName"], explode(":", $qualifiedName, 2)));
            } else {
                $qualifiedName = $this->coerceName($qualifiedName);
            }
            return parent::createElementNS($namespaceURI, $qualifiedName, $value);
        }
    }

    public function createEntityReference($name): bool {
        return false;
    }

    public function load($filename, $options = null, ?string $encodingOrContentType = null): bool {
        $data = Parser::fetchFile($filename, $encodingOrContentType);
        if (!$data) {
            return false;
        }
        [$data, $encodingOrContentType] = $data;
        Parser::parse($data, $this, $encodingOrContentType, null, (string)$filename);
        return true;
    }

    public function loadHTML($source, $options = null, ?string $encodingOrContentType = null): bool {
        assert(is_string($source), new DOMException(DOMException::STRING_EXPECTED, 'source', gettype($source)));
        Parser::parse($source, $this, $encodingOrContentType);
        return true;
    }

    public function loadHTMLFile($filename, $options = null, ?string $encodingOrContentType = null): bool {
        return $this->load($filename, $options, $encodingOrContentType);
    }

    public function loadXML($source, $options = null): bool {
        return false;
    }

    public function save($filename, $options = null) {
        return file_put_contents($filename, $this->serialize());
    }

    public function saveHTML(\DOMNode $node = null): string {
        return $node->serialize($node);
    }

    public function saveHTMLFile($filename): int {
        return $this->save($filename);
    }

    public function saveXML(?\DOMNode $node = null, $options = null): bool {
        return false;
    }

    public function serialize(\DOMNode $node = null): string {
        $node = $node ?? $this;
        $formatOutput = $this->formatOutput;

        if ($node !== $this) {
            if (!$node->ownerDocument->isSameNode($this)) {
                throw new DOMException(DOMException::WRONG_DOCUMENT);
            }

            // This method is used to serialize any node. If not a Document or a
            // DocumentFragment or a DocumentType clone the node in a fragment and serialize
            // that. Otherwise, if a DocumentFragment create a new Document with a clone of
            // the DocumentFragment as its doctype and then serialize the new document.
            if (!$node instanceof Document && !$node instanceof DocumentFragment) {
                // If the node isn't an element disable output formatting
                if ($formatOutput && !$node instanceof Element) {
                    $formatOutput = false;
                }

                if (!$node instanceof \DOMDocumentType) {
                    $frag = $this->createDocumentFragment();
                    $frag->appendChild($node->cloneNode(true));
                    $node = $frag;
                } else {
                    $newDoc = new self();
                    $newDoc->appendChild($newDoc->implementation->createDocumentType($node->name, $node->publicId, $node->systemId));
                    $node = $newDoc;
                }
            }
        } elseif ($formatOutput && $node instanceof DocumentFragment) {
            // If node is a document fragment disable output formatting if the
            // DocumentFragment doesn't have any Element children.
            $formatOutput = ($node->childElementCount > 0);
        }

        return $this->serializeFragment($node, $formatOutput);
    }

    public function validate(): bool {
        return true;
    }

    public function xinclude($options = null): bool {
        return false;
    }


    protected function preInsertionValidity(\DOMNode $node, ?\DOMNode $child = null) {
        parent::preInsertionValidity($node, $child);

        # 6. If parent is a document, and any of the statements below, switched on node,
        # are true, then throw a "HierarchyRequestError" DOMException.
        #
        # DocumentFragment node
        #    If node has more than one element child or has a Text node child.
        #    Otherwise, if node has one element child and either parent has an element
        #    child, child is a doctype, or child is non-null and a doctype is following
        #    child.
        if ($node instanceof \DOMDocumentType) {
            if ($node->childNodes->length > 1 || $node->firstChild instanceof Text) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            } else {
                if ($node->firstChild instanceof \DOMDocumentType) {
                    throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                }

                foreach ($this->childNodes as $c) {
                    if ($c instanceof Element) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }

                if ($child !== null) {
                    $n = $child;
                    while ($n = $n->nextSibling) {
                        if ($n instanceof \DOMDocumentType) {
                            throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                        }
                    }
                }
            }
        }
        # element
        #    parent has an element child, child is a doctype, or child is non-null and a
        #    doctype is following child.
        elseif ($node instanceof Element) {
            if ($child instanceof \DOMDocumentType) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }

            if ($child !== null) {
                $n = $child;
                while ($n = $n->nextSibling) {
                    if ($n instanceof \DOMDocumentType) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }
            }

            foreach ($this->childNodes as $c) {
                if ($c instanceof Element) {
                    throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                }
            }
        }

        # doctype
        #    parent has a doctype child, child is non-null and an element is preceding
        #    child, or child is null and parent has an element child.
        elseif ($node instanceof \DOMDocumentType) {
            foreach ($this->childNodes as $c) {
                if ($c instanceof \DOMDocumentType) {
                    throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                }
            }

            if ($child !== null) {
                $n = $child;
                while ($n = $n->prevSibling) {
                    if ($n instanceof Element) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }
            } else {
                foreach ($this->childNodes as $c) {
                    if ($c instanceof Element) {
                        throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
                    }
                }
            }
        }
    }

    protected function serializeBlockElementFilter(\DOMNode $ignoredNode): \Closure {
        $blockElementFilter = function($n) use ($ignoredNode) {
            if (!$n->isSameNode($ignoredNode) && $n instanceof Element && $n->namespaceURI === null && (in_array($n->nodeName, self::BLOCK_ELEMENTS) || $n->walk(function($nn) {
                if ($nn instanceof Element && $nn->namespaceURI === null && in_array($nn->nodeName, self::BLOCK_ELEMENTS)) {
                    return true;
                }
            })->current() !== null)) {
                return true;
            }
        };

        return $blockElementFilter;
    }

    protected function serializeFragment(\DOMNode $node, bool $formatOutput = false): string {
        if ($formatOutput) {
            // Stores the root foreign element when parsing its descendants
            static $foreignElement = null;
            // Flag used if the root foreign element above has block element siblings
            static $foreignElementWithBlockElementSiblings = false;
            // Stores the indention level
            static $indent = 0;
            // Stores the root preformatted element when parsing its descendants
            static $preformattedElement = null;
            // Stores the previous non text node name so it can be used to check for adding
            // additional space.
            static $previousNonTextNodeSiblingName = null;
        }

        # 13.3. Serializing HTML fragments
        #
        # 1. If the node serializes as void, then return the empty string.
        if (in_array($node->nodeName, self::VOID_ELEMENTS)) {
            return '';
        }

        # 2. Let s be a string, and initialize it to the empty string.
        $s = '';

        # 3. If the node is a template element, then let the node instead be the
        # template element’s template contents (a DocumentFragment node).
        if ($node instanceof TemplateElement) {
            $node = $node->content;
        }

        $nodesLength = $node->childNodes->length;
        # 4. For each child node of the node, in tree order, run the following steps:
        ## 1. Let current node be the child node being processed.
        foreach ($node->childNodes as $currentNode) {
            $foreign = ($currentNode->namespaceURI !== null);

            if ($this->formatOutput) {
                // Filter meant to be used with DOM walker generator methods which checks if
                // elements are block or if elements are inline with block descendants
                $blockElementFilter = self::serializeBlockElementFilter($currentNode->parentNode);
            }

            # 2. Append the appropriate string from the following list to s:
            # If current node is an Element
            if ($currentNode instanceof Element) {
                # If current node is an element in the HTML namespace, the MathML namespace, or
                # the SVG namespace, then let tagname be current node's local name. Otherwise,
                # let tagname be current node's qualified name.
                $tagName = (!$foreign || $currentNode->namespaceURI === Parser::MATHML_NAMESPACE || $currentNode->namespaceURI === Parser::SVG_NAMESPACE) ? $currentNode->localName : $currentNode->nodeName;

                // Since tag names can contain characters that are invalid in PHP's XML DOM
                // uncoerce the name when printing if necessary.
                if (strpos($tagName, 'U') !== false) {
                    $tagName = $this->uncoerceName($tagName);
                }

                if ($formatOutput) {
                    $blockElementFilter = self::serializeBlockElementFilter($currentNode);
                    $hasChildNodes = ($currentNode->hasChildNodes());
                    $modify = false;

                    if (!$foreign) {
                        if ($hasChildNodes && $preformattedElement === null && in_array($tagName, self::PREFORMATTED_ELEMENTS)) {
                            $preformattedElement = $currentNode;
                        }

                        // If a block element, an inline element with block element siblings, or an
                        // inline element with block element descendants...
                        if (in_array($tagName, self::BLOCK_ELEMENTS) || $currentNode->parentNode->walkShallow($blockElementFilter)->current() !== null || ($hasChildNodes && $currentNode->walk($blockElementFilter)->current() !== null)) {
                            $modify = true;
                        }
                    } else {
                        // If a foreign element with block element siblings
                        if ($hasChildNodes && $foreignElement === null) {
                            $foreignElement = $currentNode;
                            if ($currentNode->parentNode->walkShallow($blockElementFilter)->current() !== null) {
                                $foreignElementWithBlockElementSiblings = true;
                                $modify = true;
                            }
                        }
                        // If a foreign element with a foreign element ancestor with block element
                        // siblings
                        elseif ($foreignElement !== null && $foreignElementWithBlockElementSiblings) {
                            $modify = true;
                        }
                    }

                    if ($modify) {
                        // If the previous non text node sibling doesn't have the same name as the
                        // current node and neither are h1-h6 elements then add an additional newline.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $tagName && !(in_array($previousNonTextNodeSiblingName, self::H_ELEMENTS) && in_array($tagName, self::H_ELEMENTS))) {
                            $s .= "\n";
                        }

                        $s .= "\n" . str_repeat(' ', $indent);
                    }
                }


                # Append a U+003C LESS-THAN SIGN character (<), followed by tagname.
                $s .= "<$tagName";

                # If current node's is value is not null, and the element does not have an is
                # attribute in its attribute list, then append the string " is="", followed by
                # current node's is value escaped as described below in attribute mode, followed
                # by a U+0022 QUOTATION MARK character (").
                // DEVIATION: There is no scripting support in this implementation.

                # For each attribute that the element has, append a U+0020 SPACE character,
                # the attribute’s serialized name as described below, a U+003D EQUALS SIGN
                # character (=), a U+0022 QUOTATION MARK character ("), the attribute’s value,
                # escaped as described below in attribute mode, and a second U+0022 QUOTATION
                # MARK character (").
                foreach ($currentNode->attributes as $attr) {
                    # An attribute’s serialized name for the purposes of the previous paragraph
                    # must be determined as follows:
                    switch ($attr->namespaceURI) {
                        # If the attribute has no namespace
                        case null:
                            # The attribute’s serialized name is the attribute’s local name.
                            $name = $attr->localName;
                        break;
                        # If the attribute is in the XML namespace
                        case Parser::XML_NAMESPACE:
                            # The attribute’s serialized name is the string "xml:" followed by the
                            # attribute’s local name.
                            $name = 'xml:' . $attr->localName;
                        break;
                        # If the attribute is in the XMLNS namespace...
                        case Parser::XMLNS_NAMESPACE:
                            # ...and the attribute’s local name is xmlns
                            if ($attr->localName === 'xmlns') {
                                # The attribute’s serialized name is the string "xmlns".
                                $name = 'xmlns';
                            }
                            # ... and the attribute’s local name is not xmlns
                            else {
                                # The attribute’s serialized name is the string "xmlns:" followed by the
                                # attribute’s local name.
                                $name = 'xmlns:' . $attr->localName;
                            }
                        break;
                        # If the attribute is in the XLink namespace
                        case Parser::XLINK_NAMESPACE:
                            # The attribute’s serialized name is the string "xlink:" followed by the
                            # attribute’s local name.
                            $name = 'xlink:' . $attr->localName;
                        break;
                        # If the attribute is in some other namespace
                        default:
                            # The attribute’s serialized name is the attribute’s qualified name.
                            $name = $attr->nodeName;
                    }
                    // undo any name mangling
                    if (strpos($name, 'U') !== false) {
                        $name = $this->uncoerceName($name);
                    }
                    $value = $this->escapeString($attr->value, true);
                    $s .= " $name=\"$value\"";
                }

                # While the exact order of attributes is UA-defined, and may depend on factors
                # such as the order that the attributes were given in the original markup, the
                # sort order must be stable, such that consecutive invocations of this
                # algorithm serialize an element’s attributes in the same order.
                // Okay.

                // When formatting output set the previous non text node sibling name to the
                // current node name so void elements and empty foreign elements will be
                // recognized by their next sibling.
                if ($formatOutput) {
                    $previousNonTextNodeSiblingName = $tagName;
                }

                # Append a U+003E GREATER-THAN SIGN character (>).
                // DEVIATION: Printing XML-based content such as SVG as if it's HTML might be
                // practical when a browser is serializing, but it's not in this library's
                // usage. So, if the element is foreign and doesn't contain any children close
                // the element instead and continue on to the next child node.
                $hasChildNodes = $currentNode->hasChildNodes();
                if (!$foreign || $hasChildNodes) {
                    $s .= '>';
                } elseif (!$hasChildNodes) {
                    $s .= '/>';
                    continue;
                }

                # If current node serializes as void, then continue on to the next child node at
                # this point.
                if (in_array($currentNode->nodeName, self::VOID_ELEMENTS)) {
                    continue;
                }

                if ($formatOutput) {
                    // If formatting output set the previous non text node sibling to null before
                    // serializing children.
                    $previousNonTextNodeSiblingName = null;

                    // If formatting output and the element has already been modified increment the
                    // indention level
                    if ($modify) {
                        $indent++;
                    }
                }

                # Append the value of running the HTML fragment serialization algorithm on the
                # current node element (thus recursing into this algorithm for that element),
                # followed by a U+003C LESS-THAN SIGN character (<), a U+002F SOLIDUS character (/),
                # tagname again, and finally a U+003E GREATER-THAN SIGN character (>).
                $s .= $this->serializeFragment($currentNode, $formatOutput);

                if ($formatOutput) {
                    if ($modify) {
                        // Decrement the indention level.
                        $indent--;

                        if ($preformattedElement === null) {
                            // If a foreign element with a foreign element ancestor with block element
                            // siblings and has at least one element child or any element with a block
                            // element descendant...
                            if (($foreign && $foreignElementWithBlockElementSiblings && $currentNode->firstElementChild !== null) || ($currentNode->walk($blockElementFilter)->current() !== null)) {
                                $s .= "\n" . str_repeat(' ', $indent);
                            }
                        }
                    }

                    if ($foreignElement !== null && $currentNode->isSameNode($foreignElement)) {
                        $foreignElement = null;
                        $foreignElementWithBlockElementSiblings = false;
                    } elseif ($preformattedElement !== null && $currentNode->isSameNode($preformattedElement)) {
                        $preformattedElement = null;
                    }

                    // Set the previous text node sibling name to the current node's name so it may
                    // be recognized by the following sibling.
                    $previousNonTextNodeSiblingName = $tagName;
                }

                $s .= "</$tagName>";
            }
            # If current node is a Text node
            elseif ($currentNode instanceof Text) {
                $text = $currentNode->data;

                # If the parent of current node is a style, script, xmp, iframe, noembed,
                # noframes, or plaintext element, or if the parent of current node is a noscript
                # element and scripting is enabled for the node, then append the value of
                # current node’s data IDL attribute literally.
                if ($currentNode->parentNode->namespaceURI === null && in_array($currentNode->parentNode->nodeName, [ 'style', 'script', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext' ])) {
                    $s .= $text;
                }
                # Otherwise, append the value of current node’s data IDL attribute, escaped as
                # described below.
                else {
                    if ($formatOutput) {
                        if ($preformattedElement === null) {
                            // Condense spaces and tabs into a single space.
                            $text = preg_replace('/ +/', ' ', str_replace("\t", '    ', $text));
                            if ($foreignElementWithBlockElementSiblings || $currentNode->parentNode->walk($blockElementFilter)->current() !== null) {
                                // If the text node's data is made up of only whitespace characters continue
                                // onto the next node
                                if (strspn($text, Data::WHITESPACE) === strlen($text)) {
                                    continue;
                                }

                                // Otherwise, remove newlines from the text node's data; if that causes the data
                                // to be empty then continue onto the next node.
                                $text = preg_replace('/[\n\x0C\x0D]+/', '', $text);
                                if ($text === '') {
                                    continue;
                                }
                            }
                        }
                    }

                    $s .= $this->escapeString($text);
                }
            }
            # If current node is a Comment
            elseif ($currentNode instanceof Comment) {
                if ($formatOutput) {
                    if ($preformattedElement === null && $foreignElementWithBlockElementSiblings || $currentNode->parentNode->walk($blockElementFilter)->current() !== null) {
                        // Add an additional newline if the previous sibling wasn't a comment.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $this->nodeName) {
                            $s .= "\n";
                        }

                        $s .= "\n" . str_repeat(' ', $indent);
                    }

                    $previousNonTextNodeSiblingName = $this->nodeName;
                }

                # Append the literal string "<!--" (U+003C LESS-THAN SIGN, U+0021 EXCLAMATION
                # MARK, U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS), followed by the value of
                # current node’s data IDL attribute, followed by the literal string "-->"
                # (U+002D HYPHEN-MINUS, U+002D HYPHEN-MINUS, U+003E GREATER-THAN SIGN).
                $s .= "<!--{$currentNode->data}-->";
            }
            # If current node is a ProcessingInstruction
            elseif ($currentNode instanceof ProcessingInstruction) {
                if ($formatOutput) {
                    if ($preformattedElement === null && $foreignElementWithBlockElementSiblings || $currentNode->parentNode->walk($blockElementFilter)->current() !== null) {
                        // Add an additional newline if the previous sibling wasn't a processing
                        // instruction.
                        if ($previousNonTextNodeSiblingName !== null && $previousNonTextNodeSiblingName !== $this->nodeName) {
                            $s .= "\n";
                        }

                        $s .= "\n" . str_repeat(' ', $indent);
                    }

                    $previousNonTextNodeSiblingName = $this->nodeName;
                }

                # Append the literal string "<?" (U+003C LESS-THAN SIGN, U+003F QUESTION MARK),
                # followed by the value of current node’s target IDL attribute, followed by a
                # single U+0020 SPACE character, followed by the value of current node’s data
                # IDL attribute, followed by a single U+003E GREATER-THAN SIGN character (>).
                $s .= "<?{$currentNode->target} {$currentNode->data}>";
            }
            # If current node is a DocumentFragment
            elseif ($currentNode instanceof \DOMDocumentType) {
                # Append the literal string "<!DOCTYPE" (U+003C LESS-THAN SIGN, U+0021
                # EXCLAMATION MARK, U+0044 LATIN CAPITAL LETTER D, U+004F LATIN CAPITAL LETTER
                # O, U+0043 LATIN CAPITAL LETTER C, U+0054 LATIN CAPITAL LETTER T, U+0059
                # LATIN CAPITAL LETTER Y, U+0050 LATIN CAPITAL LETTER P, U+0045 LATIN CAPITAL
                # LETTER E), followed by a space (U+0020 SPACE), followed by the value of
                # current node's name IDL attribute, followed by the literal string ">" (U+003E
                # GREATER-THAN SIGN).
                // DEVIATION: The name is trimmed because PHP's DOM does not
                //   accept the empty string as a DOCTYPE name
                $name = trim($node->childNodes->item(0)->name, ' ');
                $s .= "<!DOCTYPE $name>";
            }
        }

        # 5. Return s.
        return $s;
    }


    public function __get(string $prop) {
        $value = parent::__get($prop);
        if ($value !== null) {
            return $value;
        }

        if ($prop === 'body') {
            if ($this->documentElement === null || $this->documentElement->childNodes->length === 0) {
                return null;
            }

            $body = null;

            # The body element of a document is the first of the html element's children
            # that is either a body element or a frameset element, or null if there is no
            # such element.
            $n = $this->documentElement->firstChild;
            do {
                if ($n instanceof Element && $n->namespaceURI === null && ($n->nodeName === 'body' || $n->nodeName === 'frameset')) {
                    $body = $n;
                    break;
                }
            } while ($n = $n->nextSibling);

            if ($body !== null) {
                // References are handled weirdly by PHP's DOM. Return a stored body element
                // unless it is changed so operations (like classList) can be done without
                // losing the reference.
                if ($body !== $this->_body) {
                    $this->_body = $body;
                }

                return $this->_body;
            }

            $this->_body = null;
            return null;
        }
    }

    public function __set(string $prop, $value) {
        if ($prop === 'body') {
            # On setting, the following algorithm must be run:
            #
            # 1. If the new value is not a body or frameset element, then throw a
            # "HierarchyRequestError" DOMException.
            if (!$value instanceof Element || $value->namespaceURI !== null) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }
            if ($value->nodeName !== 'body' && $value->nodeName !== 'frameset') {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }

            if ($this->_body !== null) {
                # 2. Otherwise, if the new value is the same as the body element, return.
                if ($value->isSameNode($this->_body)) {
                    return;
                }

                # 3. Otherwise, if the body element is not null, then replace the body element
                # with the new value within the body element's parent and return.
                $this->documentElement->replaceChild($value, $this->_body);
                $this->_body = $value;
                return;
            }

            # 4. Otherwise, if there is no document element, throw a "HierarchyRequestError"
            # DOMException.
            if ($this->documentElement === null) {
                throw new DOMException(DOMException::HIERARCHY_REQUEST_ERROR);
            }

            # 5. Otherwise, the body element is null, but there's a document element. Append
            # the new value to the document element.
            $this->documentElement->appendChild($value);
            $this->_body = $value;
        }
    }

    public function __toString() {
        return $this->serialize();
    }
}
