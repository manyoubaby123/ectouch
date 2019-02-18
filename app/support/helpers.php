<?php

/**
 * 应用根目录
 * @param string $path
 * @return string
 */
function base_path($path = '')
{
    return dirname(dirname(__DIR__)) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
}

/**
 * 应用核心目录
 * @param string $path
 * @return string
 */
function app_path($path = '')
{
    return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 应用配置目录
 * @param string $path
 * @return string
 */
function config_path($path = '')
{
    return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 应用数据库目录
 * @param string $path
 * @return string
 */
function database_path($path = '')
{
    return base_path('database' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 入口文件目录
 * @param string $path
 * @return string
 */
function public_path($path = '')
{
    return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 资源文件目录
 * @param string $path
 * @return string
 */
function resource_path($path = '')
{
    return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 文件存储目录
 * @param string $path
 * @return string
 */
function storage_path($path = '')
{
    return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 插件目录
 * @param string $path
 * @return string
 */
function plugin_path($path = '')
{
    return app_path('plugins' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * @param $path
 * @return mixed
 */
function asset($path)
{
    return $path;
}

/**
 * 加载函数库
 * @param array $files
 * @param string $module
 * @param string $sub
 */
function load_helper($files = [], $module = '', $sub = 'common')
{
    static $_helper = [];

    if (!is_array($files)) {
        $files = [$files];
    }

    if (empty($module)) {
        $base_path = app_path('helpers/');
    } else {
        $module = ($module == 'admin') ? 'console' : $module; // 兼容模块名称
        $base_path = app_path('Modules/' . parse_name($module, true) . '/' . parse_name($sub, true) . '/');
    }

    foreach ($files as $vo) {
        $helper = $base_path . $vo . '.php';
        $hash = md5($helper);

        if (isset($_helper[$hash])) {
            continue;
        }

        if (file_exists($helper)) {
            $_helper[$hash] = $helper;
            require($helper);
        }
    }
}

/**
 * 加载语言包
 * @param array $files
 * @param string $module
 */
function load_lang($files = [], $module = '')
{
    static $_LANG = [];

    if (!is_array($files)) {
        $files = [$files];
    }

    $base_path = resource_path('lang/' . $GLOBALS['_CFG']['lang'] . '/');

    $base_path .= empty($module) ? $module : parse_name($module) . '/';

    foreach ($files as $vo) {
        $helper = $base_path . $vo . '.php';
        $lang = null;

        if (file_exists($helper)) {
            $lang = require($helper);
            if (!is_null($lang)) {
                $_LANG = array_merge($_LANG, $lang);
            }
        }
    }

    $GLOBALS['_LANG'] = $_LANG;
}
