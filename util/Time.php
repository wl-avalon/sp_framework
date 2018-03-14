<?php
/**
 * Created by PhpStorm.
 * User: AndreasWang
 * Date: 2017/6/27
 * Time: 上午10:30
 */

namespace sp_framework\util;


class Time{
    public static function secToDays($sec){
        return intval($sec / (60 * 60 * 24));
    }

    public static function isToday($timeStamp){
        return date('Y-m-d', $timeStamp) == date('Y-m-d', time());
    }

    public static function udate($format = 'u', $uTimeStamp = null){
        if(is_null($uTimeStamp))
            $uTimeStamp = microtime(true);

        $timestamp = floor($uTimeStamp);
        $milliseconds = round(($uTimeStamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }
    
    /**
     * 计算两个日期的间隔天数，仅考虑日期
     * @param startDate
     * @param endDate
     * @return int @间隔天数
     */
    public static function dateDiffDays($startDate, $endDate) {
        if (is_numeric($startDate) && is_numeric($endDate)) {
            $startDate = date('Y-m-d', $startDate);
            $endDate = date('Y-m-d', $endDate);
        } else {
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));
        }
         
        return floor((strtotime($endDate) - strtotime($startDate)) / 86400);
    }
}