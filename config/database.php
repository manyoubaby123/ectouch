<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=ectouch',
    'username' => 'homestead',
    'password' => 'secret',
    'charset' => 'utf8',
    //'on afterOpen' => function($event) {
    //    $event->sender->createCommand("set session sql_mode=''")->execute();
    //},

    // Schema cache options (for production environment)
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];
