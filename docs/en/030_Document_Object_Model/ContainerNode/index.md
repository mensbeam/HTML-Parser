# The ContainerNode trait #

## Introduction ##

Allows the extended PHP DOM classes to simulate inheriting from a theoretical extended [\DOMNode](https://www.php.net/manual/en/class.domnode.php). This one implements improved DOM child insertion methods.

<pre><code class="php">trait MensBeam\HTML\ContainerNode {

    use <a href="../Node/index.html">Node</a>;

    public <a href="appendChild.html">appendChild</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false
    public <a href="insertBefore.html">insertBefore</a> ( <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a> $node , <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|null $child = null ) : <a href="https://www.php.net/manual/en/class.domnode.php">\DOMNode</a>|false

}</code></pre>