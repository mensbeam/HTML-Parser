# The LeafNode trait #

## Introduction ##

Allows the extended PHP DOM classes to simulate inheriting from a theoretical extended [\DOMNode](https://www.php.net/manual/en/class.domnode.php). This one disables all DOM child insertion methods.

<pre><code class="php">trait MensBeam\HTML\LeafNode {

    use <a href="../Node/index.html">Node</a>;
    
    public <a href="appendChild.html">appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : DOMException
    public <a href="Node_insertBefore.html">insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : DOMException
    public <a href="removeChild.html">removeChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : DOMException
    public <a href="replaceChild.html">replaceChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node, <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $child ) : DOMException

}</code></pre>