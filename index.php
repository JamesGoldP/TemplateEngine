<?php
include './TemplateEngine/Smarty.php';

define('APP',__DIR__.DIRECTORY_SEPARATOR);
$template_dir = APP.'templates'.DIRECTORY_SEPARATOR;
$compie_dir = APP.'templates_c'.DIRECTORY_SEPARATOR;


$data = array(
	array('name'=>'Nezumi'),
	array('name'=>'Jimmy'),
	array('name'=>'JameGold'),
);

$smarty = new Smarty();
$smarty->debug = true;  //开启调试
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezumi');
$smarty->assign('title', 'HelloWorld');
$smarty->assign('code', 1);
$smarty->assign('data', $data);
$smarty->display('index');