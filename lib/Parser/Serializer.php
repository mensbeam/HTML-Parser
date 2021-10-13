<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\HTML\Parser;

abstract class Serializer {
    protected const VOID_ELEMENTS = ["basefont", "bgsound", "frame", "keygen", "area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"];

    public function seerializeOuter(\DOMNode $node): string {
        $s = "";
        $depth = 0;
        $n = $node;
        do {
            # If current node is an Element
            if ($n instanceof \DOMElement) {
                # If current node is an element in the HTML namespace,
                #   the MathML namespace, or the SVG namespace, then let
                #   tagname be current node's local name. Otherwise, let
                #   tagname be current node's qualified name.
                if (in_array($n->namespaceURI ?? Parser::HTML_NAMESPACE, [Parser::HTML_NAMESPACE, Parser::SVG_NAMESPACE, Parser::MATHML_NAMESPACE])) {
                    $tagName = $n->localName;
                } else {
                    $tagName = $n->tagName;
                }
            }
        } while (false);
        return $s;
    }
}
