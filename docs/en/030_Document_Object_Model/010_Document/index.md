---
title: Document
---

# The Document Class #

## Introduction ##

Represents an entire HTML document; serves as the root of the document tree. Unlike the PHP [`\DOMDocument`](https://www.php.net/manual/en/class.domdocument.php) class in which it inherits from it cannot be used to represent an XML document. It is strictly used to represent HTML.

<div class="info"><p><strong>Info</strong> Only new methods and methods which make outward-facing changes from <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> will be documented here, otherwise they will be linked back to PHP's documentation.</p></div>

## Class Synopsis ##

<pre><code class="php">MensBeam\HTML\Document extends <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> {

    use <a href="../Node/index.html">Node</a>, <a href="../Walk/index.html">Walk</a>;

    /* Constants */
    public const NO_QUIRKS_MODE = 0 ;
    public const QUIRKS_MODE = 1 ;
    public const LIMITED_QUIRKS_MODE = 2 ;

    /* Properties */
    public Element|null <a href="#document-props-body">$body</a> = null ;
    public string|null <a href="#document-props-documentencoding">$documentEncoding</a> = null ;
    public int <a href="#document-props-quirksmode">$quirksMode</a> = 0 ;

    /* Inherited properties from <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> */
    public readonly DocumentType <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.doctype">$doctype</a> ;
    public readonly Element <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.documentelement">$documentElement</a> ;
    public string|null <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.documenturi">$documentURI</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domimplementation.php">\DOMImplementation</a> <a href="https://www.php.net/manual/en/class.domdocument.php#domdocument.props.implementation">$implementation</a> ;

    /* Inherited properties from <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> */
    public readonly string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodename">$nodeName</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodevalue">$nodeValue</a> ;
    public readonly int <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nodetype">$nodeType</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.parentnode">$parentNode</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnodelist.php">\DOMNodeList</a> <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.childnodes">$childNodes</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.firstchild">$firstChild</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.lastchild">$lastChild</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.previoussibling">$previousSibling</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.nextsibling">$nextSibling</a> ;
    public readonly <a href="https://www.php.net/manual/en/class.domnamednodemap.php">\DOMNamedNodeMap</a>|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.attributes">$attributes</a> ;
    public readonly Document|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.ownerdocument">$ownerDocument</a> ;
    public readonly string|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.namespaceuri">$namespaceURI</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.prefix">$prefix</a> ;
    public readonly string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.localname">$localName</a> ;
    public readonly string|null <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.baseuri">$baseURI</a> ;
    public string <a href="https://www.php.net/manual/en/class.domnode.php#domnode.props.textcontent">$textContent</a> ;

    /* Methods */
    public <a href="construct.html">__construct</a> ( )
    public <a href="createEntityReference.html">createEntityReference</a> ( string $name ) : false
    public <a href="load.html">load</a> ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="loadHTML.html">loadHTML</a> ( string $source , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="loadHTMLFile.html">loadHTMLFile</a> ( string $filename , null $options = null , string|null $encodingOrContentType = null ) : bool
    public <a href="loadHTML.html">loadXML</a> ( string $source , null $options = null ) : false
    public <a href="save.html">save</a> ( string $filename , null $options = null ) : int|false
    public <a href="saveHTMLFile.html">saveHTMLFile</a> ( string $filename , null $options = null ) : int|false
    public <a href="saveXML.html">saveXML</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $node = null , null $options = null ) : false
    public <a href="validate.html">validate</a> ( ) : true
    public <a href="xinclude.html">xinclude</a> ( null $options = null ) : false

    /* Magic Methods */
    public __toString() : string

    /* Methods from <a href="../Node/index.html">Node</a> */
    public <a href="../Node/appendChild.html">Node::appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="../Node/C14N.html">Node::C14N</a> ( bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../Node/C14NFile.html">Node::C14NFile</a> ( string $uri , bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../Node/insertBefore.html">Node::insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false

    /* Methods from <a href="../Walk/index.html">Walk</a> */
    public <a href="../Walk/walk.html">walk</a> ( ?<a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter = null ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>

    /* Methods inherited from <a href="https://www.php.net/manual/en/class.domdocument.php">\DOMDocument</a> */
    public <a href="https://www.php.net/manual/en/domdocument.createattribute.php">createAttribute</a> ( string $localName ) : <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|false
    public <a href="https://www.php.net/manual/en/domdocument.createattributens.php">createAttributeNS</a> ( string|null $namespace , string $qualifiedName ) : <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|false
    public <a href="https://www.php.net/manual/en/domdocument.createcdatasection.php">createCDATASection</a> ( string $data ) : <a href="https://www.php.net/manual/en/class.domcdatasection.php">\DOMCdataSection</a>|false
    public <a href="https://www.php.net/manual/en/domdocument.createcomment.php">createComment</a> ( string $data ) : Comment|false
    public <a href="https://www.php.net/manual/en/domdocument.createdocumentfragment.php">createDocumentFragment</a> ( ) : DocumentFragment|false
    public <a href="https://www.php.net/manual/en/domdocument.createelement.php">createElement</a> ( string $localName , string $value = "" ) : Element|false
    public <a href="https://www.php.net/manual/en/domdocument.createelementns.php">createElementNS</a> ( string|null $namespace , string $qualifiedName , string $value = "" ) : Element|false
    public <a href="https://www.php.net/manual/en/domdocument.createprocessinginstruction.php">createProcessingInstruction</a> ( string $target , string $data = "" ) : ProcessingInstruction|false
    public <a href="https://www.php.net/manual/en/domdocument.createtextnode.php">createTextNode</a> ( string $data ) : Text|false
    public <a href="https://www.php.net/manual/en/domdocument.getelementbyid.php">getElementById</a> ( string $elementId ) : Element|null
    public <a href="https://www.php.net/manual/en/domdocument.getelementsbytagname.php">getElementsByTagName</a> ( string $qualifiedName ) : <a href="https://www.php.net/manual/en/class.domnodelist.php">\DOMNodeList</a>
    public <a href="https://www.php.net/manual/en/domdocument.createelementsbytagnamens.php">getElementsByTagNameNS</a> ( string $namespace , string $localName ) : <a href="https://www.php.net/manual/en/class.domnodelist.php">\DOMNodeList</a>
    public <a href="https://www.php.net/manual/en/domdocument.importnode.php">importNode</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , bool $deep = false ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="https://www.php.net/manual/en/domdocument.normalizedocument.php">normalizeDocument</a> ( ) : void
    public <a href="https://www.php.net/manual/en/domdocument.registernodeclass.php">registerNodeClass</a> ( string $baseClass , string|null $extendedClass ) : bool
    public <a href="https://www.php.net/manual/en/domdocument.relaxngvalidate.php">relaxNGValidate</a> ( string $filename ) : bool
    public <a href="https://www.php.net/manual/en/domdocument.relaxngvalidatesource.php">relaxNGValidateSource</a> ( string $source ) : bool
    public <a href="https://www.php.net/manual/en/domdocument.savehtml.php">saveHTML</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $node = null ) : string|false
    public <a href="https://www.php.net/manual/en/domdocument.schemavalidate.php">schemaValidate</a> ( string $filename , int $flags = 0 ) : bool
    public <a href="https://www.php.net/manual/en/domdocument.schemavalidatesource.php">schemaValidateSource</a> ( string $source , int $flags = 0 ) : bool

    /* Methods inherited from <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> */
    public <a href="https://www.php.net/manual/en/domnode.clonenode.php">\DOMNode::cloneNode</a> ( bool $deep = false ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="https://www.php.net/manual/en/domnode.getlineno.php">\DOMNode::getLineNo</a> ( ) : int
    public <a href="https://www.php.net/manual/en/domnode.getnodepath.php">\DOMNode::getNodePath</a> ( ) : string|null
    public <a href="https://www.php.net/manual/en/domnode.hasattributes.php">\DOMNode::hasAttributes</a> ( ) : bool
    public <a href="https://www.php.net/manual/en/domnode.haschildnodes.php">\DOMNode::hasChildNodes</a> ( ) : bool
    public <a href="https://www.php.net/manual/en/domnode.isdefaultnamespace.php">\DOMNode::isDefaultNamespace</a> ( string $namespace ) : bool
    public <a href="https://www.php.net/manual/en/domnode.issamenode.php">\DOMNode::isSameNode</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $otherNode ) : bool
    public <a href="https://www.php.net/manual/en/domnode.issupported.php">\DOMNode::isSupported</a> ( string $feature , string $version ) : bool
    public <a href="https://www.php.net/manual/en/domnode.lookupnamespaceuri.php">\DOMNode::lookupNamespaceUri</a> ( string $prefix ) : string
    public <a href="https://www.php.net/manual/en/domnode.lookupprefix.php">\DOMNode::lookupPrefix</a> ( string $namespace ) : string|null
    public <a href="https://www.php.net/manual/en/domnode.normalize.php">\DOMNode::normalize</a> ( ) : void
    public <a href="https://www.php.net/manual/en/domnode.removechild.php">\DOMNode::removeChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="https://www.php.net/manual/en/domnode.replacechild.php">\DOMNode::replaceChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false

}</code></pre>

## Constants ##

| Constant                                              | Value | Description                           |
| ----------------------------------------------------- | ----- | ------------------------------------- |
| <var>MensBeam\HTML\Document::NO_QUIRKS_MODE</var>     | 0     | Document not in quirks mode           |
| <var>MensBeam\HTML\Document::QUIRKS_MODE</var>        | 1     | Document is in quirks mode            |
| <var>MensBeam\HTML\Document::LIMITEDQUIRKS_MODE</var> | 2     | Document is in limited quirks mode    |

## Properties ##

<dl>
 <dt id="document-props-body"><var>body</var></dt>
 <dd>Represents the <code>body</code> or <code>frameset</code> node of the current document, or <code>null</code> if no such element exists.</dd>

 <dt id="document-props-documentencoding"><var>documentEncoding</var></dt>
 <dd>Encoding of the document, as specified when parsing or when determining encoding type. Use this instead of <a href="https://php.net/manual/en/class.domdocument.php#domdocument.props.encoding"><code>\DOMDocument::encoding</code></a>.</dd>

 <dt id="document-props-quirksmode"><var>quirksMode</var></dt>
 <dd>Used when parsing. Specifies which mode the document was parsed in. One of the <a href="#page_Constants">predefined quirks mode constants</a>.</dd>
</dl>

The following properties inherited from [`\DOMDocument`](https://www.php.net/manual/en/class.domdocument.php) have no effect in `Mensbeam\HTML\Document`, so therefore are not listed in the schema above:

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