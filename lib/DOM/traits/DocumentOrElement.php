<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML;

// This exists because the DOM spec for some stupid reason doesn't give
// DocumentFragment some methods.
trait DocumentOrElement {
    public function getElementsByClassName(string $classNames): \DOMNodeList {
        # The list of elements with class names classNames for a node root is the
        # HTMLCollection returned by the following algorithm:
        // DEVIATION: There's no HTMLCollection. The result will be a DOMNodeList
        // instead. It is, fortunately, almost exactly the same thing anyway.

        # 1. Let classes be the result of running the ordered set parser on classNames.
        #
        ## The ordered set parser takes a string input and then runs these steps:
        ##
        ## 1. Let inputTokens be the result of splitting input on ASCII whitespace.
        // There isn't a Set object in php, so make sure all the tokens are unique.
        $inputTokens = ($classNames !== '') ? array_unique(preg_split(Data::WHITESPACE_REGEX, $classNames)) : [];

        $isDocument = ($this instanceof Document);
        $document = ($isDocument) ? $this : $this->ownerDocument;

        ## 2. Let tokens be a new ordered set.
        ## 3. For each token in inputTokens, append token to tokens.
        ## 4. Return tokens.
        // There isn't a Set object in php, so just use the uniqued input tokens.

        # 2. If classes is the empty set, return an empty HTMLCollection.
        // DEVIATION: We can't do that, so let's create a bogus Xpath query instead.
        if ($inputTokens === []) {
            $ook = $document->createElement('ook');
            $query = $document->xpath->query('//eek', $ook);
            unset($ook);
            return $query;
        }

        # 3. Return a HTMLCollection rooted at root, whose filter matches descendant
        # elements that have all their classes in classes.
        #
        # The comparisons for the classes must be done in an ASCII case-insensitive manner
        # if root’s node document’s mode is "quirks"; otherwise in an identical to manner.
        // DEVIATION: Since we can't just create a \DOMNodeList we must instead query the document with XPath with the root element to get a list.

        $query = '//*';
        foreach ($inputTokens as $token) {
            $query .= "[@class=\"$token\"]";
        }

        return ($isDocument) ? $document->xpath->query($query) : $document->xpath->query($query, $this);
    }
}