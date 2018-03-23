<?php

namespace sp_framework\apis\helper;

use sp_framework\apis\handler\DefaultHandler;
use sp_framework\apis\handler\IRequestHandler;
use sp_framework\util\Str;
use Yii;

/**
 * 系统调用帮助类
 * Class ApiHelper
 * @package app\modules\ploanframework\apis\helper
 * @author wangdj
 */
class ApiHelper{
    /**
     * @param string $service
     *
     * @return IRequestHandler
     */
    public static function getHandler($service){
        $customerHandler = 'app\modules\apis\handler\\' . Str::ucfirst($service) . 'Handler';
        $handler = 'sp_framework\apis\handler\\' . Str::ucfirst($service) . 'Handler';

        if(class_exists($customerHandler)){
            return new $customerHandler();
        }elseif(class_exists($handler)){
            return new $handler();
        }else{
            return new DefaultHandler();
        }
    }

    public static function initApiConfig(){
        global $server_ini;
        $app = Yii::$aliases['@app'];
        $config = require($app . "/../sp_framework/config/params.php");

        foreach((array)$server_ini as $key => $userConfig){
            if(isset($config[$key])){
                $config[$key] = array_merge($config[$key], $userConfig);
            }
        }

        Yii::$app->params = array_replace_recursive($config, Yii::$app->params);
    }
}
