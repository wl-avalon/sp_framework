<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午3:00
 */

namespace sp_framework\components;

use Yii;
use yii\base\Component;

class SpLog {

    private static $_logger;

    static public function getLogger(){
        return self::$_logger;
    }

    static public function setLogger($logger){
        if($logger instanceof Component){//TODO:可以考虑抽象一个日志抽象类，或接口
            self::$_logger = $logger;
        }
    }

    static public function addNotice($key, $value) {
        if(self::$_logger !== null){
            self::$_logger->addNotice($key, $value);
        }
    }

    static public function setErrno($err_no){
        if(self::$_logger !== null){
            self::$_logger->setErrno($err_no);
        }
    }


    static public function debug($str, $errno = 0, $args = null, $depth = 0) {
        if(self::$_logger !== null){
            self::$_logger->debug($str, $errno, $args, $depth + 1);
        }else{
            Yii::info("msg[$str] errno[$errno] data[".serialize($args)."]");
        }
    }

    static public function trace($str, $errno = 0, $args = null, $depth = 0) {
        if(self::$_logger !== null){
            self::$_logger->trace($str, $errno, $args, $depth + 1);
        }else{
            Yii::trace("msg[$str] errno[$errno] data[".serialize($args)."]");
        }
    }

    static public function notice($str, $errno = 0, $args = null, $depth = 0) {
        if(self::$_logger !== null){
            self::$_logger->notice($str, $errno, $args, $depth + 1);
        }
    }

    static public function warning($str, $errno = 0, $args = null, $depth = 0) {
        if(self::$_logger !== null){
            self::$_logger->warning($str, $errno, $args, $depth + 1);
        }else{
            Yii::warning("msg[$str] errno[$errno] data[".serialize($args)."]");
        }
    }

    static public function fatal($str, $errno = 0, $args = null, $depth = 0) {
        if(self::$_logger !== null){
            self::$_logger->fatal($str, $errno, $args, $depth + 1);
        }else{
            Yii::error("msg[$str] errno[$errno] data[".serialize($args)."]");
        }
    }

    static public function startTimer($timer_name) {
        if(self::$_logger !== null){
            self::$_logger->startTimer($timer_name);
        }else{
            Yii::beginProfile($timer_name);
        }
    }

    static public function endTimer($timer_name) {
        if(self::$_logger !== null){
            self::$_logger->endTimer($timer_name);
        }else{
            Yii::endProfile($timer_name);
        }
    }

    static public function getLogID() {
        if (defined('LOG_ID')) {
            return LOG_ID;
        }

        if (isset($_SERVER['HTTP_JDB_HEADER_RID'])){
            $log_id = trim($_SERVER['HTTP_JDB_HEADER_RID']);
        }else{
            $arr = gettimeofday();
            $log_id = ((($arr['sec'] * 100000 + $arr['usec'] / 10) & 0x7FFFFFFF) | 0x80000000);
        }

        define('LOG_ID', $log_id);

        return LOG_ID;
    }
}
