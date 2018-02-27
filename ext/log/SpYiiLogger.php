<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午3:05
 */

namespace sp_framework\ext\log;
use Yii;
use yii\base\Component;
use yii\log\Logger;
use sp_framework\components\SpLog;

class SpYiiLogger extends Component
{
    const LOG_LEVEL_FATAL   = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE  = 0x04;
    const LOG_LEVEL_TRACE   = 0x08;
    const LOG_LEVEL_DEBUG  = 0x10;


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


    /**
     * @var array logged messages. This property is managed by [[log()]] and [[flush()]].
     * Each log message is of the following structure:
     *
     * ~~~
     * [
     *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
     *   [1] => level (integer)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true))
     *   [4] => traces (array, debug backtrace, contains the application code call stacks)
     *   [5] => errno
     *   [6] => args
     *   [7] => depth
     * ]
     * ~~~
     */
    public $messages = [];

    /**
     * @var integer how much call stack information (file name and line number) should be logged for each message.
     * If it is greater than 0, at most that number of call stacks will be logged. Note that only application
     * call stacks are counted.
     */
    public $traceLevel = 0;

    /**
     * @var Dispatcher the message dispatcher
     */
    public $dispatcher;

    public $log_level;
    protected $add_notice_data;
    protected $timer_data;
    protected $errno = 0;

    public function __construct($config = []) {

        $this->add_notice_data = [];
        $this->timer_data = [];
        Yii::setLogger($this);
        SpLog::setLogger($this);

        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
        $this->startTimer('TOTAL');
        register_shutdown_function(function () {
            $this->notice($str = '', $this->errno, $args = null, $depth = 1);
        });
    }

    public function addNotice($key, $value) {
        $this->add_notice_data[$key] = $value;
    }

    public function setErrno($err_no){
        $this->errno = $err_no;
    }

    public function startTimer($timer_name,$renew = false){
        if($renew || !isset($this->timer_data[$timer_name]['start_time'])){
            $this->timer_data[$timer_name] = [];
            $this->timer_data[$timer_name]['start_time'] = $this->getMilliTime();
        }
    }

    public function endTimer($timer_name){
        if(isset($this->timer_data[$timer_name]['start_time']) && !isset($this->timer_data[$timer_name]['interval'])){
            $this->timer_data[$timer_name]['end_time'] = $this->getMilliTime();
            $this->timer_data[$timer_name]['interval'] = $this->timer_data[$timer_name]['end_time']
                - $this->timer_data[$timer_name]['start_time'];
        }
    }

    public function getTimer($timer_name = null){
        if(isset($this->timer_data[$timer_name])){
            return $this->timer_data[$timer_name];
        }else if($timer_name === null){
            return $this->timer_data;
        }
        return array();
    }

    public function calcTimers($timer_prefix = ''){
        $end_time = $this->getMilliTime();
        $ret_timers = [];
        foreach($this->timer_data as $timer => &$timer_value){
            if(!isset($timer_value['interval'])){
                $timer_value['end_time'] = $end_time;
                $timer_value['interval'] = $end_time - $timer_value['start_time'];
            }

            $ret_timers[$timer_prefix.$timer] = $timer_value['interval'] . "ms";
        }
        return $ret_timers;
    }

    public function getMilliTime(){
        return intval(gettimeofday(true) * 1000);
    }

    public function debug($str = '', $errno = 0, $args = null, $depth = 0) {
        $level = self::LOG_LEVEL_DEBUG;
        if($this->log_level > 0 && $level > $this->log_level){
            return;
        }
        $this->jdbLog($level, $str, $errno, $args, $depth + 1);
    }

    public function trace($str = '', $errno = 0, $args = null, $depth = 0) {
        $level = self::LOG_LEVEL_TRACE;
        if($this->log_level > 0 && $level > $this->log_level){
            return;
        }
        $this->jdbLog($level, $str, $errno, $args, $depth + 1);
    }

    public function notice($str = '', $errno = 0, $args = null, $depth = 0) {
        $level = self::LOG_LEVEL_NOTICE;
        if($this->log_level > 0 && $level > $this->log_level){
            return;
        }

        $timers = $this->calcTimers('TIMER_');
        $args = is_array($args) ? array_merge($args, $this->add_notice_data, $timers) : array_merge($this->add_notice_data, $timers);
        $this->jdbLog($level, $str, $errno, $args, $depth + 1);
    }

    public function warning($str = '', $errno = 0, $args = null, $depth = 0) {
        $level = self::LOG_LEVEL_WARNING;
        if($this->log_level > 0 && $level > $this->log_level){
            return;
        }
        $this->jdbLog($level, $str, $errno, $args, $depth + 1);
    }

    public function fatal($str = '', $errno = 0, $args = null, $depth = 0) {
        $level = self::LOG_LEVEL_FATAL;
        if($this->log_level > 0 && $level > $this->log_level){
            return;
        }
        $this->jdbLog($level, $str, $errno, $args, $depth + 1);
    }

    private function jdbLog($log_level, $str, $errno, $args, $depth, $category = "application"){
        $time = microtime(true);
        $traces = [];
        $this->messages[] = [$str, $log_level, $category, $time, $traces, $errno, $args, $depth + 1];
        $this->flush(true);
    }
    //提供yii框架与扩展调用
    public function log($message, $level, $category = null, $errno = 0, $args = null)
    {
        //不支持profile
        if($level === Logger::LEVEL_PROFILE_BEGIN || $level === Logger::LEVEL_PROFILE_END){
            return;
        }

        $message_level = self::$yii_log_level_map[$level];
        if($this->log_level > 0 && $message_level > $this->log_level){
            return;
        }

        if(is_array($message)){
            $message = serialize($message);
        }

        $time = microtime(true);
        $traces = [];
        if ($this->traceLevel > 0) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->traceLevel) {
                        break;
                    }
                }
            }
        }

        $this->messages[] = [$message, $message_level, $category, $time, $traces, $errno, $args, 2];
        $this->flush(true);
    }

    public function flush($final = false)
    {

        $messages = $this->messages;
        // https://github.com/yiisoft/yii2/issues/5619
        // new messages could be logged while the existing ones are being handled by targets
        $this->messages = [];
        if ($this->dispatcher instanceof SpYiiLogDispatcher) {
            $this->dispatcher->dispatch($messages, $final);
        }
    }


    /**
     * 提供给yii扩展使用，所以参数$level的范围为yii框架中log的level，而非自定义
     * @param $level
     * @return string
     */
    public static function getLevelName($level)
    {
        if(isset(self::$yii_log_level_map[$level])){
            return self::$log_level_map[self::$yii_log_level_map[$level]];
        }
        return 'unknown';
    }

    /**
     * Returns the total elapsed time since the start of the current request.
     * This method calculates the difference between now and the timestamp
     * defined by constant `YII_BEGIN_TIME` which is evaluated at the beginning
     * of [[\yii\BaseYii]] class file.
     * @return float the total elapsed time in seconds for current request.
     */
    public function getElapsedTime()
    {
        return microtime(true) - YII_BEGIN_TIME;
    }

    /**
     * Returns the statistical results of DB queries.
     * The results returned include the number of SQL statements executed and
     * the total time spent.
     * @return array the first element indicates the number of SQL statements executed,
     * and the second element the total time spent in SQL execution.
     */
    public function getDbProfiling()
    {
        //TODO:
    }

    /**
     * Returns the profiling results.
     *
     * By default, all profiling results will be returned. You may provide
     * `$categories` and `$excludeCategories` as parameters to retrieve the
     * results that you are interested in.
     *
     * @param array $categories list of categories that you are interested in.
     * You can use an asterisk at the end of a category to do a prefix match.
     * For example, 'yii\db\*' will match categories starting with 'yii\db\',
     * such as 'yii\db\Connection'.
     * @param array $excludeCategories list of categories that you want to exclude
     * @return array the profiling results. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`.
     */
    public function getProfiling($categories = [], $excludeCategories = [])
    {
        //TODO:
    }

    /**
     * Calculates the elapsed time for the given log messages.
     * @param array $messages the log messages obtained from profiling
     * @return array timings. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`.
     */
    public function calculateTimings($messages)
    {
        //TODO:
        return array();
    }
}