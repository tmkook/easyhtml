<?php
require 'vendor/autoload.php';
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charSet="utf-8"/>
<meta name="description" content="description content"/>
<link  rel="shortcut icon" href="/favicon.ico" />
<title>EasyHTML Title Test</title>
</head>

<body>
<ul>
<li><a href="/detail/2c72529879a1" target="_blank">test article list 1</a></li>
<li><a href="/detail/a0e9bb6885f6" target="_blank">test article list 2</a></li>
<li><a href="/detail/e2278837846f" target="_blank">test article list 3</a></li>
<li><a href="/detail/bf94ebc98d1c" target="_blank">test article list 4</a></li>
<li><a href="/detail/413080d0af68" target="_blank">test article list 5</a></li>
<li><a href="/detail/cb86900733d3" target="_blank">test article list 6</a></li>
<li><a href="/detail/63a38da17fa0" target="_blank">test article list 7</a></li>
<li><a href="/detail/63a38da17fa1" target="_blank">test article list 8</a></li>
<li><a href="/detail/63a38da17fa2" target="_blank">test article list 9</a></li>
<li><a href="/detail/63a38da17fa3" target="_blank">test article list 10</a></li>
<li><a href="/test/63a38da17fa4" target="_blank">test article list remove</a></li>
</ul>

<span class="publish">2021/10/21</span>
<div class="content">
    <p>
        article content
    </p>
    <img src="https://img.example.com/test1.jpg" />
    <p>
        article content
    </p>
    <img src="https://img.example.com/test2.jpg" />
    <p>
        article content
    </p>
    <img src="https://img.example.com/test3.jpg" />
    <pre>
        <code>
            $easy = new Tmkook\EasyHTML($html);
            print_r($easy->getLogo());
            print_r($easy->getDate());
            print_r($easy->getTitle());
            print_r($easy->getImages());
            print_r($easy->getMeta("description"));
            print_r($easy->getList());
            print_r($easy->getContent());
        </code>
    </pre>
</div>
</body>
</html>';

//$html = file_get_contents('url');
$easy = new Tmkook\EasyHTML($html);

echo '<pre>';
var_dump($easy->getList());
var_dump($easy->getContent());
var_dump($easy->getDate());
var_dump($easy->getTitle());
var_dump($easy->getLogo());
var_dump($easy->getImages());
var_dump($easy->getMeta("description")); //only name and property
echo '</pre>';