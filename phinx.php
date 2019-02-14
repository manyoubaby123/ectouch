<?php

$db = require __DIR__ . '/config/database.php';

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => $db['prefix'] . 'migrations',
        'default_database' => 'production',
        'production' => [
            'adapter' => $db['type'],
            'host' => $db['hostname'],
            'name' => $db['database'],
            'user' => $db['username'],
            'pass' => $db['password'],
            'port' => $db['hostport'],
            'charset' => $db['charset'],
        ]
    ],
    'version_order' => 'creation'
];
