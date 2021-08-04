# easyhtml

自动识别提取HTML页面文章列表和文章正文

## 安装

```shell
# tmkook/easyhtml 1.0
composer require "tmkook/easyhtml:*"
```

## 使用

```php
$html = file_get_contents('url');
$easy = new Tmkook\EasyHTML($html);

var_dump($easy->getList()); //获取文章列表
var_dump($easy->getContent()); //获取正文
var_dump($easy->getDate()); //获取日期
var_dump($easy->getTitle()); //获取标题
var_dump($easy->getLogo()); //获取favicon或LOGO
var_dump($easy->getImages()); //获取正文图片
var_dump($easy->getMeta("description")); //only name and property

$easy->load($html); //重新加载HTML页面
$easy->getContentNode(); //获取正文DOMDocument
$easy->getDocumentNode(); //获页面DOMDocument

```

## License

MIT
