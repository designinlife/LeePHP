<?php
namespace LeePHP;

use ErrorException;
use RuntimeException;
use LeePHP\Base\ProcessBase;
use LeePHP\Base\WebBase;
use LeePHP\DB\DbPdo;
use LeePHP\Interfaces\IController;
use LeePHP\Interfaces\IDb;
use LeePHP\Interfaces\IPrinter;
use LeePHP\Interfaces\IProcess;
use LeePHP\Interfaces\ITemplate;
use LeePHP\System\Application;
use LeePHP\System\DefPrinter;
use LeePHP\System\Logger;
use LeePHP\System\ParamWrapper;
use LeePHP\Template\TemplateFactory;
use LeePHP\Utility\Console;

/**
 * LeePHP 框架核心启动对象。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2013, Lei Lee
 */
class Bootstrap {
    /**
     * 系统时区。
     *
     * @var string
     */
    private $_timeZone = 'Asia/Shanghai';

    /**
     * 错误级别。
     *
     * @var int
     */
    private $_errorLevel = 32759;

    /**
     * 控制器名称空间。
     *
     * @var string
     */
    private $_controllerNs = NULL;

    /**
     * 缺省控制器名称。
     *
     * @var string
     */
    private $_defaultControllerName = 'Index';

    /**
     * 框架根目录。
     *
     * @var string
     */
    private $_frameworkDirectory;

    /**
     * 模版目录。
     *
     * @var string
     */
    private $_templateDirectory;

    /**
     * 编译目录。
     *
     * @var string
     */
    private $_compileDirectory;

    /**
     * 指示是否缓存模版？
     *
     * @var boolean
     */
    private $_templateCacheEnable = false;

    /**
     * 指示是否开启模版缓存自动重载机制？
     *
     * @var boolean
     */
    private $_templateAutoReload = true;

    /**
     * 模版引擎名称。
     *
     * @var string
     */
    private $_templateEngine = 'Twig';

    /**
     * DB 自动提交。(仅适用于 InnoDB 引擎)
     *
     * @var boolean
     */
    private $_dbAutoCommit = true;

    /**
     * DB 持久化连接。
     *
     * @var boolean
     */
    private $_dbPersistent = false;

    /**
     * 日志级别。
     *
     * @var int
     */
    private $_logLevel = 0;

    /**
     * 日志文件存储目录。
     *
     * @var string
     */
    private $_logDir = NULL;

    /**
     * DEBUG 模式开关。
     *
     * @var boolean
     */
    private $_debug_enable = false;

    /**
     * 依赖的 Pecl 扩展名称列表。(注: 半角逗号分隔)
     *
     * @var string
     */
    private $_depends = NULL;

    /**
     * 指示是否 CLI 模式运行？
     *
     * @var boolean
     */
    private $_isProcessMode = false;

    /**
     * 系统配置 INI 文件路径列表。
     *
     * @var array
     */
    private $_iniFiles;

    /**
     * 开始时间。(毫秒)
     *
     * @var float
     */
    private $_start_ms = 0;

    /**
     * 结束时间。(毫秒)
     *
     * @var float
     */
    private $_end_ms = 0;

    /**
     * 总计执行时间。(毫秒)
     *
     * @var float
     */
    private $_execute_ms = 0;

    /**
     * 当前 IController 实例。
     *
     * @var IController|IProcess
     */
    private $_c_instance = NULL;

    /**
     * 系统当前时间戳。
     *
     * @var int
     */
    public $timestamp = 0;

    /**
     * CLI 命令行参数集合。
     *
     * @var array
     */
    public $argv = NULL;

    /**
     * 系统配置参数。
     *
     * @var array
     */
    public $cfgs = array();
    
    /**
     * 当前 CMD 命令数据项。
     *
     * @var array
     */
    public $cmd_data;
    
    /**
     * 当前 CMD 命令编号。
     *
     * @var int
     */
    public $cmd_id;

    /**
     * 当前进程 PID。
     *
     * @var int
     */
    public $pid = 0;

    /**
     * Application 对象实例。
     *
     * @var Application
     */
    public $application;

    /**
     * DataReceiver 对象实例。
     *
     * @var ParamWrapper
     */
    public $dw;

    /**
     * IDb 对象实例。
     *
     * @var IDb
     */
    public $db;

    /**
     * ITemplate 模版对象。
     *
     * @var ITemplate
     */
    public $template;

    /**
     * Logger 日志管理对象。
     *
     * @var Logger
     */
    public $logger;

    /**
     * IPrinter 数据输出对象。
     *
     * @var IPrinter
     */
    public $dp;

    /**
     * 请求调度。
     * 
     * @param array $argv    指定 CLI 模式运行时的命令行参数集合。
     * @param array $cmd_map 指定命令配置列表。(默认值: Null | 注: CLI 模式运行时无需此参数)
     */
    function dispatch(&$argv, $cmd_map = NULL) {
        // 设置框架根目录
        $this->_frameworkDirectory = dirname(__FILE__);

        include ($this->_frameworkDirectory . '/Exceptions.php');

        // 检查是否 CLI 命令行执行模式?
        if (0 == strcmp(PHP_SAPI, 'cli')) {
            $this->_isProcessMode = true;
            $this->argv           = $argv;
            $this->pid            = getmypid();
        }

        $this->timestamp = time();
        $this->_start_ms = microtime(true);

        // 设置系统全局参数
        date_default_timezone_set($this->_timeZone);
        error_reporting($this->_errorLevel);
        set_error_handler(array($this, 'defErrorHandler'), $this->_errorLevel);
        set_exception_handler(array($this, 'defExceptionHandler'));
        register_shutdown_function(array($this, 'dispose'));

        // 解析系统配置 INI 文件
        if (is_array($this->_iniFiles)) {
            if (!isset($this->_iniFiles['default']))
                throw new ArgumentException('缺少缺省的 INI 系统配置。', -1);

            foreach ($this->_iniFiles as $key => $file)
                $this->cfgs[$key] = parse_ini_file($file, true);
        } else {
            throw new ArgumentException('至少需要一个缺省 INI 系统配置。', -1);
        }

        // 初始化全局基础对象
        $this->application = new Application($this);

        if (false === $this->_isProcessMode) {
            // 解析路由器参数 ...
            if (!$cmd_map)
                throw new RuntimeException('未指定命令列表。', -1);

            // 实例化数据接收器对象
            $this->dw = new ParamWrapper($this, $_GET, $_POST, $_FILES);

            $this->cmd_id = $this->dw->GInt32('cmd', 0);

            // 实例化模版对象
            $this->template = TemplateFactory::create($this, $this->_templateEngine);
            
            if (isset($cmd_map[$this->cmd_id])) {
                $this->cmd_data = $cmd_map[$this->cmd_id];

                $cls_name = $this->_controllerNs . '\\' . $this->cmd_data[0];
                $cls_func = $this->cmd_data[1];
            } else {
                $cls_name = $this->_controllerNs . '\\' . $this->_defaultControllerName;
                $cls_func = 'index';
            }
        } else {
            Console::initialize($this);

            if (empty($this->argv[1]))
                $cls_name = $this->_controllerNs . '\\' . $this->_defaultControllerName;
            else
                $cls_name = $this->_controllerNs . '\\' . $this->argv[1];
        }

        // 实例化基础对象 ...
        $this->logger = new Logger($this);

        $this->db = new DbPdo($this->_dbAutoCommit, $this->_dbPersistent);
        $this->db->addDb($this->cfgs['default']['db']);

        $this->_c_instance = new $cls_name($this);

        if (!($this->_c_instance instanceof IController)) {
            throw new RuntimeException('必须实现 IController 接口。', -1);
        }

        // 初始化控制器 ...
        $this->_c_instance->onPreInit();
        $this->_c_instance->initialize();

        if (false === $this->_isProcessMode) {
            if (!($this->_c_instance instanceof WebBase))
                throw new RuntimeException('控制器 ' . $cls_name . ' 必须是 WebBase 的子类。', -1);

            if (!method_exists($this->_c_instance, $cls_func))
                throw new RuntimeException('控制器方法 ' . $cls_name . '::' . $cls_func . '() 尚未定义。', -1);

            // 初始化 IPrinter 对象 ...
            $this->dp = new DefPrinter($this);

            $this->_c_instance->$cls_func();
        } else {
            if (!($this->_c_instance instanceof ProcessBase))
                throw new RuntimeException('控制器 ' . $cls_name . ' 必须是 ProcessBase 的子类。', -1);
            if (!($this->_c_instance instanceof IProcess))
                throw new RuntimeException('进程处理器 ' . $cls_name . ' 必须实现 IProcess 接口。', -1);

            $this->_c_instance->execute();
        }

        $this->_c_instance->dispose();
    }

    /**
     * [Event] Shutdown 事件回调。
     */
    function dispose() {
        $this->dp       = NULL;
        $this->dw       = NULL;
        $this->template = NULL;

        if ($this->db) {
            $this->db->close();
            $this->db = NULL;
        }
    }

    /**
     * 程序执行终止之前调用此方法。
     */
    function terminate() {
        $this->_end_ms     = microtime(true);
        $this->_execute_ms = ($this->_end_ms - $this->_start_ms) * 1000;
    }

    /**
     * 缺省异常处理函数。
     * 
     * @param \Exception $ex
     */
    function defExceptionHandler($ex) {
        if ($this->_isProcessMode) {
            echo '(', $ex->getCode(), ') ', $ex->getMessage(), PHP_EOL, $ex->getTraceAsString(), PHP_EOL;
        } else {
            echo '<div style="margin: 36px;">';
            echo '[Message] ', $ex->getMessage(), '<br/>';
            echo '[File] ', $ex->getFile(), '<br/>';
            echo '[Line] ', $ex->getLine(), '<br/>';
            echo '[Trace] <pre>', $ex->getTraceAsString(), '</pre><br/>';
            echo '</div>';
        }
        Application::bye(-1);
    }

    /**
     * 缺省错误处理函数。
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    function defErrorHandler($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    /**
     * 指示是否 CLI 命令行模式运行脚本？
     * 
     * @return boolean
     */
    function isProcessMode() {
        return $this->_isProcessMode;
    }

    /**
     * 设置系统时区。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setTimeZone($value) {
        $this->_timeZone = $value;
        return $this;
    }

    /**
     * 设置系统错误报告级别。
     * 
     * @param int $value
     * @return Bootstrap
     */
    function setErrorLevel($value) {
        $this->_errorLevel = $value;
        return $this;
    }

    /**
     * 设置缺省控制器名称。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setDefaultControllerName($value) {
        $this->_defaultControllerName = $value;
        return $this;
    }

    /**
     * 设置应用程序控制器名称空间。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setControllerNs($value) {
        $this->_controllerNs = $value;
        return $this;
    }

    /**
     * 获取日志级别。
     * 
     * @return int
     */
    function getLogLevel() {
        return $this->_logLevel;
    }

    /**
     * 设置日志级别。
     * 
     * @param int $value
     * @return Bootstrap
     */
    function setLogLevel($value) {
        $this->_logLevel = $value;
        return $this;
    }

    /**
     * 获取日志文件存储目录。
     * 
     * @return string
     */
    function getLogDir() {
        return $this->_logDir;
    }

    /**
     * 设置日志文件存储目录。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setLogDir($value) {
        $this->_logDir = $value;
        return $this;
    }

    /**
     * 设置 DEBUG 模式开启状态。
     * 
     * @param boolean $enable
     * @return Bootstrap
     */
    function setDebugEnable($enable) {
        $this->_debug_enable = $enable;
        return $this;
    }

    /**
     * 指示是否已开启 DEBUG 模式？
     * 
     * @return boolean
     */
    function isDebug() {
        return $this->_debug_enable;
    }

    /**
     * 获取框架根目录路径。
     * 
     * @return string
     */
    function getFrameworkDirectory() {
        return $this->_frameworkDirectory;
    }

    /**
     * 获取模版引擎名称。
     * 
     * @return string
     */
    function getTemplateEngine() {
        return $this->_templateEngine;
    }

    /**
     * 设置模版引擎。
     * 
     * @param string $engine
     * @return Bootstrap
     */
    function setTemplateEngine($engine) {
        $this->_templateEngine = $engine;
        return $this;
    }

    /**
     * 指示是否缓存模版文件?
     * 
     * @return boolean
     */
    function isTemplateCacheEnable() {
        return $this->_templateCacheEnable;
    }

    /**
     * 指示是否缓存模版文件?
     * 
     * @param boolean $enable
     * @return Bootstrap
     */
    function setTemplateCacheEnable($enable) {
        $this->_templateCacheEnable = $enable;
        return $this;
    }

    /**
     * 指示是否开启模版缓存自动重载机制？
     * 
     * @return boolean
     */
    function isTemplateAutoReload() {
        return $this->_templateAutoReload;
    }

    /**
     * 指示是否开启模版缓存自动重载机制？
     * 
     * @param boolean $enable
     * @return Bootstrap
     */
    function setTemplateAutoReload($enable) {
        $this->_templateAutoReload = $enable;
        return $this;
    }

    /**
     * 获取模版目录路径。
     * 
     * @return string
     */
    function getTemplateDirectory() {
        return $this->_templateDirectory;
    }

    /**
     * 设置模版目录路径。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setTemplateDirectory($value) {
        $this->_templateDirectory = $value;
        return $this;
    }

    /**
     * 获取模版编译目录路径。
     * 
     * @return string
     */
    function getCompileDirectory() {
        return $this->_compileDirectory;
    }

    /**
     * 设置模版编译目录路径。
     * 
     * @param string $value
     * @return Bootstrap
     */
    function setCompileDirectory($value) {
        $this->_compileDirectory = $value;
        return $this;
    }

    /**
     * 指示是否开启 DB 自动提交？(仅适用于 InnoDB 引擎)
     * 
     * @param boolean $enable
     * @return Bootstrap
     */
    function setDbAutoCommit($enable) {
        $this->_dbAutoCommit = $enable;
        return $this;
    }

    /**
     * 指示是否开启 DB 持久化连接？
     * 
     * @param boolean $enable
     * @return Bootstrap
     */
    function setDbPersistent($enable) {
        $this->_dbPersistent = $enable;
        return $this;
    }

    /**
     * 设置依赖的 Pecl 扩展名称列表。(注: 半角逗号分隔)
     * 
     * @param string $depends
     * @return Bootstrap
     */
    function setDepends($depends) {
        $this->_depends = $depends;
        return $this;
    }

    /**
     * 设置系统配置 INI 文件路径。
     * 
     * @param string $files
     * @return Bootstrap
     */
    function setIniFiles($files) {
        $this->_iniFiles = $files;
        return $this;
    }
}
