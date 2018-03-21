<?php
declare(strict_types=1);
namespace dW\HTML5;

class Parser {
    /* Non-static properties */

    // Context element for fragments
    public $fragmentContext;
    // The DOMDocument that is assembled by the tree builder
    public $DOM;
    // If a fragment a fragment is assembled instead
    public $DOMFragment;
    // Input data that's being parsed, uses DataStream
    public $data;
    // The form element pointer points to the last form element that was opened and
    // whose end tag has not yet been seen. It is used to make form controls associate
    // with forms in the face of dramatically bad markup, for historical reasons. It is
    // ignored inside template elements
    public $formElement;
    // Flag that shows whether the content that's being parsed is a fragment or not
    public $fragmentCase = false;
    // Once a head element has been parsed (whether implicitly or explicitly) the head
    // element pointer gets set to point to this node
    public $headElement;
    // The stack of open elements, uses Stack
    public $stack;
    // Controls the primary operation of the tokenizer
    public $tokenizerState;
    // Treebuilder insertion mode
    public $insertionMode;
    // Current token that's being constructed
    public $currentToken;
    // Used to check if the document is in quirks mode
    public $quirksMode;


    /* Static properties */

    // Property used as an instance for the non-static properties
    public static $self;
    // For debugging
    public static $debug = false;


    /* Constants */

    // Constants used for the tokenizer state
    const DATA_STATE = 0;
    const RCDATA_STATE = 1;
    const RAWTEXT_STATE = 2;
    const SCRIPT_DATA_STATE = 3;
    const PLAINTEXT_STATE = 4;
    const TAG_OPEN_STATE = 5;
    const END_TAG_OPEN_STATE = 6;
    const TAG_NAME_STATE = 7;
    const RCDATA_LESS_THAN_SIGN_STATE = 8;
    const RCDATA_END_TAG_OPEN_STATE = 9;
    const RCDATA_END_TAG_NAME_STATE = 10;
    const RAWTEXT_LESS_THAN_SIGN_STATE = 11;
    const RAWTEXT_END_TAG_OPEN_STATE = 12;
    const RAWTEXT_END_TAG_NAME_STATE = 13;
    const SCRIPT_DATA_LESS_THAN_SIGN_STATE = 14;
    const SCRIPT_DATA_END_TAG_OPEN_STATE = 15;
    const SCRIPT_DATA_END_TAG_NAME_STATE = 16;
    const SCRIPT_DATA_ESCAPE_START_STATE = 17;
    const SCRIPT_DATA_ESCAPE_START_DASH_STATE = 18;
    const SCRIPT_DATA_ESCAPED_STATE = 19;
    const SCRIPT_DATA_ESCAPED_DASH_STATE = 20;
    const SCRIPT_DATA_ESCAPED_DASH_DASH_STATE = 21;
    const SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE = 22;
    const SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE = 23;
    const SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE = 24;
    const SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE = 25;
    const SCRIPT_DATA_DOUBLE_ESCAPED_STATE = 26;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE = 27;
    const SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE = 28;
    const SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE = 29;
    const SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE = 30;
    const BEFORE_ATTRIBUTE_NAME_STATE = 31;
    const ATTRIBUTE_NAME_STATE = 32;
    const AFTER_ATTRIBUTE_NAME_STATE = 33;
    const BEFORE_ATTRIBUTE_VALUE_STATE = 34;
    const ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE = 35;
    const ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE = 36;
    const ATTRIBUTE_VALUE_UNQUOTED_STATE = 37;
    const AFTER_ATTRIBUTE_VALUE_QUOTED_STATE = 38;
    const SELF_CLOSING_START_TAG_STATE = 39;
    const BOGUS_COMMENT_STATE = 40;
    const MARKUP_DECLARATION_OPEN_STATE = 41;
    const COMMENT_START_STATE = 42;
    const COMMENT_START_DASH_STATE = 43;
    const COMMENT_STATE = 44;
    const COMMENT_END_DASH_STATE = 45;
    const COMMENT_END_STATE = 46;
    const COMMENT_END_BANG_STATE = 47;
    const DOCTYPE_STATE = 48;
    const BEFORE_DOCTYPE_NAME_STATE = 49;
    const DOCTYPE_NAME_STATE = 50;
    const AFTER_DOCTYPE_NAME_STATE = 51;
    const AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE = 52;
    const BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 53;
    const DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE = 54;
    const DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE = 55;
    const AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE = 56;
    const BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE = 57;
    const AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE = 58;
    const BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 59;
    const DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE = 60;
    const DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE = 61;
    const AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE = 62;
    const BOGUS_DOCTYPE_STATE = 63;
    const CDATA_SECTION_STATE = 64;

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

    // Ctype constants
    const CTYPE_ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const CTYPE_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    // Namespace constants
    const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    const MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';
    const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    // Quirks mode constants
    const QUIRKS_MODE_OFF = 0;
    const QUIRKS_MODE_ON = 1;
    const QUIRKS_MODE_LIMITED = 2;


    // Protected construct used for creating an instance to access properties which must
    // be reset on every parse
    protected function __construct() {
        $this->tokenizerState = static::DATA_STATE;
        $this->insertionMode = static::INITIAL_MODE;
        $this->quirksMode = static::QUIRKS_MODE_OFF;
        $this->stack = new Stack();
    }

    public static function parse(string $data, bool $file = false) {
        // If parse() is called by parseFragment() then don't create an instance. It has
        // already been created.
        $c = __CLASS__;
        if (!(static::$self instanceof $c && !static::$self->fragmentCase)) {
            static::$self = new $c;
        }

        // Process the input stream.
        static::$self->data = new DataStream(($file === true) ? '' : $data, ($file === true) ? $data : 'STDIN');

        // Set the locale for CTYPE to en_US.UTF8 so ctype functions and strtolower only
        // work on basic latin characters. Used extensively when tokenizing.
        setlocale(LC_CTYPE, 'en_US.UTF8');

        static::$self->tokenize();
        //return static::$self->fixDOM();
        return 'OOK!';
    }

    public static function parseFragment(string $data, \DOMDocument $dom = null, \DOMElement $context = null, bool $file = false): \DOMDocument {
        // If a context is provided and either the DOM isn't provided or the DOM isn't
        // the owner document of the provided context then the context is invalid and
        // should be set to null.
        if (!is_null($context) && (is_null($dom) || !$dom->isSameNode($context->ownerDocument))) {
            $context = null;
        }

        // Create an instance of this class to use the non static properties.
        $c = __CLASS__;
        static::$self = new $c;

        if (!is_null($dom)) {
            static::$self->DOM = $dom;
        } else {
            $imp = new DOMImplementation;
            static::$self->DOM = $imp->createDocument();
        }

        static::$self->DOMFragment = static::$self->DOM->createDocumentFragment();

        // DEVIATION: The spec says to let the document be in quirks mode if the
        // DOMDocument is in quirks mode. Cannot check whether the context element is in
        // quirks mode, so going to assume it isn't.

        // DEVIATION: The spec's version of parsing fragments isn't remotely useful in
        // the context this library is intended for use in. This implementation uses a
        // DOMDocumentFragment for inserting nodes into. There's no need to have a
        // different process for when there isn't a context. There will always be one:
        // the DOMDocumentFragment.

        static::$self->fragmentContext = (!is_null($context)) ? $context : static::$self->DOMFragment;

        $name = static::$self->fragmentContext->nodeName;
        # Set the state of the HTML parser's tokenization stage as follows:
        if ($name === 'title' || $name === 'textarea') {
            static::$self->tokenizerState = static::RCDATA_STATE;
        } elseif ($name === 'style' || $name === 'xmp' || $name === 'iframe' || $name === 'noembed' || $name === 'noframes') {
            static::$self->tokenizerState = static::RAWTEXT_STATE;
        } elseif ($name === 'script') {
            static::$self->tokenizerState = static::SCRIPT_STATE;
        } elseif ($name === 'noscript') {
            static::$self->tokenizerState = static::NOSCRIPT_STATE;
        } elseif ($name === 'plaintext') {
            static::$self->tokenizerState = static::PLAINTEXT_STATE;
        } else {
            static::$self->tokenizerState = static::DATA_STATE;
        }

        // DEVIATION: Since this implementation uses a DOMDocumentFragment for insertion
        // there is no need to create an html element for inserting stuff into. If the
        // context element is a template element, push "in template" onto the stack of
        // template insertion modes so that it is the new current template insertion
        // mode.
        if ($name === 'template') {
            static::$self->templateInsertionModeStack[] = static::IN_TEMPLATE_MODE;
        }

        # Reset the parser's insertion mode appropriately.

        // DEVIATION: The insertion mode will be always 'in body', not 'before head' if
        // there isn't a context. There isn't a need to reconstruct a valid HTML
        // document when using a DOMDocumentFragment.
        static::$self->resetInsertionMode();

        # Set the parser's form element pointer to the nearest node to the context element
        # that is a form element (going straight up the ancestor chain, and including the
        # element itself, if it is a form element), if any. (If there is no such form
        # element, the form element pointer keeps its initial value, null.)
        static::$self->formElement = ($name === 'form') ? $context : DOM::getAncestor('form', $context);

        # Start the parser and let it run until it has consumed all the characters just inserted into the input stream.
        static::$self->fragmentCase = true;
        static::parse($data, $file);

        # If there is a context element, return the child nodes of root, in tree order.
        # Otherwise, return the children of the Document object, in tree order.

        // DEVIATION: This method will always return a DOMDocumentFragment.
        return static::$self->DOMFragment;
    }

    protected function fixDOM($dom = null) {
        if (is_null($dom)) {
            $dom = &$this->DOM;
        }

        // TODO: Take fragments, append them to a document, fix shit, and then poop out a
        // fragment so selecting id attributes works on fragments.

        // Fix id attributes so they may be selected by the DOM. Fix the PHP id attribute
        // bug. Allows DOMDocument->getElementById() to work on id attributes.

        if (!static::$self->fragmentCase) {
            $dom->relaxNGValidateSource('<grammar xmlns="http://relaxng.org/ns/structure/1.0" datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
 <start>
  <element>
   <anyName/>
   <ref name="anythingID"/>
  </element>
 </start>
 <define name="anythingID">
  <zeroOrMore>
   <choice>
    <element>
     <anyName/>
     <ref name="anythingID"/>
    </element>
    <attribute name="id"><data type="ID"/></attribute>
    <zeroOrMore><attribute><anyName/></attribute></zeroOrMore>
    <text/>
   </choice>
  </zeroOrMore>
 </define>
</grammar>');
        }

        # Normalize the document before outputting.
        $dom->normalize();
        return $dom;
    }

    protected function tokenize() {
        # The tokenizer state machine consists of the states defined in the following
        # subsections.

        // DEVIATION: The tokenizer spec has it work around NULL characters.
        // HTML5DataStream removes all NULL characters from the document instead. There
        // isn't a need to work around them when there isn't any scripting in this
        // implementation. the HTML5DataStream class removes them and triggers parse errors
        // then instead. So, all mentions of "U+0000 NULL" in the spec are ignored.

        while (true) {
            if (static::$debug) {
                echo "State: ";

                switch ($this->tokenizerState) {
                    case static::DATA_STATE: echo "Data\n";
                    break;
                    case static::RCDATA_STATE: echo "RCDATA\n";
                    break;
                    case static::RAWTEXT_STATE: echo "RAWTEXT\n";
                    break;
                    case static::SCRIPT_DATA_STATE: echo "Script data\n";
                    break;
                    case static::PLAINTEXT_STATE: echo "PLAINTEXT\n";
                    break;
                    case static::TAG_OPEN_STATE: echo "Tag open\n";
                    break;
                    case static::END_TAG_OPEN_STATE: echo "End tag open\n";
                    break;
                    case static::TAG_NAME_STATE: echo "Tag name\n";
                    break;
                    case static::RCDATA_LESS_THAN_SIGN_STATE: echo "RCDATA less-than sign\n";
                    break;
                    case static::RCDATA_END_TAG_OPEN_STATE: echo "RCDATA end tag open\n";
                    break;
                    case static::RCDATA_END_TAG_NAME_STATE: echo "RCDATA end tag name\n";
                    break;
                    case static::RAWTEXT_LESS_THAN_SIGN_STATE: echo "RAWTEXT less than sign\n";
                    break;
                    case static::RAWTEXT_END_TAG_OPEN_STATE: echo "RAWTEXT end tag open\n";
                    break;
                    case static::RAWTEXT_END_TAG_NAME_STATE: echo "RAWTEXT end tag name\n";
                    break;
                    case static::SCRIPT_DATA_LESS_THAN_SIGN_STATE: echo "Script data less-than sign\n";
                    break;
                    case static::SCRIPT_DATA_END_TAG_OPEN_STATE: echo "Script data end tag open\n";
                    break;
                    case static::SCRIPT_DATA_END_TAG_NAME_STATE: echo "Script data end tag name\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPE_START_STATE: echo "Script data escape start\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPE_START_DASH_STATE: echo "Script data escape start dash\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_STATE: echo "Script data escaped\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_DASH_STATE: echo "Script data escaped dash\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE: echo "Script data escaped dash dash\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE: echo "Script data escaped less-than sign\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE: echo "Script data escaped end tag open\n";
                    break;
                    case static::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE: echo "Script data escaped end tag name\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE: echo "Script data double escape start\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPED_STATE: echo "Script data double escaped\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_STATE: echo "Script data double escaped dash\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE: echo "Script data double escaped dash dash\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE: echo "Script data double escaped less-than sign\n";
                    break;
                    case static::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE: echo "Script data double escape end\n";
                    break;
                    case static::BEFORE_ATTRIBUTE_NAME_STATE: echo "Before attribute\n";
                    break;
                    case static::ATTRIBUTE_NAME_STATE: echo "Attribute name\n";
                    break;
                    case static::AFTER_ATTRIBUTE_NAME_STATE: echo "After attribute name\n";
                    break;
                    case static::BEFORE_ATTRIBUTE_VALUE_STATE: echo "Before attribute value\n";
                    break;
                    case static::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE: echo "Attribute value (double quoted)\n";
                    break;
                    case static::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE: echo "Attribute value (single quoted)\n";
                    break;
                    case static::ATTRIBUTE_VALUE_UNQUOTED_STATE: echo "Attribute value (unquoted)\n";
                    break;
                    case static::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE: echo "After attribute value (quoted)\n";
                    break;
                    case static::SELF_CLOSING_START_TAG_STATE: echo "Self-closing start tag\n";
                    break;
                    case static::BOGUS_COMMENT_STATE: echo "Bogus comment\n";
                    break;
                    case static::MARKUP_DECLARATION_OPEN_STATE: echo "Markup declaration open\n";
                    break;
                    case static::COMMENT_START_STATE: echo "Comment start\n";
                    break;
                    case static::COMMENT_START_DASH_STATE: echo "Comment start dash\n";
                    break;
                    case static::COMMENT_STATE: echo "Comment\n";
                    break;
                    case static::COMMENT_END_DASH_STATE: echo "Comment end dash\n";
                    break;
                    case static::COMMENT_END_STATE: echo "Comment end\n";
                    break;
                    case static::COMMENT_END_BANG_STATE: echo "Comment end bang\n";
                    break;
                    case static::DOCTYPE_STATE: echo "DOCTYPE\n";
                    break;
                    case static::BEFORE_DOCTYPE_NAME_STATE: echo "Before DOCTYPE name\n";
                    break;
                    case static::DOCTYPE_NAME_STATE: echo "DOCTYPE name\n";
                    break;
                    case static::AFTER_DOCTYPE_NAME_STATE: echo "After DOCTYPE name\n";
                    break;
                    case static::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE: echo "After DOCTYPE public keyword\n";
                    break;
                    case static::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE: echo "Before DOCTYPE public identifier\n";
                    break;
                    case static::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE: echo "DOCTYPE public identifier (double quoted)\n";
                    break;
                    case static::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE: echo "DOCTYPE public identifier (single quoted)\n";
                    break;
                    case static::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE: echo "After DOCTYPE public identifier\n";
                    break;
                    case static::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE: echo "Between DOCTYPE public and system identifiers\n";
                    break;
                    case static::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE: echo "After DOCTYPE system keyword\n";
                    break;
                    case static::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE: echo "Before DOCTYPE system identifier\n";
                    break;
                    case static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE: echo "DOCTYPE system identifier (double-quoted)\n";
                    break;
                    case static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE: echo "DOCTYPE system identifier (single-quoted)\n";
                    break;
                    case static::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE: echo "After DOCTYPE system identifier\n";
                    break;
                    case static::BOGUS_DOCTYPE_STATE: echo "Bogus comment\n";
                    break;
                    case static::CDATA_SECTION_STATE: echo "CDATA section\n";
                }
            }

            # 12.2.4.1 Data state
            if ($this->tokenizerState === static::DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Switch to the character reference in data state.

                    # 8.2.4.2 Character reference in data state:
                    # Switch to the data state.
                    # Attempt to consume a character reference, with no additional allowed character.
                    # If nothing is returned, emit a U+0026 AMPERSAND character (&) token.
                    # Otherwise, emit the character tokens that were returned.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $this->emitToken(new CharacterToken($this->data->consumeCharacterReference()));
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the tag open state.
                    $this->tokenizerState = static::TAG_OPEN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    $token = new EOFToken();
                    $this->emitToken($token);
                    break;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('&<')));
                }
            }

            # 12.2.4.2 Character reference in data state
            // OPTIMIZATION: This is instead done in the block above.

            # 12.2.4.3 RCDATA state
            elseif ($this->tokenizerState === static::RCDATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0026 AMPERSAND (&)
                if ($char === '&') {
                    # Switch to the character reference in RCDATA state.

                    # 8.2.4.4 Character reference in RCDATA state:
                    # Switch to the RCDATA state.
                    # Attempt to consume a character reference, with no additional allowed character.
                    # If nothing is returned, emit a U+0026 AMPERSAND character (&) token.
                    # Otherwise, emit the character tokens that were returned.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $this->emitToken(new CharacterToken($this->data->consumeCharacterReference()));
                }
                # U+003C LESS-THAN SIGN (<)
                elseif ($char === '<') {
                    # Switch to the RCDATA less-than sign state.
                    $this->tokenizerState = static::RCDATA_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    $this->emitToken(new EOFToken());
                    break;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('&<')));
                }
            }

            # 12.2.4.4 Character reference in RCDATA state
            // OPTIMIZATION: This is instead done in the block above.

            # 12.2.4.5 RAWTEXT state
            elseif ($this->tokenizerState === static::RAWTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the RAWTEXT less-than sign state.
                    $this->tokenizerState = static::RAWTEXT_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    $this->emitToken(new EOFToken());
                    break;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('<')));
                }
            }

            # 12.2.4.6 Script data state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+003C LESS-THAN SIGN (<)
                if ($char === '<') {
                    # Switch to the script data less-than sign state.
                    $this->tokenizerState = static::SCRIPT_DATA_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Emit an end-of-file token.
                    $this->emitToken(new EOFToken());
                    break;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('<')));
                }
            }

            # 12.2.4.7 PLAINTEXT state
            elseif ($this->tokenizerState === static::PLAINTEXT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # EOF
                if ($char === '') {
                    # Emit an end-of-file token.
                    $this->emitToken(new EOFToken());
                    break;
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that don't match what is above and emit
                    // that as a character token instead to prevent having to loop back through here
                    // every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('')));
                }
            }

            # 12.2.4.8 Tag open state
            elseif ($this->tokenizerState === static::TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0021 EXCLAMATION MARK (!)
                if ($char === '!') {
                    # Switch to the markup declaration open state.
                    $this->tokenizerState = static::MARKUP_DECLARATION_OPEN_STATE;
                }
                # U+002F SOLIDUS (/)
                elseif ($char === '/') {
                    # Switch to the end tag open state.
                    $this->tokenizerState = static::END_TAG_OPEN_STATE;
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new start tag token, set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point), then switch
                    # to the tag name state. (Don't emit the token yet; further details will be filled
                    # in before it is emitted.)
                    # Lowercase:
                    # Create a new start tag token, set its tag name to the current input character,
                    # then switch to the tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new StartTagToken(strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA)));
                    $this->tokenizerState = static::TAG_NAME_STATE;
                }
                # U+003F QUESTION MARK (?)
                elseif ($char === '?') {
                    # Parse error. Switch to the bogus comment state.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $this->data, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'tag name');
                    }

                    $this->tokenizerState = static::BOGUS_COMMENT_STATE;
                }
                # Anything else
                else {
                    # Parse error. Switch to the data state. Emit a U+003C LESS-THAN SIGN character
                    # token. Reconsume the current input character.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $this->data, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'tag name');
                    }

                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.9 End tag open state
            elseif ($this->tokenizerState === static::END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point), then switch
                    # to the tag name state. (Don't emit the token yet; further details will be filled
                    # in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, set its tag name to the current input character,
                    # then switch to the tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA)));
                    $this->tokenizerState = static::TAG_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state.
                    ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $this->data, $char);
                    $this->tokenizerState = static::DATA_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit a U+003C LESS-THAN SIGN character
                    # token and a U+002F SOLIDUS character token. Reconsume the EOF character.
                    // Making errors more expressive.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'tag name');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken(new CharacterToken('</'));
                    $this->data->unconsume();
                }
                # Anything else
                else {
                   # Parse error. Switch to the bogus comment state.
                   ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $this->data, $char);
                   $this->tokenizerState = static::BOGUS_COMMENT_STATE;
                }
            }

            # 8.2.4.10 Tag name state
            elseif ($this->tokenizerState === static::TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before attribute name state.
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name.

                    // OPTIMIZATION: Consume all characters that are Uppercase ASCII characters to
                    // prevent having to loop back through here every single time.
                    $token->name = $token->name.strtolower($char.$this->data->consumeWhile(static::CTYPE_UPPER));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.

                    // Making errors more expressive.
                    if ($char !== '') {
                        ParseError::trigger(ParseError::TAG_NAME_EXPECTED, $this->data, $char);
                    } else {
                        ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'tag name');
                    }

                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current tag token's tag name.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name = $token->name.$char.$this->data->consumeUntil("\t\n\x0c />".static::CTYPE_UPPER);
                }
            }

            # 8.2.4.11 RCDATA less-than sign state
            elseif ($this->tokenizerState === static::RCDATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the RCDATA end tag open
                    # state.
                    $temporaryBuffer = '';
                    $this->tokenizerState = static::RCDATA_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->tokenizerState = static::RCDATA_STATE;
                    $this->emitToken(new CharacterToken('<'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.12 RCDATA end tag open state
            elseif ($this->tokenizerState === static::RCDATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the RCDATA
                    # end tag name state. (Don't emit the token yet; further details will be filled in
                    # before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the RCDATA end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->tokenizerState = static::RCDATA_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token and a
                    # U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->tokenizerState = static::RCDATA_STATE;
                    $this->emitToken(new CharacterToken('</'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.13 RCDATA end tag name state
            elseif ($this->tokenizerState === static::RCDATA_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->tokenizerState = static::RCDATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->tokenizerState = static::RCDATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::DATA_STATE;
                        $this->emitToken($token);
                    } else {
                        $this->tokenizerState = static::RCDATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the RCDATA state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->tokenizerState = static::RCDATA_STATE;
                    $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.14 RAWTEXT less-than sign state
            elseif ($this->tokenizerState === static::RAWTEXT_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the RAWTEXT end tag open
                    # state.
                    $temporaryBuffer = '';
                    $this->tokenizerState = static::RAWTEXT_END_TAG_OPEN_STATE;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->tokenizerState = static::RAWTEXT_STATE;
                    $this->emitToken(new CharacterToken('<'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.15 RAWTEXT end tag open state
            elseif ($this->tokenizerState === static::RAWTEXT_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the RAWTEXT
                    # end tag name state. (Don't emit the token yet; further details will be filled in
                    # before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the RAWTEXT end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->tokenizerState = static::RAWTEXT_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token and a
                    # U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->tokenizerState = static::RAWTEXT_STATE;
                    $this->emitToken(new CharacterToken('</'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.16 RAWTEXT end tag name state
            elseif ($this->tokenizerState === static::RAWTEXT_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->tokenizerState = static::RAWTEXT_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->tokenizerState = static::RAWTEXT_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::DATA_STATE;
                        $this->emitToken($token);
                    } else {
                        $this->tokenizerState = static::RAWTEXT_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the RAWTEXT state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->tokenizerState = static::RAWTEXT_STATE;
                    $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.17 Script data less-than sign state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data end tag
                    # open state.
                    $temporaryBuffer = '';
                    $this->tokenizerState = static::SCRIPT_DATA_END_TAG_OPEN_STATE;
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Switch to the script data escape start state. Emit a U+003C LESS-THAN SIGN
                    # character token and a U+0021 EXCLAMATION MARK character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPE_START_STATE;
                    $this->emitToken(new CharacterToken('<!'));
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token.
                    # Reconsume the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->emitToken(new CharacterToken('<'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.18 Script data end tag open state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the script
                    # data end tag name state. (Don't emit the token yet; further details will be
                    # filled in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the script data end tag name state. (Don't emit the token yet; further details
                    # will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->tokenizerState = static::SCRIPT_DATA_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token
                    # and a U+002F SOLIDUS character token. Reconsume the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->emitToken(new CharacterToken('</'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.19 Script data end tag name state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::DATA_STATE;
                        $this->emitToken($token);
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.20 Script data escape start state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPE_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escape start dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPE_START_DASH_STATE;
                    $this->emitToken(new CharacterToken('-'));
                }
                # Anything else
                else {
                    # Switch to the script data state. Reconsume the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.21 Script data escape start dash state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPE_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    $this->emitToken(new CharacterToken('-'));
                }
                # Anything else
                else {
                    # Switch to the script data state. Reconsume the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.22 Script data escaped state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_DASH_STATE;
                    $this->emitToken(new CharacterToken('-'));
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->tokenizerState = static::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Emit the current input character as a character token.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $this->emitToken(new CharacterToken($char.$this->data->consumeUntil('-<')));
                }
            }

            # 8.2.4.23 Script data escaped dash state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data escaped dash dash state. Emit a U+002D HYPHEN-MINUS
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE;
                    $this->emitToken(new CharacterToken('-'));
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->tokenizerState = static::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit the current input character as a
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken($char));
                }
            }

            # 8.2.4.24 Script data escaped dash dash state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_DASH_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Emit a U+002D HYPHEN-MINUS character token.
                    $this->emitToken(new CharacterToken('-'));
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data escaped less-than sign state.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state. Emit a U+003E GREATER-THAN SIGN character
                    # token.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->emitToken(new CharacterToken('>'));
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Parse error. Reconsume the EOF character.
                    $this->tokenizerState = static::DATA_STATE;
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'script data');
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit the current input character as a
                    # character token.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken($char));
                }
            }

            # 8.2.4.25 Script data escaped less-than sign state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data escaped
                    # end tag open state.
                    $temporaryBuffer .= '';
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE;
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Set the temporary buffer to the empty string. Append the lowercase version of
                    # the current input character (add 0x0020 to the character's code point) to the
                    # temporary buffer. Switch to the script data double escape start state. Emit a
                    # U+003C LESS-THAN SIGN character token and the current input character as a
                    # character token.
                    # Lowercase:
                    # Set the temporary buffer to the empty string. Append the current input character
                    # to the temporary buffer. Switch to the script data double escape start state.
                    # Emit a U+003C LESS-THAN SIGN character token and the current input character as
                    # a character token.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    $temporaryBuffer = strtolower($char);
                    $this->tokenizerState = static::SCRIPT_DATA_DOUBLE_ESCAPE_START_STATE;
                    $this->emitToken(new CharacterToken('<'.$char));
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit a U+003C LESS-THAN SIGN character
                    # token. Reconsume the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken($char));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.26 Script data escaped end tag open state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_END_TAG_OPEN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # Uppercase ASCII letter
                # Lowercase ASCII letter
                if (ctype_alpha($char)) {
                    # Uppercase:
                    # Create a new end tag token, and set its tag name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Append the
                    # current input character to the temporary buffer. Finally, switch to the script
                    # data escaped end tag name state. (Don't emit the token yet; further details will
                    # be filled in before it is emitted.)
                    # Lowercase:
                    # Create a new end tag token, and set its tag name to the current input character.
                    # Append the current input character to the temporary buffer. Finally, switch to
                    # the script data escaped end tag name state. (Don't emit the token yet; further
                    # details will be filled in before it is emitted.)

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token = new EndTagToken(strtolower($char));
                    $temporaryBuffer .= $char;
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE;
                }
                # Anything else
                else {
                    # Switch to the script data escaped state. Emit a U+003C LESS-THAN SIGN character
                    # token and a U+002F SOLIDUS character token. Reconsume the current input
                    # character.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken('</'));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.27 Script data escaped end tag name state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_ESCAPED_END_TAG_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # before attribute name state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # self-closing start tag state. Otherwise, treat it as per the "anything else"
                    # entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # If the current end tag token is an appropriate end tag token, then switch to the
                    # data state and emit the current tag token. Otherwise, treat it as per the
                    # "anything else" entry below.
                    if ($token->name === $this->stack->currentNode()->name) {
                        $this->tokenizerState = static::DATA_STATE;
                        $this->emitToken($token);
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                        $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                        $this->data->unconsume();
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current tag token's tag name. Append the current
                    # input character to the temporary buffer.
                    # Lowercase:
                    # Append the current input character to the current tag token's tag name. Append
                    # the current input character to the temporary buffer.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= $token->name.strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA));
                    $temporaryBuffer .= $char;
                }
                # Anything else
                else {
                    # Switch to the script data state. Emit a U+003C LESS-THAN SIGN character token, a
                    # U+002F SOLIDUS character token, and a character token for each of the characters
                    # in the temporary buffer (in the order they were added to the buffer). Reconsume
                    # the current input character.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken('</'.$temporaryBuffer));
                    $this->data->unconsume();
                }
            }

            # 8.2.4.29 Script data double escaped state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_DOUBLE_ESCAPED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the script data double escaped dash dash state. Emit a U+002D
                    # HYPHEN-MINUS character token.
                    $this->tokenizerState = static::SCRIPT_DATA_DOUBLE_ESCAPED_DASH_DASH_STATE;
                    $this->emitToken(new CharacterToken('-'));
                }
                # "<" (U+003C)
                elseif ($char === '<') {
                    # Switch to the script data double escaped less-than sign state. Emit a U+003C
                    # LESS-THAN SIGN character token.
                    $this->tokenizerState = static::DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE;
                    $this->emitToken(new CharacterToken('<'));
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the script data state. Emit a U+003E GREATER-THAN SIGN character
                    # token.
                    $this->tokenizerState = static::SCRIPT_DATA_STATE;
                    $this->emitToken(new CharacterToken('>'));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'script data');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Emit the current input character
                    # as a character token.
                    $this->tokenizerState = static::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    $this->emitToken(new CharacterToken($char));
                }
            }

            # 8.2.4.32 Script data double escaped less-than sign state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_DOUBLE_ESCAPED_LESS_THAN_SIGN_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "/" (U+002F)
                if ($char === '/') {
                    # Set the temporary buffer to the empty string. Switch to the script data double
                    # escape end state. Emit a U+002F SOLIDUS character token.
                    $temporaryBuffer = '';
                    $this->tokenizerState === static::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE;
                    $this->emitToken(new CharacterToken('/'));
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Reconsume the current input
                    # character.
                    $this->tokenizerState === static::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.33 Script data double escape end state
            elseif ($this->tokenizerState === static::SCRIPT_DATA_DOUBLE_ESCAPE_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                # "/" (U+002F)
                # ">" (U+003E)
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ' || $char === '/' || $char === '>') {
                    # If the temporary buffer is the string "script", then switch to the script data
                    # escaped state. Otherwise, switch to the script data double escaped state. Emit
                    # the current input character as a character token.
                    if ($temporaryBuffer === 'script') {
                        $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    } else {
                        $this->tokenizerState = static::SCRIPT_DATA_DOUBLE_ESCAPED_STATE;
                        $this->emitToken(new CharacterToken($char));
                    }
                }
                # Uppercase ASCII letter
                # Lowercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Uppercase:
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the temporary buffer. Emit the current input
                    # character as a character token.
                    # Lowercase:
                    # Append the current input character to the temporary buffer. Emit the current
                    # input character as a character token.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $char = $char.$this->data->consumeWhile(static::CTYPE_ALPHA);
                    $temporaryBuffer .= strtolower(strtolower($char));
                    $this->emitToken(new CharacterToken($char));
                }
                # Anything else
                else {
                    # Switch to the script data double escaped state. Reconsume the current input
                    # character.
                    $this->tokenizerState = static::SCRIPT_DATA_ESCAPED_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.34 Before attribute name state
            elseif ($this->tokenizerState === static::BEFORE_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # lowercase version of the current input character (add 0x0020 to the character's
                    # code point), and its value to the empty string. Switch to the attribute name
                    # state.

                    // DEVIATION: Will use a buffer for the attribute name instead.
                    $attributeName = strtolower($char);
                    $attributeValue = '';
                    $this->tokenizerState = static::ATTRIBUTE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute name');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # Anything else
                else {
                    # Quotes, less than sign, equals:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # current input character, and its value to the empty string. Switch to the
                    # attribute name state.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute name');
                    }

                    // DEVIATION: Will use a buffer for the attribute name instead.
                    $attributeName = $char;
                    $attributeValue = '';
                    $this->tokenizerState = static::ATTRIBUTE_NAME_STATE;
                }
            }

            # 8.2.4.35 Attribute name state
            elseif ($this->tokenizerState === static::ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    if ($token->hasAttribute($attributeName)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $this->data, $attributeName);
                    }

                    # Switch to the after attribute name state.
                    $this->tokenizerState = static::AFTER_ATTRIBUTE_NAME_STATE;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    if ($token->hasAttribute($attributeName)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $this->data, $attributeName);
                    }

                    # Switch to the self-closing start tag state.
                    $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    if ($token->hasAttribute($attributeName)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $this->data, $attributeName);
                    }

                    # Switch to the before attribute value state.
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    if ($token->hasAttribute($attributeName)) {
                        ParseError::trigger(ParseError::ATTRIBUTE_EXISTS, $this->data, $attributeName);
                    }

                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;

                    // Need to add the current attribute name and value to the token if necessary.
                    if ($attributeName) {
                        $token->setAttribute($attributeName, $attributeValue);
                    }
                    $this->emitToken($token);
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current attribute's name.

                    // OPTIMIZATION: Consume all characters that are uppercase ASCII letters to prevent
                    // having to loop back through here every single time.
                    $attributeName .= strtolower($char.$this->data-consumeWhile(static::CTYPE_UPPER));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute name');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # Anything else
                else {
                    # Quotes, less than sign, equals:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's name.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute name');
                    }

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attributeName .= $char.$this->data->consumeUntil("\t\n\x0c /=>\"'<".static::CTYPE_UPPER);
                }

                # When the user agent leaves the attribute name state (and before emitting the tag
                # token, if appropriate), the complete attribute's name must be compared to the
                # other attributes on the same token; if there is already an attribute on the
                # token with the exact same name, then this is a parse error and the new attribute
                # must be removed from the token.

                // DEVIATION: Because this implementation uses a buffer to hold the attribute name
                // it is only added if it is valid. The result is the same, though.
            }

            # 8.2.4.36 After attribute name state
            elseif ($this->tokenizerState === static::AFTER_ATTRIBUTE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                }
                # "=" (U+003D)
                elseif ($char === '=') {
                    # Switch to the before attribute value state.
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;

                    // Need to add the current attribute name and value to the token if necessary.
                    if ($attributeName) {
                        $token->setAttribute($attributeName, $attributeValue);
                    }
                    $this->emitToken($token);
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # lowercase version of the current input character (add 0x0020 to the character's
                    # code point), and its value to the empty string. Switch to the attribute name
                    # state.

                    // DEVIATION: Will use a buffer for the attribute name instead.
                    $attributeName = strtolower($char);
                    $attributeValue = '';
                    $this->tokenizerState = static::ATTRIBUTE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute name, attribute value, or tag end');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # Anything else
                else {
                    # Quotes, less than sign, equals:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Start a new attribute in the current tag token. Set that attribute's name to the
                    # current input character, and its value to the empty string. Switch to the
                    # attribute name state.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute name, attribute value, or tag end');
                    }

                    $attributeName = $char;
                    $attributeValue = '';
                    $this->tokenizerState = static::ATTRIBUTE_NAME_STATE;
                }
            }

            # 8.2.4.37 Before attribute value state
            elseif ($this->tokenizerState === static::BEFORE_ATTRIBUTE_VALUE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Switch to the attribute value (double-quoted) state.
                    $this->tokenizerState = static::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the attribute value (unquoted) state. Reconsume the current input
                    # character.
                    $this->tokenizerState = static::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                    $this->data->unconsume();
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Switch to the attribute value (single-quoted) state.
                    $this->tokenizerState = static::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the current tag token.
                    ParseError::trigger(ParseError::UNEXPECTED_TAG_END, $this->data, 'attribute value');
                    $this->tokenizerState = static::DATA_STATE;

                    // Need to add the current attribute name and value to the token if necessary.
                    if ($attributeName) {
                        $token->setAttribute($attributeName, $attributeValue);
                    }
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute value');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # "<" (U+003C)
                # "=" (U+003D)
                # "`" (U+0060)
                # Anything else
                else {
                    # less than sign, equals, tick:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's value. Switch to
                    # the attribute value (unquoted) state.

                    if ($char === '<' || $char === '=' || $char === '`') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute value');
                    }

                    $attributeValue .= $char;
                    $this->tokenizerState = static::ATTRIBUTE_VALUE_UNQUOTED_STATE;
                }
            }

            # 8.2.4.38 Attribute value (double-quoted) state
            elseif ($this->tokenizerState === static::ATTRIBUTE_VALUE_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    $this->tokenizerState = static::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being U+0022 QUOTATION MARK (").

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attributeValue .= $this->data->consumeCharacterReference('"', true);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute value');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.
                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attributeValue .= $char.$this->data->consumeUntil('"&');
                }
            }

            # 8.2.4.39 Attribute value (single-quoted) state
            elseif ($this->tokenizerState === static::ATTRIBUTE_VALUE_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    $this->tokenizerState = static::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being "'" (U+0027).

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    # DEVIATION: This implementation does the character reference consuming in a
                    # function for which it is more suited for.
                    $attributeValue .= $this->data->consumeCharacterReference("'", true);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute value');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current attribute's value.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attributeValue .= $char.$this->data->consumeUntil("'&");
                }
            }


            # 8.2.4.40 Attribute value (unquoted) state
            elseif ($this->tokenizerState === static::ATTRIBUTE_VALUE_UNQUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_VALUE_STATE;
                }
                # U+0026 AMPERSAND (&)
                elseif ($char === '&') {
                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being ">" (U+003E).

                    # Switch to the character reference in attribute value state, with the additional
                    # allowed character being "'" (U+0027).

                    # 8.2.4.41 Character reference in attribute value state:
                    # Attempt to consume a character reference.
                    # If nothing is returned, append a U+0026 AMPERSAND character (&) to the current
                    # attribute's value.
                    # Otherwise, append the returned character tokens to the current attribute's
                    # value.
                    # Finally, switch back to the attribute value state that switched into this state.

                    // DEVIATION: This implementation does the character reference consuming in a
                    // function for which it is more suited for.
                    $attributeValue .= $this->data->consumeCharacterReference('>', true);
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;

                    // Need to add the current attribute name and value to the token if necessary.
                    if ($attributeName) {
                        $token->setAttribute($attributeName, $attributeValue);
                    }
                    $this->emitToken($token);
                }
                # Parse error. Switch to the data state. Reconsume the EOF character.
                elseif ($char === '') {
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute value');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # U+0022 QUOTATION MARK (")
                # "'" (U+0027)
                # "<" (U+003C)
                # "=" (U+003D)
                # "`" (U+0060)
                # Anything else
                else {
                    # Quotes, less than sign, equals, tick:
                    # Parse error. Treat it as per the "anything else" entry below.
                    # Anything else:
                    # Append the current input character to the current attribute's value.

                    if ($char === '"' || $char === "'" || $char === '<' || $char === '=' || $char === '`') {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute value');
                    }

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $attributeValue .= $char.$this->data->consumeUntil("\t\n\x0c &>\"'<=`");
                }
            }

            # 8.2.4.42 After attribute value (quoted) state
            elseif ($this->tokenizerState === static::AFTER_ATTRIBUTE_VALUE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before attribute name state.
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                }
                # "/" (U+002F)
                elseif ($char === '/') {
                    # Switch to the self-closing start tag state.
                    $this->tokenizerState = static::SELF_CLOSING_START_TAG_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current tag token.
                    $this->tokenizerState = static::DATA_STATE;

                    // Need to add the current attribute name and value to the token if necessary.
                    if ($attributeName) {
                        $token->setAttribute($attributeName, $attributeValue);
                    }
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'attribute name or tag end');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Switch to the before attribute name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'attribute name or tag end');
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.43 Self-closing start tag state
            elseif ($this->tokenizerState === static::SELF_CLOSING_START_TAG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Set the self-closing flag of the current tag token. Switch to the data state.
                    # Emit the current tag token.
                    $token->selfClosing = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'tag end');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Switch to the before attribute name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'tag end');
                    $this->tokenizerState = static::BEFORE_ATTRIBUTE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.44 Bogus comment state
            elseif ($this->tokenizerState === static::BOGUS_COMMENT_STATE) {
                # Consume every character up to and including the first ">" (U+003E) character or
                # the end of the file (EOF), whichever comes first. Emit a comment token whose
                # data is the concatenation of all the characters starting from and including the
                # character that caused the state machine to switch into the bogus comment state,
                # up to and including the character immediately before the last consumed character
                # (i.e. up to the character just before the U+003E or EOF character), but with any
                # U+0000 NULL characters replaced by U+FFFD REPLACEMENT CHARACTER characters. (If
                # the comment was started by the end of the file (EOF), the token is empty.
                # Similarly, the token is empty if it was generated by the string "<!>".)

                $char = $char.$this->data->consumeUntil('>');
                $nextChar = $this->data->consume();
                $this->emitToken(new CommentToken($char));

                # Switch to the data state.
                $this->tokenizerState = static::DATA_STATE;

                # If the end of the file was reached, reconsume the EOF character.
                if ($nextChar === '') {
                    $this->data->unconsume();
                }
            }

            # 8.2.4.45 Markup declaration open state
            elseif ($this->tokenizerState === static::MARKUP_DECLARATION_OPEN_STATE) {
                # If the next two characters are both "-" (U+002D) characters, consume those two
                # characters, create a comment token whose data is the empty string, and switch to
                # the comment start state.
                if ($this->data->peek(2) === '--') {
                    $this->data->consume(2);
                    $token = new CommentToken();
                    $this->tokenizerState = static::COMMENT_START_STATE;
                }
                # Otherwise, if the next seven characters are an ASCII case-insensitive match for
                # the word "DOCTYPE", then consume those characters and switch to the DOCTYPE
                # state.
                elseif (strtolower($this->data->peek(7)) === 'doctype') {
                    $this->data->consume(7);
                    $this->tokenizerState = static::DOCTYPE_STATE;
                }
                # Otherwise, if there is an adjusted current node and it is not an element in the
                # HTML namespace and the next seven characters are a case-sensitive match for the
                # string "[CDATA[" (the five uppercase letters "CDATA" with a U+005B LEFT SQUARE
                # BRACKET character before and after), then consume those characters and switch to
                # the CDATA section state.
                else {
                    $adjustedCurrentNode = $this->stack->adjustedCurrentNode;
                    if ($adjustedCurrentNode && $adjustedCurrentNode->namespace !== static::HTML_NAMESPACE && $this->data->peek(7) === '[CDATA[') {
                        $this->data->consume(7);
                        $this->tokenizerState = static::CDATA_SECTION_STATE;
                    }
                    # Otherwise, this is a parse error. Switch to the bogus comment state. The next
                    # character that is consumed, if any, is the first character that will be in the
                    # comment.
                    else {
                        $char = $this->data->consume();
                        if ($char !== '') {
                            ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'markup declaration');
                        } else {
                            ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'markup declaration');
                        }

                        $this->tokenizerState = static::BOGUS_COMMENT_STATE;
                    }
                }
            }

            # 8.2.4.46 Comment start state
            elseif ($this->tokenizerState === static::COMMENT_START_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment start dash state.
                    $this->tokenizerState = static::COMMENT_START_DASH_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the comment token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data. Switch to the
                    # comment state.
                    $token->data .= $char;
                    $this->tokenizerState = static::COMMENT_STATE;
                }
            }

            # 8.2.4.47 Comment start dash state
            elseif ($this->tokenizerState === static::COMMENT_START_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment start dash state.
                    $this->tokenizerState = static::COMMENT_END_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Switch to the data state. Emit the comment token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append a "-" (U+002D) character and the current input character to the comment
                    # token's data. Switch to the comment state.
                    $token->data .= '-'.$char;
                    $this->tokenizerState = static::COMMENT_STATE;
                }
            }

            # 8.2.4.48 Comment state
            elseif ($this->tokenizerState === static::COMMENT_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment end dash state
                    $this->tokenizerState = static::COMMENT_END_DASH_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the comment token's data.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->data .= $char.$this->data->consumeUntil('-');
                }
            }

            # 8.2.4.49 Comment end dash state
            elseif ($this->tokenizerState === static::COMMENT_END_DASH_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Switch to the comment end state
                    $this->tokenizerState = static::COMMENT_END_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                   # Append a "-" (U+002D) character and the current input character to the comment
                   # token's data. Switch to the comment state.
                   $token->data .= '-'.$char;
                   $this->tokenizerState = static::COMMENT_STATE;
                }
            }

            # 8.2.4.50 Comment end state
            elseif ($this->tokenizerState === static::COMMENT_END_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state. Emit the comment token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # "!" (U+0021)
                elseif ($char === '!') {
                    # Parse error. Switch to the comment end bang state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '!', 'comment end');
                    $this->tokenizerState = static::COMMENT_END_BANG_STATE;
                }
                # "-" (U+002D)
                elseif ($char === '-') {
                    # Parse error. Append a "-" (U+002D) character to the comment token's data.

                    // OPTIMIZATION: Consume all '-' characters to prevent having to loop back through
                    // here every single time.
                    $char .= $this->data->consumeWhile('-');
                    for ($i = 0; $i < strlen($char); $i++) {
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '-', 'comment end');
                    }

                    $token->data .= $char;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment end');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Append two "-" (U+002D) characters and the current input character
                    # to the comment token's data. Switch to the comment state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'comment end');
                    $token->data .= '--'.$char;
                    $this->tokenizerState = static::COMMENT_STATE;
                }
            }

            # 8.2.4.51 Comment end bang state
            elseif ($this->tokenizerState === static::COMMENT_END_BANG_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "-" (U+002D)
                if ($char === '-') {
                    # Append two "-" (U+002D) characters and a "!" (U+0021) character to the comment
                    # token's data. Switch to the comment end dash state.
                    $token->data .= '--!';
                    $this->tokenizerState = static::COMMENT_END_DASH_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the comment token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Emit the comment token. Reconsume the EOF
                    # character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'comment end');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append two "-" (U+002D) characters, a "!" (U+0021) character, and the current
                    # input character to the comment token's data. Switch to the comment state.
                    $token->data .= '--!'.$char;
                    $this->tokenizerState = static::COMMENT_STATE;
                }
            }

            # 8.2.4.52 DOCTYPE state
            elseif ($this->tokenizerState === static::DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE name state.

                    // Spec doesn't say to create a token here, but if you don't it leads to a
                    // situation where a token doesn't exist.
                    $token = new DOCTYPEToken();
                    $this->tokenizerState = static::DOCTYPE_NAME_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Create a new DOCTYPE token. Set its
                    # force-quirks flag to on. Emit the token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE');
                    $this->tokenizerState = static::DATA_STATE;
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Switch to the before DOCTYPE name state. Reconsume the character.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE');
                    $this->tokenizerState = static::DOCTYPE_NAME_STATE;
                    $this->data->unconsume();
                }
            }

            # 8.2.4.53 Before DOCTYPE name state
            elseif ($this->tokenizerState === static::BEFORE_DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # Uppercase ASCII letter
                elseif (ctype_upper($char)) {
                    # Create a new DOCTYPE token. Set the token's name to the lowercase version of the
                    # current input character (add 0x0020 to the character's code point). Switch to
                    # the DOCTYPE name state.
                    $token = new DOCTYPEToken($char);
                    $token->tokenizerState = static::DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Create a new DOCTYPE token. Set its force-quirks flag to on. Switch
                    # to the data state. Emit the token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE');
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Create a new DOCTYPE token. Set its
                    # force-quirks flag to on. Emit the token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE');
                    $this->tokenizerState = static::DATA_STATE;
                    $token = new DOCTYPEToken();
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Create a new DOCTYPE token. Set the token's name to the current input character.
                    # Switch to the DOCTYPE name state.
                    $token = new DOCTYPEToken($char);
                    $token->tokenizerState = static::DOCTYPE_NAME_STATE;
                }
            }

            # 8.2.4.54 DOCTYPE name state
            elseif ($this->tokenizerState === static::DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the after DOCTYPE name state.
                    $this->tokenizerState = static::AFTER_DOCTYPE_NAME_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # Uppercase ASCII letter
                elseif (ctype_alpha($char)) {
                    # Append the lowercase version of the current input character (add 0x0020 to the
                    # character's code point) to the current DOCTYPE token's name.

                    // OPTIMIZATION: Will just check for alpha characters and strtolower the
                    // characters.
                    // OPTIMIZATION: Consume all characters that are ASCII characters to prevent having
                    // to loop back through here every single time.
                    $token->name .= strtolower($char.$this->data->consumeWhile(static::CTYPE_ALPHA));
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's name.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->name .= $char.$this->data->consumeUntil("\t\n\x0c >".static::CTYPE_ALPHA);
                }
            }

            # 8.2.4.55 After DOCTYPE name state
            elseif ($this->tokenizerState === static::AFTER_DOCTYPE_NAME_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the after DOCTYPE name state.
                    continue;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE name');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # If the six characters starting from the current input character are an ASCII
                    # case-insensitive match for the word "PUBLIC", then consume those characters and
                    # switch to the after DOCTYPE public keyword state.
                    // Simpler to just consume and then unconsume if they're not needed.
                    $char .= $this->data->consume(5);
                    if (strtolower($char) === 'public') {
                        $this->tokenizerState = static::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE;
                    }
                    # Otherwise, if the six characters starting from the current input character are
                    # an ASCII case-insensitive match for the word "SYSTEM", then consume those
                    # characters and switch to the after DOCTYPE system keyword state.
                    elseif (strtolower($char) === 'system') {
                        $this->tokenizerState = static::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE;
                    }
                    # Otherwise, this is a parse error. Set the DOCTYPE token's force-quirks flag to
                    # on. Switch to the bogus DOCTYPE state.
                    else {
                        // Need to unconsume what was consumed earlier.
                        $this->data->unconsume(5);
                        ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char[0], 'DOCTYPE name');
                        $token->forceQuirks = true;
                        $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                    }
                }
            }

            # 8.2.4.56 After DOCTYPE public keyword state
            elseif ($this->tokenizerState === static::AFTER_DOCTYPE_PUBLIC_KEYWORD_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE public identifier state.
                    $this->tokenizerState = static::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Parse error. Set the DOCTYPE token's public identifier to the empty string (not
                    # missing), then switch to the DOCTYPE public identifier (double-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '"', 'DOCTYPE public keyword');
                    $token->public = '';
                    $this->tokenizerState = static::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Parse error. Set the DOCTYPE token's public identifier to the empty string (not
                    # missing), then switch to the DOCTYPE public identifier (single-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, "'", 'DOCTYPE public keyword');
                    $token->public = '';
                    $this->tokenizerState = static::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE public keyword');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public keyword');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE public keyword');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.57 Before DOCTYPE public identifier state
            elseif ($this->tokenizerState === static::BEFORE_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    # then switch to the DOCTYPE public identifier (double-quoted) state.
                    $token->public = '';
                    $this->tokenizerState = static::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's public identifier to the empty string (not missing),
                    # then switch to the DOCTYPE public identifier (single-quoted) state.
                    $token->public = '';
                    $this->tokenizerState = static::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.58 DOCTYPE public identifier (double-quoted) state
            elseif ($this->tokenizerState === static::DOCTYPE_PUBLIC_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->tokenizerState = static::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's public identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil('">');
                }
            }

            # 8.2.4.59 DOCTYPE public identifier (single-quoted) state
            elseif ($this->tokenizerState === static::DOCTYPE_PUBLIC_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE public identifier state.
                    $this->tokenizerState = static::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's public identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->public .= $char.$this->data->consumeUntil("'>");
                }
            }

            # 8.2.4.60 After DOCTYPE public identifier state
            elseif ($this->tokenizerState === static::AFTER_DOCTYPE_PUBLIC_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the between DOCTYPE public and system identifiers state.
                    $this->tokenizerState = static::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.61 Between DOCTYPE public and system identifiers state
            elseif ($this->tokenizerState === static::BETWEEN_DOCTYPE_PUBLIC_AND_SYSTEM_IDENTIFIERS_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE public identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE public identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.62 After DOCTYPE system keyword state
            elseif ($this->tokenizerState === static::AFTER_DOCTYPE_SYSTEM_KEYWORD_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the before DOCTYPE system identifier state.
                    $this->tokenizerState = static::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Parse error. Set the DOCTYPE token's system identifier to the empty string (not
                    # missing), then switch to the DOCTYPE system identifier (double-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '"', 'DOCTYPE system keyword');
                    $token->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Parse error. Set the DOCTYPE token's system identifier to the empty string (not
                    # missing), then switch to the DOCTYPE system identifier (single-quoted) state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, "'", 'DOCTYPE system keyword');
                    $token->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE system keyword');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE system keyword');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE system keyword');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.63 Before DOCTYPE system identifier state
            elseif ($this->tokenizerState === static::BEFORE_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Ignore the character.
                    continue;
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $token->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $token->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.64 DOCTYPE system identifier (double-quoted) state
            elseif ($this->tokenizerState === static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # U+0022 QUOTATION MARK (")
                if ($char === '"') {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->tokenizerState = static::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil('">');
                }
            }

            # 8.2.4.65 DOCTYPE system identifier (single-quoted) state
            elseif ($this->tokenizerState === static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "'" (U+0027)
                if ($char === "'") {
                    # Switch to the after DOCTYPE system identifier state.
                    $this->tokenizerState = static::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>') {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the data
                    # state. Emit that DOCTYPE token.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, '>', 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Append the current input character to the current DOCTYPE token's system identifier.

                    // OPTIMIZATION: Consume all characters that aren't listed above to prevent having
                    // to loop back through here every single time.
                    $token->system .= $char.$this->data->consumeUntil("'>");
                }
            }

            # 8.2.4.66 After DOCTYPE system identifier state
            elseif ($this->tokenizerState === static::AFTER_DOCTYPE_SYSTEM_IDENTIFIER_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # "tab" (U+0009)
                # "LF" (U+000A)
                # "FF" (U+000C)
                # U+0020 SPACE
                if ($char === "\t" || $char === "\n" || $char === "\x0c" || $char === ' ') {
                    # Switch to the between DOCTYPE system and system identifiers state.
                    $this->tokenizerState = static::BETWEEN_DOCTYPE_SYSTEM_AND_SYSTEM_IDENTIFIERS_STATE;
                }
                # ">" (U+003E)
                elseif ($char === '>')  {
                    # Switch to the data state. Emit the current DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # U+0022 QUOTATION MARK (")
                elseif ($char === '"') {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (double-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_DOUBLE_QUOTED_STATE;
                }
                # "'" (U+0027)
                elseif ($char === "'") {
                    # Set the DOCTYPE token's system identifier to the empty string (not missing),
                    # then switch to the DOCTYPE system identifier (single-quoted) state.
                    $this->system = '';
                    $this->tokenizerState = static::DOCTYPE_SYSTEM_IDENTIFIER_SINGLE_QUOTED_STATE;
                }
                # EOF
                elseif ($char === '') {
                    # Parse error. Switch to the data state. Set the DOCTYPE token's force-quirks flag
                    # to on. Emit that DOCTYPE token. Reconsume the EOF character.
                    ParseError::trigger(ParseError::UNEXPECTED_EOF, $this->data, 'DOCTYPE system identifier');
                    $this->tokenizerState = static::DATA_STATE;
                    $token->forceQuirks = true;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Parse error. Set the DOCTYPE token's force-quirks flag to on. Switch to the
                    # bogus DOCTYPE state.
                    ParseError::trigger(ParseError::UNEXPECTED_CHARACTER, $this->data, $char, 'DOCTYPE system identifier');
                    $token->forceQuirks = true;
                    $this->tokenizerState = static::BOGUS_DOCTYPE_STATE;
                }
            }

            # 8.2.4.67 Bogus DOCTYPE state
            elseif ($this->tokenizerState === static::BOGUS_DOCTYPE_STATE) {
                # Consume the next input character
                $char = $this->data->consume();

                # ">" (U+003E)
                if ($char === '>') {
                    # Switch to the data state. Emit the DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                }
                # EOF
                elseif ($char === '') {
                    # Switch to the data state. Emit the DOCTYPE token.
                    $this->tokenizerState = static::DATA_STATE;
                    $this->emitToken($token);
                    $this->data->unconsume();
                }
                # Anything else
                else {
                    # Ignore the character.
                    continue;
                }
            }

            # 8.2.4.68 CDATA section state
            elseif ($this->tokenizerState === static::CDATA_SECTION_STATE) {
                # Switch to the data state.
                $this->tokenizerState = static::DATA_STATE;

                # Consume every character up to the next occurrence of the three character
                # sequence U+005D RIGHT SQUARE BRACKET U+005D RIGHT SQUARE BRACKET U+003E
                # GREATER-THAN SIGN (]]>), or the end of the file (EOF), whichever comes first.
                # Emit a series of character tokens consisting of all the characters consumed
                # except the matching three character sequence at the end (if one was found before
                # the end of the file).
                $char = '';
                while (true) {
                    $char .= $this->data->consumeUntil(']');
                    $peek = $this->data->peek(3);
                    $peeklen = strlen($peek);

                    if ($peek === ']]>') {
                        $this->data->consume(3);
                        $this->emitToken(new CharacterToken($char));
                        break;
                    } elseif ($peek === '') {
                        $this->emitToken(new CharacterToken($char));

                        # If the end of the file was reached, reconsume the EOF character.
                        $this->data->unconsume();
                        break;
                    } elseif ($peeklen < 3) {
                        $char .= $this->data->consume($peeklen);
                        $this->emitToken(new CharacterToken($char));

                        # If the end of the file was reached, reconsume the EOF character.
                        $this->data->unconsume();
                        break;
                    } else {
                        $char .= $this->data->consume();
                    }
                }
            }

        }
    }

    protected function emitToken($token) {
        $quirksMode = false;

        var_export($token);
        echo "\n\n";

        if ($token instanceof StartTagToken && !$token->selfClosing) {
            $this->stack[] = $token;
        } elseif ($token instanceof EndTagToken) {
            $this->stack->pop();
        }
    }

}