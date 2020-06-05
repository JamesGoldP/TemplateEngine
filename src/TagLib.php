<?php
namespace zero;

use Exception;

class TagLib
{
    /**
     * 
     * @object
     * @access protected
     */
    protected $tpl;

    public function __construct(Template $template)
    {
        $this->tpl = $template;
    }

    public function parseTag(string &$content): void
    {
        $tags = [];

        foreach($this->tags as $name=>$value){
            $close = $value['close'] ?? '1';
            $tags[$close][$name] = $name;
        }
        //没有close的
        if( !empty($tags[1]) ){
            $nodes = [];
            $regex = $this->getRegex(array_keys($tags[1]), 1);
            //匹配到标签
            if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)){
                $right = [];
                foreach($matches as $match){
                    if( '' == $match[1][0] ){
                        $name = $match[2][0]; //内部标签名字
                        if( !empty($right[$name]) ){
                            $startPosition = $match[0][1];
                            $nodes[$startPosition] = [
                                'name' => $name,
                                'begin' => array_pop($right[$name]), //便签开始符
                                'end' => $match[0], //标签结束符
                            ];
                        }
                    } else {
                        $name = strtolower($match[1][0]); //内部标签名字
                        $right[$name][] = $match[0];
                    }
                }
            }   
            unset($right, $matches);
            krsort($nodes);
        }
        //如果匹配到，就去替换
        if($nodes){
            $beginArray = [];
            foreach($nodes as $pos => $node){
                $method = 'tag'. $node['name'];
                $attrs = $this->parseAttr($node['begin'][0], $node['name']);
                $replace = $this->$method($attrs);
                if( count($replace) > 1 ){
                    while($beginArray){
                        $begin = array_pop($beginArray);
                        //替换标签头部
                        $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']); 
                    }
                    //替换标签尾部
                    $content = substr_replace($content, $replace['1'], $node['end'][1], strlen($node['end'][0]) );
                    //标签头部入栈
                    $beginArray[] = [
                        'pos' => $node['begin'][1],
                        'len' => strlen($node['begin'][0]),
                        'str' => $replace['0'],
                    ];
                }
            }
            while($beginArray){
                $begin = array_pop($beginArray);
                //替换标签头部
                $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']); 
            }
        }

        //有close的
        if( !empty($tags[0]) ){
            $regex = $this->getRegex(array_keys($tags[0]), 0); 
            $content = preg_replace_callback($regex, function($matches) use ($tags){
                $name = $tags[0][$matches[1]];
                $method = 'tag'. ucwords($name);
                $attrs = $this->parseAttr($matches[0], $name);
                $result = $this->$method($attrs);
                return $result;
            }, $content);
        }
    }

    public function parseAttr(string $str, string $name): array
    {
        if( $this->tags[$name]['expression'] ){
            $start = strlen($this->tpl->config['tpl_begin'].$name);
            $end   = strlen($this->tpl->config['right_delimiter']);
            $expression = substr($str, $start, -$end);
            //清楚{else /}的/
            $result['expression'] = trim(rtrim($expression, '/'));
        } else {
            throw new Exception('tag'. $name .' error');
        }
        return $result;
    }

    /**
     * 
     */
    public function parseCondition(string $condition): string
    {
        return $condition;
    }

    protected function getRegex($tags, bool $close): string
    {
        list($begin, $end) = [$this->tpl->config['tpl_begin'], $this->tpl->config['right_delimiter']];
        $single = 1 == strlen( ltrim($begin, '\\')) && 1 == strlen( ltrim($end, '\\')) ? true : false;
        $tagName = is_array($tags) ? implode('|', $tags) : $tags;

        if( $single ){
            if($close){
                $regex = $begin . '(?:('.$tagName.')\s+[^'.$end.']*|\/('.$tagName.'))'.$end;
            } else {
                $regex = $begin . '(' . $tagName. ')' .'\s+[^'.$end.']*' . $end;
            }
        } else {
            if($close){

            } else {

            }
        }
        $regex = '/' . $regex . '/is';
        return $regex;
    }
}