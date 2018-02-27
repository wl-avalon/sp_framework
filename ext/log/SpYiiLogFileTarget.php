<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午3:06
 */

namespace sp_framework\ext\log;
use sp_framework\SpModule;
use Yii;
use yii\log\Logger;
use yii\log\Target;
use yii\helpers\FileHelper;

class SpYiiLogFileTarget extends Target
{
    const LOG_LEVEL_FATAL   = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE  = 0x04;
    const LOG_LEVEL_TRACE   = 0x08;
    const LOG_LEVEL_DEBUG   = 0x10;

    static public $yii_log_level_map = array(
        Logger::LEVEL_WARNING => self::LOG_LEVEL_WARNING,
        Logger::LEVEL_ERROR => self::LOG_LEVEL_FATAL,
        Logger::LEVEL_INFO => self::LOG_LEVEL_DEBUG,
        Logger::LEVEL_TRACE => self::LOG_LEVEL_TRACE,
        Logger::LEVEL_PROFILE => self::LOG_LEVEL_TRACE,
        Logger::LEVEL_PROFILE_BEGIN => self::LOG_LEVEL_TRACE,
        Logger::LEVEL_PROFILE_END => self::LOG_LEVEL_TRACE,
    );

    static public $log_level_map = array(
        self::LOG_LEVEL_FATAL   => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE  => 'NOTICE',
        self::LOG_LEVEL_TRACE   => 'TRACE',
        self::LOG_LEVEL_DEBUG   => 'DEBUG',
    );

    static public $log_suffix_map = array(
        self::LOG_LEVEL_FATAL   => '.wf',
        self::LOG_LEVEL_WARNING => '.wf',
        self::LOG_LEVEL_NOTICE  => '',
        self::LOG_LEVEL_TRACE   => '',
        self::LOG_LEVEL_DEBUG   => '',
    );

    const DEFAULT_FORMAT_DEBUG = "%s: %s %s [%s:%s] errno[%s] log_id[%s] %s %s\n";
    const DEFAULT_LOG_LEVEL = 16;
    const DEFAULT_MODULE = 'default';
    const URI_SEPATATOR = '/';

    // 配置的日志级别
    public $log_level;
    public $log_path;
    public $log_func = null;

    /**
     * @var integer the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;


    public function init()
    {
        parent::init();

        $this->log_path = empty($this->log_path) ? Yii::$app->getRuntimePath()."/logs" : $this->log_path;
        $this->log_level = empty($this->log_level) ? self::DEFAULT_LOG_LEVEL : $this->log_level;

    }

    public function collect($messages, $final)
    {
        $this->messages = $messages;

        $this->export();
    }


    public function export()
    {
        $messages = $this->messages;
        //TODO:当messages为多条记录时可以修改为合并写，但是目前messages只会有一条记录
        foreach($messages as $message){
            $field_count = count($message);
            if($field_count >= 8){
                list($str,$log_level,$category,$time,$traces,$errno,$args,$depth) = $message;
                $this->writeLog($log_level, $str, $errno, $args, $depth + 4);
            }else if($field_count === 5){
                list($str,$log_level,$category,$time,$traces) = $message;
                $this->writeLog($log_level, $str, 0, [], 0);
            }


        }

    }

    static public function genLogID() {
        if (defined('LOG_ID')) {
            return LOG_ID;
        }

        if (isset($_SERVER['HTTP_SP_HEADER_RID'])){
            $log_id = trim($_SERVER['HTTP_SP_HEADER_RID']);
        }else{
            $arr = gettimeofday();
            $log_id = ((($arr['sec'] * 100000 + $arr['usec'] / 10) & 0x7FFFFFFF) | 0x80000000);
        }

        define('LOG_ID', $log_id);

        return LOG_ID;
    }

    /**
     * 实际执行写日志函数
     *
     * @param $level
     * @param $message
     * @param $errno
     * @param $args
     * @param $depth
     * @return boolean true/false
     * @throws \yii\base\Exception
     */
    protected function writeAppLog($level, $message, $errno, $args, $depth) {

        if (($level > $this->log_level) || !isset(self::$log_level_map[$level])) {
            return;
        }

        $time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        // ex:/data/logs/payui/20160405/payui_09.log
        $moduleName = SpModule::getModuleName();
        $logDir = $this->log_path;
        if(!is_dir($logDir)) {
            FileHelper::createDirectory($logDir, $this->dirMode, true);
        }

        $logFile = $logDir . "/" . $moduleName .'_'. date('H', $time)  . ".log";
        $logFile = $logFile . self::getLogSuffixByLevel($level);

        $clientIp = '127.0.0.1';
        if(Yii::$app instanceof yii\web\Application){
            $clientIp = Yii::$app->request->getUserIP();
        }

        if($level === self::LOG_LEVEL_NOTICE){
            $traceFile = "URI";
            $request = Yii::$app->getRequest();
            if($request->getIsConsoleRequest()){
                $params = $request->getParams();
                $pathInfo = trim($params[0], '/');
            } else {
                $pathInfo = $request->getPathInfo();
            }

            $traceLine = $pathInfo;
        } else {
            $trace = debug_backtrace();
            $traceFile = isset($trace[$depth]['file']) ? $trace[$depth]['file'] : '';
            $traceLine = isset($trace[$depth]['line']) ? $trace[$depth]['line'] : '';
        }

        $argsStr = '';
        if (is_array($args) && !empty($args)) {
            foreach ($args as $key => $val) {
                $str = is_array($val) ? json_encode($val) : $val;
                $argsStr .= "{$key}[{$str}] ";
            }
        }

        $message = sprintf(self::DEFAULT_FORMAT_DEBUG,
            self::$log_level_map[$level],
            date('y-m-d H:i:s'),
            $clientIp,
            $traceFile,
            $traceLine,
            $errno,
            self::genLogID(),
            $message,
            $argsStr
        );


        return file_put_contents($logFile, $message, FILE_APPEND);
    }

    protected function writeLog($log_level, $str, $errno, $args, $depth) {

        // 采用自定义函数写日志
        if (isset($this->log_func) && $this->log_func != 'writeLog' && method_exists($this, $this->log_func)) {
            $func = $this->log_func;
            return $this->$func($log_level, $str, $errno, $args, $depth);
        }

        if (($log_level > $this->log_level) || !isset(self::$log_level_map[$log_level])) {
            return;
        }

        $module_name = SpModule::getModuleName();
        $log_dir_path = $this->log_path . "/" .  $module_name;
        if(!is_dir($log_dir_path)){
            FileHelper::createDirectory($log_dir_path, $this->dirMode, true);
        }

        $log_file = $log_dir_path . "/" . $module_name . ".log";

        $log_level_str = self::$log_level_map[$log_level];
        $time_str = date('y-m-d H:i:s');

        if(Yii::$app instanceof yii\web\Application){
            $client_ip = Yii::$app->request->getUserIP();
        }else{
            $client_ip = '127.0.0.1';
        }

        if($log_level === self::LOG_LEVEL_NOTICE){
            $trace_file = "URI";
            $request = Yii::$app->getRequest();
            if($request->getIsConsoleRequest()){
                $params = $request->getParams();
                $path_info = trim($params[0], '/');
            }else{
                $path_info = $request->getUrl();
            }

            $trace_line = $path_info;
        }else{
            $trace = debug_backtrace();
            $trace_file = isset($trace[$depth]['file']) ? $trace[$depth]['file'] : '';
            $trace_line = isset($trace[$depth]['line']) ? $trace[$depth]['line'] : '';
        }

        $log_id = self::genLogID();

        $args_str = '';
        if (is_array($args) && !empty($args)) {
            foreach ($args as $arg_key => $arg_value) {
                $arg_value_str = is_array($arg_value) ? json_encode($arg_value) : $arg_value;
                $args_str .= "${arg_key}[${arg_value_str}] ";
            }
        }

        $str = sprintf(self::DEFAULT_FORMAT_DEBUG
            , $log_level_str, $time_str, $client_ip, $trace_file, $trace_line, $errno, $log_id, $str, $args_str);

        $file_name_suffix = self::getLogSuffixByLevel($log_level);
        $log_file = $log_file . $file_name_suffix;

        file_put_contents($log_file, $str, FILE_APPEND);
    }

    static public function getLogSuffixByLevel($log_level){
        return self::$log_suffix_map[$log_level];
    }
}