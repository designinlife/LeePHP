<?php
namespace LeePHP\IO;

use LeePHP\Base\Base;
use LeePHP\Bootstrap;
use LeePHP\Entity\ShellResult;
use LeePHP\NetworkException;
use LeePHP\PermissionException;
use LeePHP\Utility\Console;

/**
 * Shell 命令行工具辅助类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class Shell extends Base {
    /**
     * Linux Shell 会话对象。
     *
     * @var resource
     */
    private $shell_session;

    /**
     * Shell 连接状态标识。
     *
     * @var boolean
     */
    private $shell_ok = false;

    /**
     * 静态创建 Shell 对象实例。
     * 
     * @param Bootstrap $ctx
     * @return Shell
     */
    static function create($ctx) {
        return (new Shell($ctx));
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        parent::__destruct();

        $this->shell_ok      = false;
        $this->shell_session = NULL;
    }

    /**
     * 连接远程 Shell 主机。
     * 
     * @param string $host 指定主机地址。
     * @param int    $port 指定端口。
     * @param string $user 指定登录名称。
     * @param string $pass 指定密码。
     * @throws PermissionException
     * @throws NetworkException
     */
    function connect($host, $port, $user, $pass) {
        $this->shell_session = ssh2_connect($host, $port);

        if ($this->shell_session) {
            $ok = ssh2_auth_password($this->shell_session, $user, $pass);

            if (!$ok)
                throw new PermissionException('远程 Shell 登录失败!', -1);

            $this->shell_ok = true;
        } else
            throw new NetworkException('连接到远程 Shell 主机失败!', -1);
    }

    /**
     * 关闭远程 Shell 连接。
     */
    function close() {
        $this->shell_session = NULL;
        $this->shell_ok      = false;
    }

    /**
     * 执行远程 Shell 命令。
     * 
     * @param string $command 指定命令字符串。
     * @param string $su_pass 指定 SU 帐号密码。(默认值: Null | 传入此参数将以 SU 超级用户权限执行)
     * @param string $su_user 指定执行 SU 命令的帐号。(默认值: root)
     * @return ShellResult|boolean
     * @throws NetworkException
     */
    function execute($command, $su_pass = NULL, $su_user = 'root') {
        if (!$this->shell_ok)
            throw new NetworkException('尚未连接远程主机。', -1);

        // $stream = ssh2_exec($this->shell_session, $command);
        $stream = ssh2_shell($this->shell_session, "vanilla", null, 200);

        if (!$stream)
            throw new NetworkException('执行命令失败。');

        $sr = new ShellResult();

        if ($su_user && $su_pass)
            $this->_execute_su($stream, $sr, $command, $su_user, $su_pass);
        else
            $this->_execute($stream, $sr, $command);

        fclose($stream);

        return $sr;
    }

    /**
     * 以 SU - 命令模式执行。
     * 
     * @param resource $stream
     * @param ShellResult $sr
     * @param string $command
     * @param string $su_user
     * @param string $su_pass
     * @return boolean
     */
    private function _execute_su(&$stream, &$sr, $command, $su_user, $su_pass) {
        stream_set_blocking($stream, true);

        if (fputs($stream, "su {$su_user}\n") === false) {
            fclose($stream);
            return false;
        }

        $line       = '';
        $returnCode = 1;

        while (($char = fgetc($stream)) !== false) {
            $line .= $char;

            if ($char != "\n") {
                if (preg_match("/Password:/", $line)) {
                    // Password prompt.
                    if (fputs($stream, "{$su_pass}\n{$command}\necho [end] $?\n") === false) {
                        return false;
                    }
                    $line = "";
                } else if (preg_match("/incorrect/", $line)) {
                    //Incorrect root password
                    return false;
                }
            } else {
                if (preg_match("/\[end\]\s*([0-9]+)/", $line, $matches)) {
                    // End of command detected.
                    $returnCode = $matches[1];
                    break;
                } else {
                    $sr->append($line);
                }

                $line = '';
            }
        }
    }

    /**
     * 以当前登录用户模式运行。
     * 
     * @param resource $stream
     * @param ShellResult $sr
     * @param string $command
     */
    private function _execute($stream, &$sr, $command) {
        fwrite($stream, $command . PHP_EOL);
        sleep(1);

        while ($line = fgets($stream, 4096)) {
            flush();
            $sr->append($line);
        }
    }

    /**
     * 执行本地系统 Shell 命令并返回结果。
     * 
     * @param string|array $command 指定 Shell 命令语法。
     * @return ShellResult
     */
    static function exec($command) {
        $output     = NULL;
        $return_var = false;
        $cmd_s      = NULL;

        if (is_array($command))
            $cmd_s = implode('; ', $command);
        elseif (is_string($command))
            $cmd_s = $command;

        $last_line = exec($cmd_s, $output, $return_var);

        $r = new ShellResult();

        $r->exit_code = $return_var;
        $r->lines     = $output;
        $r->last_line = $last_line;

        return $r;
    }
}
