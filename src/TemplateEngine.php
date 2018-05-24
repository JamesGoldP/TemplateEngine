<?php
/*
 * Project:		imitation Simarty: the PHP compiled template engine
 * File:		TemplateEngine.php
 * Author:		Nezumi
 *
 */

namespace TemplateEngine; 

class TemplateEngine
{
	private $vars = array(); //赋值的数组

	private $template_dir = ''; //模板存放的路径
	private $template_extension = '.html';

	private $compie_dir = '';  //编译目录 
	private $compie_extension = '.php';
	

	public $left_delimiter = '{';
	public $right_delimiter = '}';

	private $template_file = ''; //模板文件
	private $compie_file = ''; //编译文件

	public  $debug = false;  //whether debug
	private $error_msg = ''; //error messages 
	private $var_reg = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	public  function __construct()
	{
	}

	public function assign($key, $value)
	{
		$this->vars[$key] = $value; 
	}

	public function setTemplateDir($dir)
	{
		$this->template_dir = $dir; 
	}

	public function setCompileDir($dir)
	{
		if( !is_dir($dir) ){
			mkdir($dir, 0777, TRUE);
		}
		$this->compie_dir = $dir; 
	}

	public function display($file)
	{

		$this->template_file = $this->template_dir.$file.$this->template_extension;
		if( !file_exists($this->template_file) ){
			return false;
		}
		$content = $this->read();
		if( empty($content) ){
			return false;
		}

		$patter = array();
		$replacement = array();
		$ld = preg_quote($this->left_delimiter, '/');
		$rd = preg_quote($this->right_delimiter, '/');
		$var_reg = $this->var_reg;	

		//relace include e.g. 
		$include_pattern = '/'.$ld.'include\s+file=[\'\"](.+)[\'\"]'.$rd.'/U';
		$content = preg_replace_callback($include_pattern, function ($match) {
		            return file_get_contents($this->template_dir.$match[1].$this->template_extension);
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
		$pattern[] = '/'.$ld.'\s*\$('.$var_reg.')\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"] ?>';

		//replace variables
		$pattern[] = '/'.$ld.'\s*\$('.$var_reg.')\[(.+)\]\s*'.$rd.'/U';
		$replacement[] = '<?php echo $this->vars["\\1"][\\2] ?>';

		$content =  preg_replace($pattern , $replacement, $content);


		//relace if
		$if_pattern = '/'.$ld.'\s*if(.+)\s*'.$rd.'/U';
		//为了避免/e报错,使用preg_replace_callback来代替/e
		$content = preg_replace_callback($if_pattern, function ($match) {
		            return '<?php if('.$this->getVariable($match[1]).'):?>';
		        }, $content);

		//relace else if
		$elseif_pattern = '/'.$ld.'\s*else\s*if(.+)\s*'.$rd.'/U';
		$content = preg_replace_callback($elseif_pattern, function ($match) {
		            return '<?php elseif('.$this->getVariable($match[1]).'):?>';
		        }, $content);
		
		//relace foreach e.g. "<{ foreach $arrs $value }>
		$foreach_pattern = '/'.$ld.'\s*foreach\s*(\$'.$var_reg.')\s+as\s+(\$'.$var_reg.')\s*'.$rd.'/U';
		$content = preg_replace_callback($foreach_pattern, function ($match) {
		            return '<?php foreach('.$this->getVariable($match[1]).' as '.$this->getVariable($match[2]).'):?>';
		        }, $content);

		//relace foreach e.g. "<{ foreach $arrs $key=>$value }>
		$foreach_pattern2 = '/'.$ld.'\s*foreach\s*(\$'.$var_reg.')\s+as\s+(\$'.$var_reg.')\s*=>\s*(\$'.$var_reg.')'.$rd.'/U';
		$content = preg_replace_callback($foreach_pattern2, function ($match) {
		            return '<?php foreach('.$this->getVariable($match[1]).' as '.$this->getVariable($match[2]).'=>'.$this->getVariable($match[3]).'):?>';
		        }, $content);


		$this->write($content);
		include $this->compie_file;
	}

	private function read()
	{
		$handle = fopen($this->template_file ,'r');
		$result = fread($handle, filesize($this->template_file));
		fclose($handle);
		// $result = file_get_contents($file);
		return $result;	
	}

	private function write($info)
	{
		$this->compie_file = $this->compie_dir.md5($this->template_file).$this->compie_extension;

		//如果不是调试的话,意思实时写入文件
		if( !$this->debug ){
			//判断文件是否过期
			if(!$this->expiry()) {
				return false;
			}
		}	

		$handle = fopen($this->compie_file ,'w');
		$result = fwrite($handle, $info);
		fclose($handle);
		// $result = file_put_contents();
	}

	/**
	 * 文件是否过期
	 */
	private function expiry()
	{
		//如果模板文件的修改时间大于被编译的文件修改时间就是过期了
		if(filemtime($this->template_file)>filemtime($this->compie_file)){
			return true;
		} else {
			return false;
		}
	}


    /**
     * 如果调试的话输出错误信息
     * @param string $errMsg 
     * @return boolean
     */
    public function throw_exception($errMsg)
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
		$pattern = '/\$('.$this->var_reg.')/';
		$replacement = '$this->vars["\\1"]';
		return preg_replace($pattern , $replacement, $variable);
    } 


}
