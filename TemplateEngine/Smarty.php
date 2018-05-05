<?php
class Smarty
{
	private $vars = array(); //赋值的数组

	private $template_dir = ''; //模板存放的路径
	private $template_extension = '.html';

	private $compie_dir = '';  //编译目录 
	private $compie_extension = '.php';
	

	private $left_delimiter = '{';
	private $right_delimiter = '}';

	private $template_file = ''; //模板文件
	private $compie_file = ''; //编译文件

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
			mkdir($dir);
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
		//replace variables
		$pattern = '/'.$this->left_delimiter.'\s*\$([\w_]*?)\s*'.$this->right_delimiter.'/i';
		$replacement = '<?php echo $this->vars["$1"] ?>';
		$info =  preg_replace($pattern , $replacement, $content);
		$this->write($info);
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

		//判断文件是否过期
		if(!$this->expiry()) {
			return false;
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

}