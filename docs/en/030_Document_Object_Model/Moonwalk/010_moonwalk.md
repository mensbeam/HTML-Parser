---
title: Moonwalk::moonwalk
---

Moonwalk::moonwalk — Output generator for walking up the DOM tree

## Description ##

```php
public Moonwalk::moonwalk ( <a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>
```

Non-standard. Creates a [`\Generator`](https://www.php.net/manual/en/class.generator.php) object for walking up the DOM tree. This is in lieu of recreating the awful [DOM TreeWalker API](https://developer.mozilla.org/en-US/docs/Web/API/Treewalker).

## Examples ##

**Example \#1 Print name of all ancestors of the H1 element**

```php
<?php

namespace MensBeam\HTML;

$dom = new Document();
$dom->loadHTML('<!DOCTYPE html><html><head><title>Ook!</title></head><body><h1>Eek</h1></body></html>');
$h1 = $dom->getElementsByTagName('h1')->item(0);

// All ancestors will be elements so there's no reason to have a filter.
$tree = $h1->moonwalk();

foreach ($tree as $t) {
    echo "{$t->nodeName}\n";
}

?>
```

The above example will output something similar to:

```php
body
html

```