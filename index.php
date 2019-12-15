<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');

require './vendor/autoload.php';

use Nezimi\MySmarty; 

$template_dir = __DIR__ . DIRECTORY_SEPARATOR .'templates' . DIRECTORY_SEPARATOR;
$compie_dir   = __DIR__ . DIRECTORY_SEPARATOR .'templates_c' . DIRECTORY_SEPARATOR;

$data = [
	['name'=>'Nezimi'],
	['name'=>'Jimmy'],
	['name'=>'JameGold'],
];

$smarty = new MySmarty();

$smarty->debug = true;  //the debug enable
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezimi');
$smarty->assign('title', 'HelloWorld');
$smarty->assign('code', 1);
$smarty->assign('data', $data);
$smarty->display('index.html');