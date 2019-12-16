<?php
namespace Nezimi; 

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
	 * error messages 
	 */
	private $varReg = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	public  function __construct($templateDir, $compileDir, array $config = [])
	{
		$this->templateDir = $templateDir;
		$this->compileDir  = $compileDir;
		$this->leftDelimiter = $config['left_delimiter'];
		$this->rightDelimiter = $config['right_delimiter'];
		$this->config = $config;
	}

	public function assign($key, $value)
	{
		$this->vars[$key] = $value; 
	}

	public function fetch($file)
	{
		ob_start();
		$this->display($file);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	public function display($file)
	{
		$this->templateFile = $this->templateDir . $file . '.' .$this->config['template_suffix'];
		if( !file_exists($this->templateFile) ){
			return false;
		}
		$content = $this->read();
		if( empty($content) ){
			return false;
		}
		$content = $this->compileFile($content);
		$this->write($content);
		include $this->compileFile;
	}

	public function compileFile($content)
	{
		$patter = [];
		$replacement = [];
		$ld = preg_quote($this->leftDelimiter, '/');
		$rd = preg_quote($this->rightDelimiter, '/');
		$varReg = $this->varReg;	

		//Gather all template tags

		//relace include e.g. 
		$includePattern = '/'.$ld.'include\s+file=[\'\"](.+)[\'\"]'.$rd.'/U';
		$content = preg_replace_callback($includePattern, function ($match) {
		            return file_get_contents($this->templateDir.$match[1]);
		        }, $content);

		//else
		$pattern[] = '/'.$ld.'\s*else\s*'.$rd.'/';
		$replacement[] = '<?php else:  ?>';

		//endif
		$pattern[] = '/'.$ld.'\s*\/foreach\s*'.$rd.'/';
		$replacement[] = '<?php endforeach;  ?>';

		//endforeach
		$pattern[] = '/'.$ld.'\s*\/if\s*'.$rd.'/';
		$replacement[] = '<?php endif;  ?>';

		//replace variables
		$pattern[] = '/'.$ld.'\s*\$('.$varReg.')\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"] ?>';

		//replace array
		$pattern[] = '/'.$ld.'\s*\$('.$varReg.')\[(.+)\]\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"][\\2] ?>';

		//replace array for smarty
		$pattern[] = '/'.$ld.'\s*\$('.$varReg.')\.('.$varReg.')\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"]["\\2"] ?>';

		//replace array for smarty
		$pattern[] = '/'.$ld.'\s*\$('.$varReg.')\.('.$varReg.')\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"]["\\2"] ?>';

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
		return $content;
	}

	private function read()
	{
		$handle = fopen($this->templateFile ,'r');
		$result = fread($handle, filesize($this->templateFile));
		fclose($handle);
		// $result = file_get_contents($file);
		return $result;	
	}

	private function write($info)
	{
		if( !is_dir($this->compileDir) ) {
			mkdir($this->compileDir);
		}
		$this->compileFile = $this->compileDir . md5($this->templateFile). '.' .$this->config['compile_extension'];
		//如果不是调试的话,意思实时写入文件
		if( !$this->debug ){
			//whether expiry
			if(!$this->expiry()) {
				return false;
			}
		}	

		$handle = fopen($this->compileFile ,'w');
		$result = fwrite($handle, $info);
		fclose($handle);
		// $result = file_put_contents();
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