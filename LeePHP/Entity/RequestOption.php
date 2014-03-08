<?php
namespace LeePHP\Entity;

/**
 * HTTP 请求选项定义。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class RequestOption {
    private $_url;
    private $_timeout   = 60;
    private $_timeoutMs = 0;
    private $_dataPost  = NULL;
    private $_headers   = array();
    private $_asyncable = false;
    private $_infoable  = false;

    /**
     * 快速创建 RequestOption 对象实例。
     * 
     * @param string  $url       指定请求地址。
     * @param int     $timeout   指定超时时长。(单位: 秒)
     * @param int     $timeoutMs 指定超时时长。(单位: 毫秒 | 注: 当设定此参数 > 0 时, $timeout 参数将无效)
     * @param array   $dataPost  指定 POST 数据集合。
     * @param boolean $asyncable 指示是否异步请求模式？(默认值: False)
     * @return RequestOption
     */
    static function create($url, $timeout = 60, $timeoutMs = 0, $dataPost = NULL, $asyncable = false) {
        $obj = new self;
        $obj->setUrl($url);
        $obj->setTimeout($timeout);
        $obj->setTimeoutMs($timeoutMs);
        $obj->setDataPost($dataPost);
        $obj->setAsyncable($asyncable);

        return $obj;
    }

    function getUrl() {
        return $this->_url;
    }

    function getTimeout() {
        return $this->_timeout;
    }

    function getTimeoutMs() {
        return $this->_timeoutMs;
    }

    function getDataPost() {
        return $this->_dataPost;
    }

    function getHeaders() {
        return $this->_headers;
    }

    function isPostMethod() {
        return !empty($this->_dataPost);
    }

    function isAsyncable() {
        return $this->_asyncable;
    }

    function isInfoable() {
        return $this->_infoable;
    }

    function setUrl($url) {
        $this->_url = $url;
        return $this;
    }

    function setTimeout($timeout) {
        $this->_timeout = $timeout;
        return $this;
    }

    function setTimeoutMs($timeoutMs) {
        $this->_timeoutMs = $timeoutMs;
        return $this;
    }

    function setDataPost($dataPost) {
        $this->_dataPost = $dataPost;
        return $this;
    }

    function setHeaders($headers) {
        $this->_headers = $headers;
        return $this;
    }

    function setAsyncable($asyncable) {
        $this->_asyncable = $asyncable;
        return $this;
    }

    function setInfoable($enable) {
        $this->_infoable = $enable;
        return $this;
    }
}
