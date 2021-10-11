# HTML-Parser

A modern, accurate HTML parser for PHP.

## Usage

### Parsing documents

```php
public MensBeam\HTML\Parser::parse(
    string $data, 
    ?string $encodingOrContentType = null. 
    ?MensBeam\HTML\Parser\Config $config = null
): MensBeam\HTML\Parser\Output
```

The `MensBeam\HTML\Parser::parse` static method is used to parse document. An arbitrary string (and optional encoding) are taken as input, and a `MensBeam\HTML\Parser\Output` object is returned as output. The `Output` object has the following properties:

- `document`: A `DOMDocument` object representing the parsed document
- `encoding`: The original character encoding of the document, as supplied by the user or otherwise detected during parsing
- `quirksMode`: The detected "quirks mode" property of the document. This will be one of `Parser::NO_QURIKS_MODE` (`0`), `Parser::QUIRKS_MODE` (`1`), or `Parser::LIMITED_QUIRKS_MODE` (`2`)
- `errors`: An array containing the list of parse errors emitted during processing if parse error reporting was turned on (see **Configuration** below), or `null` otherwise

Extra configuration parameters may be given to the parser by passing a `MensBeam\HTML\Parser\Config` object as the final `$config` argument. See the **Configuration** section below for more details.

### Parsing fragments

```php
public MensBeam\HTML\Parser::parse(
    DOMElement $contextElement,
    int $quirksMode,
    string $data, 
    ?string $encodingOrContentType = null. 
    ?MensBeam\HTML\Parser\Config $config = null
): DOMDocumentFragment
```

The `MensBeam\HTML\Parser::parseFragment` static method is used to parse document fragments. The primary use case for this method is in the implementation of the `innerHTML` setter of HTML elements. Consequently a context element is required, as well as the "quirks mode" property of the context element's document (which must be one of `Parser::NO_QURIKS_MODE` (`0`), `Parser::QUIRKS_MODE` (`1`), or `Parser::LIMITED_QUIRKS_MODE` (`2`)). The further arguments are identical to those used when parsing documents.

If the "quirks mode" property of the document is not know, using `Parser::NO_QUIRKS_MODE` (`0`) is usually the best choice.

Unlike the `parse()` method, the `parseFragment()` method returns a `DOMDocumentFragment` object belonging to `$contextElement`'s owner document.

### Examples

- Parsing a document with unknown encoding:

  ```php
  use MensBeam\HTML\Parser;

  echo Parser::parse('<!DOCTYPE html><b>Hello world!</b>')->encoding;
  // prints "windows-1252"
  echo Parser::parse('<!DOCTYPE html><meta charset="UTF-8"><b>Hello world!</b>')->encoding;
  // prints "UTF-8"
  ```

- Parsing a document with a known encoding:

  ```php
  use MensBeam\HTML\Parser;

  echo Parser::parse("<!DOCTYPE html>\u{3088}", "UTF-8")
    ->document
    ->getElementsByTagName("body")[0]
    ->textContent;
  // prints "よ"
  echo Parser::parse("<!DOCTYPE html>\u{3088}", "text/html; charset=utf-8")
    ->document
    ->getElementsByTagName("body")[0]
    ->textContent;
  // also prints "よ"
  ```

- Parsing a document with a different default encoding:

  ```php
  use MensBeam\HTML\Parser;
  use MensBeam\HTML\Parser\Config;

  $config = new Config;
  $config->encodingFallback = "Shift_JIS";

  echo Parser::parse("<!DOCTYPE html>\x82\xE6", null, $config)
    ->document
    ->getElementsByTagName("body")[0]
    ->textContent;
  // also also prints "よ"
  ```

- Parsing document fragments:

  ```php
  use MensBeam\HTML\Parser;
  use MensBeam\HTML\Parser\Config;

  $config = new Config;
  $config->htmlNamespace = true;

  // set up two context nodes
  $document = Parser::parse("<!DOCTYPE html><math></math>", "UTF-8", $config)->document;
  $body = $document->getElementsByTagName("body")[0];
  $math = $document->getElementsByTagName("math")[0];
  echo $body->namespaceURI; // prints "http://www.w3.org/1999/xhtml"
  echo $math->namespaceURI; // prints "http://www.w3.org/1998/Math/MathML"

  // parse two identical fragments using different context elements
  $htmlFragment = Parser::parseFragment($body, 0, "<mi>&pi;</mi>", "UTF-8", $config);
  $mathFragment = Parser::parseFragment($math, 0, "<mi>&pi;</mi>", "UTF-8", $config);
  echo $htmlFragment->firstChild->namespaceURI; // prints "http://www.w3.org/1999/xhtml"
  echo $mathFragment->firstChild->namespaceURI; // prints "http://www.w3.org/1998/Math/MathML"
  ```

## Configuration

The `MensBeam\HTML\Parser\Config` class is used as a container for configuration parameters for the parser. We have tried to use rational defaults, but some parameters are nevertheless configurable:

- `documentClass`: The PHP class to use when constructing the document object. This class must be a subclass of `DOMDocument`. By default `DOMDocument` is used. Using another class may affect performance, especially with large documents; users are advised to conduct their own benchmarks
- `encodingFallback`: The default encoding to use when none is provided to the parser and none can be detected. The `windows-1252` encoding is used by default, but depending on locale or environment another encoding may be appropriate. See [the Encoding specification](https://encoding.spec.whatwg.org/#names-and-labels) for possible values
- `encodingPrescanBytes`: The number of bytes (by default `1024`) to examine prior to parsing to determine the document character encoding when none is provided. Normally this should not need to be changed. Using `0` will disable the encoding pre-scan
- `errorCollection`: A boolean value indicating whether parse errors should be collected into the `Output` object's `errors` array. This should usually be left at the default `false` for performance reasons. The content of the `errors` array is currently considered an implemenmtation detail subject to change without notice
- `htmlNamespace`: A boolean value indicating whether to create HTML elements within the HTML namespace i.e. `http://www.w3.org/1999/xhtml` rather than the `null` namespace. Though using the HTML namespace is the correct behaviour, the `null` namespace is used by default for performance and compatibility reasons
- `processingInstructions`: A boolean value indicating whether to preserve processing instructions in the parsed document. By default processing instructions are parsed as comments, per the specification. Note that if set to `true` the parser will insert _HTML processing sinstructions_ which are terminated by the first `>` character, not XML processing instructions terminated by `?>`

## Limitations

The primary aim of this library is accuracy. If the document object differs from what the specification mandates, this is probably a bug. However, we are also constrained by PHP, which imposes various limtations. These are as follows:

- Due to PHP's DOM being designed for XML 1.0 Second Edition, element and attribute names which are illegal in XML 1.0 Second Edition are mangled as recommended by the specification
- PHP's DOM has no special understanding of the HTML `<template>` element. Consequently template contents is treated no differently from the children of other elements
- PHP's DOM treats `xmlns` attributes specially. Attributes which would change the namespace URI of an element or prefix to inconsistent values are thus dropped
- Due to a PHP bug which severely degrades performance with large documents and in consideration of existing PHP software, HTML elements are placed in the null namespace by default rather than in the HTML namespace
- PHP's DOM does not allow DOCTYPEs with no name (i.e. `<!DOCTYPE >` rather than `<!DOCTYPE html>`); in such cases the parser will create a DOCTYPE using a single `U+0020 SPACE` character as its name

## Comparison with `masterminds/html5`

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
| Handling of processing instructions                 | Retained                              | Retained                                                 | Per specification, configurable        |
| Handling of bogus XLink namespace\*                 | Foreign content not supported         | XLink attributes are lost if preceded by bogus namespace | Bogus namespace is ignored             |
| Namespace for HTML elements                         | Null                                  | Per specification, configurable                          | Null, configurable                     |
| Time needed to parse single-page HTML specification | 0.5 seconds                           | 2.7 seconds†                                             | 6.0 seconds                            |
| Peak memory needed for same                         | 11.6 MB                               | 38 MB                                                    | 13.9 MB                                |

\* For example: `<svg xmlns:xlink='http://www.w3.org/1999/xhtml' xlink:href='http://example.com/'/>`. It is unclear what correct behaviour is, but we believe our behaviour to be more consistent with the intent of the specification.

† With HTML namespace disabled. With HTML namespace enabled it does not finish in a reasonable time due to a PHP bug.
