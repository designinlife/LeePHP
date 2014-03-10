<?php
namespace LeePHP\Base;

use LeePHP\Bootstrap;
use LeePHP\Interfaces\IController;
use LeePHP\Interfaces\IPrinter;
use LeePHP\Interfaces\ITemplate;
use LeePHP\System\ParamWrapper;

/**
 * 基于 Web 的应用程序控制器基类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class WebBase extends Base implements IController {
    /**
     * 指示是否 POST 请求方式？
     *
     * @var boolean
     */
    protected $isPost = false;

    /**
     * GET/POST 数据管理对象。
     *
     * @var ParamWrapper
     */
    protected $dw;

    /**
     * IPrinter 数据打印对象。
     *
     * @var IPrinter
     */
    protected $dp;

    /**
     * ITemplate 模版对象。
     *
     * @var ITemplate
     */
    protected $template;

    /**
     * 构造函数。
     * 
     * @param Bootstrap $ctx
     */
    function __construct($ctx) {
        parent::__construct($ctx);

        $this->isPost   = (0 === strcmp($_SERVER['REQUEST_METHOD'], 'POST'));
        $this->dw       = $this->ctx->dw;
        $this->dp       = $this->ctx->dp;
        $this->template = $this->ctx->template;

        $this->ctx->template->assign('cfgs', $this->ctx->cfgs);
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        parent::__destruct();

        unset($this->dw, $this->dp, $this->template);
    }

    /**
     * 预初始化事件。(注: 此方法在 initialize() 之前调用)
     */
    function onPreInit() {
        
    }

    /**
     * 初始化事件。
     */
    function initialize() {
        
    }

    /**
     * 内存释放。
     */
    function dispose() {
        
    }

    /**
     * 模版变量赋值。
     * 
     * @param string $tpl_var
     * @param mixed $values
     */
    function assign($tpl_var, $values) {
        $this->template->assign($tpl_var, $values);
    }

    /**
     * 打印 PHP 模版输出。
     * 
     * @param string  $tpl_file 指定模版文件相对路径。
     * @param array   $tpl_data 指定扩展输出的数据集合。(默认值: Null)
     * @param boolean $exitable 指示是否终止进程？(默认值: True)
     * @return void
     */
    function display($tpl_file, $tpl_data = NULL, $exitable = true) {
        $this->template->display($tpl_file, $tpl_data, $exitable);
    }

    /**
     * 跳转到上一页。
     */
    function referer() {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit(0);
    }

    /**
     * 页面重定向。
     */
    function go() {
        $size = func_num_args();
        $args = func_get_args();

        if ($size == 0)
            $this->referer();

        $ups = array();

        if (is_int($args[0])) {
            $ups[] = 'cmd=' . $args[0];
        } else {
            $ups[] = $args[0];
        }

        if ($size > 1) {
            for ($i = 1; $i < $size; $i++)
                $ups[] = $args[$i];
        }

        $url = '?' . implode('&', $ups);

        header('Location: ' . $url);
        exit(0);
    }
}
