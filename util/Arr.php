<?php

namespace sp_framework\util;

/**
 * 数组相关辅助方法类
 * Class Arr
 * @package app\modules\datashop\utils
 * @author wangdj
 */
class Arr{
    /**
     * 按照二维数组的某个key进行排序
     * @param array $arr 要排序的二维数组
     * @param string $sortKey 指定排序的 key
     * @param string $sortType 排序类型
     * @author yaodw
     * @return array 排序后的数组
     */
    public static function sortMultiArr($arr, $sortKey, $sortType){

        $arrSort = [];
        foreach($arr as $rowId => $row){
            foreach($row as $key => $val){
                $arrSort[$key][$rowId] = $val;
            }
        }

        array_multisort($arrSort[$sortKey], constant($sortType), $arr);
        return $arr;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array $array
     * @param  string $prepend
     * @return array
     */
    public static function dot($array, $prepend = ''){
        $results = [];

        foreach($array as $key => $value){
            if(is_array($value)){
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            }else{
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array $array
     * @return bool
     */
    public static function isAssoc($array){
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array|\ArrayAccess $array
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get($array, $key, $default = null){
        if(is_null($key)){
            return $array;
        }

        if(isset($array[$key])){
            return $array[$key];
        }

        foreach(explode('.', $key) as $segment){
            if(is_null($array)){
                break;
            }
            $array = $array[$segment]??null;
        }

        return is_null($array) ? $default : $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     *
     * @param  array $array
     * @param  string $key
     * @return bool
     */
    public static function has($array, $key){
        if(empty($array) || is_null($key)){
            return false;
        }

        if(Arr::isAssoc($array)){
            if(array_key_exists($key, $array)){
                return true;
            }
        }else{
            if(in_array($key, $array)){
                return true;
            }
        }

        foreach(explode('.', $key) as $segment){
            if(!is_array($array) || !array_key_exists($segment, $array)){
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return mixed|null
     */
    public static function last($array){
        if(empty($array)){
            return null;
        }

        return array_pop($array);
    }

    public static function snakeKey($array){
        if(empty($array) || !static::isAssoc($array)){
            return $array;
        }

        $ret = [];
        foreach($array as $key => $value){
            $snakeKey = Str::snake($key);
            $ret[$snakeKey] = $value;
        }

        return $ret;
    }

    /**
     * 对array进行遍历查询
     * @param array|\Iterator $array
     * @param callable $where
     * @param null|string|array|callable $columns
     * @return array
     */
    public static function select($array, callable $where, $columns = null): array{
        $result = [];
        if(empty($array) || count($array) == 0){
            return $result;
        }

        foreach($array as $key => $value){
            if($where($value)){
                if(is_string($columns) || is_null($columns)){
                    $result[$key] = Arr::get($value, $columns);
                }elseif(is_array($columns)){
                    $temp = [];
                    foreach($columns as $k => $v){
                        $temp[$k] = Arr::get($v, $value);
                    }
                    $result[$key] = $temp;
                }elseif(is_callable($columns)){
                    $result[$key] = $columns($value);
                }
            }
        }

        if(Arr::isAssoc($array)){
            return $result;
        }else{
            return array_values($result);
        }
    }
    
    /**
     * 索引数组转换成关联数组
     * @param array $arr
     * @param string $key
     * @return array[]
     */
    public static function convertToMapArr($arr=[], $key){
        $ret = [];
        
        foreach ($arr as $item) {
            $ret[$item[$key]] = $item;
        }
        
        return $ret;
    }
}