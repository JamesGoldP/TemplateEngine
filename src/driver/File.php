<?php
namespace Nezimi\driver;

use Exception;

class File
{
    /**
     * 写入内容到模板
     * @access public
     * @param string $compileFile 需要写入的文件
     * @param string $info  需要写入的内容
     * @return void
     */
	public function write(string $compileFile, string $info): void
	{
        $dir = dirname($compileFile);
        
        if( !is_dir($dir) ){
            mkdir($dir, 0755, true);
        }
        
		$handle = fopen($compileFile ,'w');
		$result = fwrite($handle, $info);
        fclose($handle);
        if( false === $result ){
            throw new Exception('cache write error:'. $compileFile);
        }
		// $result = file_put_contents();
	}

    /**
     * 读取编译内容
     * @access public
     * @param string $compileFile 编译好的文件
     * @param array $vars  需要编译的变量
     * @return void
     */
    public function read(string $compileFile, array $vars): void
    {
        if( !empty($vars) ){
            extract($vars);
        }
        include $compileFile;
    }

}