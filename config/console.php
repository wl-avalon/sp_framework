<?php

$params = require(__DIR__ . '/params.php');

return [
    'id'                  => 'application-console',
    'basePath'            => dirname(dirname(__DIR__)),
    'bootstrap'           => ['log', 'gii'],
    'controllerNamespace' => 'app\modules\sp_framework\commands',
    'components'          => [
        'errorHandler' => [
            'errorAction' => 'error/catchall',
        ],
        'log'          => [
            'class'      => 'sp_framework\ext\log\SpYiiLogDispatcher',
            'traceLevel' => 16,
            'logger'     => 'sp_framework\ext\log\SpYiiLogger',
            'targets'    => [
                [
                    'class'     => 'sp_framework\ext\log\SpYiiLogFileTarget',
                    'log_level' => 16,
                    'log_path'  => dirname(dirname(dirname(__DIR__))) . '/logs',
                ],
            ],
        ],
    ],
    'modules'             => [
        'sp_framework' => [
            'class' => 'sp_framework\Module',
        ],
        'gii'            => [
            'class' => 'yii\gii\Module',
        ],
    ],
    'aliases'    => [
        '@sp_framework' => '@app/sp_framework',
    ],
    'params'              => $params,
];
