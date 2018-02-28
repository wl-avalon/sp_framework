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
use sp_framework\components\SpException;

abstract class BaseAction extends Action
{
    const REQUEST_METHOD_POST = 1;
    const REQUEST_METHOD_GET = 2;
    const REQUEST_METHOD_GET_AND_POST = 3;

    protected $request_method = self::REQUEST_METHOD_GET_AND_POST;
    private $returnCode = 0;
    private $returnMessage = '成功';
    private $returnUserMessage = '成功';

    public function run(){
        try{
            $result = $this->execute();
        }catch(SpException $e){
            $this->returnCode           = $e->getErrorCode();
            $this->returnMessage        = $e->getErrorMessage();
            $this->returnUserMessage    = empty($e->getErrorUserMessage()) ? "网络繁忙,请稍后再试" : $e->getErrorUserMessage();
            $result                     = $e->getErrorData();
        } catch(\Exception $e){
            $this->returnCode           = $e->getCode();
            $this->returnMessage        = $e->getMessage();
            $this->returnUserMessage    = "网络繁忙,请稍后再试";
            $result                     = [];
        }
        $result = $this->formatResult($result);
        $this->renderResult();
        return $result;
    }

    abstract protected function execute();

    protected function formatParams(){

    }

    protected function renderResult(){
        $this->formatJson();
    }

    protected function formatResult($result){
        $response = [
            'error' => [
                'returnCode'        => $this->returnCode,
                'returnMessage'     => $this->returnMessage,
                'returnUserMessage' => $this->returnUserMessage,
            ],
            'data' => $result,
        ];
        return $response;
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