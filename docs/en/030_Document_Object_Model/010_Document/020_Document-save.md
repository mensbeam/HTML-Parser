---
title: Document::save
---

Document::save — Serializes the DOM tree into a file

## Description ##

```php
public Document::save ( string $filename , null $options = null ) : int|false
```

Creates an HTML document from the DOM representation.

## Parameters ##

<dl>
 <dt><code>filename</code></dt>
 <dd>The path to the saved HTML document</dd>

 <dt><code>options</code></dt>
 <dd>Always <code>null</code>. Was used for option constants in <a href="https://www.php.net/manual/en/class.domdocument.php"><code>\DOMDocument</code></a>.</dd>
</dl>

## Return Values ##

Returns the number of bytes written or <code>false</code> on failure.

## Examples ##

**Example \#1 Saving a DOM tree into a file**

```php
<?php

namespace MensBeam\HTML;

$dom = new Document();
$dom->loadHTML('<!DOCTYPE html><html><head><title>Ook!</title></head><body><h1>Eek</h1></body></html>');
echo 'Wrote: ' .  $dom->save('/tmp/test.html') . ' bytes'; // Wrote: 85 bytes

?>
```