<?php
/**
 * author : wangzhengjun
 * QQ     : 694487069
 * phone  : 15801450732
 * Email  : wangzjc@jiedaibao.com
 * Date   : 17/6/12
 */
namespace sp_framework\util;


use sp_framework\components\SpLog;

class Diff
{

    /**
     * 对比两个数据是否相同，不同打日志。
     * @param $oldData
     * @param $newData
     * @param $apiName
     * @param $noticeKey
     * @author yaodw
     * @return boolean
     */
    public static function compare($oldData, $newData, $apiName = '', $noticeKey = '')
    {
        if (is_array($oldData) && is_array($newData)) {

            SpLog::addNotice($noticeKey.'_oldData', json_encode($oldData));
            SpLog::addNotice($noticeKey.'_newData', json_encode($newData));

            $diff = self::compareArray($oldData, $newData, $apiName);
            $isSame = 1;
            if (count($diff['diff']) > 0) {
                $isSame = 0;
                self::_logDifferentKey($diff, $noticeKey);
            }
        } else {
            SpLog::addNotice($noticeKey.'_oldData', serialize($oldData));
            SpLog::addNotice($noticeKey.'_newData', serialize($newData));

            $isSame = 1;
            if ($oldData !== $newData) {
                $isSame = 0;
            }
        }

        if (! empty($apiName)) {
            SpLog::addNotice('apiName', $apiName);
        }

        SpLog::addNotice($noticeKey.'_isSame', $isSame);

        return $isSame;
    }

    /**
     * 递归对比两个数组是否相同
     * @param array $arr1 数组1
     * @param array $arr2 数组2
     * @param string $apiName
     * @param string $parentKey
     * @return array
     */
    public static function compareArray(array $arr1, array $arr2, $apiName, $parentKey = null)
    {
        $ret = [
            'apiName' => $apiName,
            'diff' => []
        ];

        if( empty($arr1) && !empty($arr2) )
        {
            SpLog::warning("debt_data_compare_err old_is_Null_new_has_value");
        }

        foreach ($arr1 as $key => $val) {

            if (is_int($key)) {
                $logKey = $parentKey;
            } elseif ($parentKey != null) {
                $logKey = $parentKey . '.' . $key;
            } else {
                $logKey = $key;
            }

            if (! isset($arr2[$key]) && !is_null($arr1[$key])) {
                $ret['diff'][] = [
                    'key' => $logKey,
                    'oldData' => (is_array($val))?json_encode($val):$val,
                    'newData' => null
                ];
            } else {
                if (! is_array($val)) {
                    if ($val != $arr2[$key]) {
                        $ret['diff'][] = [
                            'key' => $logKey,
                            'oldData' => (is_array($val))?json_encode($val):$val,
                            'newData' => $arr2[$key]
                        ];
                    }
                } else {
                    $temp = self::compareArray($val, $arr2[$key], $apiName, $logKey);
                    if (count($temp['diff']) > 0) {
                        $ret['diff'] = array_merge($ret['diff'], $temp['diff']);
                    }
                }
            }
        }

        return $ret;
    }

    private static function _logDifferentKey($pair, $key = '')
    {
        // apiName: apiName
        // diff: [{key,newData,oldeData}]
        $apiName = $pair['apiName'];
        foreach ($pair['diff'] as $item) {
            if (self::_needPrintDiffKey($apiName, $item['key'])) {
                $newData = isset($item['newData']) ? $item['newData'] : 'null';
                $log = "notice[$key] api_name[$apiName]" . " key[{$item['key']}] oldData[{$item['oldData']}] newData[$newData]";
                SpLog::warning('debt_data_compare_err: ' . $log);
            }
        }
    }

    /**
     * 要过滤的数据
     * @param string $apiName
     * @param string $key
     * @return boolean
     */
    private static function _needPrintDiffKey($apiName, $key)
    {
        $filterList = [ // 就先写这儿吧
//             'borrowInfo' => [ // 借入利息明细
//                 'interest',
//                 'list.persistTime',
//                 'list.profitRate'
//             ],
//             'interestInfo' => [ // 赚利差收益明细
//                 'list.profitRate'
//             ],
        ];

        if (isset($filterList[$apiName])) {
            return ! in_array($key, $filterList[$apiName]);
        }

        return true;
    }




    /**
     * 对比两个数据是否相同，不同打日志。先进行排序
     * @param $oldData
     * @param $newData
     * @param $sortKey
     * @param $apiName
     * @param $noticekey
     * @author yaodw
     * @return boolean
     */
    public static function compareSortArray($oldData, $newData, $sortKey, $apiName = '', $noticekey = '')
    {

        $newDataTmp = array_column($newData, null, $sortKey);
        $oldDataTmp = array_column($oldData, null, $sortKey);
        ksort($newDataTmp);
        ksort($oldDataTmp);

        return self::compare(array_values($oldDataTmp), array_values($newDataTmp), $apiName, $noticekey);
    }
}