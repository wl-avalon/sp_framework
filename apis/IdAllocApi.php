<?php
/**
 * Created by PhpStorm.
 * User: AndreasWang
 * Date: 2018/1/19
 * Time: 下午2:22
 */

namespace sp_framework\apis;
use sp_framework\constants\SpErrorCodeConst;

class IdAllocApi{
    //申请自增ID
    public static function nextId(){
        return ApiContext::get('idAlloc', 'nextId', [])
            ->setDeserializer(static function($res){
                if(empty($res)){
                    return [
                        'error' => [
                            'returnCode'    => SpErrorCodeConst::REQUEST_FAILED,
                            'returnMessage' => '返回值为空或格式异常',
                        ],
                        'data'  => null,
                    ];
                }else{
                    return [
                        'error' => [
                            'returnCode'    => SpErrorCodeConst::SUCCESSFUL,
                            'returnMessage' => 'success',
                        ],
                        'data'  => json_decode($res, true),
                    ];
                }
            })
            ->throwWhenFailed();
    }

    //批量申请自增IDs
    public static function batch($count){
        return ApiContext::get('idAlloc', 'batch', [
            'count' => $count,
        ])
            ->setDeserializer(static function($res){
                if(empty($res)){
                    return [
                        'error' => [
                            'returnCode'    => SpErrorCodeConst::REQUEST_FAILED,
                            'returnMessage' => '返回值为空或格式异常',
                        ],
                        'data'  => null,
                    ];
                }else{
                    return [
                        'error' => [
                            'returnCode'    => SpErrorCodeConst::SUCCESSFUL,
                            'returnMessage' => 'success',
                        ],
                        'data'  => explode(',', $res),
                    ];
                }
            })
            ->throwWhenFailed();
    }
}