<?php
if (!defined('SYS_ROOT'))
    define('SYS_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

spl_autoload_register('defSplAutoLoadHandler');

/**
 * 类自动加载处理函数。
 * 
 * @param string $class_name
 * @return boolean
 */
function defSplAutoLoadHandler($class_name) {
    // 设置 Twig 模版引擎支持 ...
    if (0 === strpos($class_name, 'Twig')) {
        $file = SYS_ROOT . 'libs' . DIRECTORY_SEPARATOR . str_replace(array('_', "\0"), array(DIRECTORY_SEPARATOR, ''), $class_name) . '.php';
        
        include ($file);
    } else {
        $file = SYS_ROOT . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        include ($file);
    }
}
