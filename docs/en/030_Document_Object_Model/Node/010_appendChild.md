---
title: Node::appendChild
---

Node::appendChild — Adds new child at the end of the children

## Description ##

```php
public Node::appendChild ( \DOMNode $node ) : \DOMNode|false
```

This function appends a child to an existing list of children or creates a new list of children. The child can be created with e.g. [`Document::createElement()`](https://www.php.net/manual/en/domdocument.createelement.php), [`Document::createTextNode()`](https://www.php.net/manual/en/domdocument.createtextnode.php) etc. or simply by using any other node.

When using an existing node it will be moved.

<div class="warning">
 <p><strong>Warning</strong> Only the following element types may be appended to any node using <code>Node</code> and subject to hierarchy restrictions depending on the type of node being appended to:</p>

 <ul>
  <li><code>Comment</code></li>
  <li><code>DocumentFragment</code></li>
  <li><a href="https://www.php.net/manual/en/class.domdocumenttype.php"><code>\DOMDocumentType</code></a></li>
  <li><code>Element</code></li>
  <li><code>ProcessingInstruction</code></li>
  <li><code>Text</code></li>
 </ul>

 <p>Note that <code>\DOMAttr</code> is missing from this list.</p>
</div>

## Examples ##

**Example \#1 Adding a child to the body**

```php
<?php

namespace MensBeam\HTML;

$dom = new Document();
$dom->loadHTML('<!DOCTYPE html><html><head><title>Ook!</title></head><body></body></html>');

$node = $dom->createElement('br');
$dom->body->appendChild($node);

?>