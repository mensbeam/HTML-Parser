---
title: Element::getAttributeNS
---

Element::getAttributeNS — Returns value of attribute

## Description ##

```php
public Element::getAttribute ( string|null $namespace , string $localName ) : string|null
```

Gets the value of the attribute in namespace `namespace` with local name `localName` for the current node.

## Parameters ##

<dl>
 <dt><code>namespace</code></dt>
 <dd>The namespace URI.</dd>
 <dt><code>localName</code></dt>
 <dd>The local name of the attribute.</dd>
</dl>

## Return Values ##

Returns a string on success or <code>null</code> if no attribute with the given `localName` and `namespace` is found. `\DOMElement::getAttribute` returns an empty string on failure which is incorrect in newer versions of the DOM.