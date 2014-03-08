<?php
namespace LeePHP\Template;

use LeePHP\Bootstrap;
use LeePHP\Interfaces\ITemplate;
use LeePHP\Interfaces\IDisposable;

/**
 * 模版引擎工厂类。
 * 
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class TemplateFactory {
    /**
     * ITemplate 对象集合。
     *
     * @var array
     */
    static private $insts = array();

    /**
     * 创建 ITemplate 实例。
     * 
     * @param Bootstrap $ctx 指定系统上下文对象。
     * @param string $engine 指定引擎名称。(默认值: PHP | 可选值: Twig)
     * @return ITemplate
     */
    static function create($ctx, $engine = NULL) {
        if (!$engine)
            $engine = 'Twig';

        if (!isset(self::$insts[$engine])) {
            if (0 == strcmp('Twig', $engine)) {
                self::$insts[$engine] = new TemplateTwig($ctx);
            } else {
                self::$insts[$engine] = new TemplatePHP($ctx);
            }
        }

        return self::$insts[$engine];
    }

    /**
     * 释放资源。
     */
    static function dispose() {
        if (self::$insts) {
            foreach (self::$insts as $item) {
                if ($item instanceof IDisposable) {
                    $item->dispose();
                }
            }
        }

        self::$insts = NULL;
    }
}