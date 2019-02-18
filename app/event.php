<?php

return [
    'bind'      => [
    ],
    'listen'    => [
        'AppInit'      => [
            'think\listener\LoadLangPack',
            'think\listener\RouteCheck',
        ],
        'AppBegin'     => [
            'think\listener\CheckRequestCache',
        ],
        'ActionBegin'  => [],
        'AppEnd'       => [],
        'LogLevel'     => [],
        'LogWrite'     => [],
        'ResponseSend' => [],
        'ResponseEnd'  => [],
    ],
    'subscribe' => [
    ],
];
