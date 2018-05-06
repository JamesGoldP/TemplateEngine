<?php
include './TemplateEngine/Smarty.php';

define('APP',__DIR__.DIRECTORY_SEPARATOR);
$template_dir = APP.'templates'.DIRECTORY_SEPARATOR;
$compie_dir = APP.'templates_c'.DIRECTORY_SEPARATOR;
$smarty = new Smarty();
$smarty->debug = true;  //开启调试
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezumi');
$smarty->assign('code', 1);
$smarty->display('index');