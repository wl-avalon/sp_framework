<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午10:34
 */

namespace sp_framework\util;
use sp_framework\components\SpLog;
use Yii;
class HttpUtil {

    private $arrOptions = [
        CURLOPT_USERAGENT            => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.104 SP/1.0',
        CURLOPT_REFERER              => '',
        CURLOPT_ENCODING             => 'UTF-8',
        CURLOPT_TIMEOUT_MS           => 6000,
        CURLOPT_CONNECTTIMEOUT_MS    => 1200,
        CURLOPT_MAXREDIRS            => 3,
        CURLOPT_FOLLOWLOCATION       => true,
        CURLOPT_RETURNTRANSFER       => 1,
        CURLOPT_HEADER               => false,
        CURLOPT_NOSIGNAL             => true,
        CURLOPT_SSL_VERIFYPEER       => false,
        CURLOPT_SSL_VERIFYHOST       => false,
    ];

    protected $requestParam = [];

    /**
     * 获取HttpUtil实例
     *
     * @param array $arrOpt curl option 已设置默认值的有[user_agent,referer,encoding,conn_timeout,conn_retry,max_redirs,timeout,max_response_size,follow_location]
     * @return HttpUtil
     */
    public static function instance($arrOpt = []) {
        $proxy = new self();
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $proxy->arrOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if (is_array($arrOpt) && !empty($arrOpt)) {
            $proxy->arrOptions = $arrOpt + $proxy->arrOptions;
        }
        return $proxy;
    }

    public function curl($arrReq, $serviceName = 'service') {

        // 失败重试次数
        $intMaxRetry = 1;
        $intCounter = 0;
        if(isset($arrReq['option']['retry'])) {
            $intMaxRetry = intval($arrReq['option']['retry']) + 1;
            unset($arrReq['option']['retry']);
        }

        $intStart = microtime(true);
        $log = [];
        $intErrno = -1;
        $arrRet = ['errno' => $intErrno, 'msg' => 'error happen', 'content' => ''];
        $strUrl = $this->getRequestUrl($arrReq);
        $curl = $this->getHandle($strUrl, $arrReq);

        while ($intCounter < $intMaxRetry) {
            $this->setHeaderOption($curl, $arrReq);
            // 记录重试日志
            if ($intCounter > 0) {
                SpLog::warning("retry request curl url[$strUrl] retrytimes[$intCounter]");
            }

            $csStartTime = DictUtil::microtimeFloat();
            $strContent = curl_exec($curl);
            $csEndTime  = DictUtil::microtimeFloat();

            $intErrno = curl_errno($curl);
            $strMsg = curl_error($curl);
            $arrInfo = curl_getinfo($curl);

            $log = $this->getExpireInfo($arrInfo);
            $log['url'] = $strUrl;
            $arrRet['errno'] = $log['curl_errno'] = $intErrno;
            $arrRet['msg'] = $log['curl_error_msg'] = $strMsg;

            // 做http_code和curl_error校验
            if($this->checkResponse($arrInfo, $intErrno, $strMsg)) {
                // func checkResponse 做完了再最后做$intErrno === CURLE_OK
                if ($intErrno === CURLE_OK) {
                    // do log request
                    $log['file'] = __CLASS__ . ':' . __LINE__;
                    $log['total_cost'] = round((microtime(true)-$intStart), 3);
                    $arrRet['content'] = $strContent;

//                    ServiceWfUtil::rpcLog($serviceName, $intErrno, $arrInfo['url'], $strContent, $csStartTime, $csEndTime, $strRpcId);
                    break;
                }
            } else {
//                $errorLevel = (($intMaxRetry - 1) === $intCounter) ? ServiceWfUtil::ERR_LEVEL_FATAL
//                    : ServiceWfUtil::ERR_LEVEL_WARN;

//                ServiceWfUtil::rpcLog($serviceName, $intErrno, $arrInfo['url'], $strContent, $csStartTime, $csEndTime, $strRpcId);
//                $logExtInfo = [
//                    'http_code' => $arrInfo['http_code'],
//                    'retry' => $intCounter,
//                    'error_level' => $errorLevel,
//                ];
//                ServiceWfUtil::netLog($serviceName, $intErrno, $strMsg, $strUrl, $this->requestParam
//                    , $strContent, $logExtInfo);

                $intErrno = $arrInfo['http_code'] == 200 ? 0 : $arrInfo['http_code'];
                // bugfix:部分超时的情况下$arrInfo['http_code'] = 0
                if ($arrInfo['http_code'] == 0 && strpos($arrRet['msg'], 'Operation timed out after') !== false) {
                    $intErrno = CURLE_OPERATION_TIMEOUTED;
                }
                if (empty($strMsg)) {
                    $arrRet['msg'] = $strContent;
                }
            }

            $intCounter++;
        }

        curl_close($curl);

        if ($intErrno == CURLE_OK) {
            $strLog = '';
            foreach ($log as $k => $v) {
                $strLog .= "{$k}[{$v}] ";
            }
            SpLog::debug("msg[HttpUtil->curl execute detail] {$strLog}");
        }

        $arrRet['errno'] = $intErrno;
        return $arrRet;
    }

    /**
     * 校验执行结果状态码
     *
     * @param array $arrResp 函数curl_getinfo返回的数组
     * @param int $intErrno
     * @param string $strMsg
     * @return boolean
     */
    private function checkResponse(&$arrResp, $intErrno, $strMsg) {

        $bolValid = false;
        $intCode = intval($arrResp['http_code']);
        // 出错的情况下记录详尽的错误信息
        $arrLog = [
            'curl_errno' => $intErrno,
            'curl_erorr_msg' => $strMsg,
            'http_code' => $intCode,
            'total_time' => isset($arrResp['total_time']) ? $arrResp['total_time'] : -1,
            'namelookup_time' => isset($arrResp['namelookup_time']) ? $arrResp['namelookup_time'] : -1,
            'connect_time' => isset($arrResp['connect_time']) ? $arrResp['connect_time'] : -1,
            'starttransfer_time' => isset($arrResp['starttransfer_time']) ? $arrResp['starttransfer_time'] : -1,
            'pretransfer_time' => isset($arrResp['pretransfer_time']) ? $arrResp['pretransfer_time'] : -1,
            'primary_ip' => isset($arrResp['primary_ip']) ? $arrResp['primary_ip'] : 'unknow',
            'primary_port' => isset($arrResp['primary_port']) ? $arrResp['primary_port'] : -1,
            'local_ip' => isset($arrResp['local_ip']) ? $arrResp['local_ip'] : 'unknow',
            'local_port' => isset($arrResp['local_port']) ? $arrResp['local_port'] : -1,
            'redirect_count' => isset($arrResp['redirect_count']) ? $arrResp['redirect_count'] : -1,
            'redirect_time' => isset($arrResp['redirect_time']) ? $arrResp['redirect_time'] : -1,
            'url' => isset($arrResp['url']) ? $arrResp['url'] : 'unknow',
        ];

        // 域名或URL错误
        if($intErrno == CURLE_URL_MALFORMAT
            || $intErrno == CURLE_COULDNT_RESOLVE_HOST) {
            $arrLog['msg'] = 'The URL is not valid.';

            // URL连接不上
        } elseif ($intErrno == CURLE_COULDNT_CONNECT) {
            $arrLog['msg'] = 'Service for URL is invalid now, could not connect to.';

            // 超出最大耗时
        } elseif( $intErrno == CURLE_OPERATION_TIMEOUTED) {
            $arrLog['msg'] = 'Request for URL timeout.';

            // 重定向次数过多
        } elseif( $intErrno == CURLE_TOO_MANY_REDIRECTS
            || $intCode == 301
            || $intCode == 302
            || $intCode == 307 ) {
            //$intErrno == CURLE_OK can only indicate that the response is received, but it may
            //also be an error page or empty page, so we also need more checking when $intErrno == CURLE_OK
            $arrLog['msg'] = 'Request for URL caused too many redirections.';

            // 其他异常(可能对方服务器内部错误等)
        } elseif( $intCode >= 400 ) {
            $arrLog['msg'] = 'Received HTTP error code >= 400 while loading';

        } else {
            $bolValid = true;
        }

        if ($bolValid !== true) {
            $strLog = '';
            foreach ($arrLog as $k => $v) {
                $strLog .= "{$k}[{$v}] ";
            }
            $arrLog['file'] = __CLASS__ . ':' . __LINE__;
            SpLog::warning("msg[HttpUtil->checkResponse] {$strLog}");
        }

        return $bolValid;
    }

    /**
     * 不间断执行curl multi handle，知道全部请求处理完成
     *
     * @param resource $mh curl multi handle
     * @return void
     */
    private function execMultiHandle($mh) {
        do {
            $bolActive = false;
            curl_multi_exec($mh, $bolActive);
            curl_multi_select($mh);
        } while ($bolActive);
    }

    /**
     * 文件抓取
     *
     * @param string $strUrl 抓取文件URL
     * @param string $strDest 本地文件存储路径
     * @param array $arrOpt curl_option list
     * @return boolean true on success, false on failure
     */
    public function fileCurl($strUrl, $strDest, $arrOpt = array()) {

    }

    /**
     * 给multi-curl添加handle
     *
     * @param string $strUrl
     * @param array $arrReq
     * @return resource on success, false on failure
     */
    private function getHandle($strUrl, &$arrReq) {

        $ch = curl_init();
        if (!is_resource($ch)) {
            SpLog::warning('curl_init execute failure url['.$strUrl.']');
            return false;
        }
        $arrOption = $this->getOption($strUrl, $arrReq);
        $bolRet = curl_setopt_array($ch, $arrOption);

        if ($bolRet !== true) {
            SpLog::warning('curl_setopt_array execute failure url['.$strUrl.']');
            curl_close($ch);
            return false;
        }

        return $ch;
    }

    /**
     * 获得完整URL信息
     *
     * @param string $strUrl
     * @param array $arrOpt [{'option', 'get', 'post', 'cookie'}, ...]
     * @return string
     */
    private function getRequestUrl(&$arrOpt) {

        $strUrl = $arrOpt['url'];
        if(!isset($arrOpt['get'])) {
            return $strUrl;
        }

        // 设置GET参数
        if (is_array($arrOpt['get']) && !empty($arrOpt['get'])) {
            $strGet = http_build_query($arrOpt['get']);
            if (strpos($strUrl, '?', 7) > 0) {
                $strUrl .= '&' . $strGet;
            } else {
                $strUrl .= '?' . $strGet;
            }
        }
        unset($arrOpt['get']);

        return $strUrl;
    }

    /**
     * 获取请求的时间信息
     *
     * @param array $arrInfo
     * @return array [errno,total_time,namelookup_time,connect_time,pretransfer_time,starttransfer_time,redirect_time]
     */
    private function getExpireInfo($arrInfo) {

        $ret = [];
        // 单个请求耗时(in seconds)
        $ret['cost'] = $arrInfo['total_time'];
        // 状态码
        $ret['http_code'] = $arrInfo['http_code'];
        // DNS查询时间(in seconds)
        $ret['namelookup_time'] = $arrInfo['namelookup_time'];
        // 连接耗时(in seconds)
        $ret['connect_time'] = $arrInfo['connect_time'];
        // 从建立连接到准备传输所运用的时间(in seconds)
        $ret['pretransfer_time'] = $arrInfo['pretransfer_time'];
        // 从建立连接到传输开始所运用的时间(in seconds)
        $ret['starttransfer_time'] = $arrInfo['starttransfer_time'];
        // 在事务传输开始前重定向所运用的时间(in seconds)
        $ret['redirect_time'] = $arrInfo['redirect_time'];

        return $ret;
    }

    /**
     * 获取请求头的curl_option
     *
     * @param string $strUrl 请求URL
     * @param array $arrReq [{'option', 'get', 'post', 'cookie'}, ...]
     * @return array curl_option 相关选项数据
     */
    private function getOption($strUrl, &$arrReq) {

        // 设置POST参数
        $arrOption = array();
        if (isset($arrReq['post']) && !empty($arrReq['post'])) {
            $this->requestParam = is_array($arrReq['post']) ? $arrReq['post'] : [$arrReq['post']];

            $arrOption[CURLOPT_POST] = 1;
            $arrOption[CURLOPT_POSTFIELDS] = is_array($arrReq['post']) ? http_build_query($arrReq['post']) : $arrReq['post'];

            unset($arrReq['post']);
        }

        // 设置COOKIE
        if (isset($arrReq['cookie'])) {
            if(is_array($arrReq['cookie']) && !empty($arrReq['cookie'])) {
                $strCookie = '';
                foreach($arrReq['cookie'] as $k => $v) {
                    $strCookie .= ($k . '=' . $v.'; ');
                }
                $arrOption[CURLOPT_COOKIE] = $strCookie;
            }
            unset($arrReq['cookie']);
        }
        $arrOption[CURLOPT_URL] = $strUrl;

        // 用户自定义curl_option选项
        $arrUserOpt = array();
        if (isset($arrReq['option'])) {
            $arrUserOpt = $arrReq['option'];
        }

        // 没设置浏览器useragent时默认设置请求URL的主域名
        if (!isset($arrUserOpt[CURLOPT_REFERER])) {
            $arrUrl = parse_url($strUrl);
            $arrOption[CURLOPT_REFERER] = $arrUrl['scheme'] . '://' . $arrUrl['host'] . '/';
        }

        // 两个数组相加(保持索引不变)
        if (!empty($arrUserOpt) && is_array($arrUserOpt)) {
            // 不允许指定CURLOPT_RETURNTRANSFER参数
            if (isset($arrUserOpt[CURLOPT_RETURNTRANSFER])) {
                unset($arrUserOpt[CURLOPT_RETURNTRANSFER]);
            }
            $arrOption = $arrUserOpt + $arrOption;
        }

        $arrOption = $arrOption + $this->arrOptions;

        return $arrOption;
    }

    //set rpc header
    private function setHeaderOption($ch, $arrOption){
        if(isset($arrOption['option'][CURLOPT_HTTPHEADER])){
            if(is_array($arrOption['option'][CURLOPT_HTTPHEADER])){
                $arrHOption = $arrOption['option'][CURLOPT_HTTPHEADER];
            }else{
                $arrHOption = array($arrOption['option'][CURLOPT_HTTPHEADER]);
            }
        }else{
            $arrHOption = array();
        }
        $bolRet = curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHOption);

        if ($bolRet !== true) {
            SpLog::warning('curl_setopt sp rpc info execute failure info['.serialize($arrHOption).']');
            return false;
        }
        return true;
    }
}