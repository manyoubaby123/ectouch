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
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = (new think\App())->name('shop')->autoMulti([ADMIN_PATH => 'console']);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/


/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
