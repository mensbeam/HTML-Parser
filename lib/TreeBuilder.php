<?php
declare(strict_types=1);
namespace dW\HTML5;

class TreeBuilder {
    use ParseErrorEmitter;

    public $debugLog = "";

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
    // Used to store the template insertion modes
    protected $templateInsertionModes;

    // Constants used for insertion modes
    protected const INITIAL_MODE = 0;
    protected const BEFORE_HTML_MODE = 1;
    protected const BEFORE_HEAD_MODE = 2;
    protected const IN_HEAD_MODE = 3;
    protected const IN_HEAD_NOSCRIPT_MODE = 4;
    protected const AFTER_HEAD_MODE = 5;
    protected const IN_BODY_MODE = 6;
    protected const TEXT_MODE = 7;
    protected const IN_TABLE_MODE = 8;
    protected const IN_TABLE_TEXT_MODE = 9;
    protected const IN_CAPTION_MODE = 10;
    protected const IN_COLUMN_GROUP_MODE = 11;
    protected const IN_TABLE_BODY_MODE = 12;
    protected const IN_ROW_MODE = 13;
    protected const IN_CELL_MODE = 14;
    protected const IN_SELECT_MODE = 15;
    protected const IN_SELECT_IN_TABLE_MODE = 16;
    protected const IN_TEMPLATE_MODE = 17;
    protected const AFTER_BODY_MODE = 18;
    protected const IN_FRAMESET_MODE = 19;
    protected const AFTER_FRAMESET_MODE = 20;
    protected const AFTER_AFTER_BODY_MODE = 21;
    protected const AFTER_AFTER_FRAMESET_MODE = 22;

    // Quirks mode constants
    protected const QUIRKS_MODE_OFF = 0;
    protected const QUIRKS_MODE_ON = 1;
    protected const QUIRKS_MODE_LIMITED = 2;

    protected const INSERTION_MODE_NAMES = [
        self::INITIAL_MODE              => "Initial",
        self::BEFORE_HTML_MODE          => "Before html",
        self::BEFORE_HEAD_MODE          => "Before head",
        self::IN_HEAD_MODE              => "In head",
        self::IN_HEAD_NOSCRIPT_MODE     => "In head noscript",
        self::AFTER_HEAD_MODE           => "After head",
        self::IN_BODY_MODE              => "In body",
        self::TEXT_MODE                 => "Text",
        self::IN_TABLE_MODE             => "In table",
        self::IN_TABLE_TEXT_MODE        => "In table text",
        self::IN_CAPTION_MODE           => "In caption",
        self::IN_COLUMN_GROUP_MODE      => "In column group",
        self::IN_TABLE_BODY_MODE        => "In table body",
        self::IN_ROW_MODE               => "In row",
        self::IN_CELL_MODE              => "In cell",
        self::IN_SELECT_MODE            => "In select",
        self::IN_SELECT_IN_TABLE_MODE   => "In select in table",
        self::IN_TEMPLATE_MODE          => "In template mode",
        self::AFTER_BODY_MODE           => "After body",
        self::IN_FRAMESET_MODE          => "In frameset",
        self::AFTER_FRAMESET_MODE       => "After frameset",
        self::AFTER_AFTER_BODY_MODE     => "After after body",
        self::AFTER_AFTER_FRAMESET_MODE => "After after frameset",
    ];

    public function __construct(Document $dom, $formElement, bool $fragmentCase = false, $fragmentContext = null, OpenElementsStack $stack, Stack $templateInsertionModes, Tokenizer $tokenizer, ParseError $errorHandler, Data $data) {
        // If the form element isn't an instance of DOMElement that has a node name of
        // "form" or null then there's a problem.
        if (!is_null($formElement) && !($formElement instanceof \DOMElement && $formElement->nodeName === 'form')) {
            throw new Exception(Exception::TREEBUILDER_FORMELEMENT_EXPECTED, gettype($formElement));
        }

        // If the fragment context is not null and is not a document fragment, document,
        // or element then we have a problem. Additionally, if the parser is created for
        // parsing a fragment and the fragment context is null then we have a problem,
        // too.
        if ((!is_null($fragmentContext) && !$fragmentContext instanceof \DOMDocumentFragment && !$fragmentContext instanceof \DOMDocument && !$fragmentContext instanceof \DOMElement) ||
            (is_null($fragmentContext) && $fragmentCase)) {
            throw new Exception(Exception::TREEBUILDER_DOCUMENTFRAG_ELEMENT_DOCUMENT_DOCUMENTFRAG_EXPECTED, gettype($fragmentContext));
        }

        $this->DOM = $dom;
        $this->formElement = $formElement;
        $this->fragmentCase = $fragmentCase;
        $this->fragmentContext = $fragmentContext;
        $this->stack = $stack;
        $this->templateInsertionModes = $templateInsertionModes;
        $this->tokenizer = $tokenizer;
        $this->data = $data;
        $this->errorHandler = $errorHandler;

        // Initialize the list of active formatting elements.
        $this->activeFormattingElementsList = new ActiveFormattingElementsList($stack);

        $this->insertionMode = self::INITIAL_MODE;
        $this->quirksMode = self::QUIRKS_MODE_OFF;
    }

    public function emitToken(Token $token) {
        assert((function() use ($token) {
            $this->debugLog .= "EMITTED: ".constant(get_class($token)."::NAME")."\n";
            return true;
        })());
        // Loop used for reprocessing.
        while (true) {
            $adjustedCurrentNode = $this->stack->adjustedCurrentNode;
            $adjustedCurrentNodeName = $this->stack->adjustedCurrentNodeName;
            $adjustedCurrentNodeNamespace = $this->stack->adjustedCurrentNodeNamespace;

            # 13.2.6 Tree construction
            #
            # As each token is emitted from the tokenizer, the user agent must follow the
            # appropriate steps from the following list, known as the tree construction dispatcher:
            #
            # If the stack of open elements is empty
            if ($this->stack->length === 0 ||
                # If the adjusted current node is an element in the HTML namespace
                // PHP's DOM returns null when the namespace isn't specified... eg. HTML.
                is_null($adjustedCurrentNodeNamespace) || (
                        # If the adjusted current node is a MathML text integration point and the token is a
                        # start tag whose tag name is neither "mglyph" nor "malignmark"
                        # If the adjusted current node is a MathML text integration point and the token is a
                        # character token
                        $adjustedCurrentNode->isMathMLTextIntegrationPoint() && ((
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
                        $adjustedCurrentNode->isHTMLIntegrationPoint() && (
                            $token instanceof StartTagToken || $token instanceof CharacterToken
                        )
                    ) ||
                    # If the token is an end-of-file token
                    $token instanceof EOFToken) {
                # Process the token according to the rules given in the section corresponding to
                # the current insertion mode in HTML content.
                $this->parseTokenInHTMLContent($token);
            }
            # Otherwise
            else {
                # Process the token according to the rules given in the section for parsing
                # tokens in foreign content.
                // Returns false when needing to reprocess.
                if ($this->parseTokenInForeignContent($token) === false) {
                    continue;
                }
                # When a start tag token is emitted with its self-closing flag set, if the flag
                #   is not acknowledged when it is processed by the tree construction stage, that
                #   is a non-void-html-element-start-tag-with-trailing-solidus parse error.
                if ($token instanceof StartTagToken && $token->selfClosing && !$token->selfClosingAcknowledged) {
                    $this->error(ParseError::NON_VOID_HTML_ELEMENT_START_TAG_WITH_TRAILING_SOLIDUS);
                }
            }

            break;
        }
    }

    protected function parseTokenInHTMLContent(Token $token, int $insertionMode = null): bool {
        ProcessToken:
        $insertionMode = $insertionMode ?? $this->insertionMode;
        assert((function() use ($insertionMode) {
            $mode = self::INSERTION_MODE_NAMES[$insertionMode] ?? $insertionMode;
            $this->debugLog .= "    Mode: $mode\n";
            return true;
        })());

        # 13.2.6.4. The rules for parsing tokens in HTML content
        # 13.2.6.4.1. The "initial" insertion mode
        if ($insertionMode === self::INITIAL_MODE) {
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            #   (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            if ($token instanceof WhitespaceToken) {
                # Ignore the token.
            }
            # A comment token
            elseif ($token instanceof CommentToken) {
                # Insert a comment as the last child of the Document object.
                // DEVIATION: PHP's DOM cannot have comments before the DOCTYPE, so just going
                // to ignore them instead.
                //$this->insertCommentToken($token, $this->DOM);
            }
            # A DOCTYPE token
            elseif ($token instanceof DOCTYPEToken) {
                # If the DOCTYPE token's name is not "html", or the token's public identifier is
                #   not missing, or the token's system identifier is neither missing nor 
                #   "about:legacy-compat", then there is a parse error.
                if ($token->name !== 'html' || $token->public !== '' || ($token->system !== '' && $token->system !== 'about:legacy-compat')) {
                    $this->error(ParseError::INVALID_DOCTYPE);
                }

                # Append a DocumentType node to the Document node, with the name attribute set
                #   to the name given in the DOCTYPE token, or the empty string if the name was
                #   missing; the publicId attribute set to the public identifier given in the
                #   DOCTYPE token, or the empty string if the public identifier was missing; the
                #   systemId attribute set to the system identifier given in the DOCTYPE token, or
                #   the empty string if the system identifier was missing; and the other
                #   attributes specific to DocumentType objects set to null and empty lists as
                #   appropriate. Associate the DocumentType node with the Document object so that
                #   it is returned as the value of the doctype attribute of the Document object.
                $this->DOM->appendChild($this->DOM->implementation->createDocumentType((!is_null($token->name)) ? $token->name : '', $token->public, $token->system));

                
                # Then, if the document is not an iframe srcdoc document, and the DOCTYPE token
                # matches one of the conditions in the following list, then set the Document to
                # quirks mode:
                // DEVIATION: This implementation does not render, so there is no nested
                // browsing contexts to consider.
                $public = strtolower($token->public);
                if ($token->forceQuirks === true 
                    || $token->name !== 'html' 
                    || $public === '-//w3o//dtd w3 html strict 3.0//en//' 
                    || $public === '-/w3c/dtd html 4.0 transitional/en' 
                    || $public === 'html' 
                    || strtolower($token->system) === 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' 
                    || strpos($public, '+//silmaril//dtd html pro v0r11 19970101//') === 0 
                    || strpos($public, '-//as//dtd html 3.0 aswedit + extensions//') === 0 
                    || strpos($public, '+//silmaril//dtd html pro v0r11 19970101//') === 0 
                    || strpos($public, '-//as//dtd html 3.0 aswedit + extensions//') === 0 
                    || strpos($public, '-//advasoft ltd//dtd html 3.0 aswedit + extensions//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0 level 1//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0 level 2//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0 strict level 1//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0 strict level 2//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0 strict//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.0//') === 0 
                    || strpos($public, '-//ietf//dtd html 2.1e//') === 0 
                    || strpos($public, '-//ietf//dtd html 3.0//') === 0 
                    || strpos($public, '-//ietf//dtd html 3.2 final//') === 0 
                    || strpos($public, '-//ietf//dtd html 3.2//') === 0 
                    || strpos($public, '-//ietf//dtd html 3//') === 0 
                    || strpos($public, '-//ietf//dtd html level 0//') === 0 
                    || strpos($public, '-//ietf//dtd html level 1//') === 0 
                    || strpos($public, '-//ietf//dtd html level 2//') === 0 
                    || strpos($public, '-//ietf//dtd html level 3//') === 0 
                    || strpos($public, '-//ietf//dtd html strict level 0//') === 0 
                    || strpos($public, '-//ietf//dtd html strict level 1//') === 0 
                    || strpos($public, '-//ietf//dtd html strict level 2//') === 0 
                    || strpos($public, '-//ietf//dtd html strict level 3//') === 0 
                    || strpos($public, '-//ietf//dtd html strict//') === 0 
                    || strpos($public, '-//ietf//dtd html//') === 0 
                    || strpos($public, '-//metrius//dtd metrius presentational//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 2.0 html strict//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 2.0 html//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 2.0 tables//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 3.0 html strict//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 3.0 html//') === 0 
                    || strpos($public, '-//microsoft//dtd internet explorer 3.0 tables//') === 0 
                    || strpos($public, '-//netscape comm. corp.//dtd html//') === 0 
                    || strpos($public, '-//netscape comm. corp.//dtd strict html//') === 0 
                    || strpos($public, '-//o\'reilly and associates//dtd html 2.0//') === 0 
                    || strpos($public, '-//o\'reilly and associates//dtd html extended 1.0//') === 0 
                    || strpos($public, '-//o\'reilly and associates//dtd html extended relaxed 1.0//') === 0 
                    || strpos($public, '-//sq//dtd html 2.0 hotmetal + extensions//') === 0 
                    || strpos($public, '-//softquad software//dtd hotmetal pro 6.0::19990601::extensions to html 4.0//') === 0 
                    || strpos($public, '-//softquad//dtd hotmetal pro 4.0::19971010::extensions to html 4.0//') === 0 
                    || strpos($public, '-//spyglass//dtd html 2.0 extended//') === 0 
                    || strpos($public, '-//sun microsystems corp.//dtd hotjava html//') === 0 
                    || strpos($public, '-//sun microsystems corp.//dtd hotjava strict html//') === 0 
                    || strpos($public, '-//w3c//dtd html 3 1995-03-24//') === 0 
                    || strpos($public, '-//w3c//dtd html 3.2 draft//') === 0 
                    || strpos($public, '-//w3c//dtd html 3.2 final//') === 0 
                    || strpos($public, '-//w3c//dtd html 3.2//') === 0 
                    || strpos($public, '-//w3c//dtd html 3.2s draft//') === 0 
                    || strpos($public, '-//w3c//dtd html 4.0 frameset//') === 0 
                    || strpos($public, '-//w3c//dtd html 4.0 transitional//') === 0 
                    || strpos($public, '-//w3c//dtd html experimental 19960712//') === 0 
                    || strpos($public, '-//w3c//dtd html experimental 970421//') === 0 
                    || strpos($public, '-//w3c//dtd w3 html//') === 0 
                    || strpos($public, '-//w3o//dtd w3 html 3.0//') === 0 
                    || strpos($public, '-//webtechs//dtd mozilla html 2.0//') === 0 
                    || strpos($public, '-//webtechs//dtd mozilla html//') === 0 
                    || (is_null($token->system) && strpos($public, '-//w3c//dtd html 4.01 frameset//') === 0)
                    || (is_null($token->system) && strpos($public, '-//w3c//dtd html 4.01 transitional//') === 0)
                ) {
                    $this->quirksMode = self::QUIRKS_MODE_ON;
                }
                # Otherwise, if the document is not an iframe srcdoc document, and the DOCTYPE
                # token matches one of the conditions in the following list, then set the
                # Document to limited-quirks mode:
                // DEVIATION: There is no iframe srcdoc document because there are no nested
                // browsing contexts in this implementation.
                elseif (
                    strpos($public, '-//w3c//dtd xhtml 1.0 frameset//') === 0 
                    || strpos($public, '-//w3c//dtd xhtml 1.0 transitional//') === 0 
                    || (!is_null($token->system) && strpos($public, '-//w3c//dtd html 4.01 frameset//') === 0) 
                    || (!is_null($token->system) && strpos($public, '-//w3c//dtd html 4.01 transitional//') === 0)
                ) {
                    $this->quirksMode = self::QUIRKS_MODE_LIMITED;
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
                if ($token instanceof StartTagToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_START_TAG);
                } elseif ($token instanceof EndTagToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_END_TAG);
                } elseif ($token instanceof CharacterToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_CHARS);
                } elseif ($token instanceof EOFToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_EOF);
                } else {
                    throw new \Exception("Unexpected token type".get_class($token));
                }

                $this->quirksMode = self::QUIRKS_MODE_ON;

                # In any case, switch the insertion mode to "before html", then reprocess the
                # token.
                $insertionMode = $this->insertionMode = self::BEFORE_HTML_MODE;
                goto ProcessToken;
            };
        }
        # 13.2.6.4.2. The "before html" insertion mode
        elseif ($insertionMode === self::BEFORE_HTML_MODE) {
            # A DOCTYPE token
            if ($token instanceof DOCTYPEToken) {
                # Parse error. Ignore the token
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A comment token
            elseif ($token instanceof CommentToken) {
                # Insert a comment as the last child of the Document object.
                $this->insertCommentToken($token, $this->DOM);
            }
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            elseif ($token instanceof WhitespaceToken) {
                # Ignore the token.
            }
            # A start tag whose tag name is "html"
            elseif ($token instanceof StartTagToken && $token->name === 'html') {
                # Create an element for the token in the HTML namespace, with the Document as
                # the intended parent. Append it to the Document object. Put this element in the
                # stack of open elements.
                $element = $this->insertStartTagToken($token, $this->DOM);

                # Switch the insertion mode to "before head".
                $this->insertionMode = self::BEFORE_HEAD_MODE;
            }
            # An end tag whose tag name is one of: "head", "body", "html", "br"
            #   Act as described in the "anything else" entry below.
            # Any other end tag
            elseif ($token instanceof EndTagToken && $token->name !== 'head' && $token->name !== 'body' && $token->name !== 'html' && $token->name !== 'br') {
                # Parse error.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # Anything else
            else {
                # Create an html element whose node document is the Document object. Append it
                # to the Document object. Put this element in the stack of open elements.
                $element = $this->DOM->createElement('html');
                $this->DOM->appendChild($element);
                $this->stack[] = $element;

                # Switch the insertion mode to "before head", then reprocess the token.
                $insertionMode = $this->insertionMode = self::BEFORE_HEAD_MODE;
                goto ProcessToken;
            }

            # The document element can end up being removed from the Document object, e.g.,
            # by scripts; nothing in particular happens in such cases, content goto ProcessTokens
            # being appended to the nodes as described in the next section.
            // Good to know. There's no scripting in this implementation, though.
        }
        # 13.2.6.4.3. The "before head" insertion mode
        elseif ($insertionMode === self::BEFORE_HEAD_MODE) {
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            if ($token instanceof WhitespaceToken) {
                # Ignore the token.
            }
            # A comment token
            elseif ($token instanceof CommentToken) {
                # insert a comment.
                $this->insertCommentToken($token);
            }
            # A DOCTYPE token
            elseif ($token instanceof DOCTYPEToken) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A start tag whose tag name is "html"
            elseif ($token instanceof StartTagToken && $token->name === 'html') {
                # Process the token using the rules for the "in body" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
            # A start tag whose tag name is "head"
            elseif ($token instanceof StartTagToken && $token->name === 'head') {
                # Insert an HTML element for the token.
                $element = $this->insertStartTagToken($token);
                # Set the head element pointer to the newly created head element.
                $this->headElement = $element;
                # Switch the insertion mode to "in head".
                $insertionMode = $this->insertionMode = self::IN_HEAD_MODE;
            }
            # An end tag whose tag name is one of: "head", "body", "html", "br"
            #   Act as described in the "anything else" entry below.
            # Any other end tag
            elseif ($token instanceof EndTagToken && $token->name !== 'head' && $token->name !== 'body' && $token->name !== 'html' && $token->name === 'br') {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # Anything else
            else {
                # Insert an HTML element for a "head" start tag token with no attributes.
                $element = $this->insertStartTagToken(new StartTagToken('head'));
                # Set the head element pointer to the newly created head element.
                $this->headElement = $element;
                # Switch the insertion mode to "in head".
                $insertionMode = $this->insertionMode = self::IN_HEAD_MODE;
                # Reprocess the current token.
                goto ProcessToken;
            }
        }
        # 13.2.6.4.4. The "in head" insertion mode
        elseif ($insertionMode === self::IN_HEAD_MODE) {
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            if ($token instanceof WhitespaceToken) {
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
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A start tag...
            elseif ($token instanceof StartTagToken) {
                # A start tag whose tag name is "html"
                if ($token->name === 'html') {
                    # Process the token using the rules for the "in body" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
                }
                # A start tag whose tag name is one of: "base", "basefont", "bgsound", "link"
                elseif ($token->name === 'base' || $token->name === 'basefont' || $token->name === 'bgsound' || $token->name === 'link') {
                    # Insert an HTML element for the token. 
                    # Immediately pop the current node off the stack of open elements.
                    $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token’s *self-closing flag*, if it is set.
                    $token->selfClosingAcknowledged = true;
                }
                # A start tag whose tag name is "meta"
                elseif ($token->name === 'meta') {
                    # Insert an HTML element for the token. 
                    # Immediately pop the current node off the stack of open elements.
                    $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token’s *self-closing flag*, if it is set.
                    $token->selfClosingAcknowledged = true;

                    # If the element has a charset attribute, and getting an encoding from its value
                    #   results in an encoding, and the confidence is currently tentative, then change
                    #   the encoding to the resulting encoding.
                    # Otherwise, if the element has an http-equiv attribute whose value is an ASCII
                    #   case-insensitive match for the string "Content-Type", and the element has a
                    #   content attribute, and applying the algorithm for extracting a character
                    #   encoding from a meta element to that attribute’s value returns an encoding,
                    #   and the confidence is currently tentative, then change the encoding to the
                    #   extracted encoding.
                    // DEVIATION: FIXME: This implementation does not support changing the encoding mid-stream
                }
                # A start tag whose tag name is "title"
                elseif ($token->name === 'title') {
                    # Follow the generic RCDATA element parsing algorithm.
                    $this->parseGenericRCDATA($token);
                }
                # A start tag whose tag name is "noscript", if the scripting flag is enabled
                # A start tag whose tag name is one of: "noframes", "style"
                // DEVIATION: There is no scripting in this implementation, so the scripting
                // flag is always disabled.
                elseif ($token->name === 'noframes' || $token->name === 'style') {
                    # Follow the generic raw text element parsing algorithm.
                    $this->parseGenericRawText($token);
                }
                # A start tag whose tag name is "noscript", if the scripting flag is disabled
                // DEVIATION: There is no scripting in this implementation, so the scripting
                // flag is always disabled.
                elseif ($token->name === 'noscript') {
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
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
                    // need to get the adjusted insertion location as the intended parent isn't used 
                    // when determining anything; Parser::createAndInsertElement will get the 
                    // adjusted insertion location anyway.
                    $this->insertStartTagToken($token);

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
                    $this->insertStartTagToken($token);
                    # Insert a marker at the end of the list of active formatting elements.
                    $this->activeFormattingElementsList->insertMarker();
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Switch the insertion mode to "in template".
                    $this->insertionMode = self::IN_TEMPLATE_MODE;
                    # Push "in template" onto the stack of template insertion modes so that it is
                    # the new current template insertion mode.
                    $this->templateInsertionModes = self::IN_TEMPLATE_MODE;
                }
                # A start tag whose tag name is "head"
                elseif ($token->name === 'head') {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                }
                # Any other start tag
                else {
                    # Act as described in the "anything else" entry below.

                    # Pop the current node (which will be the head element) off 
                    # the stack of open elements.
                    $this->stack->pop();
                    # Switch the insertion mode to "after head".
                    $insertionMode = $this->insertionMode = self::AFTER_HEAD_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # And end tag...
            elseif ($token instanceof EndTagToken) {
                # An end tag whose tag name is "head"
                if ($token->name === 'head') {
                    # Pop the current node (which will be the head element) off 
                    #   the stack of open elements.
                    $this->stack->pop();
                    # Switch the insertion mode to "after head".
                    $this->insertionMode = self::AFTER_HEAD_MODE;
                }
                # An end tag whose tag name is one of: "body", "html", "br"
                elseif ($token->name === 'body' || $token->name === 'html' || $token->name === 'br') {
                    # Act as described in the "anything else" entry below.

                    # Pop the current node (which will be the head element) off 
                    #   the stack of open elements.
                    $this->stack->pop();
                    # Switch the insertion mode to "after head".
                    $insertionMode = $this->insertionMode = self::AFTER_HEAD_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
                # An end tag whose tag name is "template"
                elseif ($token->name === 'template') {
                    # If there is no template element on the stack of open elements, then this is a
                    # parse error; ignore the token.
                    if ($this->stack->search('template') === -1) {
                        $this->error(ParseError::UNEXPECTED_END_TAG);
                    }
                    # Otherwise, run these steps:
                    else {
                        # 1. Generate all implied end tags thoroughly.
                        $this->stack->generateImpliedEndTags();
                        # 2. If the current node is not a template element, then this is a parse error.
                        if ($this->stack->currentNodeName !== 'template') {
                            $this->error(ParseError::UNEXPECTED_END_TAG);
                        }
                        # 3. Pop elements from the stack of open elements until a template element has been popped from the stack.
                        $this->stack->popUntil('template');
                        # 4. Clear the list of active formatting elements up to the last marker.
                        $this->activeFormattingElementsList->clearToTheLastMarker();
                        # 5. Pop the current template insertion mode off the stack of template insertion modes.
                        $this->templateInsertionModes->pop();
                        # 6. Reset the insertion mode appropriately.
                        $this->resetInsertionMode();
                    }
                }
                # Any other end tag
                else {
                    # Parse error. Ignore the token.
                    $this->error(ParseError::UNEXPECTED_END_TAG);
                }
            }
            # Anything else
            else {
                # Pop the current node (which will be the head element) off the stack of open
                # elements.
                $this->stack->pop();
                # Switch the insertion mode to "after head".
                $insertionMode = $this->insertionMode = self::AFTER_HEAD_MODE;
                # Reprocess the token.
                goto ProcessToken;
            }
        }
        # 13.2.6.4.5. The "in head noscript" insertion mode
        elseif ($insertionMode === self::IN_HEAD_NOSCRIPT_MODE) {
            # DOCTYPE token
            if ($token instanceof DOCTYPEToken) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A start tag...
            elseif ($token instanceof StartTagToken) {
                # A start tag whose tag name is "html"
                if ($token->name === 'html') {
                    # Process the token using the rules for the "in body" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
                }
                # A start tag whose tag name is one of: "basefont", "bgsound", "link", "meta",
                # "noframes", "style"
                elseif ($token->name === 'basefont' || $token->name === 'bgsound' || $token->name === 'link' || $token->name === 'meta' || $token->name === 'noframes' || $token->name === 'style'){
                    # Process the token using the rules for the "in head" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                # A start tag whose tag name is one of: "head", "noscript"
                elseif ($token->name === 'head' || $token->name === 'noscript') {
                    # Parse error. Ignore the token.
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                }
                # Any other start tag
                else {
                    # Act as described in the "anything else" entry below.

                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                    # Pop the current node (which will be a noscript element) from the stack of open
                    # elements; the new current node will be a head element.
                    $this->stack->pop();
                    # Switch the insertion mode to "in head".
                    $insertionMode = $this->insertionMode = self::IN_HEAD_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is "noscript"
            elseif ($token instanceof EndTagToken && $token->name === 'noscript') {
                # Pop the current node (which will be a noscript element) from the stack of open
                # elements; the new current node will be a head element.
                $this->stack->pop();
                # Switch the insertion mode to "in head".
                $this->insertionMode = self::IN_HEAD_MODE;
            }
            # An end tag whose name is "br"
            #   Act as described in the "anything else" entry below.
            # Any other end tag
            elseif ($token instanceof EndTagToken && $token->name !== 'br') {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG);
            }
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            #   (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            # A comment token
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            elseif ($token instanceof CommentToken || $token instanceof WhitespaceToken) {
                # Process the token using the rules for the "in head" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
            }
            # Anything else
            else {
                # Parse error.
                $this->error(ParseError::UNEXPECTED_END_TAG);
                # Pop the current node (which will be a noscript element) from the stack 
                #   of open elements; the new current node will be a head element.
                $this->stack->pop();
                # Switch the insertion mode to "in head".
                $insertionMode = $this->insertionMode = self::IN_HEAD_MODE;
                # Reprocess the token.
                goto ProcessToken;
            }
        }
        # 13.2.6.4.6. The "after head" insertion mode
        elseif ($insertionMode === self::AFTER_HEAD_MODE) {
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            // OPTIMIZATION: Will check for multiple space characters at once as character
            // tokens can contain more than one character.
            if ($token instanceof WhitespaceToken) {
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
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A start tag...
            elseif ($token instanceof StartTagToken) {
                # A start tag whose tag name is "html"
                if ($token->name === 'html') {
                    # Process the token using the rules for the "in body" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
                }
                # A start tag whose tag name is "body"
                elseif ($token->name === 'body') {
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Switch the insertion mode to "in body".
                    $this->insertionMode = self::IN_BODY_MODE;
                }
                # A start tag whose tag name is "frameset"
                elseif ($token->name === 'frameset') {
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # Switch the insertion mode to "in frameset".
                    $this->insertionMode = self::IN_FRAMESET_MODE;
                }
                # A start tag whose tag name is one of: "base", "basefont", "bgsound", "link",
                # "meta", "noframes", "script", "style", "template", "title"
                elseif ($token->name === 'base' || $token->name === 'basefont' || $token->name === 'bgsound' || $token->name === 'link' || $token->name === 'meta' || $token->name === 'noframes' || $token->name === 'script' || $token->name === 'style' || $token->name === 'template' || $token->name === 'title') {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                    # Push the node pointed to by the head element pointer onto the stack of open elements.
                    $this->stack[] = $this->headElement;
                    # Process the token using the rules for the "in head" insertion mode.
                    $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                    # Remove the node pointed to by the head element pointer from the stack of open
                    # elements. (It might not be the current node at this point.)
                    $key = $this->stack->search($this->headElement);
                    if ($key !== -1) {
                        unset($this->stack[$key]);
                    }
                }
                # A start tag whose tag name is "head"
                elseif ($token->name === 'head') {
                    # Parse error. Ignore the token
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                }
                # Any other start tag
                else {
                    # Act as described in the "anything else" entry below.

                    # Insert an HTML element for a "body" start tag token with no attributes.
                    $this->insertStartTagToken(new StartTagToken('body'));
                    # Switch the insertion mode to "in body".
                    $insertionMode = $this->insertionMode = self::IN_BODY_MODE;
                    # Reprocess the current token.
                    goto ProcessToken;
                }
            }
            elseif ($token instanceof EndTagToken) {
                # An end tag whose tag name is "template"
                if ($token->name === 'template') {
                    # Process the token using the rules for the "in head" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                # An end tag whose tag name is one of: "body", "html", "br"
                elseif ($token->name === 'body' || $token->name === 'html' || $token->name === 'br') {
                    # Act as described in the "anything else" entry below.
                    #
                    # Insert an HTML element for a "body" start tag token with no attributes.
                    $this->insertStartTagToken(new StartTagToken('body'));
                    # Switch the insertion mode to "in body".
                    $insertionMode = $this->insertionMode = self::IN_BODY_MODE;
                    # Reprocess the current token.
                    goto ProcessToken;
                }
                # Any other end tag
                else {
                    # Parse error. Ignore the token.
                    $this->error(ParseError::UNEXPECTED_END_TAG);
                }
            }
            # Anything else
            else {
                # Insert an HTML element for a "body" start tag token with no attributes.
                $this->insertStartTagToken(new StartTagToken('body'));
                # Switch the insertion mode to "in body".
                $insertionMode = $this->insertionMode = self::IN_BODY_MODE;
                # Reprocess the current token.
                goto ProcessToken;
            }
        }
        # 13.2.6.4.7. The "in body" insertion mode
        elseif ($insertionMode === self::IN_BODY_MODE) {
            # A character token that is U+0000 NULL
            if ($token instanceof CharacterToken && $token->data === "\0") {
                # Parse error. Ignore the token
                // DEVIATION: the parse error is already reported by the tokenizer; 
                // this is probably an oversight in the specification, so we don't
                // report it a second time
            }
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            # (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            elseif ($token instanceof WhitespaceToken) {
                # Reconstruct the active formatting elements, if any.
                $this->activeFormattingElementsList->reconstruct();
                # Insert the token’s character.
                $this->insertCharacterToken($token);
            }
            # Any other character token
            elseif ($token instanceof CharacterToken) {
                # Reconstruct the active formatting elements, if any.
                $this->activeFormattingElementsList->reconstruct();
                # Insert the token’s character.
                $this->insertCharacterToken($token);
                # Set the frameset-ok flag to "not ok".
                $this->framesetOk = false;
            }
            # A comment token
            elseif ($token instanceof CommentToken) {
                # Insert a comment.
                $this->insertCommentToken($token);
            }
            # A DOCTYPE token
            elseif ($token instanceof DOCTYPEToken) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_DOCTYPE);
            }
            # A start tag...
            elseif ($token instanceof StartTagToken) {
                # A start tag whose tag name is "html"
                if ($token->name === 'html') {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, 'html');
                    # If there is a template element on the stack of open elements, then ignore the
                    # token.
                    if ($this->stack->search('template') === -1) {
                        # Otherwise, for each attribute on the token, check to see if the attribute is
                        # already present on the top element of the stack of open elements. If it is
                        # not, add the attribute and its corresponding value to that element.
                        $top = $this->stack[0];
                        foreach ($token->attributes as $a) {
                            if (!$top->hasAttribute($a->name)) {
                                $top->setAttribute($a->name, $a->value);
                            }
                        }
                    }
                }
                # A start tag whose tag name is one of: "base", "basefont", "bgsound", "link",
                # "meta", "noframes", "script", "style", "template", "title"
                elseif ($token->name === 'base' || $token->name === 'basefont' || $token->name === 'bgsound' || $token->name === 'link' || $token->name === 'meta' || $token->name === 'noframes' || $token->name === 'script' || $token->name === 'style' || $token->name === 'template' || $token->name === 'title') {
                    # Process the token using the rules for the "in head" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                # A start tag whose tag name is "body"
                elseif ($token->name === 'body') {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, 'body');
                    # If the second element on the stack of open elements is not a body element, if
                    # the stack of open elements has only one node on it, or if there is a template
                    # element on the stack of open elements, then ignore the token. (fragment case)
                    if (!($this->stack[1]->name !== 'body' || $this->stack->length === 1 || $this->stack->search('template') !== -1)) {
                        # Otherwise, set the frameset-ok flag to "not ok"; then, for each attribute on
                        # the token, check to see if the attribute is already present on the body
                        # element (the second element) on the stack of open elements, and if it is not,
                        # add the attribute and its corresponding value to that element.
                        $this->framesetOk = false;

                        $body = $this->stack[1];
                        foreach ($token->attributes as $a) {
                            if (!$body->hasAttribute($a->name)) {
                                $body->setAttribute($a->name, $a->value);
                            }
                        }
                    }
                }
                # A start tag whose tag name is "frameset"
                elseif ($token->name === 'frameset') {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, 'frameset');

                    # If the stack of open elements has only one node on it, or if the second
                    # element on the stack of open elements is not a body element, then ignore the
                    # token. (fragment case)
                    # If the frameset-ok flag is set to "not ok", ignore the token.
                    if (!($this->stack->length === 1 || $this->stack[1]->name !== 'body' || $this->framesetOk === false)) {
                        # Otherwise, run the following steps:
                        #
                        # 1. Remove the second element on the stack of open elements from its parent
                        # node, if it has one.
                        $second = $this->stack[1];
                        if ($second->parentNode) {
                            $second->parentNode->removeChild($second);
                        }
                        # 2. Pop all the nodes from the bottom of the stack of open elements, from the
                        # current node up to, but not including, the root html element.
                        for ($i = $this->stack->length - 1; $i > 0; $i--) {
                            $this->stack->pop();
                        }
                        # 3. Insert an HTML element for the token.
                        $this->insertStartTagToken($token);
                        # 4. Switch the insertion mode to "in frameset".
                        $this->insertionMode = self::IN_FRAMESET_MODE;
                    }
                }
                # A start tag whose tag name is one of: "address", "article", "aside",
                # "blockquote", "center", "details", "dialog", "dir", "div", "dl", "fieldset",
                # "figcaption", "figure", "footer", "header", "main", "nav", "ol", "p",
                # "section", "summary", "ul"
                elseif ($token->name === 'address' || $token->name === 'article' || $token->name === 'aside' || $token->name === 'blockquote' || $token->name === 'center' || $token->name === 'details' || $token->name === 'dialog' || $token->name === 'dir' || $token->name === 'div' || $token->name === 'dl' || $token->name === 'fieldset' || $token->name === 'figcaption' || $token->name === 'figure' || $token->name === 'footer' || $token->name === 'header' || $token->name === 'main' || $token->name === 'nav' || $token->name === 'ol' || $token->name === 'p' || $token->name === 'section' || $token->name === 'summary' || $token->name === 'ul') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "h1", "h2", "h3", "h4", "h5", "h6"
                elseif ($token->name === 'h1' || $token->name === 'h2' || $token->name === 'h3' || $token->name === 'h4' || $token->name === 'h5' || $token->name === 'h6') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # If the current node is an HTML element whose tag name is one of "h1", "h2",
                    # "h3", "h4", "h5", or "h6", then this is a parse error; pop the current node
                    # off the stack of open elements.
                    $currentNodeName = $this->stack->currentNodeName;
                    $currentNodeNamespace = $this->stack->currentNodeNamespace;
                    if ($currentNodeNamespace === '' && ($currentNodeName === 'h1' || $currentNodeName === 'h2' || $currentNodeName === 'h3' || $currentNodeName === 'h4' || $currentNodeName === 'h5' || $currentNodeName === 'h6')) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                        $this->stack->pop();
                    }

                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "pre", "listing"
                elseif ($token->name === 'pre' || $token->name === 'listing') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);

                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;

                    # If the next token is a U+000A LINE FEED (LF) character token, then ignore that
                    # token and move on to the next one. (Newlines at the start of pre blocks are
                    # ignored as an authoring convenience.)
                    $nextToken = $this->tokenizer->createToken();
                    if ($nextToken instanceof CharacterToken) {
                        // Character tokens in this implementation can have more than one character in
                        // them.
                        if (strlen($nextToken->data) === 1 && $nextToken->data === "\n") {
                            return true;
                        } elseif (strpos($nextToken->data, "\n") === 0) {
                            $nextToken->data = substr($nextToken->data, 1);
                        }
                    }

                    // Process the next token
                    $token = $nextToken;
                    goto ProcessToken;
                }
                # A start tag whose tag name is "form"
                elseif ($token->name === 'form') {
                    # If the form element pointer is not null, and there is no template element on
                    # the stack of open elements, then this is a parse error; ignore the token.
                    $templateInStack = ($this->stack->search('template') !== -1);
                    if (!is_null($this->formElement) && !$templateInStack) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    }
                    # Otherwise:
                    else {
                        # If the stack of open elements has a p element in button scope, then close a p
                        # element.
                        if ($this->stack->hasElementInButtonScope('p')) {
                            $this->closePElement();
                        }

                        # Insert an HTML element for the token, and, if there is no template element on
                        # the stack of open elements, set the form element pointer to point to the
                        # element created.
                        $form = $this->insertStartTagToken($token);
                        if ($templateInStack) {
                            $this->formElement = $form;
                        }
                    }
                }
                # A start tag whose tag name is "li"
                elseif ($token->name === 'li') {
                    # 1. Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;

                    # 2. Initialize node to be the current node (the bottommost node of the stack).
                    # 3. Loop: If node is an li element, then run these substeps:
                    for ($i = $this->stack->length - 1; $i >= 0; $i--) {
                        $node = $this->stack[$i];
                        $nodeName = $node->nodeName;

                        if ($nodeName === 'li') {
                            # 1. Generate implied end tags, except for li elements.
                            $this->stack->generateImpliedEndTags(["li"]);

                            # 2. If the current node is not an li element, then this is a parse error.
                            if ($this->stack->currentNodeName !== 'li') {
                                $this->error(ParseError::UNEXPECTED_START_TAG, $nodeName);
                            }

                            # 3. Pop elements from the stack of open elements until an li element has been
                            # popped from the stack.
                            $this->stack->popUntil('li');

                            # 4. Jump to the step labeled Done below.
                            return true;
                        }

                        # 4. If node is in the special category, but is not an address, div, or p
                        # element, then jump to the step labeled Done below.
                        if ($nodeName !== 'address' && $nodeName !== 'div' && $nodeName !== 'p' && $this->isElementSpecial($node)) {
                            return true;
                        }

                        # 5. Otherwise, set node to the previous entry in the stack of open elements and
                        # return to the step labeled Loop.
                        // The loop handles that.
                    }

                    # 6. Done: If the stack of open elements has a p element in button scope, then
                    # close a p element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # 7. Finally, insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "dd", "dt"
                elseif ($token->name === 'dd' || $token->name === 'dt') {
                    # 1. Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;

                    # 2. Initialize node to be the current node (the bottommost node of the stack).
                    for ($i = $this->stack->length - 1; $i >= 0; $i--) {
                        $node = $this->stack[$i];
                        $nodeName = $node->nodeName;

                        // Combining these two sets of instructions as they're identical except for the
                        // element name.
                        # 3. Loop: If node is a dd element, then run these substeps:
                        # 4. If node is a dt element, then run these substeps:
                        if ($nodeName === 'dd' || $nodeName === 'dt') {
                            # 1. Generate implied end tags, except for dd or dt elements.
                            $this->stack->generateImpliedEndTags(['dd', 'dt']);

                            # 2. If the current node is not a dd or dt element, then this is a parse error.
                            if ($this->stack->currentNodeName !== $nodeName) {
                                $this->error(ParseError::UNEXPECTED_START_TAG, $nodeName);
                            }

                            # 3. Pop elements from the stack of open elements until a dd or dt element has been
                            # popped from the stack.
                            $this->stack->popUntil(['dd', 'dt']);

                            # 4. Jump to the step labeled Done below.
                            return true;
                        }

                        # 5. If node is in the special category, but is not an address, div, or p
                        # element, then jump to the step labeled Done below.
                        if ($nodeName !== 'address' && $nodeName !== 'div' && $nodeName !== 'p' && $this->isElementSpecial($node)) {
                            return true;
                        }

                        # 6. Otherwise, set node to the previous entry in the stack of open elements and
                        # return to the step labeled Loop.
                        // The loop handles that.
                    }

                    # 7. Done: If the stack of open elements has a p element in button scope, then
                    # close a p element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # 8. Finally, insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is "plaintext"
                elseif ($token->name === 'plaintext') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement();
                    }

                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);

                    # Switch the tokenizer to the §8.2.4.5 PLAINTEXT state.
                    $this->tokenizer->state = Tokenizer::PLAINTEXT_STATE;
                }
                # A start tag whose tag name is "button"
                elseif ($token->name === 'button') {
                    # 1. If the stack of open elements has a button element in scope, then run these
                    # substeps:
                    if ($this->stack->hasElementInScope('button')) {
                        # 1. Parse error.
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);

                        # 2. Generate implied end tags.
                        $this->stack->generateImpliedEndTags();

                        # 3. Pop elements from the stack of open elements until a button element has
                        # been popped from the stack.
                        $this->stack->popUntil('button');
                    }

                    # 2. Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();

                    # 3. Insert an HTML element for the token.
                    $this->insertStartTagToken($token);

                    # 4. Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                }
                elseif ($token->name === "a") {
                    # If the list of active formatting elements contains an a element between the end
                    #   of the list and the last marker on the list (or the start of the list if there
                    #   is no marker on the list), then this is a parse error;
                    $this->error(ParseError::UNEXPECTED_START_TAG);
                    # ... run the adoption agency algorithm for the token, 
                    $this->adopt($token);
                    # ... then remove that element from the list of active formatting elements and the 
                    #   stack of open elements if the adoption agency algorithm didn't already remove it
                    #   (it might not have if the element is not in table scope).
                }
            }
            elseif ($token instanceof EndTagToken) {
                # An end tag whose tag name is "template"
                if ($token->name === 'template') {
                    # Process the token using the rules for the "in head" insertion mode.
                    $insertionMode = self::IN_HEAD_MODE;
                    goto ProcessToken;
                }
                # An end tag whose tag name is "body"
                # An end tag whose tag name is "html"
                elseif ($token->name === 'body' || $token->name === 'html') {
                    # If the stack of open elements does not have a body element in scope, this is a
                    # parse error; ignore the token.
                    if (!$this->stack->hasElementInScope('body')) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, 'body');
                    }
                    # Otherwise, if there is a node in the stack of open elements that is not either
                    # a dd element, a dt element, an li element, an optgroup element, an option
                    # element, a p element, an rb element, an rp element, an rt element, an rtc
                    # element, a tbody element, a td element, a tfoot element, a th element, a thead
                    # element, a tr element, the body element, or the html element, then this is a
                    # parse error.
                    else {
                        if ($this->stack->search(function($node) {
                            $n = $node->nodeName;
                            if ($n !== 'dd' && $n !== 'dt' && $n !== 'li' && $n !== 'optgroup' && $n !== 'option' && $n !== 'p' && $n !== 'rb' && $n !== 'rp' && $n !== 'rt' && $n !== 'rtc' && $n !== 'tbody' && $n !== 'td' && $n !== 'tfoot' && $n !== 'th' && $n !== 'thead' && $n !== 'tr' && $n !== 'body' && $n !== 'html') {
                                return true;
                            }

                            return false;
                        }) !== -1) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, 'body');
                            return true;
                        }

                        # Switch the insertion mode to "after body".
                        $this->insertionMode = self::AFTER_BODY_MODE;

                        // The only thing different between body and html here is that when processing
                        // an html end tag the token is reprocessed.
                        if ($token->name === 'html') {
                            # Reprocess the token.
                            goto ProcessToken;
                        }
                    }
                }
                # An end tag whose tag name is one of: "address", "article", "aside",
                # "blockquote", "button", "center", "details", "dialog", "dir", "div", "dl",
                # "fieldset", "figcaption", "figure", "footer", "header", "listing", "main",
                # "nav", "ol", "pre", "section", "summary", "ul"
                elseif ($token->name === 'address' || $token->name === 'article' || $token->name === 'aside' || $token->name === 'blockquote' || $token->name === 'button' || $token->name === 'center' || $token->name === 'details' || $token->name === 'dialog' || $token->name === 'dir' || $token->name === 'div' || $token->name === 'dl' || $token->name === 'fieldset' || $token->name === 'figcaption' || $token->name === 'figure' || $token->name === 'footer' || $token->name === 'header' || $token->name === 'listing' || $token->name === 'main' || $token->name === 'nav' || $token->name === 'ol' || $token->name === 'pre' || $token->name === 'section' || $token->name === 'summary' || $token->name === 'ul') {
                    # If the stack of open elements does not have an element in scope that is an
                    # HTML element with the same tag name as that of the token, then this is a parse
                    # error; ignore the token.
                    if (!$this->stack->hasElementInScope($token->name)) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # 1. Generate implied end tags.
                        $this->stack->generateImpliedEndTags();

                        # 2. If the current node is not an HTML element with the same tag name as that
                        # of the token, then this is a parse error.
                        if ($this->stack->currentNodeName !== $token->name) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }

                        # 3. Pop elements from the stack of open elements until an HTML element with the
                        # same tag name as the token has been popped from the stack.
                        $this->stack->popUntil($token->name);
                    }
                }
                # An end tag whose tag name is "form"
                elseif ($token->name === 'form') {
                    # If there is no template element on the stack of open elements, then run these
                    # substeps:
                    if ($this->stack->search('template') === -1) {
                        # 1. Let node be the element that the form element pointer is set to, or null if it
                        # is not set to an element.
                        $node = $this->formElement;
                        # 2. Set the form element pointer to null.
                        $this->formElement = null;
                        # 3. If node is null or if the stack of open elements does not have node in
                        # scope, then this is a parse error; return and ignore the token.
                        if (is_null($node) || !$this->stack->hasElementInScope($node)) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                            return true;
                        }
                        # 4. Generate implied end tags.
                        $this->stack->generateImpliedEndTags();
                        # 5. If the current node is not node, then this is a parse error.
                        if (!$this->stack->currentNode->isSameNode($node)) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # 6. Remove node from the stack of open elements
                        $this->stack->remove($node);
                    }
                    # If there is a template element on the stack of open elements, then run these
                    # substeps instead:
                    else {
                        # 1. If the stack of open elements does not have a form element in scope, then
                        # this is a parse error; return and ignore the token.
                        if ($this->stack->hasElementInScope('form')) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                            return true;
                        }
                        # 2. Generate implied end tags.
                        $this->stack->generateImpliedEndTags();
                        # 3. If the current node is not a form element, then this is a parse error.
                        if (!$this->stack->currentNodeName !== 'form') {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # 4. Pop elements from the stack of open elements until a form element has been
                        # popped from the stack.
                        $this->stack->popUntil('form');
                    }
                }
            }
            # An end-of-file token
            elseif ($token instanceof EOFToken) {
                # If the stack of template insertion modes is not empty, then process the token using the rules for the "in template" insertion mode.
                if ($this->templateInsertionModes->length !== 0) {
                    $insertionMode = self::IN_TEMPLATE_MODE;
                    goto ProcessToken;
                }

                # Otherwise, follow these steps:
                # 1. If there is a node in the stack of open elements that is not either a dd
                # element, a dt element, an li element, an optgroup element, an option element,
                # a p element, an rb element, an rp element, an rt element, an rtc element, a
                # tbody element, a td element, a tfoot element, a th element, a thead element, a
                # tr element, the body element, or the html element, then this is a parse error.
                if ($this->stack->search(function($node) {
                    $n = $node->nodeName;
                    if ($n !== 'dd' && $n !== 'dt' && $n !== 'li' && $n !== 'optgroup' && $n !== 'option' && $n !== 'p' && $n !== 'rb' && $n !== 'rp' && $n !== 'rt' && $n !== 'rtc' && $n !== 'tbody' && $n !== 'td' && $n !== 'tfoot' && $n !== 'th' && $n !== 'thead' && $n !== 'tr' && $n !== 'body' && $n !== 'html') {
                        return true;
                    }

                    return false;
                }) !== -1) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, 'body');
                    return true;
                }

                # 2. Stop parsing.
                // Abort!
            }
        }
        // IMPLEMENTATION PENDING
        else {
            throw new \Exception("NOT IMPLEMENTED");
        }
        return true;
    }

    protected function adopt(TagToken $token): void {
        # The adoption agency algorithm, which takes as its only argument a 
        #   token 'token' for which the algorithm is being run, consists of 
        #   the following steps:

        // STUB

        assert(false, new \Exception("Adoption agency not implemented yet"));
    }

    protected function parseTokenInForeignContent(Token $token): bool {
        $currentNode = $this->stack->currentNode;
        $currentNodeName = $this->stack->currentNodeName;
        $currentNodeNamespace = $this->stack->currentNodeNamespace;
        # 13.2.6.5 The rules for parsing tokens in foreign content
        #
        # When the user agent is to apply the rules for parsing tokens in foreign
        # content, the user agent must handle the token as follows:

        # A character token that is one of U+0009 CHARACTER TABULATION, "LF" (U+000A),
        # "FF" (U+000C), "CR" (U+000D), or U+0020 SPACE
        if ($token instanceof WhitespaceToken) {
            # Insert the token's character.
            $this->insertCharacterToken($token);
        }
        # Any other character token
        elseif ($token instanceof CharacterToken) {
            # Set the frameset-ok flag to "not ok".
            $this->framesetOk = false;
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
            $this->error(ParseError::UNEXPECTED_DOCTYPE);
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
                $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);

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
                    $n = $this->stack->currentNode;
                    $nns = $currentNode->namespaceURI;
                } while (!is_null($popped) && !(
                        $n->isMathMLTextIntegrationPoint() ||
                        $n->isHTMLIntegrationPoint() ||
                        // PHP's DOM returns null when the namespace isn't specified... eg. HTML.
                        is_null($nns)
                    )
                );

                # Then, reprocess the token.
                return false;
            }
            # Any other start tag
            else {
                // ¡TEMPORARY!
                foreignContentAnyOtherStartTag:

                # If the adjusted current node is an element in the SVG namespace, and the
                # token’s tag name is one of the ones in the first column of the following
                # table, change the tag name to the name given in the corresponding cell in the
                # second column. (This fixes the case of SVG elements that are not all
                # lowercase.)
                if ($this->stack->adjustedCurrentNodeNamespace === Parser::SVG_NAMESPACE) {
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
                    }
                }

                # Insert a foreign element for the token, in the same namespace as the adjusted
                # current node.
                $this->insertStartTagToken($token, null, $this->stack->adjustedCurrentNode->namespaceURI);

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
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # 3. Loop: If node's tag name, converted to ASCII lowercase, is the same as the
            # tag name of the token, pop elements from the stack of open elements until node
            # has been popped from the stack, and then abort these steps.
            $count = $this->stack->length - 1;
            while (true) {
                if (strtolower($nodeName) === $token->name) {
                    $this->stack->popUntil($node);
                    break;
                }

                # 4. Set node to the previous entry in the stack of open elements.
                $node = $this->stack[--$count];
                $nodeName = $node->nodeName;

                # 5. If node is not an element in the HTML namespace, return to the step labeled
                # loop.
                // PHP DOM returns null if the namespace isn't specified... eg. HTML.
                if (!is_null($node->namespaceURI)) {
                    continue;
                }

                # 6. Otherwise, process the token according to the rules given in the section
                # corresponding to the current insertion mode in HTML content.
                $this->parseTokenInHTMLContent($token, $this->insertionMode);
                break;
            }
        }

        return true;
    }

    protected function appropriatePlaceForInsertingNode(\DOMNode $overrideTarget = null): array {
        $insertBefore = false;

        # 13.2.6.1. Creating and inserting nodes
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
            if ($lastTemplate && (!$lastTable || $lastTable && $lastTemplateKey > $lastTableKey)) {
                $insertionLocation = $lastTemplate->content;
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
        if ($insertionLocation instanceof Element && $insertionLocation->nodeName === 'template') {
            $insertionLocation = $insertionLocation->content;
        }

        # 4. Return the adjusted insertion location.
        return [
            'node' => $insertionLocation,
            'insert before' => $insertBefore
        ];
    }

    public function insertCharacterToken(CharacterToken $token) {
        # 1. Let data be the characters passed to the algorithm, or, if no characters
        # were explicitly specified, the character of the character token being
        # processed.
        // Already provided through the token object.

        # 2. Let the adjusted insertion location be the appropriate place for inserting
        # a node.
        $location = $this->appropriatePlaceForInsertingNode();
        $adjustedInsertionLocation = $location['node'];
        $insertBefore = $location['insert before'];

        # 3. If the adjusted insertion location is in a Document node, then abort these
        # steps.
        if ((($insertBefore === false) ? $adjustedInsertionLocation : $adjustedInsertionLocation->parentNode) instanceof \DOMDocument) {
            return;
        }

        # 4. If there is a Text node immediately before the adjusted insertion location,
        # then append data to that Text node’s data.
        $previousSibling = ($insertBefore === false) ? $adjustedInsertionLocation->lastChild : $adjustedInsertionLocation->previousSibling;
        if ($previousSibling instanceof \DOMText) {
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
            $location = $this->appropriatePlaceForInsertingNode();
            $adjustedInsertionLocation = $location['node'];
            $insertBefore = $location['insert before'];
        }

        # 3. Create a Comment node whose data attribute is set to data and whose node
        # document is the same as that of the node in which the adjusted insertion
        # location finds itself.
        $commentNode = $adjustedInsertionLocation->ownerDocument->createComment($token->data);

        # 4. Insert the newly created node at the adjusted insertion location.
        if ($insertBefore === false) {
            $adjustedInsertionLocation->appendChild($commentNode);
        } else {
            $adjustedInsertionLocation->parentNode->insertBefore($commentNode, $adjustedInsertionLocation);
        }
    }

    public function insertStartTagToken(StartTagToken $token, \DOMNode $intendedParent = null, string $namespace = null): Element {
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
            $element = $this->DOM->createElement($token->name);
        } else {
            $element = $this->DOM->createElementNS($namespace, $token->name);
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
            $this->error(ParseError::UNEXPECTED_XMLNS_ATTRIBUTE_VALUE, $element->namespaceURI);
        }

        $xlink = $element->getAttributeNS(Parser::XMLNS_NAMESPACE, 'xlink');
        if ($xlink !== '' && $xlink !== Parser::XLINK_NAMESPACE) {
            $this->error(ParseError::UNEXPECTED_XMLNS_ATTRIBUTE_VALUE, Parser::XLINK_NAMESPACE);
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
        $location = $this->appropriatePlaceForInsertingNode($intendedParent);

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
        $this->stack[] = $element;

        # Return element.
        return $element;
    }

    protected function parseGenericText(StartTagToken $token, bool $RAWTEXT = true) {
        # The generic raw text element parsing algorithm and the generic RCDATA element
        # parsing algorithm consist of the following steps. These algorithms are always
        # invoked in response to a start tag token.

        # 1. Insert an HTML element for the token.
        $this->insertStartTagToken($token);

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
                $this->insertionMode = $this->templateInsertionModes->currentMode;
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

    protected function closePElement() {
        # When the steps above say the UA is to close a p element, it means that the UA
        # must run the following steps:

        # 1. Generate implied end tags, except for p elements.
        $this->stack->generateImpliedEndTags(["p"]);
        # 2. If the current node is not a p element, then this is a parse error.
        $currentNodeName = $this->stack->currentNodeName;
        if ($currentNodeName !== 'p') {
            $this->error(ParseError::UNEXPECTED_END_TAG, $currentNodeName);
        }
        # 3. Pop elements from the stack of open elements until a p element has been
        # popped from the stack.
        $this->stack->popUntil('p');
    }

    protected function isElementSpecial(Element $element): bool {
        $name = $element->nodeName;
        $ns = $element->namespaceURI;

        # The following elements have varying levels of special parsing rules: HTML’s
        # address, applet, area, article, aside, base, basefont, bgsound, blockquote,
        # body, br, button, caption, center, col, colgroup, dd, details, dir, div, dl,
        # dt, embed, fieldset, figcaption, figure, footer, form, frame, frameset, h1,
        # h2, h3, h4, h5, h6, head, header, hr, html, iframe, img, input, li, link,
        # listing, main, marquee, meta, nav, noembed, noframes, noscript, object, ol, p,
        # param, plaintext, pre, script, section, select, source, style, summary, table,
        # tbody, td, template, textarea, tfoot, th, thead, title, tr, track, ul, wbr,
        # xmp; MathML mi, MathML mo, MathML mn, MathML ms, MathML mtext, and MathML
        # annotation-xml; and SVG foreignObject, SVG desc, and SVG title.
        return (($ns === '' && ($name === 'address' || $name === 'applet' || $name === 'area' || $name === 'article' || $name === 'aside' || $name === 'base' || $name === 'basefont' || $name === 'bgsound' || $name === 'blockquote' || $name === 'body' || $name === 'br' || $name === 'button' || $name === 'caption' || $name === 'center' || $name === 'col' || $name === 'colgroup' || $name === 'dd' || $name === 'details' || $name === 'dir' || $name === 'div' || $name === 'dl' || $name === 'dt' || $name === 'embed' || $name === 'fieldset' || $name === 'figcaption' || $name === 'figure' || $name === 'footer' || $name === 'form' || $name === 'frame' || $name === 'frameset' || $name === 'h1' || $name === 'h2' || $name === 'h3' || $name === 'h4' || $name === 'h5' || $name === 'h6' || $name === 'head' || $name === 'header' || $name === 'hr' || $name === 'html' || $name === 'iframe' || $name === 'img' || $name === 'input' || $name === 'li' || $name === 'link' || $name === 'listing' || $name === 'main' || $name === 'marquee' || $name === 'meta' || $name === 'nav' || $name === 'noembed' || $name === 'noframes' || $name === 'noscript' || $name === 'object' || $name === 'ol' || $name === 'p' || $name === 'param' || $name === 'plaintext' || $name === 'pre' || $name === 'script' || $name === 'section' || $name === 'select' || $name === 'source' || $name === 'style' || $name === 'summary' || $name === 'table' || $name === 'tbody' || $name === 'td' || $name === 'template' || $name === 'textarea' || $name === 'tfoot' || $name === 'th' || $name === 'thead' || $name === 'title' || $name === 'tr' || $name === 'track' || $name === 'ul' || $name === 'wbr' || $name === 'xmp')) || ($ns === Parser::MATHML_NAMESPACE && ($name === 'mi' || $name === 'mo' || $name === 'mn' || $name === 'ms' || $name === 'mtext' || $name === 'annotation-xml')) || ($ns === Parser::SVG_NAMESPACE && ($name === 'foreignObject' || $name === 'desc' || $name === 'title')));
    }
}
