<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/3/12
 * Time: 下午5:59
 */
/**
 * author : wangzhengjun
 * QQ     : 694487069
 * phone  : 15801450732
 * Email  : wangzjc@jiedaibao.com
 * Date   : 17/6/7
 */
namespace sp_framework\components;

class Assert
{
    public static function isTrue($bool, $msg, $logMsg = "", $errCode = 1)
    {
        if ( $bool === false ){
            if ( "" != $logMsg ){
                SpLog::warning("当前断言判断为假, userMsg: $msg , log:".$logMsg."errorCode:$errCode", 0, 0, 1);
            }
            throw new SpException($errCode, null, $msg, $logMsg);
        }
    }
}