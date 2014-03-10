<?php
namespace LeePHP\Base;

use LeePHP\Base\Base;
use LeePHP\Bootstrap;
use LeePHP\OptionKit\GetOptionKit;
use LeePHP\System\Application;

/**
 * 进程控制器基类。
 * 
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class ProcessBase extends Base {
    /**
     * GetOptionKit 对象实例。
     *
     * @var GetOptionKit
     */
    protected $opt = NULL;

    /**
     * 构造函数。
     * 
     * @param Bootstrap $ctx 指定 Bootstrap 上下文对象。
     */
    function __construct($ctx) {
        parent::__construct($ctx);

        $this->opt = new GetOptionKit();
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
        $this->opt->add('h|help', '显示命令帮助信息。', 'help');
        $this->opt->parse($this->ctx->argv);

        $help = $this->opt->get('help');

        if (true === $help) {
            $this->opt->printOptions();
            Application::bye();
        }
    }

    /**
     * 内存释放。
     */
    function dispose() {
        
    }
}
