<?php
namespace LeePHP\Entity;

/**
 * HTTP 请求响应结果对象。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class Response {
    private $_httpCode      = 0;
    private $_message;
    private $_contentType;
    private $_headerSize    = 0;
    private $_requestSize   = 0;
    private $_redirectCount = 0;
    private $_totalTime     = 0;

    function getHttpCode() {
        return $this->_httpCode;
    }

    function getMessage() {
        return $this->_message;
    }

    function getContentType() {
        return $this->_contentType;
    }

    function getHeaderSize() {
        return $this->_headerSize;
    }

    function getRequestSize() {
        return $this->_requestSize;
    }

    function getRedirectCount() {
        return $this->_redirectCount;
    }

    function getTotalTime() {
        return $this->_totalTime;
    }

    function setHttpCode($httpCode) {
        $this->_httpCode = $httpCode;
        return $this;
    }

    function setMessage($message) {
        $this->_message = $message;
        return $this;
    }

    function setContentType($contentType) {
        $this->_contentType = $contentType;
        return $this;
    }

    function setHeaderSize($headerSize) {
        $this->_headerSize = $headerSize;
        return $this;
    }

    function setRequestSize($requestSize) {
        $this->_requestSize = $requestSize;
        return $this;
    }

    function setRedirectCount($redirectCount) {
        $this->_redirectCount = $redirectCount;
        return $this;
    }

    function setTotalTime($totalTime) {
        $this->_totalTime = $totalTime;
        return $this;
    }
}
