<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/28
 * Time: 下午4:48
 */

namespace sp_framework\components;


class SpException extends \Exception
{
    protected $errorCode;
    protected $errorData;
    protected $errorMessage;
    protected $errorUserMessage;
    protected $deep;
    public function __construct($errorCode, $errorMessage = '', $errorUserMessage = '', $errorData = [], $deep = 0) {
        $this->errorCode        = $errorCode;
        $this->errorData        = $errorData;
        $this->errorMessage     = $errorMessage;
        $this->errorUserMessage = $errorUserMessage;
        $this->deep             = $deep;
        parent::__construct($errorMessage, $errorCode);
    }

    public function getErrorData() {
        return $this->errorData;
    }

    public function getErrorCode(){
        return $this->errorCode;
    }

    public function getErrorMessage(){
        return $this->errorMessage;
    }

    public function getErrorUserMessage(){
        return $this->errorUserMessage;
    }

    public function getDeep(){
        return $this->deep;
    }

    public function getExpLine(){
        $index = $this->getDeep();
        if($index > 0){
            $trace = $this->getTrace();
            return $trace[$index - 1]['line'];
        }

        return $this->getLine();
    }

    public function getExpFile(){
        $index = $this->getDeep();
        if($index > 0){
            $trace = $this->getTrace();
            return $trace[$index - 1]['file'];
        }

        return $this->getFile();
    }
}