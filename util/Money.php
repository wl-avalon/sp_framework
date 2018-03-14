<?php

namespace sp_framework\util;

class Money{
    public static function decimal($money){
        $money = intval(strval(round($money, 10) * 100));
        $op = ($money < 0) ? '-' : '';
        $div = $op . abs(intval($money / 100));
        $mod = abs($money % 100);

        if(0 == $mod){
            // 如果余数为0, 不需要加上.00
            return $div . ""; // 转换成字符串
        }else if($mod < 10){
            return $div . ".0" . $mod;
        }else{
            return $div . "." . trim($mod, "0");
        }
    }

    public static function convertFenToYuan($fen){
        return self::decimal(floatval($fen / 100));
    }


    public static function convertYuanToFen($yuan){
        return self::decimal(intval($yuan * 100 + 0.0001));
    }
}