<?php

namespace sp_framework\apis\handler;

use sp_framework\apis\models\Response;

/**
 * 负责处理对其他系统调用的参数和返回值
 * Interface IRequestHandler
 * @package app\modules\datashop\apis\handler
 */
interface IRequestHandler{
    /**
     * @param array $fields
     * @param string $secret
     * @return string
     */
    function genSign(array $fields, $secret);

    /**
     * IRequestHandler init.
     * @param string $service 系统名
     * @param string $method 接口名
     * @param array $params 业务参数
     */
    function init($service, $method, array $params);

    /**
     * 根据系统和接口名获取访问url
     * @return string
     */
    function getUrl();

    /**
     * 根据系统和接口名获取访问参数
     * @return array
     */
    function getParams();

    /**
     * 获取系统对于的请求设置
     * @return array
     */
    function getOptions();

    /**
     * 对于不同系统,做相应的返回处理
     * @param Response $response
     * @param array $data
     * @return Response
     */
    function handleResponse(Response &$response, array $data);

    function getDeserializer();
}