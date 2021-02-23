<?php
declare(strict_types=1);
namespace dW\HTML5;

class TreeBuilder {
    use ParseErrorEmitter;

    public $debugLog = "";

    /** @var \dW\HTML5\ActiveFormattingElementsList The list of active formatting elements, used when elements are improperly nested */
    protected $activeFormattingElementsList;
    /** @var \dW\HTML5\Document The DOMDocument that is assembled by this class */
    protected $DOM;
    /** @var ?\DOMElement The form element pointer points to the last form element that was opened and whose end tag has not yet been seen. It is used to make form controls associate with forms in the face of dramatically bad markup, for historical reasons. It is ignored inside template elements */
    protected $formElement;
    /** @var bool Flag for determining whether to use the foster parenting (badly nested table elements) algorithm. */
    protected $fosterParenting = false;
    /** @var \DOMElement Context element for fragments */
    protected $fragmentContext;
    /** @var bool Flag used to determine whether elements are okay to be used in framesets or not */
    protected $framesetOk = true;
    /** @var ?\DOMElement Once a head element has been parsed (whether implicitly or explicitly) the head element pointer gets set to point to this node */
    protected $headElement;
    /** @var int Tree construction insertion mode */
    protected $insertionMode = self::INITIAL_MODE;
    /** @var int When the insertion mode is switched to "text" or "in table text", the original insertion mode is also set. This is the insertion mode to which the tree construction stage will return. */
    protected $originalInsertionMode;
    /** @var \dW\HTML5\OpenElementsStack The stack of open elements, uses Stack */
    protected $stack;
    /** @var \dW\HTML5\Data Instance of the Data class used for reading the input character-stream */
    protected $data;
    /** @var \dW\HTML5\Tokenizer Instance of the Tokenizer class used for creating tokens */
    protected $tokenizer;
    /** @var \dW\HTML5\TemplateInsertionModesStack Used to store the template insertion modes */
    protected $templateInsertionModes;
    /** @var array TODO */
    protected $pendingTableCharacterTokens = [];

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
    protected const SVG_TAG_NAME_MAP = [
        'altglyph'            => 'altGlyph',
        'altglyphdef'         => 'altGlyphDef',
        'altglyphitem'        => 'altGlyphItem',
        'animatecolor'        => 'animateColor',
        'animatemotion'       => 'animateMotion',
        'animatetransform'    => 'animateTransform',
        'clippath'            => 'clipPath',
        'feblend'             => 'feBlend',
        'fecolormatrix'       => 'feColorMatrix',
        'fecomponenttransfer' => 'feComponentTransfer',
        'fecomposite'         => 'feComposite',
        'feconvolvematrix'    => 'feConvolveMatrix',
        'fediffuselighting'   => 'feDiffuseLighting',
        'fedisplacementmap'   => 'feDisplacementMap',
        'fedistantlight'      => 'feDistantLight',
        'feflood'             => 'feFlood',
        'fefunca'             => 'feFuncA',
        'fefuncb'             => 'feFuncB',
        'fefuncg'             => 'feFuncG',
        'fefuncr'             => 'feFuncR',
        'fegaussianblur'      => 'feGaussianBlur',
        'feimage'             => 'feImage',
        'femerge'             => 'feMerge',
        'femergenode'         => 'feMergeNode',
        'femorphology'        => 'feMorphology',
        'feoffset'            => 'feOffset',
        'fepointlight'        => 'fePointLight',
        'fespecularlighting'  => 'feSpecularLighting',
        'fespotlight'         => 'feSpotLight',
        'fetile'              => 'feTile',
        'feturbulence'        => 'feTurbulence',
        'foreignobject'       => 'foreignObject',
        'glyphref'            => 'glyphRef',
        'lineargradient'      => 'linearGradient',
        'radialgradient'      => 'radialGradient',
        'textpath'            => 'textPath',
    ];
    protected const SVG_ATTR_NAME_MAP = [
        'attributename'             => 'attributeName',
        'attributetype'             => 'attributeType',
        'basefrequency'             => 'baseFrequency',
        'baseprofile'               => 'baseProfile',
        'calcmode'                  => 'calcMode',
        'clippathunits'             => 'clipPathUnits',
        'diffuseconstant'           => 'diffuseConstant',
        'edgemode'                  => 'edgeMode',
        'filterunits'               => 'filterUnits',
        'glyphref'                  => 'glyphRef',
        'gradienttransform'         => 'gradientTransform',
        'gradientunits'             => 'gradientUnits',
        'kernelmatrix'              => 'kernelMatrix',
        'kernelunitlength'          => 'kernelUnitLength',
        'keypoints'                 => 'keyPoints',
        'keysplines'                => 'keySplines',
        'keytimes'                  => 'keyTimes',
        'lengthadjust'              => 'lengthAdjust',
        'limitingconeangle'         => 'limitingConeAngle',
        'markerheight'              => 'markerHeight',
        'markerunits'               => 'markerUnits',
        'markerwidth'               => 'markerWidth',
        'maskcontentunits'          => 'maskContentUnits',
        'maskunits'                 => 'maskUnits',
        'numoctaves'                => 'numOctaves',
        'pathlength'                => 'pathLength',
        'patterncontentunits'       => 'patternContentUnits',
        'patterntransform'          => 'patternTransform',
        'patternunits'              => 'patternUnits',
        'pointsatx'                 => 'pointsAtX',
        'pointsaty'                 => 'pointsAtY',
        'pointsatz'                 => 'pointsAtZ',
        'preservealpha'             => 'preserveAlpha',
        'preserveaspectratio'       => 'preserveAspectRatio',
        'primitiveunits'            => 'primitiveUnits',
        'refx'                      => 'refX',
        'refy'                      => 'refY',
        'repeatcount'               => 'repeatCount',
        'repeatdur'                 => 'repeatDur',
        'requiredextensions'        => 'requiredExtensions',
        'requiredfeatures'          => 'requiredFeatures',
        'specularconstant'          => 'specularConstant',
        'specularexponent'          => 'specularExponent',
        'spreadmethod'              => 'spreadMethod',
        'startoffset'               => 'startOffset',
        'stddeviation'              => 'stdDeviation',
        'stitchtiles'               => 'stitchTiles',
        'surfacescale'              => 'surfaceScale',
        'systemlanguage'            => 'systemLanguage',
        'tablevalues'               => 'tableValues',
        'targetx'                   => 'targetX',
        'targety'                   => 'targetY',
        'textlength'                => 'textLength',
        'viewbox'                   => 'viewBox',
        'viewtarget'                => 'viewTarget',
        'xchannelselector'          => 'xChannelSelector',
        'ychannelselector'          => 'yChannelSelector',
        'zoomandpan'                => 'zoomAndPan',
    ];
    protected const FOREIGN_ATTRIBUTE_NAMESPACE_MAP = [
            'xlink:actuate' => Parser::XLINK_NAMESPACE,
            'xlink:arcrole' => Parser::XLINK_NAMESPACE,
            'xlink:href'    => Parser::XLINK_NAMESPACE,
            'xlink:role'    => Parser::XLINK_NAMESPACE,
            'xlink:show'    => Parser::XLINK_NAMESPACE,
            'xlink:title'   => Parser::XLINK_NAMESPACE,
            'xlink:type'    => Parser::XLINK_NAMESPACE,
            'xml:base'      => Parser::XML_NAMESPACE,
            'xml:lang'      => Parser::XML_NAMESPACE,
            'xml:space'     => Parser::XML_NAMESPACE,
            'xmlns'         => Parser::XMLNS_NAMESPACE,
            'xmlns:xlink'   => Parser::XLINK_NAMESPACE,
    ];
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
    protected const SPECIAL_ELEMENTS = [
        Parser::HTML_NAMESPACE   => ['address', 'applet', 'area', 'article', 'aside', 'base', 'basefont', 'bgsound', 'blockquote', 'body', 'br', 'button', 'caption', 'center', 'col', 'colgroup', 'dd', 'details', 'dir', 'div', 'dl', 'dt', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'iframe', 'img', 'input', 'li', 'link', 'listing', 'main', 'marquee', 'meta', 'nav', 'noembed', 'noframes', 'noscript', 'object', 'ol', 'p', 'param', 'plaintext', 'pre', 'script', 'section', 'select', 'source', 'style', 'summary', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'ul', 'wbr', 'xmp'],
        Parser::MATHML_NAMESPACE => ['mi', 'mo', 'mn', 'ms', 'mtext', 'annotation-xml'],
        Parser::SVG_NAMESPACE    => ['foreignObject', 'desc', 'title'],
    ];
    protected const FRAGMENT_CONTEXT_TOKENIZER_STATES = [
        Parser::HTML_NAMESPACE => [
            'title'     => Tokenizer::RCDATA_STATE,
            'textarea'  => Tokenizer::RCDATA_STATE,
            'style'     => Tokenizer::RAWTEXT_STATE,
            'xmp'       => Tokenizer::RAWTEXT_STATE,
            'iframe'    => Tokenizer::RAWTEXT_STATE,
            'noembed'   => Tokenizer::RAWTEXT_STATE,
            'noframes'  => Tokenizer::RAWTEXT_STATE,
            'script'    => Tokenizer::SCRIPT_DATA_STATE,
            'noscript'  => Tokenizer::DATA_STATE, // NOTE: If ever this implementation were scripted, this would need special handling
            'plaintext' => Tokenizer::PLAINTEXT_STATE,
        ],
    ];

    public function __construct(Document $dom, Data $data, Tokenizer $tokenizer, ParseError $errorHandler, OpenElementsStack $stack, TemplateInsertionModesStack $templateInsertionModes, ?\DOMElement $fragmentContext = null) {
        assert(!$dom->hasChildNodes() && !$dom->doctype, new \Exception("Target document is not empty"));
        $this->DOM = $dom;
        $this->fragmentContext = $fragmentContext;
        $this->stack = $stack;
        $this->templateInsertionModes = $templateInsertionModes;
        $this->tokenizer = $tokenizer;
        $this->data = $data;
        $this->errorHandler = $errorHandler;
        $this->activeFormattingElementsList = new ActiveFormattingElementsList($this, $stack);

        # Parsing HTML fragments
        if ($this->fragmentContext) {
            # Create a new Document node, and mark it as being an HTML document.
            // Already done.
            # If the node document of the context element is in quirks mode, then
            #   let the Document be in quirks mode. Otherwise, the node document of
            #   the context element is in limited-quirks mode, then let the Document
            #   be in limited-quirks mode. Otherwise, leave the Document in no-quirks mode.
            $dom->quirksMode = $fragmentContext->ownerDocument->quirksMode;
            # Create a new HTML parser, and associate it with the just created Document node.
            // Already done.
            # Set the state of the HTML parser's tokenization stage as follows, switching on the context element:
            $this->tokenizer->state = (self::FRAGMENT_CONTEXT_TOKENIZER_STATES[$fragmentContext->namespaceURI ?? Parser::HTML_NAMESPACE] ?? [])[$fragmentContext->nodeName] ?? Tokenizer::DATA_STATE;
            # Let root be a new html element with no attributes.
            # Append the element root to the Document node created above.
            $dom->appendChild($dom->createElement("html"));
            # Set up the parser's stack of open elements so that it contains just the single element root.
            $this->stack[] = $dom->documentElement;
            # If the context element is a template element, push "in template" onto the stack of
            #   template insertion modes so that it is the new current template insertion mode.
            if ($fragmentContext->nodeName === "template" && !$fragmentContext->namespaceURI) {
                $this->templateInsertionModes[] = self::IN_TEMPLATE_MODE;
            }
            # Create a start tag token whose name is the local name of context and whose attributes are the attributes of context.
            # Let this start tag token be the start tag token of the context node, e.g. for the purposes of determining if it is an HTML integration point.
            // Are these even necessary?
            # Reset the parser's insertion mode appropriately.
            $this->resetInsertionMode();
            # Set the parser's form element pointer to the nearest node to the context element
            #   that is a form element (going straight up the ancestor chain, and including the 
            #   element itself, if it is a form element), if any. (If there is no such form element,
            #   the form element pointer keeps its initial value, null.)
            $node = $fragmentContext;
            do {
                if ($node->nodeName === "form" && !$fragmentContext->namespaceURI) {
                    $this->formElement = $node;
                    break;
                }
            } while ($node = $node->parentNode);
            # Place the input into the input stream for the HTML parser just created. 
            #   The encoding confidence is irrelevant.
            // Already done.
            # Start the parser and let it run until it has consumed all the characters just inserted into the input stream.
            // Handled by emitToken()
        }
    }

    public function emitToken(Token $token): void {
        assert((function() use ($token) {
            $this->debugLog .= "EMITTED: ".constant(get_class($token)."::NAME")."\n";
            return true;
        })());
        // Loop used for reprocessing.
        $iterations = 0;
        while (true) {
            assert($iterations++ < 50, new LoopException("Probable infinite loop detected in HTML content handling (outer reprocessing)"));
            $adjustedCurrentNode = $this->stack->adjustedCurrentNode;
            $adjustedCurrentNodeName = $this->stack->adjustedCurrentNodeName;
            assert(!$adjustedCurrentNode || $adjustedCurrentNodeName, new \Exception("The adjusted current node must have a name if not null"));
            $adjustedCurrentNodeNamespace = $this->stack->adjustedCurrentNodeNamespace;

            # 13.2.6 Tree construction
            #
            # As each token is emitted from the tokenizer, the user agent must follow the
            # appropriate steps from the following list, known as the tree construction dispatcher:
            #
            # If the stack of open elements is empty
            if (count($this->stack) === 0 ||
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
            }
            # When a start tag token is emitted with its self-closing flag set, if the flag
            #   is not acknowledged when it is processed by the tree construction stage, that
            #   is a non-void-html-element-start-tag-with-trailing-solidus parse error.
            if ($token instanceof StartTagToken && $token->selfClosing && !$token->selfClosingAcknowledged) {
                $this->error(ParseError::NON_VOID_HTML_ELEMENT_START_TAG_WITH_TRAILING_SOLIDUS, $token->name);
            }
            break;
        }
    }

    protected function parseTokenInHTMLContent(Token $token, int $insertionMode = null): bool {
        $iterations = 0;
        ProcessToken:
        assert($iterations++ < 50, new LoopException("Probable infinite loop detected in HTML content handling (inner reprocessing)"));
        $insertionMode = $insertionMode ?? $this->insertionMode;
        assert((function() use ($insertionMode) {
            $mode = self::INSERTION_MODE_NAMES[$insertionMode] ?? $insertionMode;
            $this->debugLog .= "    Mode: $mode (".(string) $this->stack.")\n";
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
                // DEVIATION: PHP's DOM does not allow comments as children of the document
                //   and silently drops them, so this is actually a no-op
                $this->insertCommentToken($token, $this->DOM);
            }
            # A DOCTYPE token
            elseif ($token instanceof DOCTYPEToken) {
                # If the DOCTYPE token's name is not "html", or the token's public identifier is
                #   not missing, or the token's system identifier is neither missing nor 
                #   "about:legacy-compat", then there is a parse error.
                if ($token->name !== 'html' || $token->public !== null || !($token->system === null || $token->system === 'about:legacy-compat')) {
                    $this->error(ParseError::UNKNOWN_DOCTYPE);
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
                $this->DOM->appendChild($this->DOM->implementation->createDocumentType($token->name ?? ' ', $token->public ?? '', $token->system ?? ''));

                
                # Then, if the document is not an iframe srcdoc document, and the DOCTYPE token
                # matches one of the conditions in the following list, then set the Document to
                # quirks mode:
                // DEVIATION: This implementation does not render, so there is no nested
                // browsing contexts to consider.
                $public = strtolower($token->public ?? '');
                $system = strtolower($token->system ?? '');
                if ($token->forceQuirks === true 
                    || $token->name !== 'html' 
                    || $public === '-//w3o//dtd w3 html strict 3.0//en//' 
                    || $public === '-/w3c/dtd html 4.0 transitional/en' 
                    || $public === 'html' 
                    || $system === 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' 
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
                    $this->DOM->quirksMode = Document::QUIRKS_MODE;
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
                    $this->DOM->quirksMode = Document::LIMITED_QUIRKS_MODE;
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
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_START_TAG, $token->name);
                } elseif ($token instanceof EndTagToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_END_TAG, $token->name);
                } elseif ($token instanceof CharacterToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_CHARS);
                } elseif ($token instanceof EOFToken) {
                    $this->error(ParseError::EXPECTED_DOCTYPE_BUT_GOT_EOF);
                } else {
                    throw new \Exception("Unexpected token type".get_class($token));
                }

                $this->DOM->quirksMode = Document::QUIRKS_MODE;

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
                $this->insertStartTagToken($token, $this->DOM);

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
            # by scripts; nothing in particular happens in such cases, content continues
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
            elseif ($token instanceof EndTagToken && $token->name !== 'head' && $token->name !== 'body' && $token->name !== 'html' && $token->name !== 'br') {
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
                    $this->originalInsertionMode = $this->insertionMode;
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
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
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
                    if ($this->stack->find('template') === -1) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # 1. Generate all implied end tags thoroughly.
                        $this->stack->generateImpliedEndTagsThoroughly();
                        # 2. If the current node is not a template element, then this is a parse error.
                        if ($this->stack->currentNodeName !== 'template') {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
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
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
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
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                }
                # Any other start tag
                else {
                    # Act as described in the "anything else" entry below.

                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
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
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED
            #   (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            # A comment token
            elseif ($token instanceof CommentToken || $token instanceof WhitespaceToken) {
                # Process the token using the rules for the "in head" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
            }
            # Anything else
            else {
                # Parse error.
                if ($token instanceof EndTagToken) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                } elseif ($token instanceof CharacterToken) {
                    $this->error(ParseError::UNEXPECTED_CHAR);
                } elseif ($token instanceof EOFToken) {
                    $this->error(ParseError::UNEXPECTED_EOF);
                }
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
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    # Push the node pointed to by the head element pointer onto the stack of open elements.
                    $this->stack[] = $this->headElement;
                    # Process the token using the rules for the "in head" insertion mode.
                    $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                    # Remove the node pointed to by the head element pointer from the stack of open
                    # elements. (It might not be the current node at this point.)
                    $this->stack->removeSame($this->headElement);
                }
                # A start tag whose tag name is "head"
                elseif ($token->name === 'head') {
                    # Parse error. Ignore the token
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
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
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
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
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    # If there is a template element on the stack of open elements, then ignore the
                    # token.
                    if ($this->stack->find('template') === -1) {
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
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    # If the second element on the stack of open elements is not a body element, if
                    # the stack of open elements has only one node on it, or if there is a template
                    # element on the stack of open elements, then ignore the token. (fragment case)
                    if (!(count($this->stack) === 1 || $this->stack[1]->nodeName !== 'body' || $this->stack->find('template') > -1)) {
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
                    if (!(count($this->stack) === 1 || $this->stack[1]->tagName !== 'body' || $this->framesetOk === false)) {
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
                        for ($i = count($this->stack) - 1; $i > 0; $i--) {
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
                # "figcaption", "figure", "footer", "header", "hgroup", "menu", "main", "nav", "ol", "p",
                # "section", "summary", "ul"
                elseif ($token->name === 'address' || $token->name === 'article' || $token->name === 'aside' || $token->name === 'blockquote' || $token->name === 'center' || $token->name === 'details' || $token->name === 'dialog' || $token->name === 'dir' || $token->name === 'div' || $token->name === 'dl' || $token->name === 'fieldset' || $token->name === 'figcaption' || $token->name === 'figure' || $token->name === 'footer' || $token->name === 'header' || $token->name === 'hgroup' || $token->name === 'main' || $token->name === 'menu' || $token->name === 'nav' || $token->name === 'ol' || $token->name === 'p' || $token->name === 'section' || $token->name === 'summary' || $token->name === 'ul') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement($token);
                    }
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "h1", "h2", "h3", "h4", "h5", "h6"
                elseif ($token->name === 'h1' || $token->name === 'h2' || $token->name === 'h3' || $token->name === 'h4' || $token->name === 'h5' || $token->name === 'h6') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement($token);
                    }
                    # If the current node is an HTML element whose tag name is one of "h1", "h2",
                    # "h3", "h4", "h5", or "h6", then this is a parse error; pop the current node
                    # off the stack of open elements.
                    $currentNodeName = $this->stack->currentNodeName;
                    $currentNodeNamespace = $this->stack->currentNodeNamespace;
                    if ($currentNodeNamespace === null && ($currentNodeName === 'h1' || $currentNodeName === 'h2' || $currentNodeName === 'h3' || $currentNodeName === 'h4' || $currentNodeName === 'h5' || $currentNodeName === 'h6')) {
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
                        $this->closePElement($token);
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
                    #   the stack of open elements, then this is a parse error; ignore the token.
                    $templateInStack = ($this->stack->find('template') > -1);
                    if ($this->formElement && !$templateInStack) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    }
                    # Otherwise:
                    else {
                        # If the stack of open elements has a p element in button scope, then close a p
                        # element.
                        if ($this->stack->hasElementInButtonScope('p')) {
                            $this->closePElement($token);
                        }
                        # Insert an HTML element for the token, and, if there is no template element on
                        # the stack of open elements, set the form element pointer to point to the
                        # element created.
                        $form = $this->insertStartTagToken($token);
                        if (!$templateInStack) {
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
                    foreach ($this->stack as $node) {
                        $nodeName = $node->nodeName;
                        if ($nodeName === 'li') {
                            # 1. Generate implied end tags, except for li elements.
                            $this->stack->generateImpliedEndTags("li");
                            # 2. If the current node is not an li element, then this is a parse error.
                            if ($this->stack->currentNodeName !== 'li') {
                                $this->error(ParseError::UNEXPECTED_START_TAG, $nodeName);
                            }
                            # 3. Pop elements from the stack of open elements until an li element has been
                            # popped from the stack.
                            $this->stack->popUntil('li');
                            # 4. Jump to the step labeled Done below.
                            break;
                        }
                        # 4. If node is in the special category, but is not an address, div, or p
                        # element, then jump to the step labeled Done below.
                        if ($nodeName !== 'address' && $nodeName !== 'div' && $nodeName !== 'p' && $this->isElementSpecial($node)) {
                            break;
                        }
                        # 5. Otherwise, set node to the previous entry in the stack of open elements and
                        # return to the step labeled Loop.
                        // The loop handles that.
                    }
                    # 6. Done: If the stack of open elements has a p element in button scope, then
                    # close a p element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement($token);
                    }
                    # 7. Finally, insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "dd", "dt"
                elseif ($token->name === 'dd' || $token->name === 'dt') {
                    # 1. Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # 2. Initialize node to be the current node (the bottommost node of the stack).
                    foreach ($this->stack as $node) {
                        $nodeName = $node->nodeName;
                        // Combining these two sets of instructions as they're identical except for the
                        // element name.
                        # 3. Loop: If node is a dd element, then run these substeps:
                        # 4. If node is a dt element, then run these substeps:
                        if ($nodeName === 'dd' || $nodeName === 'dt') {
                            # 1. Generate implied end tags, except for dd or dt elements.
                            $this->stack->generateImpliedEndTags('dd', 'dt');
                            # 2. If the current node is not a dd or dt element, then this is a parse error.
                            if ($this->stack->currentNodeName !== $nodeName) {
                                $this->error(ParseError::UNEXPECTED_START_TAG, $nodeName);
                            }
                            # 3. Pop elements from the stack of open elements until a dd or dt element has been
                            # popped from the stack.
                            $this->stack->popUntil('dd', 'dt');
                            # 4. Jump to the step labeled Done below.
                            break;
                        }
                        # 5. If node is in the special category, but is not an address, div, or p
                        # element, then jump to the step labeled Done below.
                        if ($nodeName !== 'address' && $nodeName !== 'div' && $nodeName !== 'p' && $this->isElementSpecial($node)) {
                            break;
                        }
                        # 6. Otherwise, set node to the previous entry in the stack of open elements and
                        # return to the step labeled Loop.
                        // The loop handles that.
                    }
                    # 7. Done: If the stack of open elements has a p element in button scope, then
                    # close a p element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement($token);
                    }
                    # 8. Finally, insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is "plaintext"
                elseif ($token->name === 'plaintext') {
                    # If the stack of open elements has a p element in button scope, then close a p
                    # element.
                    if ($this->stack->hasElementInButtonScope('p')) {
                        $this->closePElement($token);
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
                # A start tag whose tag name is "a"
                elseif ($token->name === "a") {
                    # If the list of active formatting elements contains an a element between the end
                    #   of the list and the last marker on the list (or the start of the list if there
                    #   is no marker on the list), then this is a parse error;
                    if (($pos = $this->activeFormattingElementsList->findToMarker("a")) > -1) {
                        $this->error(ParseError::UNEXPECTED_START_TAG_IMPLIES_END_TAG, $token->name);
                        $element = $this->activeFormattingElementsList[$pos]['element'];
                        # ... run the adoption agency algorithm for the token, 
                        $this->adopt($token);
                        # ... then remove that element from the list of active formatting elements and the 
                        #   stack of open elements if the adoption agency algorithm didn't already remove it
                        #   (it might not have if the element is not in table scope).
                        $this->activeFormattingElementsList->removeSame($element);
                        $this->stack->removeSame($element);
                    }
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $element = $this->insertStartTagToken($token);
                    # Push onto the list of active formatting elements that element.
                    $this->activeFormattingElementsList->insert($token, $element);
                }
                # A start tag whose tag name is one of: "b", "big", "code", "em", "font", "i", "s", "small", "strike", "strong", "tt", "u"
                elseif ($token->name === "b" || $token->name === "big" || $token->name === "code" || $token->name === "em" || $token->name === "font" || $token->name === "i" || $token->name === "s" || $token->name === "small" || $token->name === "strike" || $token->name === "strong" || $token->name === "tt" || $token->name === "u") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $element = $this->insertStartTagToken($token);
                    # Push onto the list of active formatting elements that element.
                    $this->activeFormattingElementsList->insert($token, $element);
                }
                # A start tag whose tag name is "nobr"
                elseif ($token->name === "nobr") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # If the stack of open elements has a nobr element in scope, then this is a parse error;
                    if($this->stack->hasElementInScope("nobr")) {
                        $this->error(ParseError::UNEXPECTED_START_TAG_IMPLIES_END_TAG, $token->name);
                        # ... run the adoption agency algorithm for the token, 
                        $this->adopt($token);
                        # ... then once again reconstruct the active formatting elements, if any.
                        $this->activeFormattingElementsList->reconstruct();
                    }
                    # Insert an HTML element for the token.
                    $element = $this->insertStartTagToken($token);
                    # Push onto the list of active formatting elements that element.
                    $this->activeFormattingElementsList->insert($token, $element);
                }
                # A start tag whose tag name is one of: "applet", "marquee", "object"
                elseif ($token->name === "applet" || $token->name === "marquee" || $token->name === "object") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # Insert a marker at the end of the list of active formatting elements.
                    $this->activeFormattingElementsList->insertMarker();
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                }
                # A start tag whose tag name is "table"
                elseif ($token->name === "table") {
                    # If the Document is not set to quirks mode, and the stack of open elements has a p element in button scope, then close a p element.
                    if ($this->DOM->quirksMode !== Document::QUIRKS_MODE && $this->stack->hasElementInButtonScope("p")) {
                        $this->closePElement($token);
                    }
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Switch the insertion mode to "in table".
                    $this->insertionMode = self::IN_TABLE_MODE;
                }
                # A start tag whose tag name is one of: "area", "br", "embed", "img", "keygen", "wbr"
                elseif ($token->name === "area" || $token->name === "br" || $token->name === "embed" || $token->name === "img" || $token->name === "keygen" || $token->name === "wbr") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token. 
                    # Immediately pop the current node off the stack of open elements.
                    $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token's self-closing flag, if it is set.
                    $token->selfClosingAcknowledged = true;
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                }
                # A start tag whose tag name is "input"
                elseif ($token->name === "input") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token. 
                    # Immediately pop the current node off the stack of open elements.
                    $element = $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token's self-closing flag, if it is set.
                    $token->selfClosingAcknowledged = true;
                    # If the token does not have an attribute with the name "type", 
                    #   or if it does, but that attribute's value is not an ASCII 
                    #   case-insensitive match for the string "hidden", then: 
                    #   set the frameset-ok flag to "not ok".
                    // DEVIATION: check the element instead as this is simpler
                    if ($element->getAttribute("type") !== "hidden") {
                        $this->framesetOk = false;
                    }
                }
                # A start tag whose tag name is one of: "param", "source", "track"
                elseif ($token->name === "param" || $token->name === "source" || $token->name === "track") {
                    # Insert an HTML element for the token. Immediately pop the current node off the stack of open elements.
                    $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token's self-closing flag, if it is set.
                    $token->selfClosingAcknowledged = true;
                }
                # A start tag whose tag name is "hr"
                elseif ($token->name === "hr") {
                    # If the stack of open elements has a p element in button scope, then close a p element.
                    if ($this->stack->hasElementInButtonScope("p")) {
                        $this->closePElement($token);
                    }
                    # Insert an HTML element for the token.
                    # Immediately pop the current node off the stack of open elements.
                    $this->insertStartTagToken($token);
                    $this->stack->pop();
                    # Acknowledge the token's self-closing flag, if it is set.
                    $token->selfClosingAcknowledged = true;
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                }
                # A start tag whose tag name is "image"
                elseif ($token->name === "image") {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG_ALIAS, $token->name, "img"); 
                    # Change the token's tag name to "img" and reprocess it. (Don't ask.)
                    $token->name = "img";
                    goto ProcessToken;
                }
                # A start tag whose tag name is "textarea"
                elseif ($token->name === "textarea") {
                    # Run these steps:
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # If the next token is a U+000A LINE FEED (LF) character token, then ignore that token and move on to the next one. (Newlines at the start of textarea elements are ignored as an authoring convenience.)
                    # Switch the tokenizer to the RCDATA state.
                    $this->tokenizer->state = Tokenizer::RCDATA_STATE;
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
                    # Let the original insertion mode be the current insertion mode.
                    $this->originalInsertionMode = $this->insertionMode;
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Switch the insertion mode to "text".
                    $insertionMode = $this->insertionMode = self::TEXT_MODE;
                    // Process the next token
                    $token = $nextToken;
                    goto ProcessToken;
                }
                # A start tag whose tag name is "xmp"
                elseif ($token->name === "xmp") {
                    # If the stack of open elements has a p element in button scope, then close a p element.
                    if ($this->stack->hasElementInButtonScope("p")) {
                        $this->closePElement($token);
                    }
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Follow the generic raw text element parsing algorithm.
                    $this->parseGenericRawText($token);
                }
                # A start tag whose tag name is "iframe"
                elseif ($token->name === "iframe") {
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # Follow the generic raw text element parsing algorithm.
                    $this->parseGenericRawText($token);
                }
                # A start tag whose tag name is "noembed"
                # A start tag whose tag name is "noscript", if the scripting flag is enabled
                // DEVIATION: The scripting flag is always disabled
                elseif ($token->name === "noembed") {
                    # Follow the generic raw text element parsing algorithm.
                    $this->parseGenericRawText($token);
                }
                # A start tag whose tag name is "select"
                elseif ($token->name === "select") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                    # Set the frameset-ok flag to "not ok".
                    $this->framesetOk = false;
                    # If the insertion mode is one of "in table", "in caption", 
                    #   "in table body", "in row", or "in cell", then switch 
                    #   the insertion mode to "in select in table".
                    if (in_array($insertionMode, [
                        self::IN_TABLE_MODE, 
                        self::IN_CAPTION_MODE,
                        self::IN_TABLE_BODY_MODE,
                        self::IN_ROW_MODE,
                        self::IN_CELL_MODE,
                    ])) {
                        $this->insertionMode = self::IN_SELECT_IN_TABLE_MODE;
                    }
                    # Otherwise, switch the insertion mode to "in select".
                    else {
                        $this->insertionMode = self::IN_SELECT_MODE;
                    }
                }
                # A start tag whose tag name is one of: "optgroup", "option"
                elseif ($token->name === "optgroup" || $token->name === "option") {
                    # If the current node is an option element, then pop the current node off the stack of open elements.
                    if ($this->stack->currentNodeName === "option") {
                        $this->stack->pop();
                    }
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "rb", "rtc"
                elseif ($token->name === "rb" || $token->name === "rtc") {
                    # If the stack of open elements has a ruby element in scope, then generate implied end tags. 
                    if ($this->stack->hasElementInScope("ruby")) {
                        $this->stack->generateImpliedEndTags();
                        # If the current node is not now a ruby element, this is a parse error.
                        if ($this->stack->currentNodeName !== "ruby") {
                            $this->error(ParseError::UNEXPECTED_PARENT, $token->name, $this->stack->currentNodeName);
                        }
                    }
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is one of: "rp", "rt"
                elseif ($token->name == "rp" || $token->name === "rt") {
                    # If the stack of open elements has a ruby element in scope,
                    #   then generate implied end tags, except for rtc elements. 
                    if ($this->stack->hasElementInScope("ruby")) {
                        $this->stack->generateImpliedEndTags("etc");
                        # If the current node is not now a rtc element or a ruby element, this is a parse error.
                        if (!in_array($this->stack->currentNodeName, ["rtc", "ruby"])) {
                            $this->error(ParseError::UNEXPECTED_PARENT, $token->name, $this->stack->currentNodeName);
                        }
                    }
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
                # A start tag whose tag name is "math"
                elseif ($token->name === "math") {
                    throw new NotImplementedException("NOT IMPLEMENTED");
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Adjust MathML attributes for the token. (This fixes the case of MathML attributes that are not all lowercase.)      
                    # Adjust foreign attributes for the token. (This fixes the use of namespaced attributes, in particular XLink.)
                    foreach ($token->attributes as $a) {
                        if ($a->name === 'definitionurl') {
                            $a->name = 'definitionURL';
                        }
                        $a->namespace = self::FOREIGN_ATTRIBUTE_NAMESPACE_MAP[$a->name] ?? null;
                    }
                    # Insert a foreign element for the token, in the MathML namespace.
                    $this->insertStartTagToken($token, null, Parser::MATHML_NAMESPACE);
                    # If the token has its self-closing flag set, pop the current node off the stack of open elements and acknowledge the token's self-closing flag.
                    if ($token->selfClosing) {
                        $this->stack->pop();
                        $token->selfClosingAcknowledged = true;
                    }
                }
                # A start tag whose tag name is "svg"
                elseif ($token->name === "svg") {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Adjust SVG attributes for the token. (This fixes the case of SVG attributes that are not all lowercase.)
                    # Adjust foreign attributes for the token. (This fixes the use of namespaced attributes, in particular XLink in SVG.)
                    foreach ($token->attributes as $a) {
                        $a->name = self::SVG_ATTR_NAME_MAP[$a->name] ?? $a->name;
                        $a->namespace = self::FOREIGN_ATTRIBUTE_NAMESPACE_MAP[$a->name] ?? null;
                    }
                    # Insert a foreign element for the token, in the SVG namespace.
                    $this->insertStartTagToken($token, null, Parser::SVG_NAMESPACE);
                    # If the token has its self-closing flag set, pop the current node off the stack of open elements and acknowledge the token's self-closing flag.
                    if ($token->selfClosing) {
                        $this->stack->pop();
                        $token->selfClosingAcknowledged = true;
                    }
                }
                # A start tag whose tag name is one of: "caption", "col", "colgroup", "frame", "head", "tbody", "td", "tfoot", "th", "thead", "tr"
                elseif ($token->name === "caption" || $token->name === "col" || $token->name === "colgroup" || $token->name === "frame" || $token->name === "head" || $token->name === "tbody" || $token->name === "td" || $token->name === "tfoot" || $token->name === "th" || $token->name === "thead" || $token->name === "tr") {
                    # Parse error. Ignore the token.
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                } 
                # Any other start tag
                else {
                    # Reconstruct the active formatting elements, if any.
                    $this->activeFormattingElementsList->reconstruct();
                    # Insert an HTML element for the token.
                    $this->insertStartTagToken($token);
                }
            }
            # An end tag...
            elseif ($token instanceof EndTagToken) {
                # An end tag whose tag name is "template"
                if ($token->name === 'template') {
                    # Process the token using the rules for the "in head" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                # An end tag whose tag name is "body"
                # An end tag whose tag name is "html"
                elseif ($token->name === 'body' || $token->name === 'html') {
                    # If the stack of open elements does not have a body element in scope, this is a
                    # parse error; ignore the token.
                    if (!$this->stack->hasElementInScope('body')) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, if there is a node in the stack of open elements that is not either
                    # a dd element, a dt element, an li element, an optgroup element, an option
                    # element, a p element, an rb element, an rp element, an rt element, an rtc
                    # element, a tbody element, a td element, a tfoot element, a th element, a thead
                    # element, a tr element, the body element, or the html element, then this is a
                    # parse error.
                    else {
                        if ($this->stack->findNot('dd', 'dt', 'li', 'optgroup', 'option', 'p', 'rb', 'rp', 'rt', 'rtc', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'body', 'html') > -1) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # Switch the insertion mode to "after body".
                        $insertionMode = $this->insertionMode = self::AFTER_BODY_MODE;
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
                # "fieldset", "figcaption", "figure", "footer", "header", "hgroup", "listing",
                #  "main", "menu", "nav", "ol", "pre", "section", "summary", "ul"
                elseif ($token->name === 'address' || $token->name === 'article' || $token->name === 'aside' || $token->name === 'blockquote' || $token->name === 'button' || $token->name === 'center' || $token->name === 'details' || $token->name === 'dialog' || $token->name === 'dir' || $token->name === 'div' || $token->name === 'dl' || $token->name === 'fieldset' || $token->name === 'figcaption' || $token->name === 'figure' || $token->name === 'footer' || $token->name === 'header' || $token->name === 'hgroup' || $token->name === 'listing' || $token->name === 'main' || $token->name === 'menu' || $token->name === 'nav' || $token->name === 'ol' || $token->name === 'pre' || $token->name === 'section' || $token->name === 'summary' || $token->name === 'ul') {
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
                    if ($this->stack->find('template') === -1) {
                        # 1. Let node be the element that the form element pointer is set to, 
                        #  or null if it is not set to an element.
                        $node = $this->formElement;
                        # 2. Set the form element pointer to null.
                        $this->formElement = null;
                        # 3. If node is null or if the stack of open elements does not have node in
                        # scope, then this is a parse error; return and ignore the token.
                        if (!$node || !$this->stack->hasElementInScope($node)) {
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
                        $this->stack->removeSame($node);
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
                # An end tag whose tag name is "p"
                elseif ($token->name === "p") {
                    # If the stack of open elements does not have a p element in button scope, then this is a parse error; 
                    if (!$this->stack->hasElementInButtonScope("p")) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        # insert an HTML element for a "p" start tag token with no attributes.
                        $this->insertStartTagToken(new StartTagToken("p"));
                    }
                    # Close a p element.
                    $this->closePElement($token);
                }
                # An end tag whose tag name is "li"
                elseif ($token->name === "li") {
                    # If the stack of open elements does not have an li element in 
                    #   list item scope, then this is a parse error; ignore the token.
                    if (!$this->stack->hasElementInListItemScope("li")) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # Generate implied end tags, except for li elements.
                        $this->stack->generateImpliedEndTags("li");
                        # If the current node is not an li element, then this is a parse error.
                        if ($this->stack->currentNodeName !== "li" || $this->stack->currentNodeNamespace !== null) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # Pop elements from the stack of open elements until an li element has been popped from the stack.
                        $this->stack->popUntil("li");
                    }
                }
                # An end tag whose tag name is one of: "dd", "dt"
                elseif ($token->name === "dd" || $token->name === "dt") {
                    # If the stack of open elements does not have an element in 
                    #   scope that is an HTML element with the same tag name as that of 
                    #   the token, then this is a parse error; ignore the token.
                    if (!$this->stack->hasElementInScope($token->name)) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # Generate implied end tags, except for HTML elements 
                        #   with the same tag name as the token.
                        $this->stack->generateImpliedEndTags($token->name);
                        # If the current node is not an HTML element with the same 
                        #   tag name as that of the token, then this is a parse error.
                        if ($this->stack->currentNodeName !== $token->name || $this->stack->currentNodeNamespace !== null) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # Pop elements from the stack of open elements until an HTML 
                        #   element with the same tag name as the token has been 
                        #   popped from the stack.
                        $this->stack->popUntil($token->name);
                    }
                }
                # An end tag whose tag name is one of: "h1", "h2", "h3", "h4", "h5", "h6"
                elseif ($token->name === 'h1' || $token->name === 'h2' || $token->name === 'h3' || $token->name === 'h4' || $token->name === 'h5' || $token->name === 'h6') {
                    # If the stack of open elements does not have an element in scope
                    #   that is an HTML element and whose tag name is one of "h1", "h2",
                    #   "h3", "h4", "h5", or "h6", then this is a parse error; ignore the token.
                    if (!$this->stack->hasElementInScope("h1", "h2", "h3", "h4", "h5")) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # Generate implied end tags.
                        $this->stack->generateImpliedEndTags();
                        # If the current node is not an HTML element with the same tag name
                        #   as that of the token, then this is a parse error.
                        if ($this->stack->currentNodeName !== $token->name || $this->stack->currentNodeNamespace !== null) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # Pop elements from the stack of open elements until an HTML 
                        #   element whose tag name is one of "h1", "h2", "h3", "h4", 
                        #   "h5", or "h6" has been popped from the stack.
                        $this->stack->popUntil("h1", "h2", "h3", "h4", "h5", "h6");
                    }
                }
                # An end tag whose tag name is "sarcasm"
                    # Take a deep breath, then act as described in the "any other end tag" entry below.
                # An end tag whose tag name is one of: "a", "b", "big", "code", "em", "font", "i", "nobr", "s", "small", "strike", "strong", "tt", "u"
                elseif ($token->name === "a" || $token->name === "b" || $token->name === "big" || $token->name === "code" || $token->name === "em" || $token->name === "font" || $token->name === "i" || $token->name === "nobr" || $token->name === "s" || $token->name === "small" || $token->name === "strike" || $token->name === "strong" || $token->name === "tt" || $token->name === "u") {
                    # Run the adoption agency algorithm for the token.
                    $this->adopt($token);
                }
                # An end tag token whose tag name is one of: "applet", "marquee", "object"
                elseif ($token->name === "applet" || $token->name === "marquee" || $token->name === "object") {
                    # If the stack of open elements does not have an element in scope that 
                    #   is an HTML element with the same tag name as that of the token, then
                    #   this is a parse error; ignore the token.
                    if (!$this->stack->hasElementInScope($token->name)) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise, run these steps:
                    else {
                        # Generate implied end tags.
                        $this->stack->generateImpliedEndTags();
                        # If the current node is not an HTML element with the same tag 
                        #   name as that of the token, then this is a parse error.
                        if ($this->stack->currentNodeName !== $token->name || $this->stack->currentNodeNamespace !== null) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                        }
                        # Pop elements from the stack of open elements until an HTML 
                        #   element with the same tag name as the token has been 
                        #   popped from the stack.
                        $this->stack->popUntil($token->name);
                        # Clear the list of active formatting elements up to the last marker.
                        $this->activeFormattingElementsList->clearToTheLastMarker();
                    }
                }
                # An end tag whose tag name is "br"
                elseif ($token->name === "br") {
                    # Parse error. Drop the attributes from the token, and act as described 
                    #   in the next entry; i.e. act as if this was a "br" start tag token with
                    #   no attributes, rather than the end tag token that it actually is.
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    return $this->parseTokenInHTMLContent(new StartTagToken("br"));
                }
                # Any other end tag
                else {
                    // NOTE: This logic is reproduced in the adoption agency below.
                    //   Changes here should be mirrored there, and vice versa
                    # Run these steps:
                    # Initialize node to be the current node (the bottommost node of the stack).
                    foreach ($this->stack as $node) {
                        # Loop: If node is an HTML element with the same tag name as the token, then:
                        if ($node->nodeName === $token->name && $node->namespaceURI === null) {
                            # Generate implied end tags, except for HTML elements with the same tag name as the token.
                            $this->stack->generateImpliedEndTags($token->name);
                            # If node is not the current node, then this is a parse error.
                            if (!$node->isSameNode($this->stack->currentNode)) {
                                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                            }
                            # Pop all the nodes from the current node up to node, including node, then stop these steps.
                            $this->stack->popUntilSame($node);
                            return true;
                        }
                        # Otherwise, if node is in the special category, then 
                        #   this is a parse error; ignore the token, and return.
                        elseif ($this->isElementSpecial($node)) {
                            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                            return true;
                        }
                        # Set node to the previous entry in the stack of open elements.
                        # Return to the step labeled loop.
                    }
                }
            }
            # An end-of-file token
            elseif ($token instanceof EOFToken) {
                # If the stack of template insertion modes is not empty, then process the token using the rules for the "in template" insertion mode.
                if (count($this->templateInsertionModes) !== 0) {
                    return $this->parseTokenInHTMLContent($token, self::IN_TEMPLATE_MODE);
                }

                # Otherwise, follow these steps:
                # 1. If there is a node in the stack of open elements that is not either a dd
                # element, a dt element, an li element, an optgroup element, an option element,
                # a p element, an rb element, an rp element, an rt element, an rtc element, a
                # tbody element, a td element, a tfoot element, a th element, a thead element, a
                # tr element, the body element, or the html element, then this is a parse error.
                if ($this->stack->findNot('dd', 'dt', 'li', 'optgroup', 'option', 'p', 'rb', 'rp', 'rt', 'rtc', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'body', 'html') > -1) {
                    $this->error(ParseError::UNEXPECTED_EOF);
                }

                # 2. Stop parsing.
                return true;
            }
        }
        # 13.2.6.4.8 The "text" insertion mode
        elseif ($insertionMode === self::TEXT_MODE) {
            # A character token
            if ($token instanceof CharacterToken) {
                # Insert the token's character.
                $this->insertCharacterToken($token);
            }
            # An end-of-file token
            elseif ($token instanceof EOFToken) {
                # Parse error.
                $this->error(ParseError::UNEXPECTED_EOF);
                # If the current node is a script element, mark the script 
                #   element as "already started".
                // DEVIATION: Scripting is not supported
                # Pop the current node off the stack of open elements.
                $this->stack->pop();
                # Switch the insertion mode to the original insertion mode and
                #   reprocess the token.
                $insertionMode = $this->insertionMode = $this->originalInsertionMode;
                goto ProcessToken;
            }
            # An end tag whose tag name is "script"
            // DEVIATION: Scripting is not supported, so there is no special handling
            # Any other end tag
            elseif ($token instanceof EndTagToken) {
                # Pop the current node off the stack of open elements.
                $this->stack->pop();
                # Switch the insertion mode to the original insertion mode.
                $this->insertionMode = $this->originalInsertionMode;
            }
            else {
                assert(false, new \Exception("UNREACHABLE CODE")); // @codeCoverageIgnore
            }
        }
        # 13.2.6.4.9 The "in table" insertion mode
        elseif ($insertionMode === self::IN_TABLE_MODE) {
            # A character token, if the current node is table, tbody, tfoot, thead, or tr element
            if ($token instanceof CharacterToken && in_array($this->stack->currentNodeName, ["table", "tbody", "tfoot", "thead", "tr"])) {
                # Let the pending table character tokens be an empty list of tokens.
                $this->pendingTableCharacterTokens = [];
                # Let the original insertion mode be the current insertion mode.
                $this->originalInsertionMode = $this->insertionMode;
                # Switch the insertion mode to "in table text" and reprocess the token.
                $insertionMode = $this->insertionMode = self::IN_TABLE_TEXT_MODE;
                goto ProcessToken;
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
                # A start tag whose tag name is "caption"
                if ($token->name === "caption") {
                    # Clear the stack back to a table context. (See below.)
                    $this->stack->clearToTableContext();
                    # Insert a marker at the end of the list of active
                    #   formatting elements.
                    $this->activeFormattingElementsList->insertMarker();
                    # Insert an HTML element for the token, then switch the
                    #   insertion mode to "in caption".
                    $this->insertStartTagToken($token);
                    $this->insertionMode = self::IN_CAPTION_MODE;
                }
                # A start tag whose tag name is "colgroup"
                elseif ($token->name === "colgroup") {
                    # Clear the stack back to a table context. (See below.)
                    $this->stack->clearToTableContext();
                    # Insert an HTML element for the token, then switch the
                    #   insertion mode to "in column group".
                    $this->insertStartTagToken($token);
                    $this->insertionMode = self::IN_COLUMN_GROUP_MODE;
                }
                # A start tag whose tag name is "col"
                elseif ($token->name === "col") {
                    # Clear the stack back to a table context. (See below.)
                    $this->stack->clearToTableContext();
                    # Insert an HTML element for a "colgroup" start tag token
                    #   with no attributes, then switch the insertion mode to
                    #   "in column group".
                    $this->insertStartTagToken(new StartTagToken("colgroup"));
                    $insertionMode = $this->insertionMode = self::IN_COLUMN_GROUP_MODE;
                    # Reprocess the current token.
                    goto ProcessToken;
                }
                # A start tag whose tag name is one of: "tbody", "tfoot", "thead"
                elseif ($token->name === "tbody" || $token->name === "tfoot" || $token->name === "thead") {
                    # Clear the stack back to a table context. (See below.)
                    $this->stack->clearToTableContext();
                    # Insert an HTML element for the token, then switch the
                    #   insertion mode to "in table body".
                    $this->insertStartTagToken($token);
                    $this->insertionMode = self::IN_TABLE_BODY_MODE;
                }
                # A start tag whose tag name is one of: "td", "th", "tr"
                elseif ($token->name === "td" || $token->name === "th" || $token->name === "tr") {
                    # Clear the stack back to a table context. (See below.)
                    $this->stack->clearToTableContext();
                    # Insert an HTML element for a "tbody" start tag token
                    #   with no attributes, then switch the insertion mode
                    #   to "in table body".
                    $this->insertStartTagToken(new StartTagToken("tbody"));
                    $insertionMode = $this->insertionMode = self::IN_TABLE_BODY_MODE;
                    # Reprocess the current token.
                    goto ProcessToken;
                }
                # A start tag whose tag name is "table"
                elseif ($token->name === "table") {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    # If the stack of open elements does not have a table
                    #   element in table scope, ignore the token.
                    if (!$this->stack->hasElementInTableScope("table")) {
                        // Ignore the token
                    }
                    # Otherwise:
                    else {
                        # Pop elements from this stack until a table element
                        #   has been popped from the stack.
                        $this->stack->popUntil("table");
                        # Reset the insertion mode appropriately.
                        $insertionMode = $this->resetInsertionMode();
                        # Reprocess the token.
                        goto ProcessToken;
                    }
                }
                # A start tag whose tag name is one of: "style", "script", "template"
                elseif ($token->name === "style" || $token->name === "script" || $token->name === "template") {
                    # Process the token using the rules for the "in head" insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                # A start tag whose tag name is "input"
                elseif ($token->name === "input") {
                    # If the token does not have an attribute with the name
                    #   "type", or if it does, but that attribute's value is
                    #   not an ASCII case-insensitive match for the string
                    #   "hidden", then: act as described in the
                    #   "anything else" entry below.
                    if (!$token->hasAttribute("type") || strtolower($token->getAttribute("type")->value) !== "hidden") {
                        goto InTableAnythingElse;
                    }
                    # Otherwise:
                    else {
                        # Parse error.
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                        # Insert an HTML element for the token.
                        $this->insertStartTagToken($token);
                        # Pop that input element off the stack of open elements.
                        $this->stack->pop();
                        # Acknowledge the token's self-closing flag, if it is set.
                        $token->selfClosingAcknowledged = true;
                    }
                }
                # A start tag whose tag name is "form"
                elseif ($token->name === "form") {
                    # Parse error.
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    # If there is a template element on the stack of open
                    #   elements, or if the form element pointer is not null,
                    #   ignore the token.
                    if ($this->formElement || $this->stack->find("template") > -1) {
                        // Ignore the token
                    }
                    # Otherwise:
                    else {
                        # Insert an HTML element for the token, and set the form
                        #   element pointer to point to the element created.
                        $element = $this->insertStartTagToken($token);
                        $this->formElement = $element;
                        # Pop that form element off the stack of open elements.
                        $this->stack->pop();
                    }
                }
                // Any other start tag
                else {
                    goto InTableAnythingElse;
                }
            }
            # An end tag...
            elseif ($token instanceof EndTagToken) {
                # An end tag whose tag name is "table"
                if ($token->name === "table") {
                    # If the stack of open elements does not have a table
                    #   element in table scope, this is a parse error;
                    #   ignore the token.
                    if (!$this->stack->hasElementInTableScope("table")) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Otherwise:
                    else {
                        # Pop elements from this stack until a table element
                        #   has been popped from the stack.
                        $this->stack->popUntil("table");
                        # Reset the insertion mode appropriately.
                        $this->resetInsertionMode();
                    }
                }
                # An end tag whose tag name is one of: "body", "caption",
                #   "col", "colgroup", "html", "tbody", "td", "tfoot", "th",
                #   "thead", "tr"
                elseif (in_array($token->name, ["body", "caption", "col", "colgroup", "html", "tbody", "td", "tfoot", "th", "thead", "tr"])) {
                    # Parse error. Ignore the token.
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # An end tag whose tag name is "template"
                elseif ($token->name === "template") {
                    # Process the token using the rules for the "in head"
                    #   insertion mode.
                    return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
                }
                // Any other end tag
                else {
                    goto InTableAnythingElse;
                }
            }
            # An end-of-file token
            elseif ($token instanceof EOFToken) {
                # Process the token using the rules for the "in body"
                #   insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
            # Anything else
            else {
                InTableAnythingElse:
                # Parse error. Enable foster parenting, process the token
                #   using the rules for the "in body" insertion mode, and
                #   then disable foster parenting.
                // TODO: parse error
                $this->fosterParenting = true;
                $result = $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
                $this->fosterParenting = false;
                return $result;
            }
        }
        # 13.2.6.4.10 The "in table text" insertion mode
        elseif ($insertionMode === self::IN_TABLE_TEXT_MODE) {
            # A character token that is U+0000 NULL
            if ($token instanceof CharacterToken && $token->data === "\0") {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_NULL_CHARACTER);
            }
            # Any other character token
            elseif ($token instanceof CharacterToken) {
                # Append the character token to the pending table character
                #   tokens list.
                $this->pendingTableCharacterTokens[] = $token;
            }
            # Anything else
            else {
                $ws = true;
                foreach ($this->pendingTableCharacterTokens as $pending) {
                    if (!$pending instanceof WhitespaceToken) {
                        $ws = false;
                        break;
                    }
                }
                # If any of the tokens in the pending table character tokens
                #   list are character tokens that are not ASCII whitespace, 
                #   then this is a parse error: reprocess the character tokens
                #   in the pending table character tokens list using the rules
                #   given in the "anything else" entry in the "in table"
                #   insertion mode.
                if (!$ws) {
                    $this->error(ParseError::UNEXPECTED_CHAR);
                    $this->fosterParenting = true;
                    foreach ($this->pendingTableCharacterTokens as $pending) {
                        $this->parseTokenInHTMLContent($pending, self::IN_BODY_MODE);
                    }
                    $this->fosterParenting = false;
                }
                # Otherwise, insert the characters given by the pending table
                #   character tokens list.
                else {
                    foreach ($this->pendingTableCharacterTokens as $pending) {
                        $this->insertCharacterToken($pending);
                    }
                }
                $this->pendingTableCharacterTokens = [];
                # Switch the insertion mode to the original insertion mode
                #   and reprocess the token.
                $insertionMode = $this->insertionMode = $this->originalInsertionMode;
                goto ProcessToken;
            }
        }
        # 13.2.6.4.11 The "in caption" insertion mode
        elseif ($insertionMode === self::IN_CAPTION_MODE) {
            # An end tag whose tag name is "caption"
            if ($token instanceof EndTagToken && $token->name === "caption") {
                # If the stack of open elements does not have a caption
                #   element in table scope, this is a parse error; ignore
                #   the token. (fragment case)
                if (!$this->stack->hasElementInTableScope("caption")) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise:
                else {
                    # Generate implied end tags.
                    $this->stack->generateImpliedEndTags();
                    # Now, if the current node is not a caption element,
                    #   then this is a parse error.
                    if ($this->stack->currentNodeName !== "caption") {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Pop elements from this stack until a caption element
                    #   has been popped from the stack.
                    $this->stack->popUntil("caption");
                    # Clear the list of active formatting elements up to
                    #   the last marker.
                    $this->activeFormattingElementsList->clearToTheLastMarker();
                    # Switch the insertion mode to "in table".
                    $this->insertionMode = self::IN_TABLE_MODE;
                }
            }
            # A start tag whose tag name is one of: "caption", "col",
            #   "colgroup", "tbody", "td", "tfoot", "th", "thead", "tr"
            # An end tag whose tag name is "table"
            elseif (
                ($token instanceof StartTagToken && in_array($token->name, ["caption", "col", "colgroup", "tbody", "td", "tfoot", "th", "thead", "tr"]))
                || ($token instanceof EndTagToken && $token->name === "table")
            ) {
                $errorCode = ($token instanceof StartTagToken) ? ParseError::UNEXPECTED_START_TAG : ParseError::UNEXPECTED_END_TAG;    
                # If the stack of open elements does not have a caption
                #   element in table scope, this is a parse error; ignore
                #   the token. (fragment case)
                if (!$this->stack->hasElementInTableScope("caption")) {
                    $this->error($errorCode, $token->name);
                }
                # Otherwise:
                else {
                    # Generate implied end tags.
                    $this->stack->generateImpliedEndTags();
                    # Now, if the current node is not a caption element,
                    #   then this is a parse error.
                    if ($this->stack->currentNodeName !== "caption") {
                        $this->error($errorCode, $token->name);
                    }
                    # Pop elements from this stack until a caption element
                    #   has been popped from the stack.
                    $this->stack->pop("caption");
                    # Clear the list of active formatting elements up to
                    #   the last marker.
                    $this->activeFormattingElementsList->clearToTheLastMarker();
                    # Switch the insertion mode to "in table".
                    $insertionMode = $this->insertionMode = self::IN_TABLE_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is one of: "body", "col", "colgroup",
            # "html", "tbody", "td", "tfoot", "th", "thead", "tr"
            elseif ($token instanceof EndTagToken && in_array($token->name, ["body", "col", "colgroup", "html", "tbody", "td", "tfoot", "th", "thead", "tr"])) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # Anything else
            else {
                # Process the token using the rules for the "in body" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
        }
        # 13.2.6.4.12 The "in column group" insertion mode
        elseif ($insertionMode === self::IN_COLUMN_GROUP_MODE) {
            # A character token that is one of U+0009 CHARACTER TABULATION,
            #   U+000A LINE FEED (LF), U+000C FORM FEED (FF), 
            #   U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
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
            # A start tag whose tag name is "html"
            elseif ($token instanceof StartTagToken && $token->name === "html") {
                # Process the token using the rules for the "in body"
                #   insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
            # A start tag whose tag name is "col"
            elseif ($token instanceof StartTagToken && $token->name === "col") {
                # Insert an HTML element for the token. Immediately pop
                #   the current node off the stack of open elements.
                $this->insertStartTagToken($token);
                $this->stack->pop();
                # Acknowledge the token's self-closing flag, if it is set.
                $token->selfClosingAcknowledged = true;
            }
            # An end tag whose tag name is "colgroup"
            elseif ($token instanceof EndTagToken && $token->name === "colgroup") {
                # If the current node is not a colgroup element,
                #   then this is a parse error; ignore the token.
                if (!$this->stack->currentNodeName !== "colgroup") {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise, pop the current node from the stack of open
                #   elements. Switch the insertion mode to "in table".
                else {
                    $this->stack->pop();
                    $this->insertionMode = self::IN_TABLE_MODE;
                }
            }
            # An end tag whose tag name is "col"
            elseif ($token instanceof EndTagToken && $token->name === "col") {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # A start tag whose tag name is "template"
            # An end tag whose tag name is "template"
            elseif ($token instanceof TagToken && $token->name === "template") {
                # Process the token using the rules for
                #   the "in head" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_HEAD_MODE);
            }
            # An end-of-file token
            elseif ($token instanceof EOFToken) {
                # Process the token using the rules for
                #   the "in body" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
            # Anything else
            else {
                # If the current node is not a colgroup element, then this
                #   is a parse error; ignore the token.
                if ($this->stack->currentNodeName !== "colgroup") {
                    if ($token instanceof CharacterToken) {
                        $this->error(ParseError::UNEXPECTED_CHAR);
                    } elseif ($token instanceof StartTagToken) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);    
                    } elseif ($token instanceof EndTagToken) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                }
                # Otherwise, pop the current node from the stack
                #   of open elements.
                # Switch the insertion mode to "in table".
                # Reprocess the token.
                else {
                    $this->stack->pop();
                    $insertionMode = $this->insertionMode = self::IN_TABLE_MODE;
                    goto ProcessToken;
                }
            }
        }
        # 13.2.6.4.13 The "in table body" insertion mode
        elseif ($insertionMode === self::IN_TABLE_BODY_MODE) {
            # A start tag whose tag name is "tr"
            if ($token instanceof StartTagToken && $token->name === "tr") {
                # Clear the stack back to a table body context. (See below.)
                $this->stack->clearToTableBodyContext();
                # Insert an HTML element for the token, then switch the
                #   insertion mode to "in row".
                $this->insertStartTagToken($token);
                $this->insertionMode = self::IN_ROW_MODE;
            }
            # A start tag whose tag name is one of: "th", "td"
            elseif ($token instanceof StartTagToken && ($token->name === "td" || $token->name === "th")) {
                # Parse error.
                $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                # Clear the stack back to a table body context. (See below.)
                $this->stack->clearToTableBodyContext();
                # Insert an HTML element for a "tr" start tag token with no
                #   attributes, then switch the insertion mode to "in row".
                $this->insertStartTagToken(new StartTagToken("tr"));
                $insertionMode = $this->insertionMode = self::IN_ROW_MODE;
                # Reprocess the current token.
                goto ProcessToken;
            }
            # An end tag whose tag name is one of: "tbody", "tfoot", "thead"
            elseif ($token instanceof EndTagToken && ($token->name === "tbody" || $token->name === "tfoot" || $token->name === "thead")) {
                # If the stack of open elements does not have an element in
                # table scope that is an HTML element with the same tag name
                # as the token, this is a parse error; ignore the token.
                if (!$this->stack->hasElementInTableScope($token->name)) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise:
                else {
                    # Clear the stack back to a table body context.
                    $this->stack->clearToTableBodyContext();
                    # Pop the current node from the stack of open elements.
                    $this->stack->pop();
                    # Switch the insertion mode to "in table".
                    $this->insertionMode = self::IN_TABLE_MODE;
                }
            }
            # A start tag whose tag name is one of: "caption", "col",
            #   "colgroup", "tbody", "tfoot", "thead"
            # An end tag whose tag name is "table"
            elseif (
                ($token instanceof StartTagToken && in_array($token->name, ["caption", "col", "colgroup", "tbody", "tfoot", "thead"]))
                || ($token instanceof EndTagToken && $token->name === "table")
            ) {
                # If the stack of open elements does not have a tbody, thead,
                #   or tfoot element in table scope, this is a parse error;
                #   ignore the token.
                if (!$this->stack->hasElementInTableScope("tbody", "tfoot", "thead")) {
                    if ($token instanceof StartTagToken) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    } else {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                }
                # Otherwise:
                else {
                    # Clear the stack back to a table body context.
                    $this->stack->clearToTableBodyContext();
                    # Pop the current node from the stack of open elements.
                    $this->stack->pop();
                    # Switch the insertion mode to "in table".
                    $insertionMode = $this->insertionMode = self::IN_TABLE_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is one of: "body", "caption", "col",
            #   "colgroup", "html", "td", "th", "tr"
            elseif ($token instanceof EndTagToken && in_array($token->name, ["body", "caption", "col", "colgroup", "html", "td", "th", "tr"])) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # Anything else
            else {
                # Process the token using the rules for
                # the "in table" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_TABLE_MODE);
            }
        }
        # 13.2.6.4.14 The "in row" insertion mode
        elseif ($insertionMode === self::IN_ROW_MODE) {
            # A start tag whose tag name is one of: "th", "td"
            if ($token instanceof StartTagToken && ($token->name === "th" || $token->name === "td")) {
                # Clear the stack back to a table row context.
                $this->stack->clearToTableRowContext();
                # Insert an HTML element for the token, then
                #   switch the insertion mode to "in cell".
                $this->insertStartTagToken($token);
                $this->insertionMode = self::IN_CELL_MODE;
                # Insert a marker at the end of the list of active
                #   formatting elements.
                $this->activeFormattingElementsList->insertMarker();
            }
            # An end tag whose tag name is "tr"
            elseif ($token instanceof EndTagToken && $token->name === "tr") {
                # If the stack of open elements does not have a tr element
                #   in table scope, this is a parse error; ignore the token.
                if (!$this->stack->hasElementInTableScope("tr")) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise:
                else {
                    # Clear the stack back to a table row context.
                    $this->stack->clearToTableRowContext();
                    # Pop the current node (which will be a tr element) from
                    #   the stack of open elements. Switch the insertion
                    #   mode to "in table body".
                    $this->stack->pop();
                    $this->insertionMode = self::IN_TABLE_BODY_MODE;
                }
            }
            # A start tag whose tag name is one of: "caption", "col",
            #   "colgroup", "tbody", "tfoot", "thead", "tr"
            # An end tag whose tag name is "table"
            elseif (
                ($token instanceof StartTagToken && in_array($token->name, ["caption", "col", "colgroup", "tbody", "tfoot", "thead", "tr"]))
                || ($token instanceof EndTagToken && $token->name === "table")
            ) {
                # If the stack of open elements does not have a tr element
                #   in table scope, this is a parse error; ignore the token.
                if (!$this->stack->hasElementInTableScope("tr")) {
                    if ($token instanceof StartTagToken) {
                        $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                    } else {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                }
                # Otherwise:
                else {
                    # Clear the stack back to a table row context.
                    $this->stack->clearToTableRowContext();
                    # Pop the current node (which will be a tr element)
                    #   from the stack of open elements. Switch the
                    #   insertion mode to "in table body".
                    $this->stack->pop();
                    $insertionMode = $this->insertionMode = self::IN_TABLE_BODY_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is one of: "tbody", "tfoot", "thead"
            elseif ($token instanceof EndTagToken && ($token->name === "tbody" || $token->name === "tfoot" || $token->name === "thead")) {
                # If the stack of open elements does not have an element
                #   in table scope that is an HTML element with the same
                #   tag name as the token, this is a parse error;
                #   ignore the token.
                if (!$this->stack->hasElementInTableScope($token->name)) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # If the stack of open elements does not have a tr element
                #   in table scope, ignore the token.
                elseif (!$this->stack->hasElementInTableScope("tr")) {
                    // Ignore the token
                }
                # Otherwise:
                else {
                    # Clear the stack back to a table row context.
                    $this->stack->clearToTableRowContext();
                    # Pop the current node (which will be a tr element) from
                    # the stack of open elements. Switch the insertion mode
                    # to "in table body".
                    $this->stack->pop();
                    $insertionMode = $this->insertionMode = self::IN_TABLE_BODY_MODE;
                    # Reprocess the token.
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is one of: "body", "caption", "col",
            #   "colgroup", "html", "td", "th"
            elseif ($token instanceof EndTagToken && in_array($token->name, ["body", "caption", "col", "colgroup", "html", "td", "th"])) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # Anything else
            else {
                # Process the token using the rules for the
                #   "in table" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_TABLE_MODE);
            }
        }
        # 13.2.6.4.15 The "in cell" insertion mode
        elseif ($insertionMode === self::IN_CELL_MODE) {
            # An end tag whose tag name is one of: "td", "th"
            if ($token instanceof EndTagToken && ($token->name === "td" || $token->name === "th")) {
                # If the stack of open elements does not have an element in
                #   table scope that is an HTML element with the same tag
                #   name as that of the token, then this is a parse error;
                #   ignore the token.
                if (!$this->stack->hasElementInTableScope($token->name)) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise:
                else {
                    # Generate implied end tags.
                    $this->stack->generateImpliedEndTags();
                    # Now, if the current node is not an HTML element with
                    #   the same tag name as the token, then this is
                    #   a parse error.
                    if (!$this->stack->hasElementInTableScope($token->name)) {
                        $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                    }
                    # Pop elements from the stack of open elements stack
                    #   until an HTML element with the same tag name as the
                    #   token has been popped from the stack.
                    $this->stack->popUntil($token->name);
                    # Clear the list of active formatting elements up to the last marker.
                    $this->activeFormattingElementsList->clearToTheLastMarker();
                    # Switch the insertion mode to "in row".
                    $this->insertionMode = self::IN_ROW_MODE;
                }
            }
            # A start tag whose tag name is one of: "caption", "col",
            #   "colgroup", "tbody", "td", "tfoot", "th", "thead", "tr"
            elseif ($token instanceof StartTagToken && in_array($token->name, ["caption", "col", "colgroup", "tbody", "td", "tfoot", "th", "thead", "tr"])) {
                # If the stack of open elements does not have a td or th
                #   element in table scope, then this is a parse error;
                #   ignore the token. (fragment case)
                if (!$this->stack->hasElementInTableScope("td", "th")) {
                    $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                }
                # Otherwise, close the cell (see below) and reprocess the token.
                else {
                    $insertionMode = $this->closeCell($token);
                    goto ProcessToken;
                }
            }
            # An end tag whose tag name is one of: "body", "caption", "col",
            #   "colgroup", "html"
            elseif ($token instanceof EndTagToken && in_array($token->name, ["body", "caption", "col", "colgroup", "html"])) {
                # Parse error. Ignore the token.
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # An end tag whose tag name is one of: "table", "tbody",
            #   "tfoot", "thead", "tr"
            elseif ($token instanceof EndTagToken && in_array($token->name, ["table", "tbody", "tfoot", "thead", "tr"])) {
                # If the stack of open elements does not have an element in
                #   table scope that is an HTML element with the same tag
                #   name as that of the token, then this is a parse error;
                #   ignore the token.
                if (!$this->stack->hasElementInTableScope($token->name)) {
                    $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
                }
                # Otherwise, close the cell (see below) and reprocess the token.
                else {
                    $insertionMode = $this->closeCell($token);
                    goto ProcessToken;
                }
            }
            # Anything else
            else {
                # Process the token using the rules for
                #   the "in body" insertion mode.
                return $this->parseTokenInHTMLContent($token, self::IN_BODY_MODE);
            }
        }
        # 13.2.6.4.16 The "in select" insertion mode
            # A character token that is U+0000 NULL
                # Parse error. Ignore the token.
            # Any other character token
                # Insert the token's character.
            # A comment token
                # Insert a comment.
            # A DOCTYPE token
                # Parse error. Ignore the token.
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # A start tag whose tag name is "option"
                # If the current node is an option element, pop that node from the stack of open elements.
                # Insert an HTML element for the token.
            # A start tag whose tag name is "optgroup"
                # If the current node is an option element, pop that node from the stack of open elements.
                # If the current node is an optgroup element, pop that node from the stack of open elements.
                # Insert an HTML element for the token.
            # An end tag whose tag name is "optgroup"
                # First, if the current node is an option element, and the node immediately before it in the stack of open elements is an optgroup element, then pop the current node from the stack of open elements.
                # If the current node is an optgroup element, then pop that node from the stack of open elements. Otherwise, this is a parse error; ignore the token.
            # An end tag whose tag name is "option"
                # If the current node is an option element, then pop that node from the stack of open elements. Otherwise, this is a parse error; ignore the token.
            # An end tag whose tag name is "select"
                # If the stack of open elements does not have a select element in select scope, this is a parse error; ignore the token. (fragment case)
                # Otherwise:
                # Pop elements from the stack of open elements until a select element has been popped from the stack.
                # Reset the insertion mode appropriately.
            # A start tag whose tag name is "select"
                # Parse error.
                # If the stack of open elements does not have a select element in select scope, ignore the token. (fragment case)
                # Otherwise:
                # Pop elements from the stack of open elements until a select element has been popped from the stack.
                # Reset the insertion mode appropriately.
                # It just gets treated like an end tag.
            # A start tag whose tag name is one of: "input", "keygen", "textarea"
                # Parse error.
                # If the stack of open elements does not have a select element in select scope, ignore the token. (fragment case)
                # Otherwise:
                # Pop elements from the stack of open elements until a select element has been popped from the stack.
                # Reset the insertion mode appropriately.
                # Reprocess the token.
            # A start tag whose tag name is one of: "script", "template"
            # An end tag whose tag name is "template"
                # Process the token using the rules for the "in head" insertion mode.
            # An end-of-file token
                # Process the token using the rules for the "in body" insertion mode.
            # Anything else
                # Parse error. Ignore the token.
        # 13.2.6.4.17 The "in select in table" insertion mode
            # A start tag whose tag name is one of: "caption", "table", "tbody", "tfoot", "thead", "tr", "td", "th"
                # Parse error.
                # Pop elements from the stack of open elements until a select element has been popped from the stack.
                # Reset the insertion mode appropriately.
                # Reprocess the token.
            # An end tag whose tag name is one of: "caption", "table", "tbody", "tfoot", "thead", "tr", "td", "th"
                # Parse error.
                # If the stack of open elements does not have an element in table scope that is an HTML element with the same tag name as that of the token, then ignore the token.
                # Otherwise:
                # Pop elements from the stack of open elements until a select element has been popped from the stack.
                # Reset the insertion mode appropriately.
                # Reprocess the token.
            # Anything else
                # Process the token using the rules for the "in select" insertion mode.
        # 13.2.6.4.18 The "in template" insertion mode
            # A character token
            # A comment token
            # A DOCTYPE token
                # Process the token using the rules for the "in body" insertion mode.
            # A start tag whose tag name is one of: "base", "basefont", "bgsound", "link", "meta", "noframes", "script", "style", "template", "title"
            # An end tag whose tag name is "template"
                # Process the token using the rules for the "in head" insertion mode.
            # A start tag whose tag name is one of: "caption", "colgroup", "tbody", "tfoot", "thead"
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Push "in table" onto the stack of template insertion modes so that it is the new current template insertion mode.
                # Switch the insertion mode to "in table", and reprocess the token.
            # A start tag whose tag name is "col"
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Push "in column group" onto the stack of template insertion modes so that it is the new current template insertion mode.
                # Switch the insertion mode to "in column group", and reprocess the token.
            # A start tag whose tag name is "tr"
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Push "in table body" onto the stack of template insertion modes so that it is the new current template insertion mode.
                # Switch the insertion mode to "in table body", and reprocess the token.
            # A start tag whose tag name is one of: "td", "th"
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Push "in row" onto the stack of template insertion modes so that it is the new current template insertion mode.
                # Switch the insertion mode to "in row", and reprocess the token.
            # Any other start tag
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Push "in body" onto the stack of template insertion modes so that it is the new current template insertion mode.
                # Switch the insertion mode to "in body", and reprocess the token.
            # Any other end tag
                # Parse error. Ignore the token.
            # An end-of-file token
                # If there is no template element on the stack of open elements, then stop parsing. (fragment case)
                # Otherwise, this is a parse error.
                # Pop elements from the stack of open elements until a template element has been popped from the stack.
                # Clear the list of active formatting elements up to the last marker.
                # Pop the current template insertion mode off the stack of template insertion modes.
                # Reset the insertion mode appropriately.
                # Reprocess the token.
        # 13.2.6.4.19 The "after body" insertion mode
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                # Process the token using the rules for the "in body" insertion mode.
            # A comment token
                # Insert a comment as the last child of the first element in the stack of open elements (the html element).
            # A DOCTYPE token
                # Parse error. Ignore the token.
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # An end tag whose tag name is "html"
                # If the parser was created as part of the HTML fragment parsing algorithm, this is a parse error; ignore the token. (fragment case)
                # Otherwise, switch the insertion mode to "after after body".
            # An end-of-file token
                # Stop parsing.
            # Anything else
                # Parse error. Switch the insertion mode to "in body" and reprocess the token.
        # 13.2.6.4.20 The "in frameset" insertion mode
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                # Insert the character.
            # A comment token
                # Insert a comment.
            # A DOCTYPE token
                # Parse error. Ignore the token.
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # A start tag whose tag name is "frameset"
                # Insert an HTML element for the token.
            # An end tag whose tag name is "frameset"
                # If the current node is the root html element, then this is a parse error; ignore the token. (fragment case)
                # Otherwise, pop the current node from the stack of open elements.
                # If the parser was not created as part of the HTML fragment parsing algorithm (fragment case), and the current node is no longer a frameset element, then switch the insertion mode to "after frameset".
            # A start tag whose tag name is "frame"
                # Insert an HTML element for the token. Immediately pop the current node off the stack of open elements.
                # Acknowledge the token's self-closing flag, if it is set.
            # A start tag whose tag name is "noframes"
                # Process the token using the rules for the "in head" insertion mode.
            # An end-of-file token
                # If the current node is not the root html element, then this is a parse error.
                # The current node can only be the root html element in the fragment case.
                # Stop parsing.
            # Anything else
                # Parse error. Ignore the token.
        # 13.2.6.4.21 The "after frameset" insertion mode
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
                # Insert the character.
            # A comment token
                # Insert a comment.
            # A DOCTYPE token
                # Parse error. Ignore the token.
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # An end tag whose tag name is "html"
                # Switch the insertion mode to "after after frameset".
            # A start tag whose tag name is "noframes"
                # Process the token using the rules for the "in head" insertion mode.
            # An end-of-file token
                # Stop parsing.
            # Anything else
                # Parse error. Ignore the token.
        # 13.2.6.4.22 The "after after body" insertion mode
            # A comment token
                # Insert a comment as the last child of the Document object.
            # A DOCTYPE token
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # An end-of-file token
                # Stop parsing.
            # Anything else
                # Parse error. Switch the insertion mode to "in body" and reprocess the token.
        # 13.2.6.4.23 The "after after frameset" insertion mode
            # A comment token
                # Insert a comment as the last child of the Document object.
            # A DOCTYPE token
            # A character token that is one of U+0009 CHARACTER TABULATION, U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+000D CARRIAGE RETURN (CR), or U+0020 SPACE
            # A start tag whose tag name is "html"
                # Process the token using the rules for the "in body" insertion mode.
            # An end-of-file token
                # Stop parsing.
            # A start tag whose tag name is "noframes"
                # Process the token using the rules for the "in head" insertion mode.
            # Anything else
                # Parse error. Ignore the token.
        else {
            throw new NotImplementedException("NOT IMPLEMENTED");
        }
        return true;
    }

    protected function adopt(TagToken $token): void {
        # The adoption agency algorithm, which takes as its only argument a 
        #   token 'token' for which the algorithm is being run, consists of 
        #   the following steps:

        assert((function() {
            $this->debugLog .= "    Adoption agency (".(string) $this->stack.")\n";
            return true;
        })());

        # Let subject be token's tag name.
        $subject = $token->name;
        $errorCode = $token instanceof StartTagToken ? ParseError::UNEXPECTED_START_TAG : ParseError::UNEXPECTED_END_TAG;
        # If the current node is an HTML element whose tag name is subject, 
        #   and the current node is not in the list of active formatting elements,
        #   then pop the current node off the stack of open elements, and return.
        $currentNode = $this->stack->currentNode;
        if (
            $currentNode->namespaceURI === null
            && $currentNode->nodeName === $subject
            && $this->activeFormattingElementsList->findSame($currentNode) === -1
        ) {
            $this->stack->pop();
            return;
        }
        # Let outer loop counter be zero.
        $outerLoopCounter = 0;
        # Outer loop: If outer loop counter is greater than or equal to eight, then return.
        OuterLoop:
        if ($outerLoopCounter >= 8) {
            return;
        }
        # Increment outer loop counter by one.
        $outerLoopCounter++;
        # Let formatting element be the last element in the list of active 
        # formatting elements that:
        # 1. is between the end of the list and the last marker in the list,
        #   if any, or the start of the list otherwise, and
        # 2. has the tag name subject.
        $formattingElementIndex = $this->activeFormattingElementsList->findToMarker($subject);
        if ($formattingElementIndex > -1) {
            $formattingElement = $this->activeFormattingElementsList[$formattingElementIndex]['element'];
        } else {
            $formattingElement = null;
        }
        # If there is no such element, then return and instead act as
        #   described in the "any other end tag" entry above.
        if (!$formattingElement) {
            // NOTE: The "entry above" refers to the "in body" insertion mode
            //   Changes here should be mirrored there
            foreach ($this->stack as $node) {
                if ($node->nodeName === $token->name && $node->namespaceURI === null) {
                    $this->stack->generateImpliedEndTags($token->name);
                    if (!$node->isSameNode($this->stack->currentNode)) {
                        $this->error($errorCode, $token->name);
                    }
                    $this->stack->popUntilSame($node);
                    return;
                } elseif ($this->isElementSpecial($node)) {
                    $this->error($errorCode, $token->name);
                    return;
                }
            }
        }
        # If formatting element is not in the stack of open elements, 
        #   then this is a parse error; remove the element from the
        #   list, and return.
        if (($stackIndex = $this->stack->findSame($formattingElement)) === -1) {
            $this->error($errorCode, $token->name);
            unset($this->activeFormattingElementsList[$formattingElementIndex]);
            return;
        }
        # If formatting element is in the stack of open elements, but 
        #   the element is not in scope, then this is a parse error; return.
        elseif (!$this->stack->hasElementInScope($formattingElement)) {
            $this->error($errorCode, $token->name);
            return;
        }
        # If formatting element is not the current node, this is a
        #   parse error. (But do not return.)
        if (!$formattingElement->isSameNode($this->stack->currentNode)) {
            $this->error($errorCode, $token->name);
        }
        # Let furthest block be the topmost node in the stack of open elements that
        #   is lower in the stack than formatting element, and is an element in the
        #   special category. There might not be one.
        $furthestBlock = null;
        for ($k = ($stackIndex - 1); $k > -1; $k--) {
            if ($this->isElementSpecial($this->stack[$k])) { 
                $furthestBlockIndex = $k;
                $furthestBlock = $this->stack[$k];
                break;
            }
        }
        # If there is no furthest block, then the UA must first pop all the nodes
        #   from the bottom of the stack of open elements, from the current node up
        #   to and including formatting element, then remove formatting element from
        #   the list of active formatting elements, and finally return.
        if (!$furthestBlock) {
            $this->stack->popUntilSame($formattingElement);
            $this->activeFormattingElementsList->removeSame($formattingElement);
            return;
        }
        throw new NotImplementedException("NOT IMPLEMENTED");
        # Let common ancestor be the element immediately above formatting element
        #   in the stack of open elements.
        $commonAncestor = $this->stack[$stackIndex - 1] ?? null;
        # Let a bookmark note the position of formatting element in the list of
        #   active formatting elements relative to the elements on either side
        #   of it in the list.
        $bookmrk = $formattingElementIndex;
        # Let node and last node be furthest block. Follow these steps:
        $node = $furthestBlock;
        $nodeIndex = $furthestBlockIndex;
        $lastNode = $furthestBlock;
        # Let inner loop counter be zero.
        $innerLoopCounter = 0;
        # Inner loop: Increment inner loop counter by one.
        InnerLoop:
        $innerLoopCounter++;
        # Let node be the element immediately above node in the stack of open
        #   elements, or if node is no longer in the stack of open elements 
        #   (e.g. because it got removed by this algorithm), the element that
        #   was immediately above node in the stack of open elements before
        #   node was removed.
        $node = $this->stack[--$nodeIndex];
        # If node is formatting element, then go to the next step in the
        #   overall algorithm.
        goto AfterInnerLoop;
        # If inner loop counter is greater than three and node is in the
        #   list of active formatting elements, then remove node from the
        #   list of active formatting elements.
        $nodeListPos = $this->activeFormattingElementsList->findSame($node);
        if ($innerLoopCounter > 3 && $nodeListPos > -1) {
            unset($this->activeFormattingElementsList[$nodeListPos]);
        }
        # If node is not in the list of active formatting elements, then
        #   remove node from the stack of open elements and then go back to
        #   the step labeled inner loop.
        elseif ($nodeListPos === -1) {
            $this->stack->removeSame($node);
            goto InnerLoop;
        }
        # Create an element for the token for which the element node was
        #   created, in the HTML namespace, with common ancestor as the
        #   intended parent; replace the entry for node in the list of 
        #   active formatting elements with an entry for the new element,
        #   replace the entry for node in the stack of open elements with
        #   an entry for the new element, and let node be the new element.
        $element = $this->insertStartTagToken($this->activeFormattingElementsList[$nodeListPos]['token'], $commonAncestor);
        $this->activeFormattingElementsList[$nodeListPos]['element'] = $element;
        $this->stack[$nodeIndex] = $element;
        # If last node is furthest block, then move the aforementioned
        #   bookmark to be immediately after the new node in the list of
        #   active formatting elements.
        if ($lastNode->isSameNode($furthestBlock)) {
            $bookmark = $nodeListPos + 1;
        }
        # Insert last node into node, first removing it from its previous
        #   parent node if any.
        if ($lastNode->parentNode) {
            $lastNode->parentNode->removeChild($lastNode);
        }
        $node->appendChild($lastNode);
        # Let last node be node.
        $lastNode = $node;
        # Return to the step labeled inner loop.
        goto InnerLoop;
        # Insert whatever last node ended up being in the previous step
        #   at the appropriate place for inserting a node, but using 
        #   common ancestor as the override target.
        AfterInnerLoop:
        $place = $this->appropriatePlaceForInsertingNode($commonAncestor);
        if ($place['insert before']) {
            $place['node']->parentNode->insertBefore($lastNode, $place['node']);
        } else {
            $place['node']->appendChild($lastNode);
        }
        # Create an element for the token for which formatting element was
        #   created, in the HTML namespace, with furthest block as the
        #   intended parent.
        $element = $this->insertStartTagToken($this->activeFormattingElementsList[$formattingElementIndex]['token'], $furthestBlock);
        # Take all of the child nodes of furthest block and append them to
        #   the element created in the last step.
        foreach ($furthestBlock->childNodes as $node) {
            $element->appendChild($node);
        }
        # Append that new element to furthest block.
        $furthestBlock->appendChild($element);
        # Remove formatting element from the list of active formatting
        #   elements, and insert the new element into the list of active
        #   formatting elements at the position of the aforementioned bookmark.

        # Remove formatting element from the stack of open elements, and insert the new element into the stack of open elements immediately below the position of furthest block in that stack.
        # Jump back to the step labeled outer loop.
    }

    protected function parseTokenInForeignContent(Token $token): bool {
        assert((function() {
            $this->debugLog .= "    Mode: Foreign content (".(string) $this->stack.")\n";
            return true;
        })());
        
        # 13.2.6.5 The rules for parsing tokens in foreign content
        #
        # When the user agent is to apply the rules for parsing tokens in foreign
        # content, the user agent must handle the token as follows:


        # A character token that is U+0000 NULL
        if ($token instanceof CharacterToken && $token->data === "\0") {
            # Parse error. Insert a U+FFFD REPLACEMENT CHARACTER character.
            // DEVIATION: Parse errors for null characters are already emitted by the tokenizer
            $this->insertCharacterToken(new CharacterToken("\u{FFFD}"));
        }
        # A character token that is one of U+0009 CHARACTER TABULATION, "LF" (U+000A),
        # "FF" (U+000C), "CR" (U+000D), or U+0020 SPACE
        elseif ($token instanceof WhitespaceToken) {
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
            # Parse error. Ignore the token.
            $this->error(ParseError::UNEXPECTED_DOCTYPE);
        }
        # A start tag...
        elseif ($token instanceof StartTagToken) {
            # A start tag whose tag name is one of: "b", "big", "blockquote", "body", "br",
            # "center", "code", "dd", "div", "dl", "dt", "em", "embed", "h1", "h2", "h3",
            # "h4", "h5", "h6", "head", "hr", "i", "img", "li", "listing", "menu", "meta",
            # "nobr", "ol", "p", "pre", "ruby", "s", "small", "span", "strong", "strike",
            # "sub", "sup", "table", "tt", "u", "ul", "var"
            # A start tag whose tag name is "font", if the token has any attributes named
            # "color", "face", or "size"
            if ($token->name === 'b' 
                || $token->name === 'big' 
                || $token->name === 'blockquote' 
                || $token->name === 'body' 
                || $token->name === 'br' 
                || $token->name === 'center' 
                || $token->name === 'code' 
                || $token->name === 'dd' 
                || $token->name === 'div' 
                || $token->name === 'dl' 
                || $token->name === 'dt' 
                || $token->name === 'em' 
                || $token->name === 'embed' 
                || $token->name === 'h1' 
                || $token->name === 'h2' 
                || $token->name === 'h3' 
                || $token->name === 'h4' 
                || $token->name === 'h5' 
                || $token->name === 'h6' 
                || $token->name === 'head' 
                || $token->name === 'hr' 
                || $token->name === 'i' 
                || $token->name === 'img' 
                || $token->name === 'li' 
                || $token->name === 'listing' 
                || $token->name === 'menu' 
                || $token->name === 'meta' 
                || $token->name === 'nobr' 
                || $token->name === 'ol' 
                || $token->name === 'p' 
                || $token->name === 'pre' 
                || $token->name === 'ruby' 
                || $token->name === 's' 
                || $token->name === 'small' 
                || $token->name === 'span' 
                || $token->name === 'strong' 
                || $token->name === 'strike' 
                || $token->name === 'sub' 
                || $token->name === 'sup' 
                || $token->name === 'table' 
                || $token->name === 'tt' 
                || $token->name === 'u' 
                || $token->name === 'ul' 
                || $token->name === 'var' 
                || ($token->name === 'font' 
                    && ($token->hasAttribute('color') || $token->hasAttribute('face') || $token->hasAttribute('size'))
                )
            ) {
                # Parse error.
                $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
                # If the parser was originally created for the HTML fragment parsing algorithm,
                # then act as described in the "any other start tag" entry below. (fragment
                # case)
                if ($this->fragmentContext) {
                    // ¡TEMPORARY!
                    goto foreignContentAnyOtherStartTag;
                }
                # Otherwise:
                # Pop an element from the stack of open elements, and then keep popping more
                # elements from the stack of open elements until the current node is a MathML
                # text integration point, an HTML integration point, or an element in the HTML
                # namespace.
                do {
                    $popped = $this->stack->pop();
                    $n = $this->stack->currentNode;
                    $nns = $this->stack->currentNode->namespaceURI;
                } while (!is_null($popped) && !(
                        $n->isMathMLTextIntegrationPoint()
                        || $n->isHTMLIntegrationPoint() 
                        || is_null($nns)
                    )
                );
                # Then, reprocess the token.
                return false;
            }
            # Any other start tag
            else {
                // ¡TEMPORARY!
                foreignContentAnyOtherStartTag:
                $currentNodeNamespace = $this->stack->currentNodeNamespace;
                # If the adjusted current node is an element in the SVG namespace, and the
                # token’s tag name is one of the ones in the first column of the following
                # table, change the tag name to the name given in the corresponding cell in the
                # second column. (This fixes the case of SVG elements that are not all
                # lowercase.)
                if ($this->stack->adjustedCurrentNodeNamespace === Parser::SVG_NAMESPACE) {
                    $token->name = self::SVG_TAG_NAME_MAP[$token->name] ?? $token->name;
                }
                foreach ($token->attributes as $a) {
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
                        $a->name = self::SVG_ATTR_NAME_MAP[$a->name] ?? $a->name;
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
                    $a->namespace = self::FOREIGN_ATTRIBUTE_NAMESPACE_MAP[$a->name] ?? null;                    
                }
                # Insert a foreign element for the token, in the same namespace as the adjusted
                # current node.
                $this->insertStartTagToken($token, null, $this->stack->adjustedCurrentNode->namespaceURI);
                # If the token has its self-closing flag set, then run the appropriate steps
                #   from the following list:
                if ($token->selfClosing) {
                    # If the token’s tag name is "script", and the new current node is in the SVG
                    # namespace
                    // DEVIATION: This implementation does not support scripting, so script elements
                    //   aren't processed differently.
                    # Otherwise
                    # Pop the current node off the stack of open elements and acknowledge the
                    #   token’s *self-closing flag*.
                    $this->stack->pop();
                    $token->selfClosingAcknowledged = true;
                }
            }
        }
        # An end tag whose tag name is "script", if the current node is a script element
        # in the SVG namespace
        // DEVIATION: This implementation does not support scripting, so script elements
        //   aren't processed differently.
        # Any other end tag
        elseif ($token instanceof EndTagToken) {
            # Run these steps:
            #
            # 1. Initialize node to be the current node (the bottommost node of the stack).
            // We do this below in the loop
            # 2. If node is not an element with the same tag name as the token, then this is
            # a parse error.
            if ($this->stack->currentNodeName !== $token->name) {
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
            # 3. Loop: If node is the topmost element in the stack of open elements, then return. (fragment case)
            $pos = count($this->stack) - 1;
            while ($pos > 0 && ($node = $this->stack[$pos])->namespaceURI !== null) {
                # If node's tag name, converted to ASCII lowercase, is the same as the
                #   tag name of the token, pop elements from the stack of open elements until node
                #   has been popped from the stack, and then abort these steps.
                if (strtolower($node->nodeName) === $token->name) {
                    $this->stack->popUntilSame($node);
                    return true;
                }
                # 4. Set node to the previous entry in the stack of open elements.
                $pos--;
                # 5. If node is not an element in the HTML namespace, return to the step labeled
                # loop.
                // See loop condition above
            }
            # 6. Otherwise, process the token according to the rules given in the section
            #   corresponding to the current insertion mode in HTML content.
            return $this->parseTokenInHTMLContent($token, $this->insertionMode);
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

        # If there was an override target specified, then let target 
        #   be the override target. Otherwise, let target be the current node.
        $target = $overrideTarget ?? $this->stack->currentNode;
        assert(isset($target), new \Exception("Open elements stack is empty"));
        # Determine the adjusted insertion location using the first matching steps
        # from the following list: 
        $targetNodeName = $target->nodeName;
        # If foster parenting is enabled and target is a table, tbody, tfoot, thead, or tr element
        if ($this->fosterParenting && ($targetNodeName === 'table' || $targetNodeName === 'tbody' || $targetNodeName === 'tfoot' || $targetNodeName === 'thead' || $targetNodeName === 'tr')) {
            # Run these substeps:
            #
            # 1. Let last template be the last template element in the stack of open
            # elements, if any.
            $lastTemplateIndex = $this->stack->find('template');
            $lastTemplate = ($lastTemplateIndex > -1 ) ? $this->stack[$lastTemplateIndex] : null;
            # 2. Let last table be the last table element in the stack of open elements, if
            # any.
            $lastTableIndex = $this->stack->find('table');
            $lastTable = ($lastTableIndex > -1 ) ? $this->stack[$lastTableIndex] : null;
            # 3. If there is a last template and either there is no last table, or there is
            # one, but last template is lower (more recently added) than last table in the
            # stack of open elements, then: let adjusted insertion location be inside last
            # template’s template contents, after its last child (if any), and abort these
            # substeps.
            if ($lastTemplate && (!$lastTable || ($lastTemplateIndex > $lastTableIndex))) {
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
                $previousElement = $this->stack[$lastTableIndex - 1];
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

    public function insertCommentToken(CommentToken $token, \DOMNode $position = null) {
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
        if ($adjustedInsertionLocation instanceof \DOMDocument) {
            $nodeDocument = $adjustedInsertionLocation;
        } else {
            $nodeDocument = $adjustedInsertionLocation->ownerDocument;
        }
        $commentNode = $nodeDocument->createComment($token->data);

        # 4. Insert the newly created node at the adjusted insertion location.
        if ($insertBefore === false) {
            $adjustedInsertionLocation->appendChild($commentNode);
        } else {
            $adjustedInsertionLocation->parentNode->insertBefore($commentNode, $adjustedInsertionLocation);
        }
    }

    public function insertStartTagToken(StartTagToken $token, \DOMNode $intendedParent = null, string $namespace = null): Element {
        $namespace = $namespace ?? $token->namespace;
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
            if (!$a->namespace || $a->namespace === Parser::HTML_NAMESPACE) {
                $element->setAttribute($a->name, $a->value);
            } else {
                $element->setAttributeNS($a->namespace, $a->name, $a->value);
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

    protected function resetInsertionMode(): int {
        # When the steps below require the UA to reset the insertion mode appropriately,
        # it means the UA must follow these steps:

        # 1. Let last be false.
        $last = false;
        # 2. Let node be the last node in the stack of open elements.
        foreach($this->stack as $position => $node) {
            # 3. Loop: If node is the first node in the stack of open elements, then set
            #   last to true, and, if the parser was originally created as part of the HTML
            #   fragment parsing algorithm (fragment case), set node to the context element
            #   passed to that algorithm.
            if ($position === 0) {
                $last = true;
                if ($this->fragmentContext) {
                    $node = $this->fragmentContext;
                }
            }
            $nodeName = $node->nodeName;
            # 4. If node is a select element, run these substeps:
            if ($nodeName === 'select') {
                # 1. If last is true, jump to the step below labeled Done.
                if ($last === false) {
                    # 2. Let ancestor be node.
                    # 3. Loop: If ancestor is the first node in the stack of
                    #   open elements, jump to the step below labeled Done.
                    for ($ancestorPosition = $position; $ancestorPosition > 0;) {
                        # 4. Let ancestor be the node before ancestor in the stack of open elements.
                        $ancestor = $this->stack[--$ancestorPosition];
                        # 5. If ancestor is a template node, jump to the step below labeled Done.
                        if ($ancestor->nodeName === 'template') {
                            break;
                        }
                        # 6. If ancestor is a table node, switch the insertion mode to "in select in
                        # table" and abort these steps.
                        if ($ancestor->nodeName === 'table') {
                            return $this->insertionMode = self::IN_SELECT_IN_TABLE_MODE;
                        }
                        # 7. Jump back to the step labeled Loop.
                    }
                }
                # 8. Done: Switch the insertion mode to "in select" and abort these steps.
                return $this->insertionMode = self::IN_SELECT_MODE;
            }
            # 5. If node is a td or th element and last is false, then switch the insertion
            # mode to "in cell" and abort these steps.
            elseif (($nodeName === 'td' || $nodeName === 'th') && $last === false) {
                return $this->insertionMode = self::IN_CELL_MODE;
            }
            # 6. If node is a tr element, then switch the insertion mode to "in row" and
            # abort these steps.
            elseif ($nodeName === 'tr') {
                return $this->insertionMode = self::IN_ROW_MODE;
            }
            # 7. If node is a tbody, thead, or tfoot element, then switch the insertion mode
            # to "in table body" and abort these steps.
            elseif ($nodeName === 'tbody' || $nodeName === 'thead' || $nodeName === 'tfoot') {
                return $this->insertionMode = self::IN_TABLE_BODY_MODE;
            }
            # 8. If node is a caption element, then switch the insertion mode to "in
            # caption" and abort these steps.
            elseif ($nodeName === 'caption') {
                return $this->insertionMode = self::IN_CAPTION_MODE;
            }
            # 9. If node is a colgroup element, then switch the insertion mode to "in column
            # group" and abort these steps.
            elseif ($nodeName === 'colgroup') {
                return $this->insertionMode = self::IN_COLUMN_GROUP_MODE;
            }
            # 10. If node is a table element, then switch the insertion mode to "in table"
            # and abort these steps.
            elseif ($nodeName === 'table') {
                return $this->insertionMode = self::IN_TABLE_MODE;
            }
            # 11. If node is a template element, then switch the insertion mode to the
            # current template insertion mode and abort these steps.
            elseif ($nodeName === 'template') {
                return $this->insertionMode = $this->templateInsertionModes->currentMode;
            }
            # 12. If node is a head element and last is false, then switch the insertion
            # mode to "in head" and abort these steps.
            elseif ($nodeName === 'head' && $last === false) {
                return $this->insertionMode = self::IN_HEAD_MODE;
            }
            # 13. If node is a body element, then switch the insertion mode to "in body" and
            # abort these steps.
            elseif ($nodeName === 'body') {
                return $this->insertionMode = self::IN_BODY_MODE;
            }
            # 14. If node is a frameset element, then switch the insertion mode to "in
            # frameset" and abort these steps. (fragment case)
            elseif ($nodeName === 'frameset') {
                return $this->insertionMode = self::IN_FRAMESET_MODE;
            }
            # 15. If node is an html element, run these substeps:
            elseif ($nodeName === 'html') {
                # 1. If the head element pointer is null, switch the insertion mode to "before
                # head" and abort these steps. (fragment case)
                if (is_null($this->headElement)) {
                    return $this->insertionMode = self::BEFORE_HEAD_MODE;
                }
                # 2. Otherwise, the head element pointer is not null, switch the insertion mode
                # to "after head" and abort these steps.
                return $this->insertionMode = self::AFTER_HEAD_MODE;
            }
            # 16. If last is true, then switch the insertion mode to "in body" and abort
            # these steps. (fragment case)
            elseif ($last === true) {
                return $this->insertionMode = self::IN_BODY_MODE;
            }
            # 17. Let node now be the node before node in the stack of open elements.
            # 18. Return to the step labeled Loop.
        }
    }

    protected function closePElement(TagToken $token) {
        # When the steps above say the UA is to close a p element, it means that the UA
        # must run the following steps:

        # 1. Generate implied end tags, except for p elements.
        $this->stack->generateImpliedEndTags("p");
        # 2. If the current node is not a p element, then this is a parse error.
        $currentNodeName = $this->stack->currentNodeName;
        if ($currentNodeName !== 'p') {
            if ($token instanceof StartTagToken) {
                $this->error(ParseError::UNEXPECTED_START_TAG, $token->name);
            } else {
                $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
            }
        }
        # 3. Pop elements from the stack of open elements until a p element has been
        # popped from the stack.
        $this->stack->popUntil('p');
    }

    protected function isElementSpecial(Element $element): bool {
        $name = $element->nodeName;
        $ns = $element->namespaceURI ?? Parser::HTML_NAMESPACE;
        return in_array($name, self::SPECIAL_ELEMENTS[$ns] ?? []);
    }

    protected function closeCell(TagToken $token): int {
        # Where the steps above say to close the cell,
        #   they mean to run the following algorithm:

        # Generate implied end tags.
        $this->stack->generateImpliedEndTags();
        # If the current node is not now a td element or a th element,
        #   then this is a parse error.
        if (!in_array($this->stack->currntNodeName, ["td", "th"])) {
            $this->error(ParseError::UNEXPECTED_END_TAG, $token->name);
        }
        # Pop elements from the stack of open elements stack until a td
        #   element or a th element has been popped from the stack.
        $this->stack->popUntil("td", "th");
        # Clear the list of active formatting elements up to the last marker.
        $this->activeFormattingElementsList->clearToTheLastMarker();
        # Switch the insertion mode to "in row".
        return $this->insertionMode = self::IN_ROW_MODE;
    }
}
