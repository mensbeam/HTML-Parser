<?php
declare(strict_types=1);
namespace dW\HTML5;

class TreeBuilder {
    // The list of active formatting elements, used when elements are improperly nested
    protected $activeFormattingElementsList;
    // The DOMDocument that is assembled by this class
    protected $DOM;
    // The form element pointer points to the last form element that was opened and
    // whose end tag has not yet been seen. It is used to make form controls associate
    // with forms in the face of dramatically bad markup, for historical reasons. It is
    // ignored inside template elements
    protected $formElement;
    // Flag for determining whether to use the foster parenting (badly nested table
    // elements) algorithm.
    protected $fosterParenting = false;
    // Flag that shows whether the content that's being parsed is a fragment or not
    protected $fragmentCase;
    // Context element for fragments
    protected $fragmentContext;
    // Flag used to determine whether elements are okay to be used in framesets or not
    protected $framesetOk = true;
    // Once a head element has been parsed (whether implicitly or explicitly) the head
    // element pointer gets set to point to this node
    protected $headElement;
    // Treebuilder insertion mode
    protected $insertionMode;
    // When the insertion mode is switched to "text" or "in table text", the
    // original insertion mode is also set. This is the insertion mode to which the
    // tree construction stage will return.
    protected $originalInsertionMode;
    // The stack of open elements, uses Stack
    protected $stack;
    // Instance of the Tokenizer class used for creating tokens
    protected $tokenizer;
    // Used to check if the document is in quirks mode
    protected $quirksMode;


    // Instance used with the static token insertion methods.
    protected static $instance;


    // Constants used for insertion modes
    const INITIAL_MODE = 0;
    const BEFORE_HTML_MODE = 1;
    const BEFORE_HEAD_MODE = 2;
    const IN_HEAD_MODE = 3;
    const IN_HEAD_NOSCRIPT_MODE = 4;
    const AFTER_HEAD_MODE = 5;
    const IN_BODY_MODE = 6;
    const TEXT_MODE = 7;
    const IN_TABLE_MODE = 8;
    const IN_TABLE_TEXT_MODE = 9;
    const IN_CAPTION_MODE = 10;
    const IN_COLUMN_GROUP_MODE = 11;
    const IN_TABLE_BODY_MODE = 12;
    const IN_ROW_MODE = 13;
    const IN_CELL_MODE = 14;
    const IN_SELECT_MODE = 15;
    const IN_SELECT_IN_TABLE_MODE = 16;
    const IN_TEMPLATE_MODE = 17;
    const AFTER_BODY_MODE = 18;
    const IN_FRAMESET_MODE = 19;
    const AFTER_FRAMESET_MODE = 20;
    const AFTER_AFTER_BODY_MODE = 21;
    const AFTER_AFTER_FRAMESET_MODE = 22;

    // Quirks mode constants
    const QUIRKS_MODE_OFF = 0;
    const QUIRKS_MODE_ON = 1;
    const QUIRKS_MODE_LIMITED = 2;


    public function __construct(\DOMDocument $dom, $formElement, bool $fragmentCase = false, $fragmentContext = null, Stack $stack, Tokenizer $tokenizer) {
        // If the form element isn't an instance of DOMElement that has a node name of
        // "form" or null then there's a problem.
        if (!is_null($formElement) && !($formElement instanceof DOMElement && $formElement->nodeName === 'form')) {
            throw new Exception(Exception::TREEBUILDER_FORMELEMENT_EXPECTED, gettype($formElement));
        }

        // If the fragment context is not null and is not a document fragment, document,
        // or element then we have a problem. Additionally, if the parser is created for
        // parsing a fragment and the fragment context is null then we have a problem,
        // too.
        if ((!is_null($fragmentContext) && !$fragmentContext instanceof DOMDocumentFragment && !$fragmentContext instanceof DOMDocument && !$fragmentContext instanceof DOMElement) ||
            (is_null($fragmentContext) && $fragmentCase)) {
            throw new Exception(Exception::TREEBUILDER_FRAGMENT_CONTEXT_DOMELEMENT_DOMDOCUMENT_DOMDOCUMENTFRAG_EXPECTED, gettype($fragmentContext));
        }

        $this->DOM = $dom;
        $this->formElement = $formElement;
        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
        $this->stack = $stack;
        $this->tokenizer = $tokenizer;

        // Initialize the list of active formatting elements.
        $this->activeFormattingElementsList = new ActiveFormattingElementsList($stack);

        $this->insertionMode = self::INITIAL_MODE;
        $this->quirksMode = self::QUIRKS_MODE_OFF;

        static::$instance = $this;
    }

    public function __destruct() {
        static::$instance = null;
    }

    public function emitToken(Token $token) {
        // Loop used for reprocessing.
        while (true) {
            $adjustedCurrentNode = $this->stack->adjustedCurrentNode;
            $adjustedCurrentNodeName = $this->stack->adjustedCurrentNodeName;
            $adjustedCurrentNodeNamespace = $this->stack->adjustedCurrentNodeNamespace;

            # 8.2.5 Tree construction
            #
            # As each token is emitted from the tokenizer, the user agent must follow the
            # appropriate steps from the following list, known as the tree construction dispatcher:
            #
            # If the stack of open elements is empty
            if ($this->stack->length === 0 ||
                # If the adjusted current node is an element in the HTML namespace
                $adjustedCurrentNodeNamespace === Parser::HTML_NAMESPACE || (
                        # If the adjusted current node is a MathML text integration point and the token is a
                        # start tag whose tag name is neither "mglyph" nor "malignmark"
                        # If the adjusted current node is a MathML text integration point and the token is a
                        # character token
                        DOM::isMathMLTextIntegrationPoint($adjustedCurrentNode) && ((
                                $token instanceof StartTagToken && (
                                    $token->name !== 'mglyph' && $token->name !== 'malignmark'
                                ) ||
                                $token instanceof CharacterToken
                            )
                        )
                    ) || (
                        # If the adjusted current node is an annotation-xml element in the MathML namespace and
                        # the token is a start tag whose tag name is "svg"
                        $adjustedCurrentNodeNamespace === Parser::MATHML_NAMESPACE &&
                        $adjustedCurrentNodeName === 'annotation-xml' &&
                        $token instanceof StartTagToken &&
                        $token->name === 'svg'
                    ) || (
                        # If the adjusted current node is an HTML integration point and the token is a start tag
                        # If the adjusted current node is an HTML integration point and the token is a character
                        # token
                        DOM::isHTMLIntegrationPoint($adjustedCurrentNode) && (
                            $token instanceof StartTagToken || $token instanceof CharacterToken
                        )
                    ) ||
                    # If the token is an end-of-file token
                    $token instanceof EOFToken) {
                # Process the token according to the rules given in the section corresponding to
                # the current insertion mode in HTML content.
                // Returns false when needing to reprocess.
                if ($this->parseTokenInHTMLContent($token) === false) {
                    continue;
                }
            }
            # Otherwise
            else {
                # Process the token according to the rules given in the section for parsing
                # tokens in foreign content.
                // Returns false when needing to reprocess.
                if ($this->parseTokenInForeignContent($token) === false) {
                    continue;
                }
            }

            # TEMPORARY
            var_export($token);
            echo "\n\n";

            break;
        }
    }

    protected function parseTokenInHTMLContent(Token $token, int $insertionMode = null) {
        $insertionMode = (is_null($insertionMode)) ? $this->insertionMode : $insertionMode;

        // Loop used when processing the token under different rules; always breaks.
        while (true) {
            # 8.2.5.4. The rules for parsing tokens in HTML content
            switch ($insertionMode) {
                # 8.2.5.4.1. The "initial" insertion mode
                case self::INITIAL_MODE:
                    # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
                    # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                    // OPTIMIZATION: Will check for multiple space characters at once as character
                    // tokens can contain more than one character.
                    if ($token instanceof CharacterToken && (strspn($token->data, "\t\n\x0c\x0d ") !== strlen($token->data))) {
                        # Ignore the token.
                        return;
                    }
                    # A comment token
                    elseif ($token instanceof CommentToken) {
                        # Insert a comment as the last child of the Document object.
                        // DEVIATION: PHP's DOM cannot have comments before the DOCTYPE, so just going
                        // to ignore them instead.
                        //$this->insertCommentToken($token, $this->$DOM);
                        return;
                    }
                    # A DOCTYPE token
                    elseif ($token instanceof DOCTYPEToken) {
                        # If the DOCTYPE token’s name is not a case-sensitive match for the string
                        # "html", or the token’s public identifier is not missing, or the token’s system
                        # identifier is neither missing nor a case-sensitive match for the string
                        # "about:legacy-compat", then there is a parse error.
                        if ($token->name !== 'html' || $token->public !== '' || ($token->system !== '' && $token->system !== 'about:legacy-compat')) {
                            ParseError::trigger(ParseError::INVALID_DOCTYPE);
                        }

                        # Append a DocumentType node to the Document node, with the name attribute set
                        # to the name given in the DOCTYPE token, or the empty string if the name was
                        # missing; the publicId attribute set to the public identifier given in the
                        # DOCTYPE token, or the empty string if the public identifier was missing; the
                        # systemId attribute set to the system identifier given in the DOCTYPE token, or
                        # the empty string if the system identifier was missing; and the other
                        # attributes specific to DocumentType objects set to null and empty lists as
                        # appropriate. Associate the DocumentType node with the Document object so that
                        # it is returned as the value of the doctype attribute of the Document object.
                        // PHP's DOM cannot just append a DOCTYPE node to the document, so a document is
                        // created with the specified DOCTYPE instead.
                        $imp = new \DOMImplementation();
                        // DEVIATION: PHP's DOMImplementation::createDocumentType() method cannot accept
                        // an empty name, so if it is missing it is replaced with 'html' instead.
                        $this->DOM = $imp->createDocument('', '', $imp->createDocumentType((!is_null($token->name)) ? $token->name : 'html', $token->public, $token->system));

                        $public = strtolower($token->public);

                        # Then, if the document is not an iframe srcdoc document, and the DOCTYPE token
                        # matches one of the conditions in the following list, then set the Document to
                        # quirks mode:
                        // DEVIATION: This implementation does not render, so there is no nested
                        // browsing contexts to consider.
                        if ($token->forceQuirks === true || $token->name !== 'html' ||
                            $public === '-//w3o//dtd w3 html strict 3.0//en//' ||
                            $public === '-/w3c/dtd html 4.0 transitional/en' ||
                            $public === 'html' ||
                            strtolower($token->system) === 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' ||
                            strpos($public, '+//silmaril//dtd html pro v0r11 19970101//') === 0 ||
                            strpos($public, '-//as//dtd html 3.0 aswedit + extensions//') === 0 ||
                            strpos($public, '+//silmaril//dtd html pro v0r11 19970101//') === 0 ||
                            strpos($public, '-//as//dtd html 3.0 aswedit + extensions//') === 0 ||
                            strpos($public, '-//advasoft ltd//dtd html 3.0 aswedit + extensions//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0 level 1//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0 level 2//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0 strict level 1//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0 strict level 2//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0 strict//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.0//') === 0 ||
                            strpos($public, '-//ietf//dtd html 2.1e//') === 0 ||
                            strpos($public, '-//ietf//dtd html 3.0//') === 0 ||
                            strpos($public, '-//ietf//dtd html 3.2 final//') === 0 ||
                            strpos($public, '-//ietf//dtd html 3.2//') === 0 ||
                            strpos($public, '-//ietf//dtd html 3//') === 0 ||
                            strpos($public, '-//ietf//dtd html level 0//') === 0 ||
                            strpos($public, '-//ietf//dtd html level 1//') === 0 ||
                            strpos($public, '-//ietf//dtd html level 2//') === 0 ||
                            strpos($public, '-//ietf//dtd html level 3//') === 0 ||
                            strpos($public, '-//ietf//dtd html strict level 0//') === 0 ||
                            strpos($public, '-//ietf//dtd html strict level 1//') === 0 ||
                            strpos($public, '-//ietf//dtd html strict level 2//') === 0 ||
                            strpos($public, '-//ietf//dtd html strict level 3//') === 0 ||
                            strpos($public, '-//ietf//dtd html strict//') === 0 ||
                            strpos($public, '-//ietf//dtd html//') === 0 ||
                            strpos($public, '-//metrius//dtd metrius presentational//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 2.0 html strict//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 2.0 html//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 2.0 tables//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 3.0 html strict//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 3.0 html//') === 0 ||
                            strpos($public, '-//microsoft//dtd internet explorer 3.0 tables//') === 0 ||
                            strpos($public, '-//netscape comm. corp.//dtd html//') === 0 ||
                            strpos($public, '-//netscape comm. corp.//dtd strict html//') === 0 ||
                            strpos($public, '-//o\'reilly and associates//dtd html 2.0//') === 0 ||
                            strpos($public, '-//o\'reilly and associates//dtd html extended 1.0//') === 0 ||
                            strpos($public, '-//o\'reilly and associates//dtd html extended relaxed 1.0//') === 0 ||
                            strpos($public, '-//sq//dtd html 2.0 hotmetal + extensions//') === 0 ||
                            strpos($public, '-//softquad software//dtd hotmetal pro 6.0::19990601::extensions to html 4.0//') === 0 ||
                            strpos($public, '-//softquad//dtd hotmetal pro 4.0::19971010::extensions to html 4.0//') === 0 ||
                            strpos($public, '-//spyglass//dtd html 2.0 extended//') === 0 ||
                            strpos($public, '-//sun microsystems corp.//dtd hotjava html//') === 0 ||
                            strpos($public, '-//sun microsystems corp.//dtd hotjava strict html//') === 0 ||
                            strpos($public, '-//w3c//dtd html 3 1995-03-24//') === 0 ||
                            strpos($public, '-//w3c//dtd html 3.2 draft//') === 0 ||
                            strpos($public, '-//w3c//dtd html 3.2 final//') === 0 ||
                            strpos($public, '-//w3c//dtd html 3.2//') === 0 ||
                            strpos($public, '-//w3c//dtd html 3.2s draft//') === 0 ||
                            strpos($public, '-//w3c//dtd html 4.0 frameset//') === 0 ||
                            strpos($public, '-//w3c//dtd html 4.0 transitional//') === 0 ||
                            strpos($public, '-//w3c//dtd html experimental 19960712//') === 0 ||
                            strpos($public, '-//w3c//dtd html experimental 970421//') === 0 ||
                            strpos($public, '-//w3c//dtd w3 html//') === 0 ||
                            strpos($public, '-//w3o//dtd w3 html 3.0//') === 0 ||
                            strpos($public, '-//webtechs//dtd mozilla html 2.0//') === 0 ||
                            strpos($public, '-//webtechs//dtd mozilla html//') === 0 ||
                            (is_null($token->system) &&
                                (strpos($public, '-//w3c//dtd html 4.01 frameset//') === 0 ||
                                 strpos($public, '-//w3c//dtd html 4.01 transitional//') === 0))) {
                            $this->quirksMode = self::QUIRKS_MODE_ON;
                        }
                        # Otherwise, if the document is not an iframe srcdoc document, and the DOCTYPE
                        # token matches one of the conditions in the following list, then set the
                        # Document to limited-quirks mode:
                        // DEVIATION: There is no iframe srcdoc document because there are no nested
                        // browsing contexts in this implementation.
                        else {
                            if (strpos($public, '-//w3c//dtd xhtml 1.0 frameset//') === 0 ||
                                strpos($public, '-//w3c//dtd xhtml 1.0 transitional//') === 0 ||
                                (!is_null($token->system) &&
                                    (strpos($public, '-//w3c//dtd html 4.01 frameset//') === 0 ||
                                     strpos($public, '-//w3c//dtd html 4.01 transitional//') === 0))) {
                                    $this->quirksMode = self::QUIRKS_MODE_LIMITED;
                                }
                        }

                        # The system identifier and public identifier strings must be compared to the
                        # values given in the lists above in an ASCII case-insensitive manner. A system
                        # identifier whose value is the empty string is not considered missing for the
                        # purposes of the conditions above.

                        # Then, switch the insertion mode to "before html".
                        $this->insertionMode = self::BEFORE_HTML_MODE;
                    }
                    # Anything else
                    else {
                        # If the document is not an iframe srcdoc document, then this is a parse error;
                        # set the Document to quirks mode.
                        // DEVIATION: There is no iframe srcdoc document because there are no nested
                        // browsing contexts in this implementation.
                        $this->quirksMode = self::QUIRKS_MODE_ON;

                        # In any case, switch the insertion mode to "before html", then reprocess the
                        # token.
                        $this->insertionMode = self::BEFORE_HTML_MODE;
                        continue;
                    }
                break;

                # 8.2.5.4.2. The "before html" insertion mode
                case self::BEFORE_HTML_MODE:
                    # A DOCTYPE token
                    if ($token instanceof DOCTYPEToken) {
                        ParseError::trigger(ParseError::UNEXPECTED_DOCTYPE, '');
                    }
                    # A comment token
                    elseif ($token instanceof CommentToken) {
                        # Insert a comment as the last child of the Document object.
                        $this->insertCommentToken($token, $this->$DOM);
                    }
                    # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
                    # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                    // OPTIMIZATION: Will check for multiple space characters at once as character
                    // tokens can contain more than one character.
                    elseif ($token instanceof CharacterToken && (strspn($token->data, "\t\n\x0c\x0d ") !== strlen($token->data))) {
                        # Ignore the token.
                        return;
                    }
                    # A start tag whose tag name is "html"
                    elseif ($token instanceof StartTagToken && $token->name === 'html') {
                        # Create an element for the token in the HTML namespace, with the Document as
                        # the intended parent. Append it to the Document object. Put this element in the
                        # stack of open elements.
                        $element = static::insertStartTagToken($token, $this->DOM);

                        # Switch the insertion mode to "before head".
                        $this->insertionMode = self::BEFORE_HEAD_MODE;
                    }
                    # Any other end tag
                    elseif ($token instanceof EndTagToken && $token->name !== 'head' && $token->name !== 'body' && $token->name !== 'html' && $token->name !== 'br') {
                        # Parse error.
                        ParseError::trigger(ParseError::UNEXPECTED_END_TAG, $token->name, 'head, body, html, br');
                    }
                    # An end tag whose tag name is one of: "head", "body", "html", "br"
                    # Anything else
                    else {
                        # Create an html element whose node document is the Document object. Append it
                        # to the Document object. Put this element in the stack of open elements.
                        $element = $this->DOM->createElement('html');
                        $this->DOM->appendChild($element);
                        $this->stack[] = $element;

                        # Switch the insertion mode to "before head", then reprocess the token.
                        $this->insertionMode = self::BEFORE_HEAD_MODE;
                        continue;
                    }

                    # The document element can end up being removed from the Document object, e.g.,
                    # by scripts; nothing in particular happens in such cases, content continues
                    # being appended to the nodes as described in the next section.
                    // Good to know. There's no scripting in this implementation, though.
                break;

                # 8.2.5.4.3. The "before head" insertion mode
                case self::BEFORE_HEAD_MODE:
                    # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
                    # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                    if ($token instanceof CharacterToken && (strspn($token->data, "\t\n\x0c\x0d ") !== strlen($token->data))) {
                        # Ignore the token.
                        return;
                    }
                    # A comment token
                    elseif ($token instanceof CommentToken) {
                        $this->insertCommentToken($token);
                    }
                    # A DOCTYPE token
                    elseif ($token instanceof DOCTYPEToken) {
                        # Parse error.
                        ParseError::trigger(ParseError::UNEXPECTED_DOCTYPE, 'head tag');
                    }
                    elseif ($token instanceof StartTagToken) {
                        # A start tag whose tag name is "html"
                        if ($token->name === 'html') {
                            # Process the token using the rules for the "in body" insertion mode.
                            $insertionMode = self::IN_BODY_MODE;
                            continue 2;
                        }
                        # A start tag whose tag name is "head"
                        elseif ($token->name === 'head') {
                            # Insert an HTML element for the token.
                            $element = static::insertStartTagToken($token);
                            # Set the head element pointer to the newly created head element.
                            $this->headElement = $element;

                            # Switch the insertion mode to "in head".
                            $this->insertionMode = self::IN_HEAD_MODE;
                        }
                    }
                    # Any other end tag
                    elseif ($token instanceof EndTagToken && $token->name !== 'head' && $token->name !== 'body' && $token->name !== 'html' && $token->name === 'br') {
                        # Parse error.
                        ParseError::trigger(ParseError::UNEXPECTED_END_TAG, $token->name, 'head, body, html, br');
                    }
                    # An end tag whose tag name is one of: "head", "body", "html", "br"
                    # Anything else
                    else {
                        # Insert an HTML element for a "head" start tag token with no attributes.
                        $element = static::insertStartTagToken(new StartTagToken('head'));
                        # Set the head element pointer to the newly created head element.
                        $this->headElement = $element;

                        # Switch the insertion mode to "in head".
                        $this->insertionMode = self::IN_HEAD_MODE;

                        # Reprocess the current token.
                        continue;
                    }
                break;

                # 8.2.5.4.4. The "in head" insertion mode
                case self::IN_HEAD_MODE:
                    # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
                    # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                    if ($token instanceof CharacterToken && (strspn($token->data, "\t\n\x0c\x0d ") !== strlen($token->data))) {
                        # Insert the character.
                        $this->insertCharacterToken($token);
                    }
                    # A comment token
                    elseif ($token instanceof CommentToken) {
                        # Insert a comment.
                        $this->insertCommentToken($token);
                    }
                    # A DOCTYPE token
                    elseif ($token instanceof DOCTYPEToken) {
                        # Parse error.
                        ParseError::trigger(ParseError::UNEXPECTED_DOCTYPE, 'head data');
                    }
                    elseif ($token instanceof StartTagToken) {
                        # A start tag whose tag name is "html"
                        if ($token->name === 'html') {
                            # Process the token using the rules for the "in body" insertion mode.
                            $insertionMode = self::IN_BODY_MODE;
                            continue 2;
                        }
                        # A start tag whose tag name is one of: "base", "basefont", "bgsound", "link"
                        elseif ($token->name === 'base' || $token->name === 'basefont' || $token->name === 'bgsound' || $token->name === 'link') {
                            # Insert an HTML element for the token. Immediately pop the current node off the
                            # stack of open elements.
                            static::insertStartTagToken($token);
                            $this->stack->pop();

                            # Acknowledge the token’s *self-closing flag*, if it is set.
                            // Acknowledged.
                        }
                        # A start tag whose tag name is "meta"
                        elseif ($token->name === 'meta') {
                            # Insert an HTML element for the token. Immediately pop the current node off the
                            # stack of open elements.
                            static::insertStartTagToken($token);
                            $this->stack->pop();

                            # Acknowledge the token’s *self-closing flag*, if it is set.
                            // Acknowledged.

                            # If the element has a charset attribute, and getting an encoding from its value
                            # results in an encoding, and the confidence is currently tentative, then change
                            # the encoding to the resulting encoding.
                            #
                            # Otherwise, if the element has an http-equiv attribute whose value is an ASCII
                            # case-insensitive match for the string "Content-Type", and the element has a
                            # content attribute, and applying the algorithm for extracting a character
                            # encoding from a meta element to that attribute’s value returns an encoding,
                            # and the confidence is currently tentative, then change the encoding to the
                            # extracted encoding.
                            // DEVIATION: FIXME: This implementation currently only supports UTF-8.
                        }
                        # A start tag whose tag name is "title"
                        elseif ($token->name === 'title') {
                            # Follow the generic RCDATA element parsing algorithm.
                            $this->parseGenericRCDATA();
                        }
                        # A start tag whose tag name is "noscript", if the scripting flag is enabled
                        # A start tag whose tag name is one of: "noframes", "style"
                        // DEVIATION: There is no scripting in this implementation, so the scripting
                        // flag is always disabled.
                        elseif ($token->name === 'noframes' || $token->name === 'style') {
                            # Follow the generic raw text element parsing algorithm.
                            $this->parseGenericRawText();
                        }
                        # A start tag whose tag name is "noscript", if the scripting flag is disabled
                        // DEVIATION: There is no scripting in this implementation, so the scripting
                        // flag is always disabled.
                        elseif ($token->name === 'noscript') {
                            # Insert an HTML element for the token.
                            static::insertStartTagToken($token);
                            # Switch the insertion mode to "in head noscript".
                            $this->insertionMode = self::IN_HEAD_NOSCRIPT_MODE;
                        }
                        # A start tag whose tag name is "script"
                        elseif ($token->name === 'script') {
                            # Run these steps:

                            # 1. Let the adjusted insertion location be the appropriate place for inserting
                            # a node.
                            # 2. Create an element for the token in the HTML namespace, with the intended
                            # parent being the element in which the adjusted insertion location finds
                            # itself.
                            // DEVIATION: Because there is no scripting in this implementation, there is no
                            // need to get the adjusted insertion location as the intended parent as the
                            // intended parent isn't used when determining anything;
                            // Parser::createAndInsertElement will get the adjusted insertion location
                            // anyway.
                            static::insertStartTagToken($token);

                            # 3. Mark the element as being "parser-inserted" and unset the element’s
                            # "non-blocking" flag.
                            # 4. Mark the element as being "parser-inserted" and unset the element’s
                            # "non-blocking" flag.
                            // DEVIATION: No scripting.
                            # 5. Insert the newly created element at the adjusted insertion location.
                            // Done.
                            # 6. Push the element onto the stack of open elements so that it is the new
                            # current node.
                            // The element insertion algorithm has it do this already...
                            # 7. Switch the tokenizer to the script data state.
                            $this->tokenizer->state = Tokenizer::SCRIPT_DATA_STATE;
                            # 8. Let the original insertion mode be the current insertion mode.
                            $this->originalInsertionMode = $this->currentInsertionMode;
                            # 9. Switch the insertion mode to "text".
                            $this->insertionMode = self::TEXT_MODE;
                        }
                        # A start tag whose tag name is "template"
                        elseif ($token->name === 'template') {
                            # Insert an HTML element for the token.
                            static::insertStartTagToken($token);
                            # Insert a marker at the end of the list of active formatting elements.
                            $this->activeFormattingElementsList->insertMarker();
                            # Set the frameset-ok flag to "not ok".
                            $this->framesetOk = false;
                            # Switch the insertion mode to "in template".
                            $this->insertionMode = self::IN_TEMPLATE_MODE;
                            # Push "in template" onto the stack of template insertion modes so that it is
                            # the new current template insertion mode.
                            // DEVIATION: No scripting.
                        }
                    }
                    elseif ($token instanceof EndTagToken) {
                        # An end tag whose tag name is "head"
                        if ($token->name === 'head') {
                            # Pop the current node (which will be the head element) off the stack of open
                            # elements.
                            $this->stack->pop();
                            # Switch the insertion mode to "after head".
                            $this->insertionMode = self::AFTER_HEAD_MODE;
                        }
                        # An end tag whose tag name is one of: "body", "html", "br"
                        elseif ($token->name === 'body' || $token->name === 'html' || $token->name === 'br') {
                            # Act as described in the "anything else" entry below.
                            #
                            # Pop the current node (which will be the head element) off the stack of open
                            # elements.
                            $this->stack->pop();
                            # Switch the insertion mode to "after head".
                            $this->insertionMOde = self::AFTER_HEAD_MODE;
                            # Reprocess the token.
                            continue;
                        }
                        # An end tag whose tag name is "template"
                        elseif ($token->name === 'template') {
                            # If there is no template element on the stack of open elements, then this is a
                            # parse error; ignore the token.
                            if ($this->stack->search('template') === -1) {
                                ParseError::trigger(ParseError::UNEXPECTED_END_TAG, 'template', (string)$this->stack);
                            }
                            # Otherwise, run these steps:
                            else {
                                # 1. Generate all implied end tags thoroughly.
                                $this->stack->generateImpliedEndTags();

                                # 2. If the current node is not a template element, then this is a parse error.
                                if ($this->stack->currentNodeName !== 'template') {
                                    ParseError::trigger(ParseError::UNEXPECTED_END_TAG, 'template', (string)$this->stack);
                                }

                                # 3. Pop elements from the stack of open elements until a template element has been popped from the stack.
                                do {
                                    $poppedNodeName = $this->stack->pop()->nodeName;
                                } while ($poppedNodeName !== 'template');

                                # 4. Clear the list of active formatting elements up to the last marker.
                                $this->activeFormattingElementsList->clearToTheLastMarker();

                                # 5. Pop the current template insertion mode off the stack of template insertion modes.
                                // DEVIATION: No scripting.

                                # 6. Reset the insertion mode appropriately.
                                $this->resetInsertionMode();
                            }
                        }
                        // ¡STOPPED HERE!
                    }
                break;
            }

            break;
        }
    }

    protected function parseTokenInForeignContent(Token $token) {
        $currentNode = $this->stack->currentNode;
        $currentNodeName = $this->stack->currentNodeName;
        $currentNodeNamespace = $this->stack->currentNodeNamespace;
        # 8.2.5.5 The rules for parsing tokens in foreign content
        #
        # When the user agent is to apply the rules for parsing tokens in foreign
        # content, the user agent must handle the token as follows:
        #
        if ($token instanceof CharacterToken) {
            # A character token that is one of U+0009 CHARACTER TABULATION, "LF" (U+000A),
            # "FF" (U+000C), "CR" (U+000D), or U+0020 SPACE
            # Any other character token
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            if (strspn($token->data, "\t\n\x0c\x0d ") !== strlen($token->data)) {
                # Set the frameset-ok flag to "not ok".
                $this->$framesetOk = false;
            }

            # Insert the token's character.
            $this->insertCharacterToken($token);
        }
        # A comment token
        elseif ($token instanceof CommentToken) {
            # Insert a comment.
            $this->insertCommentToken($token);
        }
        # A DOCTYPE token
        elseif ($token instanceof DOCTYPEToken) {
            # Parse error.
            ParseError::trigger(ParseError::UNEXPECTED_DOCTYPE, 'Character, Comment, Start Tag, or End Tag');
        }
        elseif ($token instanceof StartTagToken) {
            # A start tag whose tag name is one of: "b", "big", "blockquote", "body", "br",
            # "center", "code", "dd", "div", "dl", "dt", "em", "embed", "h1", "h2", "h3",
            # "h4", "h5", "h6", "head", "hr", "i", "img", "li", "listing", "menu", "meta",
            # "nobr", "ol", "p", "pre", "ruby", "s", "small", "span", "strong", "strike",
            # "sub", "sup", "table", "tt", "u", "ul", "var"
            # A start tag whose tag name is "font", if the token has any attributes named
            # "color", "face", or "size"
            if ($token->name === 'b' || $token->name === 'big' || $token->name === 'blockquote' || $token->name === 'body' || $token->name === 'br' || $token->name === 'center' || $token->name === 'code' || $token->name === 'dd' || $token->name === 'div' || $token->name === 'dl' || $token->name === 'dt' || $token->name === 'em' || $token->name === 'embed' || $token->name === 'h1' || $token->name === 'h2' || $token->name === 'h3' || $token->name === 'h4' || $token->name === 'h5' || $token->name === 'h6' || $token->name === 'head' || $token->name === 'hr' || $token->name === 'i' || $token->name === 'img' || $token->name === 'li' || $token->name === 'listing' || $token->name === 'menu' || $token->name === 'meta' || $token->name === 'nobr' || $token->name === 'ol' || $token->name === 'p' || $token->name === 'pre' || $token->name === 'ruby' || $token->name === 's' || $token->name === 'small' || $token->name === 'span' || $token->name === 'strong' || $token->name === 'strike' || $token->name === 'sub' || $token->name === 'sup' || $token->name === 'table' || $token->name === 'tt' || $token->name === 'u' || $token->name === 'var' || (
                    $token->name === 'font' && (
                        $token->hasAttribute('color') || $token->hasAttribute('face') || $token->hasAttribute('size')
                    )
                )
            ) {
                # Parse error.
                ParseError::trigger(ParseError::UNEXPECTED_START_TAG, $token->name, 'Non-HTML start tag');

                # If the parser was originally created for the HTML fragment parsing algorithm,
                # then act as described in the "any other start tag" entry below. (fragment
                # case)
                if ($this->fragmentCase === true) {
                    // ¡TEMPORARY!
                    goto foreignContentAnyOtherStartTag;
                }

                # Otherwise:
                #
                # Pop an element from the stack of open elements, and then keep popping more
                # elements from the stack of open elements until the current node is a MathML
                # text integration point, an HTML integration point, or an element in the HTML
                # namespace.
                do {
                    $popped = $this->stack->pop();
                } while (!is_null($popped) && (
                        !DOM::isMathMLTextIntegrationPoint($this->stack->currentNode) &&
                        !DOM::isHTMLIntegrationPoint($this->stack->currentNode) &&
                        $this->stack->currentNode->namespaceURI !== Parser::HTML_NAMESPACE
                    )
                );

                # Then, reprocess the token.
                continue;
            }
            # Any other start tag
            else {
                foreignContentAnyOtherStartTag:

                # If the adjusted current node is an element in the SVG namespace, and the
                # token’s tag name is one of the ones in the first column of the following
                # table, change the tag name to the name given in the corresponding cell in the
                # second column. (This fixes the case of SVG elements that are not all
                # lowercase.)
                if ($currentNode->namespaceURI === Parser::SVG_NAMESPACE) {
                    switch ($token->name) {
                        case 'altglyph': $token->name = 'altGlyph';
                        break;
                        case 'altglyphdef': $token->name = 'altGlyphDef';
                        break;
                        case 'altglyphitem': $token->name = 'altGlyphItem';
                        break;
                        case 'animatecolor': $token->name = 'animateColor';
                        break;
                        case 'animatemotion': $token->name = 'animateMotion';
                        break;
                        case 'animatetransform': $token->name = 'animateTransform';
                        break;
                        case 'clippath': $token->name = 'clipPath';
                        break;
                        case 'feblend': $token->name = 'feBlend';
                        break;
                        case 'fecolormatrix': $token->name = 'feColorMatrix';
                        break;
                        case 'fecomponenttransfer': $token->name = 'feComponentTransfer';
                        break;
                        case 'fecomposite': $token->name = 'feComposite';
                        break;
                        case 'feconvolvematrix': $token->name = 'feConvolveMatrix';
                        break;
                        case 'fediffuselighting': $token->name = 'feDiffuseLighting';
                        break;
                        case 'fedisplacementmap': $token->name = 'feDisplacementMap';
                        break;
                        case 'fedistantlight': $token->name = 'feDistantLight';
                        break;
                        case 'feflood': $token->name = 'feFlood';
                        break;
                        case 'fefunca': $token->name = 'feFuncA';
                        break;
                        case 'fefuncb': $token->name = 'feFuncB';
                        break;
                        case 'fefuncg': $token->name = 'feFuncG';
                        break;
                        case 'fefuncr': $token->name = 'feFuncR';
                        break;
                        case 'fegaussianblur': $token->name = 'feGaussianBlur';
                        break;
                        case 'feimage': $token->name = 'feImage';
                        break;
                        case 'femerge': $token->name = 'feMerge';
                        break;
                        case 'femergenode': $token->name = 'feMergeNode';
                        break;
                        case 'femorphology': $token->name = 'feMorphology';
                        break;
                        case 'feoffset': $token->name = 'feOffset';
                        break;
                        case 'fepointlight': $token->name = 'fePointLight';
                        break;
                        case 'fespecularlighting': $token->name = 'feSpecularLighting';
                        break;
                        case 'fespotlight': $token->name = 'feSpotLight';
                        break;
                        case 'fetile': $token->name = 'feTile';
                        break;
                        case 'feturbulence': $token->name = 'feTurbulence';
                        break;
                        case 'foreignobject': $token->name = 'foreignObject';
                        break;
                        case 'glyphref': $token->name = 'glyphRef';
                        break;
                        case 'lineargradient': $token->name = 'linearGradient';
                        break;
                        case 'radialgradient': $token->name = 'radialGradient';
                        break;
                        case 'textpath': $token->name = 'textPath';
                    }
                }

                foreach ($token->attributes as &$a) {
                    # If the current node is an element in the MathML namespace, adjust MathML
                    # attributes for the token. (This fixes the case of MathML attributes that are
                    # not all lowercase.)
                    if ($currentNodeNamespace === Parser::MATHML_NAMESPACE && $a->name === 'definitionurl') {
                        $a->name = 'definitionURL';
                    }
                    # If the current node is an element in the SVG namespace, adjust SVG attributes
                    # for the token. (This fixes the case of SVG attributes that are not all
                    # lowercase.)
                    elseif ($currentNodeNamespace === Parser::SVG_NAMESPACE) {
                        switch ($a->name) {
                            case 'attributename': $a->name = 'attributeName';
                            break;
                            case 'attributetype': $a->name = 'attributeType';
                            break;
                            case 'basefrequency': $a->name = 'baseFrequency';
                            break;
                            case 'baseprofile': $a->name = 'baseProfile';
                            break;
                            case 'calcmode': $a->name = 'calcMode';
                            break;
                            case 'clippathunits': $a->name = 'clipPathUnits';
                            break;
                            case 'contentscripttype': $a->name = 'contentScriptType';
                            break;
                            case 'contentstyletype': $a->name = 'contentStyleType';
                            break;
                            case 'diffuseconstant': $a->name = 'diffuseConstant';
                            break;
                            case 'edgemode': $a->name = 'edgeMode';
                            break;
                            case 'externalresourcesrequired': $a->name = 'externalResourcesRequired';
                            break;
                            case 'filterres': $a->name = 'filterRes';
                            break;
                            case 'filterunits': $a->name = 'filterUnits';
                            break;
                            case 'glyphref': $a->name = 'glyphRef';
                            break;
                            case 'gradienttransform': $a->name = 'gradientTransform';
                            break;
                            case 'gradientunits': $a->name = 'gradientUnits';
                            break;
                            case 'kernelmatrix': $a->name = 'kernelMatrix';
                            break;
                            case 'kernelunitlength': $a->name = 'kernelUnitLength';
                            break;
                            case 'keypoints': $a->name = 'keyPoints';
                            break;
                            case 'keysplines': $a->name = 'keySplines';
                            break;
                            case 'keytimes': $a->name = 'keyTimes';
                            break;
                            case 'lengthadjust': $a->name = 'lengthAdjust';
                            break;
                            case 'limitingconeangle': $a->name = 'limitingConeAngle';
                            break;
                            case 'markerheight': $a->name = 'markerHeight';
                            break;
                            case 'markerunits': $a->name = 'markerUnits';
                            break;
                            case 'markerwidth': $a->name = 'markerWidth';
                            break;
                            case 'maskcontentunits': $a->name = 'maskContentUnits';
                            break;
                            case 'maskunits': $a->name = 'maskUnits';
                            break;
                            case 'numoctaves': $a->name = 'numOctaves';
                            break;
                            case 'pathlength': $a->name = 'pathLength';
                            break;
                            case 'patterncontentunits': $a->name = 'patternContentUnits';
                            break;
                            case 'patterntransform': $a->name = 'patternTransform';
                            break;
                            case 'patternunits': $a->name = 'patternUnits';
                            break;
                            case 'pointsatx': $a->name = 'pointsAtX';
                            break;
                            case 'pointsaty': $a->name = 'pointsAtY';
                            break;
                            case 'pointsatz': $a->name = 'pointsAtZ';
                            break;
                            case 'preservealpha': $a->name = 'preserveAlpha';
                            break;
                            case 'preserveaspectratio': $a->name = 'preserveAspectRatio';
                            break;
                            case 'primitiveunits': $a->name = 'primitiveUnits';
                            break;
                            case 'refx': $a->name = 'refX';
                            break;
                            case 'refy': $a->name = 'refY';
                            break;
                            case 'repeatcount': $a->name = 'repeatCount';
                            break;
                            case 'repeatdur': $a->name = 'repeatDur';
                            break;
                            case 'requiredextensions': $a->name = 'requiredExtensions';
                            break;
                            case 'requiredfeatures': $a->name = 'requiredFeatures';
                            break;
                            case 'specularconstant': $a->name = 'specularConstant';
                            break;
                            case 'specularexponent': $a->name = 'specularExponent';
                            break;
                            case 'spreadmethod': $a->name = 'spreadMethod';
                            break;
                            case 'startoffset': $a->name = 'startOffset';
                            break;
                            case 'stddeviation': $a->name = 'stdDeviation';
                            break;
                            case 'stitchtiles': $a->name = 'stitchTiles';
                            break;
                            case 'surfacescale': $a->name = 'surfaceScale';
                            break;
                            case 'systemlanguage': $a->name = 'systemLanguage';
                            break;
                            case 'tablevalues': $a->name = 'tableValues';
                            break;
                            case 'targetx': $a->name = 'targetX';
                            break;
                            case 'targety': $a->name = 'targetY';
                            break;
                            case 'textlength': $a->name = 'textLength';
                            break;
                            case 'viewbox': $a->name = 'viewBox';
                            break;
                            case 'viewtarget': $a->name = 'viewTarget';
                            break;
                            case 'xchannelselector': $a->name = 'xChannelSelector';
                            break;
                            case 'ychannelselector': $a->name = 'yChannelSelector';
                            break;
                            case 'zoomandpan': $a->name = 'zoomAndPan';
                        }
                    }

                    # Adjust foreign attributes for the token. (This fixes the use of namespaced
                    # attributes, in particular XLink in SVG.)
                    # When the steps below require the user agent to adjust foreign attributes for a
                    # token, then, if any of the attributes on the token match the strings given in
                    # the first column of the following table, let the attribute be a namespaced
                    # attribute, with the prefix being the string given in the corresponding cell in
                    # the second column, the local name being the string given in the corresponding
                    # cell in the third column, and the namespace being the namespace given in the
                    # corresponding cell in the fourth column. (This fixes the use of namespaced
                    # attributes, in particular lang attributes in the XML namespace.)

                    // DOMElement::setAttributeNS requires the prefix and local name be in one
                    // string, so there is no need to separate the prefix and the local name here.
                    switch($a->name) {
                        case 'xlink:actuate':
                        case 'xlink:arcrole':
                        case 'xlink:href':
                        case 'xlink:role':
                        case 'xlink:show':
                        case 'xlink:title':
                        case 'xlink:type': $a->namespace = Parser::XLINK_NAMESPACE;
                        break;
                        case 'xml:base':
                        case 'xml:lang':
                        case 'xml:space': $a->namespace = Parser::XML_NAMESPACE;
                        break;
                        case 'xmlns': $a->namespace = Parser::XMLNS_NAMESPACE;
                        break;
                        case 'xmlns:xlink': $a->namespace = Parser::XLINK_NAMESPACE;
                        break;
                        //default: $node->setAttribute($name, $value);
                    }
                }

                # Insert a foreign element for the token, in the same namespace as the adjusted
                # current node.
                static::insertStartTagToken($token, null, $adjustedCurrentNode->namespaceURI);

                # If the token has its self-closing flag set, then run the appropriate steps
                # from the following list:
                #
                # If the token’s tag name is "script", and the new current node is in the SVG
                # namespace
                # Acknowledge the token’s *self-closing flag*, and then act as described in the
                # steps for a "script" end tag below.
                // DEVIATION: Unnecessary because there is no scripting in this implementation.

                # Otherwise
                # Pop the current node off the stack of open elements and acknowledge the
                # token’s *self-closing flag*.
                $this->stack->pop();
                // Acknowledged.
            }
        }
        # An end tag whose tag name is "script", if the current node is a script element
        # in the SVG namespace
        // DEVIATION: This implementation does not support scripting, so script elements
        // aren't processed differently.

        # Any other end tag
        elseif ($token instanceof EndTagToken) {
            # Run these steps:
            #
            # 1. Initialize node to be the current node (the bottommost node of the stack).
            $node = $currentNode;
            $nodeName = $currentNodeName;
            # 2. If node is not an element with the same tag name as the token, then this is
            # a parse error.
            if ($nodeName !== $token->name) {
                ParseError::trigger(ParseError::UNEXPECTED_END_TAG, $token->name, $nodeName);
            }
            # 3. Loop: If node's tag name, converted to ASCII lowercase, is the same as the
            # tag name of the token, pop elements from the stack of open elements until node
            # has been popped from the stack, and then abort these steps.
            $count = $this->stack->length - 1;
            while (true) {
                if (strtolower($nodeName) === $token->name) {
                    do {
                        $popped = $this->stack->pop();
                    } while ($popped !== $node && !is_null($popped));

                    break;
                }

                # 4. Set node to the previous entry in the stack of open elements.
                $node = $this->stack[--$count];
                $nodeName = $node->nodeName;

                # 5. If node is not an element in the HTML namespace, return to the step labeled
                # loop.
                if ($node->namespaceURI !== Parser::HTML_NAMESPACE) {
                    continue;
                }

                # 6. Otherwise, process the token according to the rules given in the section
                # corresponding to the current insertion mode in HTML content.
                $this->parseTokenInHTMLContent($token, $this->insertionMode);
                break;
            }
        }
    }

    protected function appropriatePlaceForInsertingNode(\DOMNode $overrideTarget = null): array {
        $insertBefore = false;

        # 8.2.5.1. Creating and inserting nodes
        #
        # While the parser is processing a token, it can enable or disable foster
        # parenting. This affects the following algorithm.
        #
        # The appropriate place for inserting a node, optionally using a particular
        # override target, is the position in an element returned by running the
        # following steps:

        # 1. If there was an override target specified, then let target be the override
        # target.
        $target = (!is_null($overrideTarget)) ? $overrideTarget : $this->stack->currentNode;

        # 2. Determine the adjusted insertion location using the first matching steps
        # from the following list: If foster parenting is enabled and target is a table,
        # tbody, tfoot, thead, or tr element
        $targetNodeName = $target->nodeName;
        if ($this->fosterParenting && ($targetNodeName === 'table' || $targetNodeName === 'tbody' || $targetNodeName === 'tfoot' || $targetNodeName === 'thead' || $targetNodeName === 'tr')) {
            # Run these substeps:
            #
            # 1. Let last template be the last template element in the stack of open
            # elements, if any.
            $lastTemplateKey = $this->stack->search('template');
            $lastTemplate = ($lastTemplateKey !== -1 ) ? $this->stack[$lastTemplateKey] : null;

            # 2. Let last table be the last table element in the stack of open elements, if
            # any.
            $lastTableKey = $this->stack->search('table');
            $lastTable = ($lastTableKey !== -1 ) ? $this->stack[$lastTableKey] : null;

            # 3. If there is a last template and either there is no last table, or there is
            # one, but last template is lower (more recently added) than last table in the
            # stack of open elements, then: let adjusted insertion location be inside last
            # template’s template contents, after its last child (if any), and abort these
            # substeps.
            // DEVIATION: PHP's DOM does not have a special element for template and
            // therefore no API for putting the template's contents into a
            // DOMDocumentFragment in a property of the element, so the contents are just
            // going to be children of the template element instead.
            if ($lastTemplate && (!$lastTable || $lastTable && $lastTemplateKey > $lastTableKey)) {
                $insertionLocation = $lastTemplate;
                // Abort!
            }

            # 4. If there is no last table, then let adjusted insertion location be inside
            # the first element in the stack of open elements (the html element), after its
            # last child (if any), and abort these substeps. (fragment case)
            elseif (!$lastTable) {
                $insertionLocation = $this->stack[0];
                // Abort!
            }

            # 5. If last table has a parent node, then let adjusted insertion location be
            # inside last table’s parent node, immediately before last table, and abort
            # these substeps.
            elseif ($lastTable->parentNode) {
                $insertionLocation = $lastTable;
                $insertBefore = true;
                // Abort!
            }
            else {
                # 6. Let previous element be the element immediately above last table in the
                # stack of open elements.
                $previousElement = $this->stack[$lastTableKey - 1];

                # 7. Let adjusted insertion location be inside previous element, after its last
                # child (if any).
                $insertionLocation = $previousElement;
            }
        }
        # Otherwise let adjusted insertion location be inside target, after its last
        # child (if any).
        else {
            $insertionLocation = $target;
        }

        # 3. If the adjusted insertion location is inside a template element, let it
        # instead be inside the template element’s template contents, after its last
        # child (if any).
        // DEVIATION: PHP's DOM does not have a special element for template and
        // therefore no API for putting the template's contents into a
        // DOMDocumentFragment in a property of the element, so the contents are just
        // going to be children of the template element instead, so there's nothing to
        // do.

        # 4. Return the adjusted insertion location.
        return [
            'node' => $insertionLocation,
            'insert before' => $insertBefore
        ];
    }

    public static function insertCharacterToken(CharacterToken $token) {
        # 1. Let data be the characters passed to the algorithm, or, if no characters
        # were explicitly specified, the character of the character token being
        # processed.
        // Already provided through the token object.

        # 2. Let the adjusted insertion location be the appropriate place for inserting
        # a node.
        $location = static::$instance->appropriatePlaceForInsertingNode();
        $adjustedInsertionLocation = $location['node'];
        $insertBefore = $location['insert before'];

        # 3. If the adjusted insertion location is in a Document node, then abort these
        # steps.
        if ((($insertBefore === false) ? $adjustedInsertionLocation : $adjustedInsertionLocation->parentNode) instanceof DOMDocument) {
            return;
        }

        # 4. If there is a Text node immediately before the adjusted insertion location,
        # then append data to that Text node’s data.
        $previousSibling = ($insertBefore === false) ? $adjustedInsertionLocation->lastChild : $adjustedInsertionLocation->previousSibling;
        if ($previousSibling instanceof DOMText) {
            $previousSibling->data .= $token->data;
            return;
        }

        # Otherwise, create a new Text node whose data is data and whose node document
        # is the same as that of the element in which the adjusted insertion location
        # finds itself, and insert the newly created node at the adjusted insertion
        # location.
        $textNode = $adjustedInsertionLocation->ownerDocument->createTextNode($token->data);

        if ($insertBefore === false) {
            $adjustedInsertionLocation->appendChild($textNode);
        } else {
            $adjustedInsertionLocation->parentNode->insertBefore($textNode, $adjustedInsertionLocation);
        }
    }

    public static function insertCommentToken(CommentToken $token, \DOMNode $position = null) {
        # When the steps below require the user agent to insert a comment while
        # processing a comment token, optionally with an explicitly insertion position
        # position, the user agent must run the following steps:

        # 1. Let data be the data given in the comment token being processed.
        // Already provided through the token object.

        # 2. If position was specified, then let the adjusted insertion location be
        # position. Otherwise, let adjusted insertion location be the appropriate place
        # for inserting a node.
        if (!is_null($position)) {
            $adjustedInsertionLocation = $position;
            $insertBefore = false;
        } else {
            $location = static::$instance->appropriatePlaceForInsertingNode();
            $adjustedInsertionLocation = $location['node'];
            $insertBefore = $location['insert before'];
        }

        # 3. Create a Comment node whose data attribute is set to data and whose node
        # document is the same as that of the node in which the adjusted insertion
        # location finds itself.
        $commentNode = $adjustedInsertionLocation->ownerDocument->createComment($data);

        # 4. Insert the newly created node at the adjusted insertion location.
        if ($insertBefore === false) {
            $adjustedInsertionLocation->appendChild($commentNode);
        } else {
            $adjustedInsertionLocation->parentNode->insertBefore($commentNode, $adjustedInsertionLocation);
        }
    }

    public static function insertStartTagToken(StartTagToken $token, \DOMNode $intendedParent = null, string $namespace = null) {
        if (!is_null($namespace)) {
            $namespace = $token->namespace;
        }

        # When the steps below require the UA to create an element for a token in a
        # particular given namespace and with a particular intended parent, the UA must
        # run the following steps:

        # 1. Let document be intended parent’s node document.
        // DEVIATION: Unnecessary because there aren't any nested contexts to consider.
        // The document will always be $this->DOM.

        # 2. Let local name be the tag name of the token.
        // Nope. Don't need it because when creating elements with
        // DOMElement::createElementNS the prefix and local name are combined.

        // DEVIATION: Steps three through six are unnecessary because there is no
        // scripting in this implementation.

        # 7. Let element be the result of creating an element given document, local
        # name, given namespace, null, and is. If will execute script is true, set the
        # synchronous custom elements flag; otherwise, leave it unset.
        // DEVIATION: There is no point to setting the synchronous custom elements flag
        // and custom element definition; there is no scripting in this implementation.
        if ($namespace === Parser::HTML_NAMESPACE) {
            $element = static::$instance->DOM->createElement($token->name);
        } else {
            $element = static::$instance->DOM->createElementNS($namespace, $token->name);
        }

        # 8. Append each attribute in the given token to element.
        foreach ($token->attributes as $a) {
            if ($namespace === Parser::HTML_NAMESPACE) {
                $element->setAttribute($a->name, $a->value);
            } else {
                $element->setAttributeNS($namespace, $a->name, $a->value);
            }
        }

        # 9. If will execute script is true, then:
        # - 1. Let queue be the result of popping the current element queue from the
        # custom element reactions stack. (This will be the same element queue as was
        # pushed above.)
        # - 2. Invoke custom element reactions in queue.
        # - 3. Decrement document’s throw-on-dynamic-markup-insertion counter.
        // DEVIATION: These steps are unnecessary because there is no scripting in this
        // implementation.

        # 10. If element has an xmlns attribute *in the XMLNS namespace* whose value is
        # not exactly the same as the element’s namespace, that is a parse error.
        # Similarly, if element has an xmlns:xlink attribute in the XMLNS namespace
        # whose value is not the XLink namespace, that is a parse error.
        $xmlns = $element->getAttributeNS(Parser::XMLNS_NAMESPACE, 'xmlns');
        if ($xmlns !== '' && $xmlns !== $element->namespaceURI) {
            ParseError::trigger(ParseError::UNEXPECTED_XMLNS_ATTRIBUTE_VALUE, $element->namespaceURI);
        }

        $xlink = $element->getAttributeNS(Parser::XMLNS_NAMESPACE, 'xlink');
        if ($xlink !== '' && $xlink !== Parser::XLINK_NAMESPACE) {
            ParseError::trigger(ParseError::UNEXPECTED_XMLNS_ATTRIBUTE_VALUE, Parser::XLINK_NAMESPACE);
        }

        # 11. If element is a resettable element, invoke its reset algorithm. (This
        # initializes the element’s value and checkedness based on the element’s
        # attributes.)
        // DEVIATION: Unnecessary because there is no scripting in this implementation.

        # 12. If element is a form-associated element, and the form element pointer is
        # not null, and there is no template element on the stack of open elements, and
        # element is either not listed or doesn’t have a form attribute, and the
        # intended parent is in the same tree as the element pointed to by the form
        # element pointer, associate element with the form element pointed to by the
        # form element pointer, and suppress the running of the reset the form owner
        # algorithm when the parser subsequently attempts to insert the element.
        // DEVIATION: Unnecessary because there is no scripting in this implementation.

        # 13. Return element.
        // Nope. Going straight into element insertion.

        # When the steps below require the user agent to insert an HTML element for a
        # token, the user agent must insert a foreign element for the token, in the HTML
        # namespace.

        # When the steps below require the user agent to insert a foreign element for a
        # token in a given namespace, the user agent must run these steps:
        // Doing both foreign and HTML elements here because the only difference between
        // the two is that foreign elements are inserted with a namespace and HTML
        // elements are not.

        # 1. Let the adjusted insertion location be the appropriate place for inserting
        # a node.
        $location = static::$instance->appropriatePlaceForInsertingNode($intendedParent);
        $adjustedInsertionLocation = $location['node'];
        $insertBefore = $location['insert before'];

        # 2. Let element be the result of creating an element for the token in the given
        # namespace, with the intended parent being the element in which the adjusted
        # insertion location finds itself.
        // Element is supplied.
        // Have that, too.

        # 3. If it is possible to insert element at the adjusted insertion location,
        # then:
        # - 1. Push a new element queue onto the custom element reactions stack.
        // DEVIATION: Unnecessary because there is no scripting in this implementation.

        # - 2. Insert element at the adjusted insertion location.
        if ($insertBefore === false) {
            $adjustedInsertionLocation->appendChild($element);
        } else {
            $adjustedInsertionLocation->parentNode->insertBefore($element, $adjustedInsertionLocation);
        }

        # - 3. Pop the element queue from the custom element reactions stack, and
        # invoke custom element reactions in that queue.
        // DEVIATION: Unnecessary because there is no scripting in this implementation.

        # 4. Push element onto the stack of open elements so that it is the new current node.
        static::$instance->stack[] = $element;

        # Return element.
        return $element;
    }

    protected function parseGenericText(StartTagToken $token, bool $RAWTEXT = true) {
        # The generic raw text element parsing algorithm and the generic RCDATA element
        # parsing algorithm consist of the following steps. These algorithms are always
        # invoked in response to a start tag token.

        # 1. Insert an HTML element for the token.
        static::insertStartTagToken($token);

        # 2. If the algorithm that was invoked is the generic raw text element parsing
        # algorithm, switch the tokenizer to the RAWTEXT state; otherwise the algorithm
        # invoked was the generic RCDATA element parsing algorithm, switch the tokenizer
        # to the RCDATA state.
        $this->tokenizer->state = ($RAWTEXT === true) ? Tokenizer::RAWTEXT_STATE : Tokenizer::RCDATA_STATE;

        # 3. Let the original insertion mode be the current insertion mode.
        $this->originalInsertionMode = $this->insertionMode;

        # 4. Then, switch the insertion mode to "text".
        $this->insertionMode = self::TEXT_MODE;
    }

    protected function parseGenericRawText(StartTagToken $token) {
        $this->parseGenericText($token, true);
    }

    protected function parseGenericRCDATA(StartTagToken $token) {
        $this->parseGenericText($token, false);
    }

    protected function resetInsertionMode() {
        # When the steps below require the UA to reset the insertion mode appropriately,
        # it means the UA must follow these steps:

        # 1. Let last be false.
        $last = false;

        # 2. Let node be the last node in the stack of open elements.
        $node = $this->stack->currentNode;
        $nodeName = $this->stack->currentNodeName;
        // Keeping up with the position, too.
        $position = $this->stack->length - 1;

        # 3. Loop: If node is the first node in the stack of open elements, then set
        # last to true, and, if the parser was originally created as part of the HTML
        # fragment parsing algorithm (fragment case), set node to the context element
        # passed to that algorithm.
        while (true) {
            if ($node->isSameNode($this->stack[0])) {
                $last = true;

                if ($this->fragmentCase === true) {
                    $node = $this->fragmentContext;
                }
            }

            # 4. If node is a select element, run these substeps:
            if ($nodeName === 'select') {
                # 1. If last is true, jump to the step below labeled Done.
                if ($last === false) {
                    # 2. Let ancestor be node.
                    $ancestor = $node;
                    $position2 = $position;

                    # 3. Loop: If ancestor is the first node in the stack of open elements, jump to
                    # the step below labeled Done.
                    while (!$ancestor->isSameNode($this->stack[0])) {
                        # 4. Let ancestor be the node before ancestor in the stack of open elements.
                        $ancestor = $this->stack[--$position2];

                        # 5. If ancestor is a template node, jump to the step below labeled Done.
                        if ($ancestor->nodeName === 'template') {
                            break;
                        }

                        # 6. If ancestor is a table node, switch the insertion mode to "in select in
                        # table" and abort these steps.
                        if ($ancestor->nodeName === 'table') {
                            $this->insertionMode = self::IN_SELECT_IN_TABLE_MODE;
                            return;
                        }

                        # 7. Jump back to the step labeled Loop.
                    }
                }

                # 8. Done: Switch the insertion mode to "in select" and abort these steps.
                $this->insertionMode = self::IN_SELECT_MODE;
            }
            # 5. If node is a td or th element and last is false, then switch the insertion
            # mode to "in cell" and abort these steps.
            elseif (($nodeName === 'td' || $nodeName === 'th') && $last === false) {
                $this->insertionMode = self::IN_CELL_MODE;
                return;
            }
            # 6. If node is a tr element, then switch the insertion mode to "in row" and
            # abort these steps.
            elseif ($nodeName === 'tr') {
                $this->insertionMode = self::IN_ROW_MODE;
                return;
            }
            # 7. If node is a tbody, thead, or tfoot element, then switch the insertion mode
            # to "in table body" and abort these steps.
            elseif ($nodeName === 'tbody' || $nodeName === 'thead' || $nodeName === 'tfoot') {
                $this->insertionMode = self::IN_TABLE_BODY_MODE;
                return;
            }
            # 8. If node is a caption element, then switch the insertion mode to "in
            # caption" and abort these steps.
            elseif ($nodeName === 'caption') {
                $this->insertionMode = self::IN_CAPTION_MODE;
                return;
            }
            # 9. If node is a colgroup element, then switch the insertion mode to "in column
            # group" and abort these steps.
            elseif ($nodeName === 'colgroup') {
                $this->insertionMode = self::IN_COLUMN_GROUP_MODE;
                return;
            }
            # 10. If node is a table element, then switch the insertion mode to "in table"
            # and abort these steps.
            elseif ($nodeName === 'table') {
                $this->insertionMode = self::IN_TABLE_MODE;
                return;
            }
            # 11. If node is a template element, then switch the insertion mode to the
            # current template insertion mode and abort these steps.
            elseif ($nodeName === 'template') {
                // FIXME: NOT SURE WHAT TO DO HERE YET.
                return;
            }
            # 12. If node is a head element and last is false, then switch the insertion
            # mode to "in head" and abort these steps.
            elseif ($nodeName === 'head' && $last === false) {
                $this->insertionMode = self::IN_HEAD_MODE;
                return;
            }
            # 13. If node is a body element, then switch the insertion mode to "in body" and
            # abort these steps.
            elseif ($nodeName === 'body') {
                $this->insertionMode = self::IN_BODY_MODE;
                return;
            }
            # 14. If node is a frameset element, then switch the insertion mode to "in
            # frameset" and abort these steps. (fragment case)
            elseif ($nodeName === 'frameset') {
                $this->insertionMode = self::IN_FRAMESET_MODE;
                return;
            }
            # 15. If node is an html element, run these substeps:
            elseif ($nodeName === 'html') {
                # 1. If the head element pointer is null, switch the insertion mode to "before
                # head" and abort these steps. (fragment case)
                if (is_null($this->headElement)) {
                    $this->insertionMode = self::BEFORE_HEAD_MODE;
                    return;
                }

                # 2. Otherwise, the head element pointer is not null, switch the insertion mode
                # to "after head" and abort these steps.
                $this->insertionMode = self::AFTER_HEAD_MODE;
                return;
            }

            # 16. If last is true, then switch the insertion mode to "in body" and abort
            # these steps. (fragment case)
            if ($last === true) {
                $this->insertionMode = self::IN_BODY_MODE;
            }

            # 17. Let node now be the node before node in the stack of open elements.
            $node = $this->stack[--$position];

            # 18. Return to the step labeled Loop.
        }
    }
}