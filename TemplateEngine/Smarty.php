<?php
class Smarty
{
	private $template_dir = ''; //模板存放的路径
	private $compie_dir = '';  //编译目录 
	public $assign = array(); //赋值的数组

	public  function __construct()
	{
	}

	public function assign($key, $value)
	{
		$this->assign[$key] = $value; 
	}

	public function setTemplateDir($dir)
	{
		$this->template_dir = $dir; 
	}

	public function setCompileDir($dir)
	{
		if( !is_dir($dir) ){
			mkdir($dir);
		}
		$this->compie_dir = $dir; 
	}

	public function display($file)
	{

		extract($this->assign);
		$file = $this->template_dir.$file.'.php';
		if( file_exists($file) ){
			include $file;
		}
	}

}