# The Walk trait #

## Introduction ##

Allows the extended PHP DOM classes to walk down the DOM via a [`\Generator`](https://www.php.net/manual/en/class.generator.php). This is in lieu of recreating the awful [DOM TreeWalker API](https://developer.mozilla.org/en-US/docs/Web/API/Treewalker).

<pre><code class="php">trait MensBeam\HTML\Walk {

    public <a href="walk.html">walk</a> ( <a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>

}</code></pre>