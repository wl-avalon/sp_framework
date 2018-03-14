<?php
namespace sp_framework\apis;

use sp_framework\apis\helper\ApiHelper;
use sp_framework\apis\http\Http;
use sp_framework\apis\models\Request;
use sp_framework\apis\models\Response;
use sp_framework\components\SpLog;
use sp_framework\util\Arr;
use sp_framework\util\Time;

/**
 * 系统调用处理类
 * Class ApiContext
 * @package app\modules\datashop\apis
 * @author wangdj
 */
class ApiContext{

    /**
     * @var ApiContext $instance
     */
    private static $instance;

    /**
     * 尚未调用的请求
     * @var Request[] $await
     */
    private $await = [];

    /**
     * 已调用的请求
     * @var array request;
     */
    private $resolved = [];

    /**
     * 返回数据池, 访问其他系统的返回存入此数组, 以service.method作为数组key
     * @var array[] response
     */
    private $responses = [];

    /**
     * ApiContext constructor.
     * 阻止用户自行实例化
     */
    private function __construct(){
        ApiHelper::initApiConfig();
    }

    /**
     * @return ApiContext
     */
    public static function getInstance(){
        if(!isset(static::$instance)){
            static::$instance = new ApiContext();
        }

        return static::$instance;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getResponse($request){
        if($request->curlMode == 'async'){
            $key = $request->getKey();
            if(!Arr::has($this->responses, $key)){
                $this->resolveRequest();
            }else{
                $params = json_encode($request->getParams());
                SpLog::trace("[ApiContext] [cache] key[$key] params[$params]");
            }

            return Arr::get($this->responses, $key);
        }else{
            return Http::syncCall($request);
        }
    }

    /**
     * @param string $key
     * @param string $response
     */
    public static function addResponse($key, $response){
        self::$instance->responses[$key] = $response;
    }

    public static function get($service, $method, $params){
        $handler = ApiHelper::getHandler($service);
        $request = new Request($service, $method, $params, $handler);
        static::getInstance()->appendRequest($request);
        $response = new Response($request);
        return $response;
    }

    public static function post($service, $method, $params){
        $handler = ApiHelper::getHandler($service);
        $request = new Request($service, $method, $params, $handler);
        $request->curlMode = 'sync';
        $response = new Response($request);
        $response->init();
        return $response;
    }

    public static function clean(){
        //调用未使用的get请求
        static::getInstance()->resolveRequest();
        //释放内存
        static::$instance = null;
    }

    /**
     * @param Request $request
     */
    private function appendRequest($request){
        if(!array_key_exists($request->getKey(), $this->await) && !array_key_exists($request->getKey(), $this->resolved)){
            $this->await[$request->getKey()] = $request;
        }
    }

    /**
     * 发起并行调用, 获取返回数据
     */
    private function resolveRequest(){
        if(!empty($this->await) || count($this->await) > 0){
            $reqId = [];
            foreach($this->await as $req){
                $reqId[] = $req->getKey();
            }

            $startTime = microtime(true);
            SpLog::trace("[ApiContext] multiple curl start at[" . Time::udate($startTime) . "]. requests[" . implode(',', $reqId) . ']');
            Http::asyncCall($this->await);
            $endTime = microtime(true);
            $cost = bcsub($endTime, $startTime, 4);
            SpLog::trace("[ApiContext] multiple curl end at[" . Time::udate($endTime) . "] cost[$cost]. requests[" . implode(',', $reqId) . ']');
            $this->resolved = $this->resolved + $this->await;
            $this->await = [];
        }
    }
}