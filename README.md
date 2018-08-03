# HTML5 Parser

A parser for HTML5 written in php. Extremely easy to use. Accepts a string as input and outputs a DOMDocument.

```php
<?php
$dom = dW\HTML5\Parser::parse('<!DOCTYPE html><html lang="en" charset="utf-8"><head><title>Ook!</title></head><body><h1>Ook!</h1><p>Ook-ook? Oooook. Ook ook oook ook oooooook ook ooook ook.</p><p>Eek!</p></body></html>');
?>
```