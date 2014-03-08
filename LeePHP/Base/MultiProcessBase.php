<?php
namespace LeePHP\Base;

use Exception;
use LeePHP\Base\ProcessBase;
use LeePHP\Utility\Console;

declare(ticks = 1);

/**
 * CLI 多进程脚本基类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class MultiProcessBase extends ProcessBase {
    /**
     * 当前多进程任务队列。
     *
     * @var array
     */
    protected $workers = array();

    /**
     * 进程信号队列。
     *
     * @var array
     */
    protected $signals = array();

    /**
     * 进程信号处理函数。
     * 
     * @param int $signo  系统信号。
     * @param int $pid    进程编号。
     * @param int $status 进程状态。
     * @return boolean
     */
    final function child_signal_handler($signo, $pid = NULL, $status = NULL) {
        if (!$pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        while ($pid > 0) {
            if ($pid && isset($this->workers[$pid])) {
                $exit_code = pcntl_wexitstatus($status);
                if ($exit_code != 0) {
                    $this->onWorkerStatus(true, $pid, $status, '子进程 #' . $pid . ' 已退出.(exit code = ' . $exit_code . ')');
                } else {
                    $this->onWorkerStatus(true, $pid, $status, '子进程 #' . $pid . ' 已正常退出.');
                }
                unset($this->workers[$pid]);
            } else if ($pid) {
                $this->signals[$pid] = $status;
                $this->onWorkerStatus(false, $pid, $status, '子进程已启动.');
            }

            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        return true;
    }

    /**
     * 启动多进程。
     * 
     * @param int $child_num 指定需要启动的子进程数量。
     * @param mixed $data    指定工作子进程所需的数据。
     */
    final protected function start($child_num, $data) {
        // 注册信号控制器 ...
        pcntl_signal(SIGCHLD, array($this, "child_signal_handler"));

        for ($i = 0; $i < $child_num; $i++) {
            $this->_fork_child_proc($data);
        }

        $this->_waitfor();
    }

    /**
     * 子进程工作函数。
     * 
     * @param array|int|string $data
     */
    function doTask($data) {
        
    }

    /**
     * 子进程状态报告事件。
     * 
     * @param boolean $exited 指示子进程是否退出？
     * @param int $pid        子进程 PID。
     * @param int $status     信号状态。
     * @param string $msg     消息描述。
     */
    function onWorkerStatus($exited, $pid, $status, $msg) {
        
    }

    /**
     * PCNTL - fork 子进程。
     * 
     * @param mixed $data
     * @return boolean
     */
    private function _fork_child_proc(&$data) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            Console::error('子进程启动失败!');
            return false;
        } else if ($pid) {
            $this->workers[$pid] = $data;

            if (isset($this->signals[$pid])) {
                $this->child_signal_handler(SIGCHLD, $pid, $this->signals[$pid]);
                unset($this->signals[$pid]);
            }
        } else {
            $exit_code = 0;

            $this->ctx->pid = getmypid();

            $this->onWorkerStatus(false, $this->ctx->pid, NULL, '子进程已启动.');

            try {
                $this->doTask($data);
            } catch (Exception $ex) {
                $this->ctx->logger->error($ex->getMessage());
            }

            exit($exit_code);
        }

        return true;
    }

    /**
     * 等待工作子进程结束。
     */
    private function _waitfor() {
        while (count($this->workers)) {
            sleep(1);
        }
    }
}
