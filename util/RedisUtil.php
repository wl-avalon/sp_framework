<?php
namespace sp_framework\util;

use sp_framework\components\SpLog;
use Yii;

class RedisUtil {

    static protected $instances = array();
    const DEFAULT_RETRY_NUM = 2;
    const DEFAULT_CONN_TIMEOUT = 0.05;
    const DEFAULT_READ_TIMEOUT = 0.05;

    protected $redis = null;

    protected $cluster = '';
    protected $arr_redis_host;
    protected $redis_host;
    protected $redis_port;
    protected $redis_con;
    protected $redis_timeout;
    protected $redis_read_timeout;
    protected $redis_retry;

    protected $is_connected = true;

    static public function getInstance($cluster) {
        if (!isset(self::$instances[$cluster])) {
            self::$instances[$cluster] = new RedisUtil($cluster);
        }

        return self::$instances[$cluster];
    }

    protected function __construct($cluster) {
        $this->cluster = $cluster;

        if($this->initConf()){
            $this->redis = new \redis();
            $this->connect();
        }
    }

    protected function initConf() {
        $redis_conf = false;
        if(isset(Yii::$app->params[$this->cluster])){
            $redis_conf = Yii::$app->params[$this->cluster];
        }

        if (!$redis_conf) {
            if(isset(Yii::$app->params['redis'][$this->cluster])){
                $redis_conf = Yii::$app->params['redis'][$this->cluster];
            }

            if(!$redis_conf){
                SpLog::warning("redis_cluster conf not found [redis_cluster=".$this->cluster."]");
                return false;
            }
        }

        $arrHost = [];
        if(isset($redis_conf['domain'])){
            $arrHost = explode(' ',trim($redis_conf['domain']));
        }else if(isset($redis_conf['host'])){
            $arrHost = explode(' ',trim($redis_conf['host']));
        }
        foreach($arrHost as $key => $value){
            $arrHostInfo = explode(':', $value);
            $tempHostInfo['host'] = $arrHostInfo[0];
            $tempHostInfo['port'] = $arrHostInfo[1];
            $this->arr_redis_host[] = $tempHostInfo;
        }

        $this->redis_timeout = max($redis_conf['timeout'], self::DEFAULT_CONN_TIMEOUT);
        $read_timeout = self::DEFAULT_READ_TIMEOUT;
        if(isset($redis_conf['readtimeout'])){
            $read_timeout = $redis_conf['readtimeout'];
        }else if(isset($redis_conf['read_timeout'])){
            $read_timeout = $redis_conf['read_timeout'];
        }
        $this->redis_read_timeout = max($read_timeout, self::DEFAULT_READ_TIMEOUT);
        $this->redis_retry = $redis_conf['retry'];

        return true;
    }

    protected function connect() {
        $max_try_connect_num = min($this->redis_retry, self::DEFAULT_RETRY_NUM);
        $cur_try_connect_num = 0;

        $ret = false;
        while ($cur_try_connect_num < $max_try_connect_num) {
            $totalNum = count($this->arr_redis_host);
            $index = rand(0, $totalNum-1);
            $ret = $this->redis->connect($this->arr_redis_host[$index]['host'], $this->arr_redis_host[$index]['port'], $this->redis_timeout);

            if ($ret !== false) {
                $this->redis_host = $this->arr_redis_host[$index]['host'];
                $this->redis_port = $this->arr_redis_host[$index]['port'];
                break;
            }
            $cur_try_connect_num ++;
            SpLog::warning('redis can not connect [host='.$this->arr_redis_host[$index]['host'].'][port='.$this->arr_redis_host[$index]['port'].']');
            unset($this->arr_redis_host[$index]);
        }

        if ($ret === false) {
            $this->is_connected = false;
            SpLog::warning('redis connect reache max try number');
            return false;
        }

        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->redis_read_timeout);
    }

    public function __call($func_name, $args) {
        if ($this->is_connected === false) {
            SpLog::warning("redis is not connected [redis_cluster=".$this->cluster."][func_name=$func_name][args=".serialize($args)."]");
            return false;
        }

        SpLog::trace(sprintf('redis[%s:%s] call %s args %s'
            , $this->redis_host, $this->redis_port, $func_name, json_encode($args)));

        try {
            $ret = call_user_func_array(array($this->redis, $func_name), $args);
        } catch (\RedisException $e) {
            $error = $e->getMessage();
            SpLog::warning('redis throw exception [exception='.$error.'][host='.$this->redis_host.'][port='.$this->redis_port.']');
            return false;
        }
        SpLog::trace('redis ret : ' . var_export($ret, true));

        return $ret;
    }
}