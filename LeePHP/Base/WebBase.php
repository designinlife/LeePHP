<?php
namespace LeePHP\Base;

use LeePHP\Interfaces\IController;
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
    protected $data;

    /**
     * 构造函数。
     * 
     * @param \LeePHP\Bootstrap $ctx
     */
    function __construct($ctx) {
        parent::__construct($ctx);

        $this->isPost = (0 === strcmp($_SERVER['REQUEST_METHOD'], 'POST'));
        $this->data   = $this->ctx->dw;

        $this->ctx->template->assign('cfgs', $this->ctx->cfgs);
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        parent::__destruct();

        unset($this->data);
    }

    /**
     * 预初始化事件。(注: 此方法在 initialize() 之前调用)
     */
    function onPreInit() {
        $this->ctx->template->assign('cfgs', $this->ctx->cfgs);
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
