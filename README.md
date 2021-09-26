# HTML #

Tools for parsing and printing HTML5 documents and fragments.

```php
<?php
$out = MensBeam\HTML\Parser::parse('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
$document = $out->document; // the parsed document
$encoding = $out->encoding; // the canonical name of the detected or supplied encoding
$quirks = $out->quirksMode; // the quirks-mode setting of the document, needed for parsing fragments into the document later
```

The API is still in flux, but should be finalized soon.

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