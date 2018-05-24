Template Engine
===============

A template engine for php.

[![Latest Stable Version](https://poser.pugx.org/yilongpeng/template-engine/v/stable)](https://packagist.org/packages/yilongpeng/template-engine)



## Installation

Use [composer](http://getcomposer.org) to install yilong/mysql in your project:

```
composer require yilongpeng/template-engine
```

## Usage

```php
use TemplateEngine\TemplateEngine;

define('DC', DIRECTORY_SEPARATOR);
define('APP_PATH',__DIR__.DC);
$template_dir = APP_PATH.'templates'.DC;
$compie_dir = APP_PATH.'templates_c'.DC;

$smarty = new TemplateEngine\TemplateEngine();
$smarty->debug = true;  //the debug enable
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezumi');
$smarty->display('index');
```


