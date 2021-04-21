# The Node trait #

## Introduction ##

Allows the extended PHP DOM classes to simulate inheriting from a theoretical extended [\DOMNode](https://www.php.net/manual/en/class.domnode.php). It is used to disable [C14N](C14N.html) and [C14NFile](C14NFile.html).

<pre><code class="php">trait MensBeam\HTML\Node {

    public <a href="C14N.html">C14N</a> ( bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false
    public <a href="C14NFile.html">C14NFile</a> ( string $uri , bool $exclusive = false , bool $withComments = false , null $xpath = null , null $nsPrefixes = null ) : false

}</code></pre>