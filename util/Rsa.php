<?php

namespace sp_framework\util;
use sp_framework\components\SpLog;

/**
 * Rsa加密解密相关方法类
 * Class Rsa
 * @package app\modules\datashop\utils
 * @author wangdj
 * @since 2017-03-06
 */
class Rsa{
    /**
     * 私钥解密
     *
     * @param string $cryptText 密文（二进制格式且base64编码）
     * @param string $priKey 私钥
     * @param bool $fromJs 密文是否来源于JS的RSA加密
     * @return string 明文
     */
    public static function decrypt($cryptText, $priKey, $fromJs = false){
        $key = openssl_get_privatekey($priKey);
        $cryptText = base64_decode($cryptText);
        $padding = $fromJs ? OPENSSL_NO_PADDING : OPENSSL_PKCS1_PADDING;

        $sourceStr = null;
        if(openssl_private_decrypt($cryptText, $sourceStr, $key, $padding)){
            return $fromJs ? rtrim(strrev($sourceStr), "/0") : "" . $sourceStr;
        }else{
            SpLog::warning("msg[decode_rsa_ret_null], text:" . $cryptText);
        }

        return null;
    }

    /**
     * 公钥加密
     *
     * @param string $text 明文
     * @param string $pubKey 公钥
     * @return string 密文（base64编码）
     */
    public static function encrypt($text, $pubKey){
        $key = openssl_get_publickey($pubKey);
        $result = $cryptText = false;
        try{
            $result = openssl_public_encrypt($text, $cryptText, $key);
        }catch(\Exception $ex){
            SpLog::warning($ex->getTraceAsString());
        }

        if(empty($result)){
            return false;
        }

        return base64_encode("" . $cryptText);
    }
}