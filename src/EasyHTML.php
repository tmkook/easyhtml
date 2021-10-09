<?php
/*
* homepage https://github.com/tmkook/easyhtml
*
* author tmkook <tmkook@gmail.com>
*
*/

namespace Tmkook;

class EasyHTML
{
    /**
     * DOMDocument object.
     *
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * DOMDocument object.
     *
     * @var \DOMDocument
     */
    protected $content;

    /**
     * html source.
     *
     * @var string
     */
    protected $source;

    /**
     * score attrubute.
     *
     * @var string
     */
    const ART_SCORE = 'art-score';

    /**
     * ignored tags.
     *
     * @var array
     */
    protected $ignoreTags = [
        "style", "form", "script", "button", "input", "textarea",
        "noscript", "select", "option", "object", "applet", "basefont",
        "bgsound", "blink", "canvas", "command", "menu", "nav", "datalist",
        "embed", "frame", "frameset", "keygen", "label", "marquee"
    ];

    /**
     * content common name.
     *
     * @var array
     */
    protected $contentName = [
        'article','archive','blog','content','detail','entry','post','news','topic'
    ];

    /**
     * content charset.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

    public function __construct($data=''){
        if(!empty($data)){
            if(strpos($data,'http') == 0){
                $this->loadURL($data);
            }else{
                $this->loadHTML($data);
            }
        }
    }

    /**
     * load html with its charset.
     *
     * @param  string $url
     * @param  int $timeout
     * @param  string $charset
     * @return static
     */
    public function loadURL($url,$timeout=60,$charset='UTF-8'){
        $options = [
            'http'=>[
                'method' => 'GET',
                'timeout' => $timeout,
                'user_agent'=> $_SERVER['HTTP_USER_AGENT'],
                'header' => "Referer: {$url}\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $source = file_get_contents($url,false,$context);
        if(empty($source)){
            throw new \Exception('request error',500);
        }
        $this->url = $url;
        return $this->loadHTML($source,$charset);
    }

    /**
     * load html with its charset.
     *
     * @param  string $source
     * @return static
     */
    public function loadHTML($source,$charset='UTF-8')
    {
        //convert charset
        $this->content = null;
        $this->charset = $charset;
        preg_match('@charset="([\w-]+)"|charset=([\w-]+)@',$source,$matches);
        $encode = empty($matches[2])? $matches[1] : $matches[2];
        if(empty($encode)){
            $title = preg_match('@<title>(.*)</title>@i',$source,$matches);
            $encode = mb_detect_encoding($matches[1]);
        }
        if($encode != $charset){
            $source = mb_convert_encoding($source,$charset,$encode);
            $source = preg_replace("@{$encode}@", $charset, $source);
        }
        $this->source = $source;

        //parse html to dom
        $this->dom = new \DOMDocument('1.0', $charset);
        @$this->dom->loadHTML('<?xml encoding="'.$charset.'">'.$source);

        return $this;
    }

    /**
     * get content dom node
     *
     * @return \DOMDocument
     */
    public function getContentNode(){
        if(!$this->content){
            $this->content = $this->makeContent($force);
        }
        return $this->content;
    }

    /**
     * get document node
     *
     * @return \DOMDocument
     */
    public function getDocumentNode(){
        return $this->dom;
    }

    /**
     * get article content
     * force=true if no content is found, the body is returned
     *
     * @param bool $force
     * @return string
     */
    public function getContent($force=false){
        if(!$this->content){
            $this->content = $this->makeContent($force);
        }
        $content = $this->content? mb_convert_encoding($this->content->saveHTML(),$this->charset,'HTML-ENTITIES') : '';
        return $content;
    }

    /**
     * get article images
     *
     * @return array
     */
    public function getImages(){
        if(!$this->content){
            $this->content = $this->makeContent($force);
        }
        $images = [];
        $imageNodes = $this->content->getElementsByTagName("img");
        foreach ($imageNodes as $img) {
            $images[] = $img->getAttribute("src");
        }
        return $images;
    }

    /**
     * get meta  name or property content
     * @param string $name
     * @return string
     */
    public function getMeta($name){
        $description = '';
        $imageNodes = $this->dom->getElementsByTagName("meta");
        foreach($imageNodes as $img){
            if($img->getAttribute('name') == $name || $img->getAttribute('property') == $name){
                $description = $img->getAttribute('content');
                break;
            }
        }
        return $description;
    }

    /**
     * get icon or favicon
     *
     * @return string
     */
    public function getLogo(){
        $icon = '';
        $imageNodes = $this->dom->getElementsByTagName("meta");
        if(empty($icon)){
            $imageNodes = $this->dom->getElementsByTagName("link");
            foreach ($imageNodes as $img) {
                $rel = $img->getAttribute("rel");
                if(strpos($rel,'icon')){
                    $icon = $img->getAttribute('href');
                    break;
                }
            }
        }
        if(empty($icon)){
            foreach($imageNodes as $img){
                $rel = $img->getAttribute('property');
                if(strpos($rel,'icon') || strpos($rel,'image')){
                    $icon = $img->getAttribute('content');
                    break;
                }
            }
        }
        if(empty($icon)){
            $icon = '/favicon.ico';
        }
        return $icon;
    }

    /**
     * get website page title
     *
     * @return string
     */
    public function getTitle(){
        $title = '';
        $titleNodes = $this->dom->getElementsByTagName("title");
        if($titleNodes->length && $titleNode = $titleNodes->item(0)){
            $title = $titleNode->nodeValue;
        }
        $ele = [];
        if(strpos($title,'_') > 0){
            $ele = explode('_',$title);
        }elseif(strpos($title,'-') > 0){
            $ele = explode('-',$title);
        }elseif(strpos($title,'·') > 0){
            $ele = explode('·',$title);
        }elseif(strpos($title,'|') > 0){
            $ele = explode('|',$title);
        }
        if(!empty($ele)){
            $title = $ele[0];
        }

        return $title;
    }

    /**
     * get publish date.
     *
     * @return string
     */
    public function getDate()
    {
        $date = '';
        if (! preg_match('@(\d{2,4}[/年\s-]+\d{1,2}[/月\s-]+\d{1,2})@s', $this->source, $matches)) {
            $date = '';
        }else{
            $date = $matches[1];
            if(strpos($date, '年')){
                $date = str_replace('年','-',$date);
            }
            if(strpos($date, '月')){
                $date = str_replace('月','-',$date);
            }
        }
        return $date;
    }

    /**
     * make url sampling
     * http://test.com/news/1.html
     * parse to
     * http://test.com/string/number.html
     *
     * @param string $url
     * @return string
     */
    public function urlSampling($url){
        $urlinfo = parse_url($url);
        $path = trim($urlinfo['path'],'/');
        $host = $this->getDomain($url);

        //扩展名
        $ext = trim(pathinfo($path)['extension']);
        if($ext){
            $ext = '.'.$ext;
            $path = str_replace($ext,'',$path);
        }
        $path = explode('/',$path);

        //取样
        $pattern = '';
        if(count($path) > 2){
            $pattern = '/'.array_shift($path);
        }
        while($a = array_shift($path)){
            if($a > 0){
                $a = '/number';
            }else{
                $a = '/string';
            }
            $pattern .= $a;
        }

        //合并后的取样结果
        $pattern = $host.$pattern.$ext;
        return $pattern;
    }

    /**
     * make article list
     * debug=true print matches
     *
     * @param bool $debug
     * @return string
     */
    public function getList($debug = false){
        $i = 0;
        $list = [];
        $page = [];
        $title = [];
        $power = [];
        $length = [];
        $nodes = $this->dom->getElementsByTagName('a');
        while ($node = $nodes->item($i++)) {
            $url = $node->getAttribute('href');
            $url = explode('#',$url)[0];
            $url = trim($url,'/');
            $url = trim($url,'./');
            if(empty($url) || strlen($url) < 2 || $url == 'javascript:;'){
                continue;
            }
            $text = $this->getMaxTitle($node->textContent);
            if(empty($text)){
                continue;
            }

            //匹配加权
            $score = 0;
            $pgscore = 0;
            $class = $node->getAttribute('class').$node->parentNode->getAttribute('class').$node->parentNode->parentNode->getAttribute('class');
            
            //匹配分页
            if(preg_match("@page/\d+|page=\d+@i",$url)){
                $pgscore += 10;
            }
            if(preg_match("@next page|下一页@i",$node->nodeValue)){
                $pgscore += 10;
            }
            if(preg_match("@(page|pagination|numbers)@i",$class)){
                $pgscore += 10;
            }
            if(!preg_match("@\d+@i",$url)){
                $pgscore -= 20;
            }
            if($pgscore > 5){
                $page[] = $url;
            }

            //匹配列表
            $class_preg = "@(article|title|content|list|cover|pic|img)@i";
            if(preg_match($class_preg,$class)){
                $score += 10;
            }
            $preg = implode('|',$this->contentName);
            if(preg_match("@({$preg})[/\-_].+@i",$url)){
                $score += 10;
            }
            if(preg_match("@/p/.+@i",$url)){
                $score += 10;
            }
            if(preg_match("@htm|shtm|xhtm@",$url)){
                $score += 5;
            }
            
            //父元素加权
            $hs = ['h1','h2','h3','h4','h5'];
            if(in_array($node->parentNode->tagName,$hs) || in_array($node->parentNode->parentNode->tagName,$hs)){
                $score += 5;
            }

            //子元素加权
            foreach($node->childNodes as $item){
                if($item->nodeType == 1){
                    $class = $item->getAttribute('class');
                    if(preg_match($class_preg,$class)){
                        $score += 10;
                    }
                    if(in_array($item->tagName,$hs)){
                        $score += 5;
                    }
                }
            }

            //非同域名减权
            // if($this->url && $a = $this->getDomain($url)){
            //     $b = $this->getDomain($this->url);
            //     if($a != $b){
            //         $score -= 1;
            //     }
            // }

            if($score < 1){
                continue;
            }

            //采样分类
            if(strlen($title[$url]) < strlen($text)){
                $title[$url] = $text;
            }
            $pattern = $this->urlSampling($url);
            $list[$pattern][] = $url;
            $length[$pattern] = count($list[$pattern]);

            //更新采样加权值
            if(!isset($power[$pattern]) || $score > $power[$pattern]){
                $power[$pattern] = $score;
            }
        }

        if($debug){
            print_r($list);
            print_r($power);
            print_r($length);
            print_r($title);
            print_r($page);
            exit;
        }

        //获取最佳列表
        $max_score_key = $this->getMaxKey($power);
        $max_length_key = $this->getMaxKey($length);
        if($max_score_key == $max_length_key){
            $list = $list[$max_score_key];
        }elseif($power[$max_score_key] == $power[$max_length_key]){
            $list = $list[$max_length_key];
        }elseif($power[$max_length_key] > 15){
            $list = array_merge($list[$max_score_key],$list[$max_length_key]);
        }elseif($power[$max_score_key] > 15){
            $list = $list[$max_score_key];
        }else{
            $list = $list[$max_length_key];
        }
        $list = array_unique($list);

        //匹配标题
        $titles = [];
        foreach($list as $k=>$item){
            if(empty($title[$item])){
                unset($list[$k]);
            }else{
                $titles[$k] = $title[$item];
            }
        }
        $page = array_unique($page);
        $list = array_values($list);
        $titles = array_values($titles);
        return ['list'=>$list,'title'=>$titles];
    }

    /**
     * make article content
     * force=true if no content is found, the body is returned
     *
     * @param bool $force
     * @return string
     */
    protected function makeContent($force=false){
        $i = 0;
        $maxScore = 0;
        $maxNode = null;
        $content = null;
        $nodes = $this->dom->getElementsByTagName('*');
        while ($node = $nodes->item($i++)) {
            if($node->nodeName == 'article'){
                $currentNode = $node;
                $currentScore = intval($currentNode->getAttribute(static::ART_SCORE));
                $currentScore += 20;
            }else if($node->nodeName == 'p' || $node->nodeName == 'pre' || $node->nodeName == 'ul' || $node->nodeName == 'img' || $node->nodeName == 'blockquote'){
                $currentNode   = $node->parentNode;
                $currentScore  = intval($currentNode->getAttribute(static::ART_SCORE));
                $classAndId   = $currentNode->getAttribute("class").$currentNode->getAttribute("id");
                $preg = implode('|',$this->contentName);
                if (preg_match("@(".$preg.")@i",$classAndId)){
                    $nodeAttr = $node->getAttribute("class").$node->getAttribute("id");
                    if(empty($nodeAttr)){
                        $currentScore += 30;
                    }else{
                        $currentScore += 1;
                    }
                }else{
                    $currentScore -= 5;
                }
            }else{
                continue;
            }
            $currentNode->setAttribute(static::ART_SCORE,$currentScore);
            if($maxScore < $currentScore){
                $maxNode = $currentNode;
                $maxScore = $currentScore;
            }
        }
        if($force && !$maxNode){
            $maxNode = $this->dom->getElementsByTagName('body')->item(0);
        }
        //make content
        if($maxNode){
            $maxNode = $this->purge($maxNode);
            $content = new \DOMDocument;
            $content->appendChild($content->importNode($maxNode, true));
        }else{
            $content = null;
        }
        return $content;
    }

    /**
     * purge content dom
     * clean up layout related
     *
     * @param \DOMDocument $dom
     * @return string
     */
    protected function purge($dom){
        //remove score attribute
        $dom->removeAttribute('id');
        $dom->removeAttribute('class');
        $dom->removeAttribute(self::ART_SCORE);

        //remove ignore tags
        foreach($this->ignoreTags as $tagName){
            $tags = $this->dom->getElementsByTagName($tagName);
            while ($tag = $tags->item(0)) {
                $tag->parentNode->removeChild($tag);
            }
        }

        //find layout related
        $i = 0;
        $cleans = [];
        $tags = $dom->getElementsByTagName('*');
        while ($tag = $tags->item($i++)) {
            $tag->removeAttribute(self::ART_SCORE);
            $classAndId   = $tag->getAttribute("class").$tag->getAttribute("id");
            if($classAndId){
                $cleans[] = $tag;
            }
        }

        //remove layout related
        foreach($cleans as $tag){
            if($tag->parentNode->childNodes->length == 1){
                $tag->parentNode->parentNode->removeChild($tag->parentNode);
            }else{
                $tag->parentNode->removeChild($tag);
            }
        }
        
        return $dom;
    }

    public function getDomain($url){
        $urlinfo = parse_url($url);
        $host = $urlinfo['host'];
        if($host){
            $host = explode('.',$host);
            if(count($host) > 2){
                unset($host[0]);
            }
            $host = implode('.',$host);
        }
        return $host;
    }

    protected function getMaxTitle($text){
        $last = '';
        $text = trim($text);
        $text = str_replace("\r\n","\n",$text);
        $text = str_replace("\r","\n",$text);
        $text = str_replace(' ','',$text);
        $text = explode("\n",$text);
        $last = $text[0];
        if(count($text) > 1){
            foreach($text as $v){
                if(strlen($last) < strlen($v)){
                    $last = $v;
                }
            }
        }
        return $last;
    }

    protected function getMaxKey($arr){
        $max = 0;
        $key = '';
        foreach($arr as $k=>$v){
            if($v > $max){
                $max = $v;
                $key = $k;
            }
        }
        return $key;
    }
}