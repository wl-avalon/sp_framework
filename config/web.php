<?php

$config = [
    'id'         => 'sp_framework',
    'timeZone'   => 'Asia/Shanghai',
    'basePath'   => dirname(dirname(__DIR__)),
    'bootstrap'  => ['log'],
    'components' => include(__DIR__ . '/components.php'),
    'params'     => include(__DIR__ . '/params.php'),
    'modules'    => [
        'sp_framework' => [
            'class' => 'sp_framework\Module',
        ],
    ],
    'aliases'    => [
        '@sp_framework' => '@app/sp_framework',
    ],
];

return $config;
