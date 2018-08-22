<?php
declare(strict_types=1);
namespace dW\HTML5;

class Parser {
    /* Non-static properties */

    // Input data that's being parsed, uses DataStream
    protected $data;
    // The DOMDocument that is assembled by the tree builder
    protected $DOM;
    // If parsed as a fragment a fragment is assembled instead
    protected $DOMFragment;
    // The form element pointer points to the last form element that was opened and
    // whose end tag has not yet been seen. It is used to make form controls associate
    // with forms in the face of dramatically bad markup, for historical reasons. It is
    // ignored inside template elements
    protected $formElement;
    // Flag that shows whether the content that's being parsed is a fragment or not
    protected $fragmentCase = false;
    // Context element for fragments
    protected $fragmentContext;
    // Used for the instance of ParseError
    protected $parseError;
    // The stack of open elements, uses Stack
    protected $stack;
    // Instance of the Tokenizer class used for creating tokens
    protected $tokenizer;
    // Instance of the TreeBuilder class used for building the document
    protected $treeBuilder;


    /* Static properties */

    // For debugging
    public static $debug = false;

    // Property used as an instance for the non-static properties
    protected static $instance;

    // Namespace constants
    const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';
    const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';


    // Protected construct used for creating an instance to access properties which must
    // be reset on every parse
    protected function __construct() {
        static::$instance = $this;
    }

    public function __destruct() {
        static::$instance = null;
    }

    public static function parse(string $data, bool $file = false) {
        // If parse() is called by parseFragment() then don't create an instance. It has
        // already been created.
        $c = __CLASS__;
        if (!(static::$instance instanceof $c && !static::$instance->fragmentCase)) {
            static::$instance = new $c;
        }

        if (is_null(static::$instance->DOM)) {
            static::$instance->DOM = new DOM();
        }

        // Process the input stream.
        static::$instance->data = new DataStream(($file === true) ? '' : $data, ($file === true) ? $data : 'STDIN');

        // Set the locale for CTYPE to en_US.UTF8 so ctype functions and strtolower only
        // work on basic latin characters. Used extensively when tokenizing.
        setlocale(LC_CTYPE, 'en_US.UTF8');

        // Initialize the stack of open elements.
        static::$instance->stack = new OpenElementsStack(static::$instance->fragmentCase, static::$instance->fragmentContext);
        // Initialize the tokenizer.
        static::$instance->tokenizer = new Tokenizer(static::$instance->data, static::$instance->stack);
        // Initialize the tree builder.
        static::$instance->treeBuilder = new TreeBuilder(static::$instance->DOM, static::$instance->formElement, static::$instance->fragmentCase, static::$instance->fragmentContext, static::$instance->stack, static::$instance->tokenizer);
        // Initialize the parse error handler.
        static::$instance->parseError = new ParseError(static::$instance->data);

        // Run the tokenizer. Tokenizer runs until after the EOF token is emitted.
        do {
            $token = static::$instance->tokenizer->createToken();
            static::$instance->treeBuilder->emitToken($token);
        } while (!$token instanceof EOFToken);

        // The Parser instance has no need to exist when finished.
        $dom = static::$instance->DOM->document;
        static::$instance->__destruct();

        return DOM::fixIdAttributes($dom);
    }

    public static function parseFragment(string $data, \DOMElement $context = null, bool $file = false): \DOMDocument {
        // Create an instance of this class to use the non static properties.
        $c = __CLASS__;
        static::$instance = new $c;

        if (!is_null($context)) {
            static::$instance->DOM = new DOM($context->ownerDocument);
        } else {
            static::$instance->DOM = new DOM();
            static::$instance->DOM->document = static::$instance->DOM->implementation->createDocument();
        }

        static::$instance->DOMFragment = static::$instance->DOM->document->createDocumentFragment();

        // DEVIATION: The spec says to let the document be in quirks mode if the
        // DOMDocument is in quirks mode. Cannot check whether the context element is in
        // quirks mode, so going to assume it isn't.

        // DEVIATION: The spec's version of parsing fragments isn't remotely useful in
        // the context this library is intended for use in. This implementation uses a
        // DOMDocumentFragment for inserting nodes into. There's no need to have a
        // different process for when there isn't a context. There will always be one:
        // the DOMDocumentFragment.

        static::$instance->fragmentContext = (!is_null($context)) ? $context : static::$instance->DOMFragment;

        $name = static::$instance->fragmentContext->nodeName;
        # Set the state of the HTML parser's tokenization stage as follows:
        switch($name) {
            case 'title':
            case 'textarea': static::$instance->tokenizer->state = Tokenizer::RCDATA_STATE;
            break;
            case 'style':
            case 'xmp':
            case 'iframe':
            case 'noembed':
            case 'noframes': static::$instance->tokenizer->state = Tokenizer::RAWTEXT_STATE;
            break;
            case 'script': static::$instance->tokenizer->state = Tokenizer::SCRIPT_STATE;
            break;
            case 'noscript': static::$instance->tokenizer->state = Tokenizer::NOSCRIPT_STATE;
            break;
            case 'plaintext': static::$instance->tokenizer->state = Tokenizer::PLAINTEXT_STATE;
            break;
            default: static::$instance->tokenizer->state = Tokenizer::DATA_STATE;
        }

        // DEVIATION: Since this implementation uses a DOMDocumentFragment for insertion
        // there is no need to create an html element for inserting stuff into.

        # If the context element is a template element, push "in template" onto the
        # stack of template insertion modes so that it is the new current template
        # insertion mode.
        // DEVIATION: No scripting.

        # Reset the parser's insertion mode appropriately.
        // DEVIATION: The insertion mode will be always 'in body', not 'before head' if
        // there isn't a context. There isn't a need to reconstruct a valid HTML
        // document when using a DOMDocumentFragment.
        TreeBuilder::resetInsertionMode();

        # Set the parser's form element pointer to the nearest node to the context element
        # that is a form element (going straight up the ancestor chain, and including the
        # element itself, if it is a form element), if any. (If there is no such form
        # element, the form element pointer keeps its initial value, null.)
        static::$instance->formElement = ($name === 'form') ? $context : DOM::getAncestor('form', $context);

        # Start the parser and let it run until it has consumed all the characters just
        # inserted into the input stream.
        static::$instance->fragmentCase = true;
        static::parse($data, $file);

        # If there is a context element, return the child nodes of root, in tree order.
        # Otherwise, return the children of the Document object, in tree order.

        // DEVIATION: This method will always return a DOMDocumentFragment.
        return static::$instance->DOMFragment;
    }
}
