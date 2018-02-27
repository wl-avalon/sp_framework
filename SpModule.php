<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/2/27
 * Time: 下午3:22
 */

namespace sp_framework;


class SpModule
{
    const DEFAULT_MODULE_NAME = "unknown";
    static protected $module_name  = self::DEFAULT_MODULE_NAME;

    static public function setModuleName($module_name) {
        self::$module_name = $module_name;
    }

    static public function getModuleName() {
        return self::$module_name;
    }
}