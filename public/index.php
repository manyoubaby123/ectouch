<?php

/**
 * ECTouch - A Modern E-Commerce Platform
 *
 * @package  ECTouch
 * @homepage https://www.ectouch.cn
 */

if (version_compare(PHP_VERSION, '7.1.3', '<')) {
    die('require PHP > 7.1.3 !');
}

/*
|--------------------------------------------------------------------------
| 应用名称
|--------------------------------------------------------------------------
*/

define('APPNAME', 'ECTouch');

/*
|--------------------------------------------------------------------------
| 应用版本
|--------------------------------------------------------------------------
*/

define('VERSION', 'v3.0.0');

/*
|--------------------------------------------------------------------------
| 发布时间
|--------------------------------------------------------------------------
*/

define('RELEASE', '20181101');

/*
|--------------------------------------------------------------------------
| 编码格式
|--------------------------------------------------------------------------
*/

define('EC_CHARSET', 'utf-8');

/*
|--------------------------------------------------------------------------
| 编码格式
|--------------------------------------------------------------------------
*/

define('ADMIN_PATH', 'seller');

/*
|--------------------------------------------------------------------------
| 编码格式
|--------------------------------------------------------------------------
*/

define('AUTH_KEY', 'this is a key');

/*
|--------------------------------------------------------------------------
| 编码格式
|--------------------------------------------------------------------------
*/

define('OLD_AUTH_KEY', '');

/*
|--------------------------------------------------------------------------
| API时间
|--------------------------------------------------------------------------
*/

define('API_TIME', '2019-02-15 05:44:57');

/*
|--------------------------------------------------------------------------
| Setting Debuger
|--------------------------------------------------------------------------
|
*/

if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    defined('APP_DEBUG') or define('APP_DEBUG', false);
} else {
    defined('APP_DEBUG') or define('APP_DEBUG', true);
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__ . '/../bootstrap/base.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = think\Container::get('app')->path(__DIR__ . '/../app/');

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run()->send();
