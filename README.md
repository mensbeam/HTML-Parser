# HTML5

Tools for parsing and printing HTML5 documents and fragments.

```php
<?php
$dom = dW\HTML5\Parser::parse('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
?>
```

or:

```php
<?php
$dom = new dW\HTML5\Document;
$dom->loadHTML('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
?>
```

## Comparison with `masterminds/html5`

This library and [masterminds/html5](https://packagist.org/packages/masterminds/html5) serve similar purposes. Generally, we are more accurate, but they are much faster. The following table summarizes the main functional differences.

|                                                     | Masterminds                           | MensBeam                               |
|-----------------------------------------------------|---------------------------------------|----------------------------------------|
| Extensions required                                 | dom, ctype, mbstring or iconv         | dom                                    |
| Handling of invalid characters                      | Characters are dropped                | [Per specification](https://encoding.spec.whatwg.org/#concept-encoding-process) |
| Handling of invalid XML element names               | Name is changed to "invalid"          | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#coercing-an-html-dom-into-an-infoset) |
| Handling of invalid XML attribute names             | Attribute is dropped                  | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#coercing-an-html-dom-into-an-infoset) |
| Handling of misnested tags                          | Parent end tags always close children | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#an-introduction-to-error-handling-and-strange-cases-in-the-parser) |
| Handling of data between table cells                | Left as-is                            | [Per specification](https://html.spec.whatwg.org/multipage/parsing.html#an-introduction-to-error-handling-and-strange-cases-in-the-parser) |
| Handling of omitted start tags                      | Elements are not inserted             | Per specification                      |
| Time needed to parse single-page HTML specification | 2.8 seconds                           | 7.0 seconds                            |
| Peak memory needed for same                         | 38 MB                                 | 13.9 MB                                |
