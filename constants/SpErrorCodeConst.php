<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/3/12
 * Time: 下午6:32
 */

namespace sp_framework\constants;


class SpErrorCodeConst
{
    const SUCCESSFUL        = 0;

    const NOT_YET_LOGIN     = 3327000;

    const INSERT_DB_ERROR   = 3327001;
    const UPDATE_DB_ERROR   = 3327002;

    const REQUEST_FAILED    = 3327003;

    const ILLEGAL_AES_KEY       = -41001;   //encodingAesKey 非法
    const ILLEGAL_IV            = -41002;   //iv非法
    const ILLEGAL_BUFFER        = -41003;   //aes 解密失败
    const DECODE_BASE64_ERROR   = -41004;   //解密后得到的buffer非法
}