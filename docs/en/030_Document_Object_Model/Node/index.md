# The Node trait #

## Introduction ##

Allows the extended PHP DOM classes to simulate inheriting from a theoretical extended [\DOMNode](https://www.php.net/manual/en/class.domnode.php).

<pre><code class="php">trait MensBeam\HTML\Node {

    public <a href="appendChild.html">appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="C14N.html">C14N</a> ( bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="C14NFile.html">C14NFile</a> ( string $uri , bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="Node_insertBefore.html">insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="Node_removeChild.html">removeChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="Node_replaceChild.html">replaceChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false

}</code></pre>