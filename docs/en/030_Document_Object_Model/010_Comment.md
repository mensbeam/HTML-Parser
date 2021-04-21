---
title: Comment
---

# The Comment Class #

## Introduction ##

<div class="info"><p><strong>Info</strong> Only new methods and methods which make outward-facing changes from <a href="https://www.php.net/manual/en/class.domcomment.php">\DOMComment</a> will be documented here, otherwise they will be linked back to PHP's documentation.</p></div>

## Class Synopsis ##

<pre><code class="php">MensBeam\HTML\Comment extends <a href="https://www.php.net/manual/en/class.domcomment.php">\DOMComment</a> {

    use <a href="../LeafNode/index.html">LeafNode</a>, <a href="../Moonwalk/index.html">Moonwalk</a>;

    /* Inherited properties */
    public string <a href="https://www.php.net/manual/en/class.domcharacterdata.php#domcharacterdata.props.data">$data</a> ;
    public readonly int <a href="https://www.php.net/manual/en/class.domcharacterdata.php#domcharacterdata.props.length">$length</a> ;
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

    /* Trait Methods */
    public <a href="../LeafNode/appendChild.html">LeafNode::appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : DOMException;
    public <a href="../Node/C14N.html">Node::C14N</a> ( bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../Node/C14NFile.html">Node::C14NFile</a> ( string $uri , bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="../LeafNode/insertBefore.html">LeafNode::insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : DOMException
    public <a href="../Moonwalk/moonwalk.html">Moonwalk::moonwalk</a> ( <a href="https://www.php.net/manual/en/class.closure.php">\Closure</a>|null $filter = null ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>
    public <a href="../LeafNode/removeChild.html">LeafNode::removeChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : DOMException
    public <a href="../LeafNode/replaceChild.html">LeafNode::replaceChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : DOMException

    /* Magic Methods */
    public __toString() : string

    /* Inherited Methods */
    public <a href="https://www.php.net/manual/en/domcomment.construct.php">__construct</a> ( string $data = "" )
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

}</code></pre>