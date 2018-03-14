<?php

namespace sp_framework\apis\http;

use sp_framework\apis\ApiContext;
use sp_framework\apis\models\Request;
use sp_framework\components\SpException;
use sp_framework\components\SpLog;
use sp_framework\constants\SpErrorCodeConst;
use sp_framework\SpModule;

/**
 * Class Http, 封装http curl, 用于发起请求, 基于rolling curl实现
 * @package app\modules\datashop\apis\http
 */
class Http{
    private static $curlMsgs = [CURLE_OK => 'OK', CURLE_UNSUPPORTED_PROTOCOL => 'UNSUPPORTED_PROTOCOL', CURLE_FAILED_INIT => 'FAILED_INIT', CURLE_URL_MALFORMAT => 'The URL is not valid.', CURLE_URL_MALFORMAT_USER => 'URL_MALFORMAT_USER', CURLE_COULDNT_RESOLVE_PROXY => 'COULDNT_RESOLVE_PROXY', CURLE_COULDNT_RESOLVE_HOST => 'The URL is not valid.', CURLE_COULDNT_CONNECT => 'Service for URL is invalid now, could not connect to.', CURLE_FTP_WEIRD_SERVER_REPLY => 'FTP_WEIRD_SERVER_REPLY', CURLE_FTP_ACCESS_DENIED => 'FTP_ACCESS_DENIED', CURLE_FTP_USER_PASSWORD_INCORRECT => 'FTP_USER_PASSWORD_INCORRECT', CURLE_FTP_WEIRD_PASS_REPLY => 'FTP_WEIRD_PASS_REPLY', CURLE_FTP_WEIRD_USER_REPLY => 'FTP_WEIRD_USER_REPLY', CURLE_FTP_WEIRD_PASV_REPLY => 'FTP_WEIRD_PASV_REPLY', CURLE_FTP_WEIRD_227_FORMAT => 'FTP_WEIRD_227_FORMAT', CURLE_FTP_CANT_GET_HOST => 'FTP_CANT_GET_HOST', CURLE_FTP_CANT_RECONNECT => 'FTP_CANT_RECONNECT', CURLE_FTP_COULDNT_SET_BINARY => 'FTP_COULDNT_SET_BINARY', CURLE_PARTIAL_FILE => 'PARTIAL_FILE', CURLE_FTP_COULDNT_RETR_FILE => 'FTP_COULDNT_RETR_FILE', CURLE_FTP_WRITE_ERROR => 'FTP_WRITE_ERROR', CURLE_FTP_QUOTE_ERROR => 'FTP_QUOTE_ERROR', CURLE_HTTP_NOT_FOUND => 'HTTP_NOT_FOUND', CURLE_WRITE_ERROR => 'WRITE_ERROR', CURLE_MALFORMAT_USER => 'MALFORMAT_USER', CURLE_FTP_COULDNT_STOR_FILE => 'FTP_COULDNT_STOR_FILE', CURLE_READ_ERROR => 'READ_ERROR', CURLE_OUT_OF_MEMORY => 'OUT_OF_MEMORY', CURLE_OPERATION_TIMEOUTED => 'Request for URL timeout.', CURLE_FTP_COULDNT_SET_ASCII => 'FTP_COULDNT_SET_ASCII', CURLE_FTP_PORT_FAILED => 'FTP_PORT_FAILED', CURLE_FTP_COULDNT_USE_REST => 'FTP_COULDNT_USE_REST', CURLE_FTP_COULDNT_GET_SIZE => 'FTP_COULDNT_GET_SIZE', CURLE_HTTP_RANGE_ERROR => 'HTTP_RANGE_ERROR', CURLE_HTTP_POST_ERROR => 'HTTP_POST_ERROR', CURLE_SSL_CONNECT_ERROR => 'SSL_CONNECT_ERROR', CURLE_FTP_BAD_DOWNLOAD_RESUME => 'FTP_BAD_DOWNLOAD_RESUME', CURLE_FILE_COULDNT_READ_FILE => 'FILE_COULDNT_READ_FILE', CURLE_LDAP_CANNOT_BIND => 'LDAP_CANNOT_BIND', CURLE_LDAP_SEARCH_FAILED => 'LDAP_SEARCH_FAILED', CURLE_LIBRARY_NOT_FOUND => 'LIBRARY_NOT_FOUND', CURLE_FUNCTION_NOT_FOUND => 'FUNCTION_NOT_FOUND', CURLE_ABORTED_BY_CALLBACK => 'ABORTED_BY_CALLBACK', CURLE_BAD_FUNCTION_ARGUMENT => 'BAD_FUNCTION_ARGUMENT', CURLE_BAD_CALLING_ORDER => 'BAD_CALLING_ORDER', CURLE_HTTP_PORT_FAILED => 'HTTP_PORT_FAILED', CURLE_BAD_PASSWORD_ENTERED => 'BAD_PASSWORD_ENTERED', CURLE_TOO_MANY_REDIRECTS => 'Request for URL caused too many redirections.', CURLE_UNKNOWN_TELNET_OPTION => 'UNKNOWN_TELNET_OPTION', CURLE_TELNET_OPTION_SYNTAX => 'TELNET_OPTION_SYNTAX', CURLE_OBSOLETE => 'OBSOLETE', CURLE_SSL_PEER_CERTIFICATE => 'SSL_PEER_CERTIFICATE', CURLE_GOT_NOTHING => 'GOT_NOTHING', CURLE_SSL_ENGINE_NOTFOUND => 'SSL_ENGINE_NOTFOUND', CURLE_SSL_ENGINE_SETFAILED => 'SSL_ENGINE_SETFAILED', CURLE_SEND_ERROR => 'SEND_ERROR', CURLE_RECV_ERROR => 'RECV_ERROR', CURLE_SHARE_IN_USE => 'SHARE_IN_USE', CURLE_SSL_CERTPROBLEM => 'SSL_CERTPROBLEM', CURLE_SSL_CIPHER => 'SSL_CIPHER', CURLE_SSL_CACERT => 'SSL_CACERT', CURLE_BAD_CONTENT_ENCODING => 'BAD_CONTENT_ENCODING', CURLE_LDAP_INVALID_URL => 'LDAP_INVALID_URL', CURLE_FILESIZE_EXCEEDED => 'FILESIZE_EXCEEDED', CURLE_FTP_SSL_FAILED => 'FTP_SSL_FAILED',];

    const MaxConcurrent = 10;
    const Options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOSIGNAL => 1];
    const Headers = [];

    public static function syncCall(Request $request){
        $isValid = false;
        $module = SpModule::getModuleName();
        $url = $request->getUrl();
        $ch = curl_init();
        if(!is_resource($ch)){
            throw new SpException(SpErrorCodeConst::REQUEST_FAILED, '请求失败, 请稍后重试', "curl_init execute failure url[{$url}]", null);
        }

        $op = curl_setopt_array($ch, self::buildOptions($request));
        if($op !== true){
            curl_close($ch);
            throw new SpException(SpErrorCodeConst::REQUEST_FAILED, '请求失败, 请稍后重试', 'curl_setopt_array execute failure url[' . json_encode($request->getOptions()) . ']', null);
        }

        $curlInfo = null;
        do{
            $request->start();
            $response = curl_exec($ch);
            $errNo = curl_errno($ch);
            $msg = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            $curlInfo['curl_errno'] = $errNo;
            $curlInfo['curl_error_msg'] = $msg;
            $request->stop($curlInfo);

            $httpCode = intval($curlInfo['http_code']);
            if($errNo === CURLE_OK && $httpCode == 200){
                $isValid = true;
                break;
            }

            if($httpCode == 301 || $httpCode == 302 || $httpCode == 307){
                //$intErrno == CURLE_OK can only indicate that the response is received, but it may
                //also be an error page or empty page, so we also need more checking when $intErrno == CURLE_OK
                SpLog::warning('Request for URL caused too many redirections.');
                // 其他异常(可能对方服务器内部错误等)
            }elseif($httpCode >= 400){
                SpLog::warning("Received HTTP error code[$httpCode] >= 400 while loading");
            }
        }while($request->canRetry());
        $request->stop($curlInfo);
        curl_close($ch);

        if($isValid){
            self::traceRequestInfo($request, $response, $curlInfo);
        }else{
            $log = '';
            foreach($curlInfo as $key => $value){
                if(is_array($value)){
                    continue;
                }
                $log .= "{$key}[{$value}] ";
            }

            SpLog::warning("[ApiContext] msg[{$module}_call_{$request->service}_network_error] url{$request->getUrl()}  method[{$request->method}] params[" . json_encode($request->getParams()) . "] return[$response] retry[$request->retryTimes] {$log}");
        }

        return $response;
    }

    /**
     * Execute the request queue
     * @param array $requests
     */
    public static function asyncCall(array $requests){
        if(count($requests) < self::MaxConcurrent){
            $maxConcurrent = count($requests);
        }else{
            $maxConcurrent = self::MaxConcurrent;
        }

        //the request map that maps the request queue to request curl handles
        $requestMap = [];
        $handle = curl_multi_init();

        //start processing the initial request queue
        $keys = array_keys($requests);
        for($i = 0; $i < $maxConcurrent; $i++){
            $request = $requests[$keys[$i]];
            self::initRequest($handle, $request, $requestMap);
        }

        do{
            do{
                $mh_status = curl_multi_exec($handle, $active);
            }while($mh_status == CURLM_CALL_MULTI_PERFORM);

            if($mh_status != CURLM_OK){
                break;
            }

            //a request is just completed, find out which one
            while($completed = curl_multi_info_read($handle)){
                $ch = $completed['handle'];
                $ch_hash = (string)$ch;
                $request = $requests[$requestMap[$ch_hash]];
                self::processRequest($completed, $handle, $requestMap, $request);
                //add/start a new request to the request queue
                if($i < count($keys) && isset($keys[$i])){ //if requests left
                    self::initRequest($handle, $requests[$keys[$i]], $requestMap);
                    $i++;
                }
            }

            usleep(15); //save CPU cycles, prevent continuous checking
        }while($active || count($requestMap)); //End do-while
    }

    /**
     * @param resource $handle
     * @param Request $request
     * @param array &$requestMap
     * @throws SpException
     */
    private static function initRequest($handle, $request, &$requestMap){
        $request->start();
        $ch = curl_init();
        $opts_set = curl_setopt_array($ch, self::buildOptions($request));

        if(!$opts_set){
            throw new SpException(SpErrorCodeConst::REQUEST_FAILED, '请求失败, 请稍后重试', 'curl option 未设置', null);
        }
        curl_multi_add_handle($handle, $ch);

        //add curl handle of a new request to the request map
        $ch_hash = (string)$ch;
        $requestMap[$ch_hash] = $request->getKey();
    }

    /**
     * 设置 curl options
     * @param Request $request
     * @return array
     */
    private static function buildOptions(Request $request){
        $url = $request->getUrl();
        $post_data = $request->getParams();
        $individual_opts = $request->getOptions();
        $individual_headers = $request->getHeaders();

        $options = ($individual_opts) ? $individual_opts + self::Options : self::Options; //merge shared and individual request options
        $headers = ($individual_headers) ? $individual_headers + self::Headers : self::Headers; //merge shared and individual request headers

        if($url){
            $options[CURLOPT_URL] = $url;
        }

        if($headers){
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        // set trace id
        if(isset($_SERVER['HTTP_SP_HEADER_RID'])){
            $options[CURLOPT_HTTPHEADER][] = "sp-header-rid:" . $_SERVER['HTTP_SP_HEADER_RID'];
        }

        // enable POST method and set POST parameters
        if($post_data){
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = is_array($post_data) ? http_build_query($post_data) : $post_data;
        }
        return $options;
    }

    /**
     * @param array $completed
     * @param resource $handle
     * @param array &$requestMap
     * @param Request $request
     */
    private static function processRequest($completed, $handle, &$requestMap, $request){
        $module = SpModule::getModuleName();
        $ch = $completed['handle'];
        $requestInfo = curl_getinfo($ch);
        $request->stop($requestInfo);

        $errorNo = $completed['result'];
        $requestInfo['msg'] = self::$curlMsgs[$errorNo];
        $requestInfo['curl_errno'] = $errorNo;

        $httpCode = intval($requestInfo['http_code']);

        if($httpCode == 301 || $httpCode == 302 || $httpCode == 307){
            //$intErrno == CURLE_OK can only indicate that the response is received, but it may
            //also be an error page or empty page, so we also need more checking when $intErrno == CURLE_OK
            $requestInfo['msg'] = 'Request for URL caused too many redirections.';

            // 其他异常(可能对方服务器内部错误等)
        }elseif($httpCode >= 400){
            $requestInfo['msg'] = "Received HTTP error code[$httpCode] >= 400 while loading";
        }

        if(curl_errno($ch) == 0 && $httpCode == 200){ //if server responded with http error
            $isValid = true;
            $response = curl_multi_getcontent($ch);
            self::traceRequestInfo($request, $response, $requestInfo);
        }else{
            $response = '';
            $isValid = false;
        }

        $hash = (string)$ch;
        unset($requestMap[$hash]);
        if($isValid || $request->canRetry() == false){
            //remove completed request and its curl handle
            curl_multi_remove_handle($handle, $ch);
            ApiContext::addResponse($request->getKey(), $response);
        }else{
            //重试
            self::initRequest($handle, $request, $requestMap);
        }

        if(!$isValid){
            $log = '';
            foreach($requestInfo as $key => $value){
                if(is_array($value)){
                    continue;
                }
                $log .= "{$key}[{$value}] ";
            }
            $params = json_encode($request->getParams());
            SpLog::warning("[ApiContext] msg[{$module}_call_{$request->service}_network_error] url{$request->getUrl()} method[{$request->method}] params[{$params}] return[$response] retry[$request->retryTimes] {$log}");
        }
        $response = null; //释放内存
    }

    private static function traceRequestInfo(Request $request, $return, $curlInfo){
        $module = SpModule::getModuleName();
        $log = [
            'http_code'           => $curlInfo['http_code'],// 状态码
            'cost'                => $curlInfo['total_time'],// 单个请求耗时(in seconds)
            'name_lookup_time'    => $curlInfo['namelookup_time'],// DNS查询时间(in seconds)
            'connect_time'        => $curlInfo['connect_time'],// 连接耗时(in seconds)
            'pre_transfer_time'   => $curlInfo['pretransfer_time'],// 从建立连接到准备传输所运用的时间(in seconds)
            'start_transfer_time' => $curlInfo['starttransfer_time'], // 从建立连接到传输开始所运用的时间(in seconds)
            'redirect_time'       => $curlInfo['redirect_time'],// 在事务传输开始前重定向所运用的时间(in seconds)
        ];
        $params = json_encode($request->getParams());
        $trace = "[ApiContext] msg[{$module}_call_{$request->service}_result] url[{$request->getUrl()}] method[{$request->method}] params[{$params}] return[$return] retry[$request->retryTimes]";
        foreach($log as $key => $value){
            $trace .= " {$key}[{$value}]";
        }
        SpLog::trace($trace);
    }
}