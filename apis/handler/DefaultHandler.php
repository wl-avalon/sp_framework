<?php

namespace sp_framework\apis\handler;
use sp_framework\apis\models\Response;
use sp_framework\util\StrUtil;
use sp_framework\components\SpException;
use sp_framework\components\SpLog;
use sp_framework\constants\SpErrorCodeConst;
use Yii;

/**
 * 默认请求参数和返回值处理类
 * Class DefaultHandler
 * @package app\modules\datashop\apis\handler
 */
class DefaultHandler implements IRequestHandler{
    protected $service;
    protected $method;
    protected $params;
    protected $config;

    public function genSign(array $fields, $secret){
        return StrUtil::genSign($fields, $secret);
    }

    private function initConfig(){
        if(!array_key_exists($this->service, Yii::$app->params)){
            $msg = "The config for service: $this->service is not found.";
            SpLog::warning($msg);
            throw new SpException(SpErrorCodeConst::SUCCESSFUL, $msg, $msg, null);
        }
        $apiConfig = Yii::$app->params[$this->service];
        if(!isset($apiConfig['apis']) || !is_array($apiConfig['apis']) || !array_key_exists($this->method, $apiConfig['apis'])){
            $msg = "The config for method: $this->service.$this->method is not found.";
            SpLog::warning($msg);
            throw new SpException(SpErrorCodeConst::REQUEST_FAILED, $msg, $msg, null);
        }

        $this->config = $apiConfig;
    }

    /**
     * DefaultHandler constructor.
     * @inheritdoc
     */
    public function init($service, $method, array $params){
        $this->service = $service;
        $this->method = $method;
        $this->params = $params;

        $this->initConfig();
    }

    /**
     * @inheritdoc
     */
    public function getUrl(){
        $domain = $this->config['domain'];
        $url = $this->config['apis'][$this->method];

        if(is_string($url)){
            return $domain . $url;
        }elseif(is_array($url) && array_key_exists('url', $url)){
            if(array_key_exists('domain', $url)){
                $domain = $url['domain'];
            }
            return $domain . $url['url'];
        }

        $msg = "Value of Key $this->service.$this->method must be a string or an array. please check you params config file or input parameters.";
        SpLog::warning($msg);
        throw new SpException(SpErrorCodeConst::REQUEST_FAILED, null, $msg);
    }

    /**
     * @inheritdoc
     */
    public function getParams(){
        $params = $this->params;
        unset($params['secret'], $params['sign']);
        $secret = $this->config['secret'] ?? '';
        $config = $this->config;
        unset($config['serverType'], $config['apis'], $config['domain'], $config['conntimeout'], $config['timeout'], $config['retry'], $config['secret']);

        $params += $config;
        $params['ts'] = time();
        $params['sign'] = $this->genSign($params, $secret);
        return $params;
    }

    /**
     * @inheritdoc
     */
    public function handleResponse(Response &$response, array $arrJson){
        $message = '未知异常';

        if((!isset($arrJson['error']) || !isset($arrJson['error']['returnCode'])) && !isset($arrJson['result'])){
            $code = SpErrorCodeConst::REQUEST_FAILED;
            $message = '未设置错误代码';
        }elseif(!key_exists('data', $arrJson)){
            $code = SpErrorCodeConst::REQUEST_FAILED;
            $message = '缺少data字段';
        }elseif(isset($arrJson['error']['returnCode'])){
            $code = $arrJson['error']['returnCode'];

            if(isset($arrJson['error']['returnMessage'])){
                $message = $arrJson['error']['returnMessage'];
            }
        }else{
            $code = $arrJson['result'] == 200 ? SpErrorCodeConst::SUCCESSFUL : $arrJson['result'];
            $message = '调用服务失败: ' . $code;
        }

        $response->setReturnCode($code);
        $response->setReturnUserMessage($arrJson['error']['returnUserMessage']??"未知异常");
        $response->setReturnMessage($arrJson['error']['returnMessage']??$message);
        $response->setData($arrJson['data']);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(){
        return ['retry' => $this->config['retry']??0, CURLOPT_TIMEOUT_MS => $this->config['timeout']??6000, CURLOPT_CONNECTTIMEOUT_MS => $this->config['conntimeout']??1000,];
    }

    /**
     * response反序列化
     * @return \Closure
     */
    public function getDeserializer(){
        return static function($data){
            if(empty($data)){
                return [
                    'error' => [
                        'returnCode'    => SpErrorCodeConst::REQUEST_FAILED,
                        'returnMessage' => '返回值为空或格式异常',
                    ],
                    'data'  => null,
                ];
            }else{
                return json_decode($data, true);
            }
        };
    }
}
