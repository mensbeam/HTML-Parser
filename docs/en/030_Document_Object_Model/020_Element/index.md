---
title: Element
---

# The Element Class #

## Introduction ##

<div class="info"><p><strong>Info</strong> Only new methods and methods which make outward-facing changes from <a href="https://www.php.net/manual/en/class.domelement.php">\DOMElement</a> will be documented here, otherwise they will be linked back to PHP's documentation.</p></div>

## Class Synopsis ##

<pre><code class="php">MensBeam\HTML\Element extends <a href="https://www.php.net/manual/en/class.domelement.php">\DOMElement</a> {

    use <a href="../Moonwalk/index.html">Moonwalk</a>, <a href="../Node/index.html">Node</a>, <a href="../Walk/index.html">Walk</a>;

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
    public <a href="getAttribute.html">getAttribute</a> ( string $qualifiedName ) : string|null
    public <a href="getAttributeNS.html">getAttributeNS</a> ( string|null $namespace , string $localName ) : string|null

    /* Magic Methods */
    public __toString() : string

    /* Methods from <a href="../Moonwalk/index.html">Moonwalk</a> */
    public <a href="../Moonwalk/moonwalk.html">moonwalk</a> ( ?<a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter = null ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>

    /* Methods from <a href="../Node/index.html">Node</a> */
    public <a href="../Node/appendChild.html">Node::appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="../Node/C14N.html">Node::C14N</a> ( bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../Node/C14NFile.html">Node::C14NFile</a> ( string $uri , bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../Node/insertBefore.html">Node::insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false

    /* Methods from <a href="../Walk/index.html">Walk</a> */
    public <a href="../Walk/walk.html">walk</a> ( ?<a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter = null ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>

    /* Methods inherited from <a href="https://www.php.net/manual/en/class.domelement.php">\DOMElement</a> */
    public <a href="https://www.php.net/manual/en/domelement.construct.php">__construct</a> ( string $qualifiedName , string|null $value = null , string $namespace = "" )
    public <a href="https://www.php.net/manual/en/domelement.getattributenode.php">getAttributeNode</a> ( string $qualifiedName ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|false
    public <a href="https://www.php.net/manual/en/domelement.getattributenodens.php">getAttributeNodeNS</a> ( string|null $namespace , string $localName ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|null
    public <a href="https://www.php.net/manual/en/domelement.getelementsbytagname.php">getElementsByTagName</a> ( string $qualifiedName ) :  <a href="https://www.php.net/manual/en/class.domnodelist.php">\DOMNodeList</a>
    public <a href="https://www.php.net/manual/en/domelement.getelementsbytagnamens.php">getElementsByTagNameNS</a> ( string $namespace , string $localName ) : <a href="https://www.php.net/manual/en/class.domnodelist.php">\DOMNodeList</a>
    public <a href="https://www.php.net/manual/en/domelement.hasattribute.php">hasAttribute</a> ( string $qualifiedName ) : bool
    public <a href="https://www.php.net/manual/en/domelement.hasattributens.php">hasAttributeNS</a> ( string|null $namespace , string $localName ) : bool
    public <a href="https://www.php.net/manual/en/domelement.removeattribute.php">removeAttribute</a> ( string $qualifiedName ) : bool
    public <a href="https://www.php.net/manual/en/domelement.removeattributenode.php">removeAttributeNode</a> (  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a> $attr ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|false
    public <a href="https://www.php.net/manual/en/domelement.removeattributenodens.php">removeAttributeNS</a> ( string|null $namespace , string $localName ) : void
    public <a href="https://www.php.net/manual/en/domelement.setattribute.php">setAttribute</a> ( string $qualifiedName , string $value ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|bool
    public <a href="https://www.php.net/manual/en/domelement.setattributenode.php">setAttributeNode</a> (  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a> $attr ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|null|false
    public <a href="https://www.php.net/manual/en/domelement.setattributenodens.php">setAttributeNodeNS</a> (  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a> $attr ) :  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a>|null|false
    public <a href="https://www.php.net/manual/en/domelement.setattributens.php">setAttributeNS</a> ( string|null $namespace , string $qualifiedName , string $value ) : void
    public <a href="https://www.php.net/manual/en/domelement.setidattribute.php">setIdAttribute</a> ( string $qualifiedName , bool $isId ) : void
    public <a href="https://www.php.net/manual/en/domelement.setidattributenode.php">setIdAttributeNode</a> (  <a href="https://www.php.net/manual/en/class.domattr.php">\DOMAttr</a> $attr , bool $isId ) : void
    public <a href="https://www.php.net/manual/en/domelement.setidattributens.php">setIdAttributeNS</a> ( string $namespace , string $qualifiedName , bool $isId ) : void

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