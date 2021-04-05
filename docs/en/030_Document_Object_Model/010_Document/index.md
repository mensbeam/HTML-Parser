---
title: Document
---

# The Document class #

## Introduction ##

Represents an entire HTML document; serves as the root of the document tree. Unlike the PHP [`\DOMDocument`](https://www.php.net/manual/en/class.domdocument.php) class in which it inherits from it cannot be used to represent an XML document. It is strictly used to represent HTML.

<div class="info"><p><strong>Info</strong> Only new methods and methods which make outward-facing changes from <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> will be documented here, otherwise they will be linked back to PHP's documentation.</p></div>

<pre><code class="php">MensBeam\HTML\Document extends <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> {

    /* Constants */
    public const NO_QUIRKS_MODE = 0 ;
    public const QUIRKS_MODE = 1 ;
    public const LIMITED_QUIRKS_MODE = 2 ;

    /* Properties */
    public string|null $documentEncoding = null ;
    public int $quirksMode = 0 ;

    /* Inherited properties from <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> */
    public readonly DocumentType <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.doctype">$doctype</a> ;
    public readonly Element <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.documentelement">$documentElement</a> ;
    public string|null <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.documenturi">$documentURI</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domimplementation.php">DOMImplementation</a> <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.implementation">$implementation</a> ;

    /* Inherited properties from <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> */
    public readonly string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodename">$nodeName</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodevalue">$nodeValue</a> ;
    public readonly int <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodetype">$nodeType</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnode.php">DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.parentnode">$parentNode</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnodelist.php">DOMNodeList</a> <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.childnodes">$childNodes</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnode.php">DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.firstchild">$firstChild</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnode.php">DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.lastchild">$lastChild</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnode.php">DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.previoussibling">$previousSibling</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnode.php">DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nextsibling">$nextSibling</a> ;
    public readonly \<a href="https://www.php.net/manual/en/class.domnamednodemap.php">DOMNamedNodeMap</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.attributes">$attributes</a> ;
    public readonly Document|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.ownerdocument">$ownerDocument</a> ;
    public readonly string|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.namespaceuri">$namespaceURI</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.prefix">$prefix</a> ;
    public readonly string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.localname">$localName</a> ;
    public readonly string|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.baseuri">$baseURI</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.textcontent">$textContent</a> ;

    /* Methods */
    public <a href="Document_construct.html">__construct</a> ( )
    public <a href="Document_createEntityReference.html">createEntityReference</a> ( string $name ) : false
    public <a href="Document_load.html">load</a> ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="Document_loadHTML.html">loadHTML</a> ( string $source , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="Document_loadHTMLFile.html">loadHTMLFile</a> ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="Document_loadHTML.html">loadXML</a> ( string $source , null $options = null ) : false
    public <a href="Document_save.html">save</a> ( string $filename , null $options = null ) : int|false
    public <a href="Document_saveHTMLFile.html">saveHTMLFile</a> ( string $filename , null $options = null ) : int|false
    public <a href="Document_saveXML.html">saveXML</a> ( DOMNode|null $node = null , null $options = null ) : false
    public <a href="Document_validate.html">validate</a> ( ) : true
    public <a href="Document_xinclude.html">xinclude</a> ( null $options = null ) : false

}</code></pre>

## Constants ##

| Constant                                              | Value | Description                           |
| ----------------------------------------------------- | ----- | ------------------------------------- |
| <var>MensBeam\HTML\Document::NO_QUIRKS_MODE</var>     | 0     | Document not in quirks mode           |
| <var>MensBeam\HTML\Document::QUIRKS_MODE</var>        | 1     | Document is in quirks mode            |
| <var>MensBeam\HTML\Document::LIMITEDQUIRKS_MODE</var> | 2     | Document is in limited quirks mode    |

## Properties ##

<dl>
 <dt id="document-props-documentencoding"><var>documentEncoding</var></dt>
 <dd>Encoding of the document, as specified when parsing or when determining encoding type. Use this instead of <a href="https://php.net/manual/en/class.domdocument.php#domdocument.props.encoding"><code>\DOMDocument::encoding</code></a>.</dd>

 <dt id="document-props-quirksmode"><var>quirksMode</var></dt>
 <dd>Used when parsing. Specifies which mode the document was parsed in. One of the <a href="#page_Constants">predefined quirks mode constants</a>.</dd>
</dl>

The following properties inherited from `\DOMDocument` have no effect in `Mensbeam\HTML\Document`, so therefore are not listed in the schema above:

* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.actualencoding"><var>actualEncoding</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.config"><var>config</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.encoding"><var>encoding</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput"><var>formatOutput</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.preservewhitespace"><var>preserveWhiteSpace</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.recover"><var>recover</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.resolveexternals"><var>resolveExternals</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.standalone"><var>standalone</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.stricterrorchecking"><var>strictErrorChecking</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.substituteentities"><var>substituteEntities</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.validateonparse"><var>validateOnParse</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.version"><var>version</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.xmlencoding"><var>xmlEncoding</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.xmlstandalone"><var>xmlStandalone</var></a>
* <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.xmlversion"><var>xmlVersion</var></a>