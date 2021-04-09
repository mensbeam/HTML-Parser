# The Moonwalk trait #

## Introduction ##

Allows the extended PHP DOM classes to Moonwalk up the DOM via a [`\Generator`](https://www.php.net/manual/en/class.generator.php). This is in lieu of recreating the awful [DOM TreeMoonwalker API](https://developer.mozilla.org/en-US/docs/Web/API/TreeMoonwalker).

<pre><code class="php">trait MensBeam\HTML\Moonwalk {

    public <a href="Moonwalk.html">Moonwalk</a> ( <a href="https://www.php.net/manual/en/class.closure.php">\Closure</a> $filter ) : <a href="https://www.php.net/manual/en/class.generator.php">\Generator</a>

}</code></pre>