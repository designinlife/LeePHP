<?php
namespace LeePHP\System;

use LeePHP\ArgumentException;
use LeePHP\Base\Base;
use LeePHP\Bootstrap;

/**
 * HTTP 客户端数据包装器。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class ParamWrapper extends Base {
    /**
     * $_GET 参数集合。
     *
     * @var array
     */
    public $gets = NULL;

    /**
     * $_POST 参数集合。
     *
     * @var array
     */
    public $posts = NULL;

    /**
     * $_FILES 参数集合。
     *
     * @var array
     */
    public $files = NULL;

    /**
     * 构造函数。
     * 
     * @param Bootstrap $ctx
     * @param array $gets
     * @param array $posts
     * @param array $files
     */
    function __construct($ctx, &$gets, &$posts, &$files) {
        parent::__construct($ctx);

        $this->gets  = &$gets;
        $this->posts = &$posts;
        $this->files = &$files;
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        parent::__destruct();

        unset($this->gets, $this->posts, $this->files);
    }

    /**
     * 获取 POST 参数 Raw 值。
     * 
     * @param string $key
     * @return string|array
     */
    function P($key) {
        return $this->posts[$key];
    }

    /**
     * 获取 GET 参数 Raw 值。
     * 
     * @param string $key
     * @return string|array
     */
    function G($key) {
        return $this->gets[$key];
    }

    /**
     * 获取 GET 参数整型值。
     * 
     * @param string $key
     * @param int $default
     * @return int
     */
    function GInt32($key, $default = 0) {
        if (!is_int($default))
            throw new ArgumentException('缺省值必须是一个整数。');

        if (!isset($this->gets[$key]))
            return $default;

        if (empty($this->gets[$key]))
            return $default;

        return ( int ) $this->gets[$key];
    }

    /**
     * 获取 POST 参数整型值。
     * 
     * @param string $key
     * @param int $default
     * @return int
     */
    function PInt32($key, $default = 0) {
        if (!is_int($default))
            throw new ArgumentException('缺省值必须是一个整数。');

        if (!isset($this->posts[$key]))
            return $default;

        if (empty($this->posts[$key]))
            return $default;

        return ( int ) $this->posts[$key];
    }

    /**
     * 获取 GET 参数字符串。
     * 
     * @param string $key
     * @param string $default
     */
    function GStr($key, $default = NULL) {
        if (!isset($this->gets[$key]))
            return $default;

        if (empty($this->gets[$key]))
            return $default;

        return trim($this->gets[$key]);
    }

    /**
     * 获取 POST 参数字符串。
     * 
     * @param string $key
     * @param string $default
     */
    function PStr($key, $default = NULL) {
        if (!isset($this->posts[$key]))
            return $default;

        if (empty($this->posts[$key]))
            return $default;

        return trim($this->posts[$key]);
    }
}
