# easyhtml

自动提取HTML的文章列表和文章正文
无需输入任何标签信息及正则信息
支持大部分主流博客和新闻站点

## 安装

```shell
# tmkook/easyhtml 1.0
composer require "tmkook/easyhtml:*"
```

## 使用

```php
//$data可以是URL或HTML
$easy = new Tmkook\EasyHTML($data);

//或者
$easy = new Tmkook\EasyHTML;
$easy->loadURL('http url'); //加载一个URL
$easy->loadHTML($html); //或者加载一段HTML


//获取当前页面所有文章链接和分页链接
//return ['list'=>list,'page'=>page]
$easy->getList();

//获取当前页面文章内容，相对链接转换可使用 EasyContent 
$easy->getContent();

//获取当前页面内的日期
$easy->getDate(); 

//获取当前页面的标题
$easy->getTitle();

//获取当前页面的favicon或LOGO
var_dump($easy->getLogo());

//获取文章内的图片，相对链接转换可使用 EasyContent 
//return array
$easy->getImages();

//获取当前页面的Meta标签值，只支持 name 和 property
$easy->getMeta("description");

//获取正文DOMDocument
$easy->getContentNode();

//获页面DOMDocument
$easy->getDocumentNode();

//DOMDocument 如何使用请参考
https://www.php.net/manual/en/class.domdocument.php

```

## EasyContent 相对链接转换

```php
//相对链接转换绝对链接的域名
$url = 'https://example.com';

//文章正文
$content = $easy->getContent();

//开始转换
$easyContent = new Tmkook\EasyContent($url,$content);

//或者
$easyContent = new Tmkook\EasyContent($url);
$easyContent->setContent($content);

//获取转换后的正文
$easyContent->getContent();

//获取文章纯文字内容，传入长度可截取简介
$easyContent->getText($length);//截取多少个字符，默认为0不截取

//获取转换后的图片链接
$easyContent->getImages($length);//获取多少个正文图片，默认为0取全部

```

## License

MIT
