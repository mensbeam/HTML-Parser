---
title: ContainerNode::insertBefore
---

ContainerNode::insertBefore — Adds a new child before a reference node

## Description ##

```php
public ContainerNode::insertBefore ( \DOMNode $node , \DOMNode|null $child = null ) : \DOMNode|false
```

This function inserts a new node right before the reference node. If you plan to do further modifications on the appended child you must use the returned node.

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

## Parameters ##

<dl>
 <dt><code>node</code></dt>
 <dd>The new node.</dd>

 <dt><code>child</code></dt>
 <dd>The reference node. If not supplied, <code>node</code> is appended to the children.</dd>
</dl>