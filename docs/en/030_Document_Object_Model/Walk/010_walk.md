---
title: Walk::walk
---

Walk::walk — Output generator for walking down the DOM tree

## Description ##

```php
public Walk::walk ( <a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>
```

Creates a [`\Generator`](https://www.php.net/manual/en/class.generator.php) object for walking down the DOM tree.

## Examples ##

**Example \#1 Print name of every Element**

```php
<?php

namespace MensBeam\HTML;

$dom = new Document();
$dom->loadHTML('<!DOCTYPE html><html><head><title>Ook!</title></head><body><h1>Eek</h1></body></html>');
$tree = $dom->walk(function($node) {
    return ($node instanceof Element);
});

foreach ($tree as $t) {
    echo "{$t->nodeName}\n";
}

?>
```

The above example will output something similar to:

```php
html
head
title
body
h1

```