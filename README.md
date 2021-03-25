# HTML #

Tools for parsing and printing HTML5 documents and fragments.

```php
<?php
$dom = MensBeam\HTML\Parser::parse('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
?>
```

or:

```php
<?php
$dom = new MensBeam\HTML\Document;
$dom->loadHTML('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
?>
```

## Comparison with `masterminds/html5` ##

This library and [masterminds/html5](https://packagist.org/packages/masterminds/html5) serve similar purposes. Generally, we are more accurate, but they are much faster. The following table summarizes the main functional differences.

|                                                     | DOMDocument                           | Masterminds                                              | MensBeam                               |
|-----------------------------------------------------|---------------------------------------|----------------------------------------------------------|----------------------------------------|
| Minimum PHP version                                 | 5.0                                   | 5.3                                                      | 7.1                                    |
| Extensions required                                 | dom                                   | dom, ctype, mbstring or iconv                            | dom                                    |
| Target HTML version                                 | HTML 4.01                             | HTML 5.0                                                 | WHATWG Living Standard                 |
| Supported encodings                                 | System-dependent                      | System-dependent                                         | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#character-encodings) |
| Encoding detection                                  | BOM, http-equiv                       | None                                                     | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#determining-the-character-encoding) (Steps 1-5 & 9) |
| Fallback encoding                                   | ISO 8859-1                            | UTF-8, configurable                                      | Windows-1252, configurable             |
| Handling of invalid characters                      | Bytes are passed through              | Characters are dropped                                   | [Per specification](https://encoding.spec.whatwg.org/#concept-encoding-process) |
| Handling of invalid XML element names               | Variable                              | Name is changed to "invalid"                             | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#coercing-an-html-dom-into-an-infoset) |
| Handling of invalid XML attribute names             | Variable                              | Attribute is dropped                                     | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#coercing-an-html-dom-into-an-infoset) |
| Handling of misnested tags                          | Parent end tags always close children | Parent end tags always close children                    | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#an-introduction-to-error-handling-and-strange-cases-in-the-parser) |
| Handling of data between table cells                | Left as-is                            | Left as-is                                               | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#an-introduction-to-error-handling-and-strange-cases-in-the-parser) |
| Handling of omitted start tags                      | Elements are not inserted             | Elements are not inserted                                | Per specification                      |
| Handling of processing instructions                 | Processing instructions are retained  | Processing instructions are retained                     | Per specification                      |
| Handling of bogus XLink namespace\*                 | Foreign content not supported         | XLink attributes are lost if preceded by bogus namespace | Bogus namespace is ignored             |
| Namespace for HTML elements                         | Null                                  | Per specification, configurable                          | Null                                   |
| Time needed to parse single-page HTML specification | 0.5 seconds                           | 2.7 seconds†                                             | 6.0 seconds‡                           |
| Peak memory needed for same                         | 11.6 MB                               | 38 MB                                                    | 13.9 MB                                |

\* For example: `<svg xmlns:xlink='http://www.w3.org/1999/xhtml' xlink:href='http://example.com/'/>`. It is unclear what correct behaviour is, but we believe our behaviour to be more consistent with the intent of the specification.

† With HTML namespace disabled. With HTML namespace enabled it does not finish in a reasonable time due to a PHP bug.

‡ With parse errors suppressed. Reporting parse errors adds approximately 10% overhead.

## Document Object Model ##

This library works by parsing HTML strings into PHP's existing XML DOM. It, however, has to force the antiquated PHP DOM extension into working properly with modern HTML DOM by extending many of the node types. The documentation below follows PHP's doc style guide with the exception of inherited methods and properties not being listed. Therefore, only new constants, properties, and methods will be listed; in addition, extended methods which change outward behavior from their parent class will be listed.

### MensBeam\\HTML\\Document ###

```php
MensBeam\HTML\Document extends \DOMDocument {

    /* Constants */
    public const NO_QUIRKS_MODE = 0;
    public const QUIRKS_MODE = 1;
    public const LIMITED_QUIRKS_MODE = 2;

    /* Properties */
    public string|null $documentEncoding = null;
    public int $quirksMode = 0;

    /* Methods */
    public load ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public loadHTML ( string $source , null $options = null , string|null $encodingOrContentType = null ) : bool
    public loadHTMLFile ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public loadXML ( string $source , null $options = null ) : false
    public save ( string $filename , null $options = null ) : int|false
    public saveXML ( DOMNode|null $node = null , null $options = null ) : false
    public validate ( ) : true
    public xinclude ( null $options = null ) : false

}
```

#### Properties ####

<dl>
 <dt>documentEncoding</dt>
 <dd>Encoding of the document, as specified when parsing or when determining encoding type.</dd>
 <dt>quirksMode</dt>
 <dd>Used when parsing. Can be not in quirks mode, quirks mode, or limited quirks mode. See the `MensBeam\HTML\Document` constants to see the valid values.</dd>
</dl>

The following properties inherited from `\DOMDocument` have no effect on `Mensbeam\HTML\Document`:

* actualEncoding
* config
* encoding
* formatOutput
* preserveWhiteSpace
* recover
* resolveExternals
* standalone
* substituteEntities
* validateOnParse
* version
* xmlEncoding
* xmlStandalone
* xmlVersion