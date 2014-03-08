<?php
namespace LeePHP\Template;

use LeePHP\Interfaces\IDisposable;
use LeePHP\Interfaces\ITemplate;
use LeePHP\Bootstrap;
use LeePHP\Base\Base;
use LeePHP\System\Application;
use LeePHP\Utility\GeneralUtil;
use LeePHP\IOException;
use LeePHP\ArgumentException;

/**
 * Template 模版引擎类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0
 */
class TemplatePHP extends Base implements ITemplate, IDisposable {
    /**
     * 模版变量数据集合。
     *
     * @var array
     */
    private $data = array();

    /**
     * $_GET 变量集合。
     *
     * @var array
     */
    private $gets = NULL;

    /**
     * $_POST 变量集合。
     *
     * @var array
     */
    private $posts = NULL;

    /**
     * $_SERVER 变量集合。
     *
     * @var array
     */
    private $server = NULL;

    /**
     * 模版变量保留关键字列表。
     *
     * @var array
     */
    private $tpl_keyword = array(
        'sys', 'env', 'gets', 'posts', 'script', 'css', 'view'
    );

    /**
     * 构造函数。
     * 
     * @param Bootstrap $ctx 指定系统上下文对象。
     */
    function __construct(&$ctx) {
        parent::__construct($ctx);

        $this->gets   = &$_GET;
        $this->posts  = &$_POST;
        $this->server = &$_SERVER;
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        $this->data = NULL;
    }

    /**
     * 模版变量赋值。
     * 
     * @param string $tpl_var
     * @param mixed $values
     */
    function assign($tpl_var, $values) {
        if (in_array($tpl_var, $this->tpl_keyword))
            throw new ArgumentException('模版变量不能采用关键字 ' . $tpl_var, -1);

        $this->data[$tpl_var] = $values;
    }

    /**
     * 打印 PHP 模版输出。
     * 
     * @param string $tpl_file  指定模版文件相对路径。
     * @param array $tpl_data   指定扩展输出的数据集合。
     * @param boolean $exitable 指示是否终止进程？(默认值: True)
     * @return void
     */
    function display($tpl_file, $tpl_data = NULL, $exitable = true) {
        $tpl_file = GeneralUtil::standardize($this->ctx->getTemplateDirectory() . DIRECTORY_SEPARATOR . $tpl_file . '.tpl.php');

        // 注入系统变量集合 ...
        $this->data['cfgs']  = &$this->ctx->cfgs;
        $this->data['gets']  = &$this->gets;
        $this->data['posts'] = &$this->posts;
        $this->data['env']   = &$this->server;

        if ($tpl_data && is_array($tpl_data))
            $this->data = array_merge($this->data, $tpl_data);

        if (!is_file($tpl_file))
            throw new IOException('模版文件不存在。(' . $tpl_file . ')', -1);

        include ($tpl_file);

        if ($exitable)
            Application::bye(0);
    }

    /**
     * 执行模版编译并返回结果字符串。
     * 
     * @param string $tpl_file 指定模版文件相对路径。
     * @param array $tpl_data  指定扩展输出的数据集合。
     * @return string
     */
    function toString($tpl_file, $tpl_data = NULL) {
        ob_start();

        $this->display($tpl_file, $tpl_data, false);

        return ob_get_clean();
    }

    /**
     * 指示模版缓存功能是否开启？
     * 
     * @param boolean $enable 指定开启状态标识。(布尔值 | 默认值: False)
     */
    function setCacheEnable($enable = false) {
        
    }

    /**
     * 指示是否自动检查模版修改状态？
     * 
     * @param boolean $enable
     */
    function setAutoReload($enable = false) {
        
    }

    /**
     * 设置当前视图唯一名称。(注: 一般使用当前执行的函数名即可.)
     * 
     * @param string $view_name 指定视图名称。
     */
    function setViewName($view_name) {
        $this->data['view'] = $view_name;
    }

    /**
     * 释放资源。
     */
    function dispose() {
        
    }
}
