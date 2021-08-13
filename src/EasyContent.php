<?php
/*
* homepage https://github.com/tmkook/easyhtml
*
* author tmkook <tmkook@gmail.com>
*
*/

namespace Tmkook;

class EasyContent
{
    protected $base;
    protected $content;
    public function __construct($base='',$content=''){
        if(!empty($base)){
            $this->setBase($base);
        }
        if(!empty($content)){
            $this->setContent($content);
        }
    }

    public function setBase($base){
        $this->base = parse_url($base);
        $info = pathinfo($this->base['path']);
        $this->base['path'] = $info['dirname'];
    }

    public function setContent($content){
        if(empty($this->base)){
            throw new \Exception('Base URL is not set',101);
        }
        $this->content = $content;

        //fix img tag url
        preg_match_all('@<img.*?src="([^"]*)"[^>]*>@i', $this->content, $cover);
        $cover = $cover[1];
        foreach($cover as $k=>$v){
            $cover[$k] = $this->url($v);
            $this->content = str_replace($v,$cover[$k],$this->content);
        }

        //fix a tag url
        preg_match_all('@<a.*?href="([^"]*)"[^>]*>@i', $this->content, $links);
        $links = $links[1];
        foreach($links as $k=>$v){
            $links[$k] = $this->url($v);
            $this->content = str_replace($v,$links[$k],$this->content);
        }
    }

    public function getContent(){
        return $this->content;
    }

    public function getText($length=0){
        $main = strip_tags($this->content);
        $main = str_replace(['  ','&nbsp;'],['',''],$main);
        if($length){
            $main = trim(mb_substr($main,0,100));
        }
        return $main;
    }

    public function getImages(){
        preg_match_all('|<img.*?src="([^"]*)"[^>]*>|i', $this->content, $cover);
        return $cover[1];
    }

    public function url($link){
        $site = $this->base;
        $info = parse_url($link);

        //relate url
        if(empty($info['host'])){
            $info['scheme'] = $site['scheme'];
            $info['host'] = $site['host'];
        }
        $rel = empty($site['path'])? '/' : $site['path'];
        $path = empty($info['path'])? '/' : $info['path'];
        $first = substr($path,0,1);
        if($first == '/'){
            $info['path'] = $path;
        }else if($first == '.'){
            $num = count(explode('../',$path)) - 1;
            if($num > 0){
                while($num--){
                    $rel = dirname($rel);
                }
            }
            $path = str_replace('../','',$path);
            $path = str_replace('./','',$path);
            $path = $rel.$path;
        }else{
            $path = $rel.$path;
        }

        //build url
        $info['path'] = rawurlencode($path);
        $url = $info['scheme'].'://'.$info['host'];
        if(!empty($info['port'])){
            $url = $url.":".$info['port'];
        }
        $url = $url.$info['path'];
        if(!empty($info['query'])){
            $url = $url.'?'.$info['query'];
        }
        if(!empty($info['fragment'])){
            $url = $url.'#'.$info['fragment'];
        }
        
        return urldecode($url);
    }

}