<?php
namespace LeePHP\Interfaces;

/**
 * 模版引擎接口。
 * 
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
interface ITemplate {
    /**
     * 模版变量赋值。
     * 
     * @param string $tpl_var
     * @param mixed $values
     */
    function assign($tpl_var, $values);

    /**
     * 打印 PHP 模版输出。
     * 
     * @param string $tpl_file  指定模版文件相对路径。
     * @param array $tpl_data   指定扩展输出的数据集合。
     * @param boolean $exitable 指示是否终止进程？(默认值: True)
     * @return void
     */
    function display($tpl_file, $tpl_data = NULL, $exitable = true);

    /**
     * 执行模版编译并返回结果字符串。
     * 
     * @param string $tpl_file 指定模版文件相对路径。
     * @param array $tpl_data  指定扩展输出的数据集合。
     * @return string
     */
    function toString($tpl_file, $tpl_data = NULL);

    /**
     * 指示模版缓存功能是否开启？
     * 
     * @param boolean $enable 指定开启状态标识。(布尔值 | 默认值: False)
     */
    function setCacheEnable($enable = false);

    /**
     * 指示是否自动检查模版修改状态？
     * 
     * @param boolean $enable
     */
    function setAutoReload($enable = false);

    /**
     * 设置当前视图唯一名称。(注: 一般使用当前执行的函数名即可.)
     * 
     * @param string $view_name 指定视图名称。
     */
    function setViewName($view_name);
}
