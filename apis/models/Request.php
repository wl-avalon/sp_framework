<?php
namespace sp_framework\apis\models;

use sp_framework\apis\handler\DefaultHandler;
use sp_framework\apis\handler\IRequestHandler;
use sp_framework\SpModule;

/**
 * 包装处理请求数据
 * Class Request
 * @package app\modules\datashop\apis\models
 */
class Request{
    /**
     * @var string $moduleName 当前模块名
     */
    private $moduleName;

    /**
     * @var string $url 请求接口地址
     */
    private $url;

    /**
     * 请求使用的参数
     * @var array $params
     */
    private $params;

    /**
     * 请求头
     * @var array $headers
     */
    private $headers;

    /**
     * 请求选项
     * @var array $options
     */
    private $options;

    /**
     * url 和 params 包装处理对象
     * @var DefaultHandler
     */
    private $handler;

    /**
     * @var int 重试次数限制
     */
    public $retry;

    /**
     * @var int 当前重试次数
     */
    public $retryTimes;

    /**
     * @var string $httpMethod
     */
    public $httpMethod;

    /**
     * 发起请求的方式，串行(sync) 或 并行(async)
     * @var string
     */
    public $curlMode;

    /**
     * 被调用的系统名
     * @var string $service
     */
    public $service;

    /**
     * 被调用的接口名
     * @var string $method
     */
    public $method;

    /**
     * 输入参数的hash值
     * @var string $hash
     */
    public $hash;

    public $curlInfo;

    /**
     * Request constructor.
     * @param string $service
     * @param string $method
     * @param array $params
     * @param IRequestHandler|null $handler
     */
    public function __construct($service, $method, array $params, IRequestHandler $handler = null){
        $this->moduleName = SpModule::getModuleName();
        $this->httpMethod = 'post';
        $this->curlMode = 'async';
        $this->service = $service;
        $this->method = $method;
        $this->hash = hash('crc32b', json_encode($params));
        $this->headers = [];
        $this->retry = 0;
        $this->retryTimes = 0;
        $this->curlInfo = [];

        if(!is_null($handler)){
            $this->handler = $handler;
        }else{
            $this->handler = new DefaultHandler();
        }
        $this->handler->init($service, $method, $params);
    }

    /**
     * 设置请求起始时间
     */
    public function start(){
        $this->retryTimes += 1;
    }

    /**
     * @return int 获取请求耗时
     */
    public function getElapsed(){
        return $this->curlInfo['total_time']??-1;
    }

    /**
     * 设置请求耗时
     * @param array $info
     */
    public function stop($info){
        $this->curlInfo = $info;
    }

    /**
     * @return bool
     */
    public function canRetry(){
        return $this->retryTimes < $this->retry;
    }

    /**
     * 获取请求地址
     * @return string
     */
    public function getUrl(){
        if(!isset($this->url)){
            $this->url = $this->handler->getUrl();
        }

        return $this->url;
    }

    /**
     * 获取请求参数
     * @return array
     */
    public function getParams(){
        if(!isset($this->params)){
            $this->params = $this->handler->getParams();
        }

        return $this->params;
    }

    public function getHeaders(){
        return $this->headers;
    }

    public function setHeaders($value){
        return $this->headers = $value;
    }

    /**
     * 获取curl设置
     * [
     * CURLOPT_USERAGENT            => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.104',
     * CURLOPT_REFERER              => '',
     * CURLOPT_ENCODING             => 'UTF-8',
     * CURLOPT_TIMEOUT_MS           => 6000,
     * CURLOPT_CONNECTTIMEOUT_MS    => 1200,
     * CURLOPT_MAXREDIRS            => 3,
     * CURLOPT_FOLLOWLOCATION       => true,
     * CURLOPT_RETURNTRANSFER       => 1,
     * CURLOPT_HEADER               => false,
     * CURLOPT_NOSIGNAL             => true,
     * CURLOPT_SSL_VERIFYPEER       => false,
     * CURLOPT_SSL_VERIFYHOST       => false,
     * ]
     *
     * @return array
     */
    public function getOptions(){
        if(!isset($this->options)){
            $options = $this->handler->getOptions();
            $this->retry = $options['retry']??$this->retry;
            unset($options['retry']);
            $this->curlMode = $options['httpMethod']??$this->curlMode;
            unset($options['httpMethod']);
            $this->options = $options;
        }

        return $this->options;
    }

    public function getHandler(){
        return $this->handler;
    }

    public function getKey(){
        return $this->service . '.' . $this->method . '.' . $this->hash;
    }
}