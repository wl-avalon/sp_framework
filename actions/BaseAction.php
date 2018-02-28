<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午2:57
 */

namespace sp_framework\actions;
use Yii;
use yii\base\Action;
use yii\web\Response;

class BaseAction extends Action
{
    const REQUEST_METHOD_POST = 1;
    const REQUEST_METHOD_GET = 2;
    const REQUEST_METHOD_GET_AND_POST = 3;

    protected $request_method = self::REQUEST_METHOD_GET_AND_POST;

    public function run(){

    }

    protected function formatParams(){

    }

    protected function formatJson(){
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    protected function get($name=null, $default=null) {
        if($this->request_method === self::REQUEST_METHOD_GET_AND_POST){
            if($name === null){
                return array_merge(Yii::$app->request->get(), Yii::$app->request->post());
            }else if(($ret = Yii::$app->request->post($name, null)) !== null){
                return $ret;
            }else{
                return Yii::$app->request->get($name, $default);
            }
        }else if($this->request_method === self::REQUEST_METHOD_GET){
            return $name === null ? Yii::$app->request->get() : Yii::$app->request->get($name, $default);
        }else if($this->request_method === self::REQUEST_METHOD_POST){
            return $name === null ? Yii::$app->request->post() : Yii::$app->request->post($name, $default);
        }
        return false;
    }
}