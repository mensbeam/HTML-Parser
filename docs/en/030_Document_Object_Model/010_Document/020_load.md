---
title: Document::load
---

Document::load — Load HTML from a file

## Description ##

```php
public Document::load ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
```

Loads an HTML document from a file.

## Parameters ##

<dl>
 <dt><code>filename</code></dt>
 <dd>The path to the HTML document.</dd>

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
$dom->load('ook.html');
echo $dom;

?>
```