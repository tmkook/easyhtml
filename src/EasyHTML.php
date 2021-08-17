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
        'article','archive','blog','content','detail','entry','post','news'
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
                'timeout' => $timeout
            ]
        ];
        $context = stream_context_create($options);
        $source = file_get_contents($url,false,$context);
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
        $encode = mb_detect_encoding($source);
        if($encode != $charset){
            $source = mb_convert_encoding($source,$charset,$encode);
            $source = preg_replace("|{$encode}|", $charset, $source,1);
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
        $imageNodes = $this->dom->getElementsByTagName("meta");
        foreach($imageNodes as $img){
            $rel = $img->getAttribute('property');
            if(strpos($rel,'icon') || strpos($rel,'image')){
                $icon = $img->getAttribute('content');
                break;
            }
        }
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
        $ele = [];
        if(strpos($title,'_') > 0){
            $ele = explode('_',$title);
        }elseif(strpos($title,'-') > 0){
            $ele = explode('-',$title);
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
            $score = 0;
            $pgscore = 0;
            $url = explode('#',$node->getAttribute('href'))[0];
            $class = $node->getAttribute('class');
            $parentClass = $node->parentNode->getAttribute('class').$node->parentNode->parentNode->getAttribute('class');

            //匹配当前元素和父元素
            if(preg_match("@p/\w+@i",$url)){
                $score += 25;
            }
            $preg = implode('|',$this->contentName);
            if(preg_match("@(".$preg.")\w*/.+@i",$url)){
                $score += 25;
            }
            if(preg_match("@(title|content|list|cover|pic|img)@i",$class)){
                $score += 10;
            }
            if(preg_match("@(title|content|list|cover|pic|img)@i",$parentClass)){
                $score += 5;
            }
            if(preg_match("@/\d{4}/\d{1,2}/.+@i",$url)){
                $score += 5;
            }
            if(preg_match("@\.htm|\.html@i",$url)){
                $score += 5;
            }

            //匹配子元素
            foreach($node->childNodes as $item){
                if($item->nodeType == 1){
                    $childclass = $item->getAttribute('class');
                    if(preg_match("@(title|cover|pic)@i",$childclass)){
                        $score += 10;
                    }
                    if($item->tagName == 'h1' || $item->tagName == 'h2' || $item->tagName == 'h3' || $item->tagName == 'h4'){
                        $score += 10;
                    }
                }
            }

            //可能是文章
            if($score > 5){
                $list[$score][] = $url;
            }

            //匹配分页
            if(preg_match("@page/\d+|page=\d+@i",$url)){
                $pgscore += 25;
            }
            if(preg_match("@next page|下一页@i",$node->nodeValue)){
                $pgscore += 10;
            }
            if(preg_match("@(page|pagination|numbers)@i",$class)){
                $pgscore += 5;
            }
            if(preg_match("@(page|pagination|numbers)@i",$parentClass)){
                $pgscore += 5;
            }
            if(!preg_match("@\d+@i",$url)){
                $pgscore -= 10;
            }
            //可能是分页
            if($pgscore > 5){
                $page[$pgscore][] = $url;
            }
        }
        // print_r($list);exit;
        // print_r($page);exit;
        if(!empty($list)){
            $key = max(array_keys($list));
            $list = array_values(array_unique($list[$key]));
        }
        
        if(!empty($page)){
            $key = max(array_keys($page));
            $page = array_values(array_unique($page[$key]));
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
}