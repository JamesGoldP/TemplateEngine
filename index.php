<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');

use Nezumi\MySmarty; 

define('DC', DIRECTORY_SEPARATOR);
define('APP',__DIR__.DC);
$template_dir = APP.'templates'.DC;
$compie_dir = APP.'templates_c'.DC;

$data = array(
	array('name'=>'Nezumi'),
	array('name'=>'Jimmy'),
	array('name'=>'JameGold'),
);

$smarty = new MySmarty();

$smarty->debug = true;  //the debug enable
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezumi');
$smarty->assign('title', 'HelloWorld');
$smarty->assign('code', 1);
$smarty->assign('data', $data);
$smarty->display('index.html');