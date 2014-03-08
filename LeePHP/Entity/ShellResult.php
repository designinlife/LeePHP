<?php
namespace LeePHP\Entity;

/**
 * Shell 命令执行结果对象。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class ShellResult {
    /**
     * 执行结果集合。
     *
     * @var array
     */
    private $_lines;

    /**
     * 控制台输出的最后一行信息。
     *
     * @var string
     */
    private $_lastLine;

    /**
     * 程序退出代码。
     *
     * @var int
     */
    private $_exitCode = 0;

    /**
     * 追加一行结果信息。
     * 
     * @param string $line
     */
    function append($line) {
        $this->_lines[] = trim($line);
    }
    
    function getLines() {
        return $this->_lines;
    }

    function getLastLine() {
        return $this->_lastLine;
    }

    function getExitCode() {
        return $this->_exitCode;
    }

    function setLastLine($lastLine) {
        $this->_lastLine = $lastLine;
        return $this;
    }

    function setExitCode($exitCode) {
        $this->_exitCode = $exitCode;
        return $this;
    }
}
