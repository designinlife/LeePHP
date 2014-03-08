LeePHP
======

LeePHP(原名: RadishPHP) 是一款简单的 PHP Web 系统基础框架。采用 PHP 5.4 Namespace 特性，重新架构。结构更清晰、更简单，易用性更强。此版本将融入更多的新特性！您可以在不违反 GPL v2 协议的情况下，完全免费自由地使用 LeePHP 框架开发您的 Web 站点。

Example
======
___基于 Web 服务器 (index.php)___
```php
<?php
define('LINE_DELIMITER', "\n");
define('TAB_INDENT', "\t");
define('DS', DIRECTORY_SEPARATOR);
define('SYS_ROOT', dirname(__FILE__) . DS);
define('SYS_CONF', SYS_ROOT . 'etc' . DS);

header('Content-Type: text/html; CharSet=UTF-8');

include (SYS_CONF . 'cmd.inc.php');
include (SYS_ROOT . 'al.php');

use LeePHP\Bootstrap;
use LeePHP\Core\C;

$dis = new Bootstrap();
$dis->setTimeZone('Asia/Shanghai')
    ->setErrorLevel(E_ALL ^ E_NOTICE)
    ->setLogLevel(0)
    ->setLogDir(SYS_ROOT . 'logs')
    ->setControllerNs('Application\Controller')
    ->setDbAutoCommit(true)
    ->setDbPersistent(false)
    ->setTemplateCacheEnable(true)
    ->setTemplateAutoReload(true)
    ->setTemplateEngine(C::TPL_ENGINE_TWIG)
    ->setTemplateDirectory(SYS_ROOT . 'templates' . DS)
    ->setCompileDirectory(SYS_ROOT . 'templates_c' . DS)
    ->setIniFiles(array(
        'default' => SYS_CONF . 'config.ini'
    ))
    ->dispatch($argv, $g_cmd_hash);
```

___基于命令行模式的脚本 (cli.php)___
```php
<?php
define('LINE_DELIMITER', "\n");
define('TAB_INDENT', "\t");
define('DS', DIRECTORY_SEPARATOR);
define('SYS_ROOT', dirname(__FILE__) . DS);
define('SYS_CONF', SYS_ROOT . 'etc' . DS);

include (SYS_ROOT . 'al.php');

use LeePHP\Bootstrap;

$dis = new Bootstrap();
$dis->setTimeZone('Asia/Shanghai')
    ->setErrorLevel(E_ALL ^ E_NOTICE)
    ->setLogLevel(0)
    ->setLogDir(SYS_ROOT . 'logs')
    ->setControllerNs('Application\Process')
    ->setDbAutoCommit(true)
    ->setDbPersistent(false)
    ->setIniFiles(array(
        'default' => SYS_CONF . 'config.ini',
        'svn'     => SYS_CONF . 'svn.ini',
    ))
    ->dispatch($argv);
```
