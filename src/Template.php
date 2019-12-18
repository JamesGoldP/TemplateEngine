<?php
namespace Nezimi;

use Exception;

class Template
{
	/**
	 * 赋值的数组
	 * @var array
	 */
	protected $vars = []; 

	/**
	 * 模板路径
	 * @var string
	 */
	protected $templateDir;  
	protected $compileDir;   

	/**
	 * 
	 */
	protected $leftDelimiter;
	protected $rightDelimiter;

	/**
	 * @var string
	 */
	protected $templateFile; 

	/**
	 * whether debug
	 * @var bool
	 */
	protected $debug = true; 

	/**
	 * 
	 */
	protected $storage;

	/**
	 * error messages 
	 */
	private $varReg = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	public  function __construct($templateDir, $compileDir, array $config = [])
	{
		$this->templateDir = $templateDir;
		$this->compileDir  = $compileDir;
		$this->leftDelimiter = preg_quote($config['left_delimiter'], '/');
		$this->rightDelimiter = preg_quote($config['right_delimiter'], '/');
		$this->config = $config;

		//初始化模板编译存储器
		$type = $this->config['compile_type'] ?: 'file';
		$class = false !== strpos('\\', $type) ? $type : 'Nezimi\\driver\\' . ucwords($type);
		$this->storage = new $class;
	}

	public function assign($key, $value)
	{
		$this->vars[$key] = $value; 
	}

	public function fetch(string $file)
	{
		$this->templateFile = $this->templateDir . $file . '.' .$this->config['template_suffix'];
		if( !file_exists($this->templateFile) ){
			return false;
		}
		$content = $this->read($this->templateFile);
		$this->compileFile = $this->compileDir . md5($this->templateFile). '.' .$this->config['compile_extension'];

		//如果不是调试的话,意思实时写入文件
		if( !$this->debug ){
			//whether expiry
			if(!$this->expiry()) {
				return false;
			}
		}	

		$content = $this->compile($content, $this->compileFile);
		$this->storage->write($this->compileFile, $content);
		ob_start();
		$this->storage->read($this->compileFile, $this->vars);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	public function display($content)
	{
		
	}

	/**
	 * 
	 */
	public function compile(string $content): string
	{
		return $this->parse($content);
	}

	public function parse(string $content): string
	{
		if( empty($content) ){
			return false;
		}
		//Gather all template tags
		$this->parseLiteral($content);
		$this->parseExtend($content);
		$this->parseLayout($content);
		//relace include tag 
		$this->parseInclude($content);
		//替换包含文件中的literal tag
		$this->parseLiteral($content);
		$this->parsePhp($content);	
		$this->parseTag($content);
		$this->parseTagLib($content);
		//还原literal内容	
		$this->parseLiteral($content);		
		return $content;
	}

	public function read(string $file)
	{
		$handle = fopen($file, 'r');
		$result = fread($handle, filesize($file));
		fclose($handle);
		// $result = file_get_contents($file);
		return $result;	
	}

	public function parseLiteral(string $content, bool $resotre = false): void
	{

	}

	public function parseExtend(string &$content): void
	{

	}

	public function parsePhp(string &$content): void
	{

	}

	public function parseLayout(string &$content): void
	{

	}

	public function parseInclude(string &$content): void
	{
		$regex = $this->getRegex('include');
		$content = preg_replace_callback($regex, function ($match) {
		            return file_get_contents($this->templateDir.$match[1]. '.' .$this->config['template_suffix']);
				}, $content);
	}

	public function parseTag(string &$content): void
	{
		$regex = $this->getRegex('tag');
		
		if( preg_match_all($regex, $content, $matches, PREG_SET_ORDER) ){
			foreach($matches as $value){
				$str = $value[1];
				$flag = substr($str, 0, 1);
				//match array
				$this->parseVar($str);
				$this->parseVarFunction($str);
				$str = '<?php echo '.$str.' ?>';
				$content = str_replace($value[0], $str, $content);
			}
		}
	}

	public function parseVar(string &$varStr): void
	{
		$regex = '/\s*\$'.$this->varReg.'(\.\w+)+\s*/isU';
		if( preg_match_all($regex, $varStr, $matches, PREG_OFFSET_CAPTURE) ){
			p($matches);
		}
	}

	public function parseVarFunction(string &$varStr)
	{
		if( !strpos($varStr, '|') ){
			return ;
		}
		$varArray = explode('|', $varStr);
		$name = array_shift($varArray);
		foreach($varArray as $key=>$value){
			$args = explode('=', $value);
			$func = $args[0];
			switch($func){
				case 'default':
					$varStr = $name . ' ?? ' . $args[1];
				break;
				default:
					if( isset($args[1]) ){
						$varStr = "$func($name, $args[1])";
					} else {
						//可以使用php的函数
						if( !empty($args[0]) ){
							$varStr = "$func($name)";
						}
					}
			}
		}
	}

	public function parseTagLib(string &$content): void
	{
		$patter = [];
		$replacement = [];
		$ld = $this->leftDelimiter;
		$rd = $this->rightDelimiter;
		$varReg = $this->varReg;	
		//else
		$pattern[] = '/'.$ld.'\s*else\s*'.$rd.'/';
		$replacement[] = '<?php else:  ?>';

		//endif
		$pattern[] = '/'.$ld.'\s*\/foreach\s*'.$rd.'/';
		$replacement[] = '<?php endforeach;  ?>';

		//endforeach
		$pattern[] = '/'.$ld.'\s*\/if\s*'.$rd.'/';
		$replacement[] = '<?php endif;  ?>';

		$content =  preg_replace($pattern , $replacement, $content);

		//relace if
		$ifPattern = '/'.$ld.'\s*if(.+)\s*'.$rd.'/U';
		//为了避免/e报错,使用preg_replace_callback来代替/e
		$content = preg_replace_callback($ifPattern, function ($match) {
		            return '<?php if('.$this->getVariable($match[1]).'):?>';
		        }, $content);

		//relace else if
		$elseifPattern = '/'.$ld.'\s*else\s*if(.+)\s*'.$rd.'/U';
		$content = preg_replace_callback($elseifPattern, function ($match) {
		            return '<?php elseif('.$this->getVariable($match[1]).'):?>';
		        }, $content);
		
		//relace foreach e.g. "<{ foreach $arrs $value }>
		$foreachPattern = '/'.$ld.'\s*foreach\s*(\$'.$varReg.')\s+as\s+(\$'.$varReg.')\s*'.$rd.'/U';
		$content = preg_replace_callback($foreachPattern, function ($match) {
		            return '<?php foreach('.$this->getVariable($match[1]).' as '.$this->getVariable($match[2]).'):?>';
		        }, $content);

		//relace foreach e.g. "<{ foreach $arrs $key=>$value }>
		$foreachPattern2 = '/'.$ld.'\s*foreach\s*(\$'.$varReg.')\s+as\s+(\$'.$varReg.')\s*=>\s*(\$'.$varReg.')'.$rd.'/U';
		$content = preg_replace_callback($foreachPattern2, function ($match) {
		            return '<?php foreach('.$this->getVariable($match[1]).' as '.$this->getVariable($match[2]).'=>'.$this->getVariable($match[3]).'):?>';
		        }, $content);
	}

	public function getRegex(string $tagName): string
	{
		switch($tagName){
			case 'tag':
				$regex = '(\s*\$.+\s*)';
			break;
			case 'include':
				$regex = 'include.+file=[\'\"](.+)[\'\"]';
			break;
		}
		return '/' . $this->leftDelimiter . $regex . $this->rightDelimiter . '/isU';
	}

	/**
	 * The file whether is expiry 
	 */
	private function expiry()
	{
		//如果模板文件的修改时间大于被编译的文件修改时间就是过期了
		if( filemtime($this->templateFile)>filemtime($this->compileFile) ){
			return true;
		} else {
			return false;
		}
	}

    /**
     * get errors if debug on
     * @param string $errMsg 
     * @return boolean
     */
    public function throwException($errMsg)
    {
        if( $this->debug ){
			$this->errorMsg = "smarty error: $errorMsg";
        }
		return true;
    }

    /**
     * 处理elseif里面的变量
     * @param string $errMsg 
     * @return boolean
     */
    private function getVariable($variable)
    {
 		//replace variables
		$pattern = '/\$('.$this->varReg.')/';
		$replacement = '$this->vars["\\1"]';
		return preg_replace($pattern , $replacement, $variable);
    } 

}