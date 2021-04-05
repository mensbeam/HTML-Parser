---
title: Document::loadHTML
---

Document::loadHTML — Load HTML from a string

## Description ##

```php
public Document::loadHTML ( string $source , null $options = null , string|null $encodingOrContentType = null ) : bool
```

The function parses the HTML contained in the string <var>source</var>.

## Parameters ##

<dl>
 <dt><code>source</code></dt>
 <dd>The HTML string.</dd>

 <dt><code>options</code></dt>
 <dd>Always <code>null</code>. Was used for option constants in <a href="https://www.php.net/manual/en/class.domdocument.php"><code>\DOMDocument</code></a>.</dd>

 <dt><code>encodingOrContentType</code></dt>
 <dd>The encoding of the document that is being loaded. If not specified it will be determined automatically.</dd>
</dl>

## Return Values ##

Returns <code>true</code> on success or <code>false</code> on failure.

## Examples ##

**Example \#1 Creating a Document**

```php
<?php

namespace MensBeam\HTML;

$dom = new Document();
$dom->loadHTML('<!DOCTYPE html><html><head><title>Ook!</title></head><body><h1>Eek</h1></body></html>');
echo $dom;

?>
```