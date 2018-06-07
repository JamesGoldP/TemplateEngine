Template Engine
===============

A template engine for php.


## Installation

Use [composer](http://getcomposer.org) to install nezumi/template-engine in your project:

```
composer require nezumi/template-engine
```

## Usage

```php
use Nezumi\TemplateEngine;

define('DC', DIRECTORY_SEPARATOR);
define('APP_PATH',__DIR__.DC);
$template_dir = APP_PATH.'templates'.DC;
$compie_dir = APP_PATH.'templates_c'.DC;

$smarty = new TemplateEngine();
$smarty->debug = true;  //the debug enable
$smarty->setTemplateDir($template_dir);
$smarty->setCompileDir($compie_dir);
$smarty->assign('name', 'Nezumi');
$smarty->display('index');
```


