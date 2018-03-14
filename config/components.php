<?php
return [
    'request'      => [// !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
        'cookieValidationKey'  => 'fa3e794d9e06350b76ad6f0943052a28',
        'enableCsrfValidation' => false,
    ],
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
    'urlManager'   => [
        'class'           => 'yii\web\UrlManager',
        'enablePrettyUrl' => true,
        'showScriptName'  => false,
    ],
];