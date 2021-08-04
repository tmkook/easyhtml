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
     * website charset.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

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
        'article','archive','blog','content','detail','entry','post','news'
    ];

    /**
     * Create a instance.
     *
     * @param string $source
     * @param string $charset
     */
    public function __construct($source,$charset='UTF-8'){
        $this->load($source, $charset);
    }

    /**
     * load html with its charset.
     *
     * @param  string $source
     * @param  string $charset
     * @return static
     */
    public function load($source, $charset="UTF-8")
    {
        //convert charset
        $this->content = null;
        $this->charset = $charset;
        $encode = mb_detect_encoding($source);
        if($encode != $charset){
            $source = mb_convert_encoding($source,$charset,$encode);
            $source = preg_replace("|{$encode}|", $charset, $source,1);
        }
        $this->source = $source;

        //parse html to dom
        $this->dom = new \DOMDocument('1.0', $charset);
        $this->dom->loadHTML('<?xml encoding="'.$charset.'">'.$source);

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
     * get article content
     * force=true if no content is found, the body is returned
     *
     * @param bool $force
     * @return string
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
     * get meta description
     *
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
        $imageNodes = $this->dom->getElementsByTagName("link");
        foreach ($imageNodes as $img) {
            $rel = $img->getAttribute("rel");
            if(strpos($rel,'icon') > 0){
                $icon = $img->getAttribute('href');
                break;
            }
        }
        if(empty($icon)){
            $imageNodes = $this->dom->getElementsByTagName("meta");
            foreach($imageNodes as $img){
                $rel = $img->getAttribute('property');
                if(strpos($rel,'icon') > 0 || strpos($rel,'image')){
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
     * get page title
     *
     * @return string
     */
    public function getTitle(){
        $title = '';
        $titleNodes = $this->dom->getElementsByTagName("title");
        if($titleNodes->length && $titleNode = $titleNodes->item(0)){
            $title = $titleNode->nodeValue;
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
        if (! preg_match('|(\d{2,4}[/年\s-]+\d{1,2}[/月\s-]+\d{1,2})|s', $this->source, $matches)) {
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
     * make article content
     * force=true if no content is found, the body is returned
     *
     * @param bool $force
     * @return string
     */
    public function getList(){
        $i = 0;
        $list = [];
        $page = [];
        $nodes = $this->dom->getElementsByTagName('a');
        while ($node = $nodes->item($i++)) {
            $url = explode('#',$node->getAttribute('href'))[0];
            $class = $node->getAttribute('class').$node->parentNode->getAttribute('class');
            $preg = implode('|',$this->contentName);
            $urlinfo = parse_url($url);
            $path = trim($urlinfo['path'],'/');
            if(preg_match("@(".$preg.")\w*/.+@i",$path)){
                $list[] = $url;
            }else if(preg_match("|/\d{4}/\d{1,2}/.+|i",$path)){
                $extend = explode('.',$path);
                if(!isset($extend[1]) || $extend[1] == 'html' || $extend[1] == 'htm'){
                    $list[] = $url;
                }
            }else if(preg_match("/(".$preg."|title|list|item)/i",$class)){
                $list[] = $url;
            }
            if(strpos($class,'page') > 0 || preg_match("/page/\d*|page=\d*/",$url)){
                $page[] = $url;
            }
        }
        $page = array_unique($page);
        $list = array_unique($list);

        //filter url
        $filter = [];
        foreach($list as $item){
            $path = trim(parse_url($item)['path'],'/');
            $first = explode('/',$path)[0];
            $filter[$first][] = $item;
        }
        $list = [];
        foreach($filter as $key=>$item){
            if(count($filter[$key]) > count($list)){
                $list = $filter[$key];
            }
        }
        
        return ['list'=>$list,'page'=>$page];
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
                if (preg_match("/(".$preg.")/i",$classAndId)){
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
}