<?php
namespace LeePHP\Utility;

use LeePHP\Bootstrap;
use LeePHP\C;

/**
 * 控制台管理工具类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0
 */
class Console {
    /**
     * Bootstrap 上下文对象。
     *
     * @var Bootstrap
     */
    static private $ctx = NULL;

    /**
     * Shell 控制台前景颜色列表。
     *
     * @var array
     */
    static private $foreground_colors = array();

    /**
     * Shell 控制台背景颜色列表。
     *
     * @var array
     */
    static private $background_colors = array();

    /**
     * 控制台消息类型。
     *
     * @var array
     */
    static private $types = array();

    /**
     * 初始化事件。
     * 
     * @param Bootstrap $ctx 指定 Bootstrap 上下文对象。
     */
    static function initialize($ctx) {
        self::$ctx = $ctx;

        // 日志消息类型 ...
        self::$types = array(
            1 => '[调试]',
            2 => '[信息]',
            3 => '[警告]',
            4 => '[错误]'
        );

        // 初始化 Shell 控制台颜色列表 ...
        // ----------------------------------------------------
        self::$foreground_colors = array(
            'black'        => '0;30',
            'dark_gray'    => '1;30',
            'blue'         => '0;34',
            'light_blue'   => '1;34',
            'green'        => '0;32',
            'light_green'  => '1;32',
            'cyan'         => '0;36',
            'light_cyan'   => '1;36',
            'red'          => '0;31',
            'light_red'    => '1;31',
            'purple'       => '0;35',
            'light_purple' => '1;35',
            'brown'        => '0;33',
            'yellow'       => '1;33',
            'light_gray'   => '0;37',
            'white'        => '1;37'
        );

        self::$background_colors = array(
            'black'      => '40',
            'red'        => '41',
            'green'      => '42',
            'yellow'     => '43',
            'blue'       => '44',
            'magenta'    => '45',
            'cyan'       => '46',
            'light_gray' => '47'
        );
    }

    /**
     * 获取 Shell 控制台颜色文本。
     * 
     * @param string $s                指定需要设置色彩的文本字符串。
     * @param string $foreground_color 指定前景颜色名称。
     * @param string $background_color 指定背景颜色名称。
     * @return string
     */
    static function colour($s, $foreground_color = null, $background_color = null) {
        $color_s = '';

        // Check if given foreground color found
        if (isset(self::$foreground_colors[$foreground_color])) {
            $color_s .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(self::$background_colors[$background_color])) {
            $color_s .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $color_s .= $s . "\033[0m";

        return $color_s;
    }

    /**
     * 打印控制台状态信息。(注: \r 结束符 | 支持若干参数.)
     */
    static function status() {
        $s   = array();
        $s[] = "\033[s";

        $args = func_get_args();
        $size = func_num_args();

        for ($i = 0; $i < $size; $i++) {
            $s[] = "\033[K" . $args[$i] . ($i == ($size - 1) ? '' : PHP_EOL);
        }

        $s[] = "\033[" . ($size - 1) . "A\r";

        echo implode('', $s);
    }

    /**
     * DEBUG.
     */
    static function debug() {
        $args = func_get_args();
        self::log(C::L_DEBUG, $args);
    }

    /**
     * 信息。
     */
    static function info() {
        $args = func_get_args();
        self::log(C::L_INFO, $args);
    }

    /**
     * 警告。
     */
    static function warning() {
        $args = func_get_args();
        self::log(C::L_WARNING, $args);
    }

    /**
     * 错误。
     */
    static function error() {
        $args = func_get_args();
        self::log(C::L_ERROR, $args);
    }

    /**
     * 打印控制台文本消息。
     */
    static private function log($type, $args) {
        if (0 != strcmp(PHP_SAPI, 'cli'))
            return false;

        $doc   = array();
        $doc[] = date('Y-m-d H:i:s') . ' ';
        $doc[] = '[#' . self::$ctx->pid . ']' . self::$types[$type] . ' ';

        foreach ($args as $v) {
            if (is_array($v))
                $doc[] = json_encode($v, 320);
            elseif (is_bool($v))
                $doc[] = $v ? 'True' : 'False';
            else
                $doc[] = strval($v);
        }

        $s = implode('', $doc);

        if (PHP_OS == 'Linux' && C::L_ERROR == $type)
            echo self::colour($s, 'light_red'), PHP_EOL;
        else
            echo $s, PHP_EOL;
    }
}
