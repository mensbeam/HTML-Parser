---
title: Element::getAttribute
---

Element::getAttribute — Returns value of attribute

## Description ##

```php
public Element::getAttribute ( string $qualifiedName ) : string|null
```

Gets the value of the attribute with name `qualifiedName` for the current node.

## Parameters ##

<dl>
 <dt><code>qualifiedName</code></dt>
 <dd>The name of the attribute.</dd>
</dl>

## Return Values ##

Returns a string on success or <code>null</code> if no attribute with the given `qualifiedName` is found. `\DOMElement::getAttribute` returns an empty string on failure which is incorrect in newer versions of the DOM.