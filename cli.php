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
