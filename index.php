<?php
namespace zero;
include './Loader.php';
spl_autoload_register('Loader::_autoload');

$config = require './config/template.php';
$config['view_path'] = './templates/';
$config['cache_path'] = './templates_c/';

$data = [
	['name'=>'Nezimi'],
	['name'=>'Jimmy'],
	['name'=>'JameGold'],
];

$template = new Template($config);

$template->assign('name', 'Nezimi');
$template->assign('title', 'HelloWorld');
$template->assign('code', 1);
$template->assign('data', $data);
$result = $template->fetch('index');
echo $result;