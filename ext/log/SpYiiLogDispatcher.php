<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午3:04
 */

namespace sp_framework\ext\log;
use Yii;
use yii\log\Dispatcher;
//use rrxframework\base\JdbModule;

class SpYiiLogDispatcher extends Dispatcher
{
    const PATH_SEPATATOR = '/';
    const DEFAULT_MODULE = 'default';

    public $default_module;
    public $module_map;

    public function __construct($config = [])
    {
        // ensure logger gets set before any other config option
        if (isset($config['logger'])) {
            $logger = Yii::createObject($config['logger']);
            $this->setLogger($logger);
            unset($config['logger']);
        }

        parent::__construct($config);
    }

    public function init(){
        //set module
        $request = Yii::$app->getRequest();
        if($request->getIsConsoleRequest()){
            $params = $request->getParams();
            $path_info = trim($params[0], self::PATH_SEPATATOR);
        }else{
            $path_info = $request->getPathInfo();

        }

        $pos = strpos($path_info, self::PATH_SEPATATOR);
        if($pos === false){
            $module_name = empty($this->default_module) ? self::DEFAULT_MODULE : $this->default_module;
        }else{
            $module_name = substr($path_info, 0, $pos);
        }

        if(!empty($this->module_map[$module_name])){
            $module_name = $this->module_map[$module_name];
        }

//        JdbModule::setModuleName($module_name);

        parent::init();
    }
}