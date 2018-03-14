<?php
namespace sp_framework\util;

/**
 * 字符相关操作工具类
 * 
 * @author hongcq
 */
class StrUtil {
    
    /**
     * 按照字符个数来判断
     * 
     * @param string $text
     * @param bool $trim 默认去除首尾空白
     * @return int
     */
    public static function strlen($text, $trim = true) {
        if ($trim === true) {
            $text = trim($text);
        }
        if (empty($text)) {
            return 0;
        }
        preg_match_all("/./u", $text, $matches);
        return count($matches[0]);
    }
    
    /**
	 * 移动手机号码判断(仅支持中国号码)
	 *
	 * @param string $strTel
	 * @return boolean true/false
	 */
	public static function isPhone($strTel) {
	    return preg_match('/^1[0-9]{10}$/', $strTel) < 1 ? false : true;
	}

    /**
     * 获取日志ID
     * 
     * @param int $len default:16
     * @param $renew default:false
     * @return string
     */
    public static function getLogId($len = 16, $renew = false) {
        static $logId = null;
        if ($logId !== null) {
            if ($renew !== true) {
                return $logId;
            }
        }
        $logId = self::getRstr($len);
        return $logId;
    }

    /**
     * 获取随机串(大小写数字)
     *
     * @param int $length default:16
     * @param string $prefix 前缀
     * @return string
     */
    public static function getRstr($length = 16, $prefix = '') {
        $chars = str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5);
        return $prefix . substr(str_shuffle($chars), 0, $length);
    }
    
    /**
     * 对输入对字符串随机进行小写转大写
     * 
     * @param string $str
     * @return string
     */
    public static function ramdonUpcase($str) {
        if (empty($str)) {
            return '';
        }
        
        $len = strlen($str);
        if ($len == 1) {
            return mt_rand(0,1) == 0 ? strtoupper($str) : $str;
        }
        if ($len == 2) {
            $s1 = $str{0};
            $s2 = $str{1};
            if (mt_rand(0,1) == 0) {
                return strtoupper($s1) . $s2;
            } else {
                return $s1 . strtoupper($s2);
            }
        }
        
        $len = mt_rand(0, $len - 1);
        $i = 0;
        $mdl = intval($len / 2);
        while ($i < $len) {
            if ($i % 2 == 0) {
                $pos = mt_rand(0, $mdl);
                if (isset($str{$pos})) {
                    $str{$pos} = strtoupper($str{$pos});
                }
            } else {
                $pos = mt_rand($mdl, $len);
                if (isset($str{$pos})) {
                    $str{$pos} = strtoupper($str{$pos});
                }
            }
            $i++;
        }
        
        return $str;
    }
    
    /**
     * 格式化电话号码输出
     *
     * @param string $phone
     * @return string like: 150****6042
     */
    public static function maskPhone($phone) {
        if (strlen($phone) < 7) {
            return $phone;
        }
        $phone{3} = '*';
        $phone{4} = '*';
        $phone{5} = '*';
        $phone{6} = '*';
        return $phone;
    }
    
    /**
     * 格式化身份证输出(隐掉生日信息)
     *
     * @param string $identity 身份证号码
     * @return string like: 370681********2839
     */
    public static function maskIdentity($identity) {
        if (strlen($identity) < 14) {
            return $identity;
        }
        $identity{6} = '*';
        $identity{7} = '*';
        $identity{8} = '*';
        $identity{9} = '*';
        $identity{10} = '*';
        $identity{11} = '*';
        $identity{12} = '*';
        $identity{13} = '*';
        return $identity;
    }
    
    /**
     * 获取固定长度的随机数字
     *
     * @param int $length default:6
     * @return string
     */
    public static function getRnum($length = 6) {
        $chars = str_repeat('0123456789', 10);
        return substr(str_shuffle($chars), 0, $length);
    }
    
    public static function serviceRet($errno, $data = '', $msg = null){
        return [
            'errno' => $errno,
            'errmsg' => $msg,
            'data' => $data,
        ];
    }
    
    public static function genSign(array $sign_fields, $private_key){
    	ksort($sign_fields);
    	$str = implode('|', array_values($sign_fields)) . "|" .$private_key;
    	return md5($str);
    }
    
    public static function compareVersion($version1, $version2){
    	if(empty($version1) && !empty($version2)) return -1;
    	if(!empty($version1) && empty($version2)) return 1;
    	if(empty($version1) && empty($version2)) return 0;
    	
    	$ver1 = str_replace(".", "", $version1);
    	$ver2 = str_replace(".", "", $version2);
    	$len1 = strlen($ver1);
    	$len2 = strlen($ver2);
    	$s1 = intval($ver1);
    	$s2 = intval($ver2);
    	
    	if($len1 < $len2){
    		for($i = 0; $i < $len2 - $len1; $i++){
    			$s1 = $s1 * 10;
    		}
    	}else if($len2 < $len1){
    		for($i = 0; $i < $len1 - $len2; $i++){
    			$s2 = $s2 * 10;
    		}
    	}
    	
    	if($s1 == $s2){
    		return 0;
    	}
    	return $s1 > $s2 ? 1 : -1;
    }
    
    public static function checkStringFormat($strTagNames){
        if($strTagNames === "" || $strTagNames === null){
            return false;
        }
        $strMatchStr = "/^[0-9a-zA-Z\x{4e00}-\x{9fa5}]+$/u";// 匹配中文，英文字母和数字
        preg_match($strMatchStr, $strTagNames, $matches);
        return $matches;
    }
    
}