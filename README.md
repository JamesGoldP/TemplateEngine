Zero Template
===============

The studying template engine for think-template.


## Installation

Use [composer](http://getcomposer.org) to install zero/zero-template in your project:

```
composer require zero/zero-template
```

## Usage

```php
namespace zero;

require __DIR__.'/vendor/autoload.php';

$config = __DIR__ . '/config/template.php';
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
```


