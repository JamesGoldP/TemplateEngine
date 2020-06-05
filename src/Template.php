<?php
namespace zero;

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

		$tagLibs = explode(',', $this->config['taglib_build_in']);

		foreach($tagLibs as $value){
			$this->parseTagLib($value, $content);
		}
		
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
				switch($flag){
					case '$':
						if( strpos($str, '|') ){
							$strArr = explode('|', $str);
							$name = $this->parseArray(array_shift($strArr));
							$str = $this->parseVarFunction($name, $strArr);
						} else {
							$str = $this->parseArray($str);	
						}
						$str = '<?php echo '.$str.'; ?>';
						break;
					case ':': //输出某个函数
						$str = substr($str, 1);
						$str = '<?php echo '.$str.'; ?>';
						break;
					case '~': //执行某个函数
						$str = substr($str, 1);
						$str = '<?php '.$str.'; ?>';
						break;
					case '/': //注释
						$flag2 = substr($str, 1, 1);
						if( '/' == $flag2 || ('*' == $flag2 && '*/' == substr($str, -2)) ){
							$str = '';
						}
						break;
					case '-':
					case '+':  //计算某个函数
						$str = '<?php echo '.$str.'; ?>';
						break;	
					default:
				}
				$content = str_replace($value[0], $str, $content);
			}
		}
	}

	public function parseArray(string $varStr): string
	{
		if( strpos($varStr, '.') ){
			$varArray = explode('.', $varStr);	
			$var = array_shift($varArray);
			$varStr = $var . '["' .implode('"]["', $varArray) . '"]';
			return $varStr;
		}
		return $varStr;
	}

	public function parseVarFunction(string $name, array $varArray): string
	{
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
		return $varStr;
	}

	public function parseTagLib(string $tagLib, string &$content): void
	{
		if( false !== strpos($tagLib, '\\') ){
			$className = $tagLib;
		} else {
			$className =  '\\Nezimi\\taglib\\'.ucwords($tagLib);
		}
		$tLib = new $className($this);
		$tLib->parseTag($content);
	}

	public function getRegex(string $tagName): string
	{
		switch($tagName){
			case 'tag':
				$regex = '((?:\${1,2}[a-wA-w_]|[+-]{2}\$[a-wA-w_]|[\:\~][\$a-wA-w_]|\/[\*\/])[^\}]*)';
				break;
			case 'include':
				$name = 'file';
				$regex = $tagName.'\s+'.$name.'=[\'\"](\w+)[\'\"]';
				break;
		}
		$regex = '/' . $this->leftDelimiter . $regex . $this->rightDelimiter . '/is';
		return $regex;
	}

	/**
	 * The file whether is expiry 
	 */
	private function expiry()
	{
		//如果模板文件的修改时间大于被编译的文件修改时间就是过期了
		if( filemtime($this->templateFile) > filemtime($this->compileFile) ){
			return true;
		} else {
			return false;
		}
	}

}