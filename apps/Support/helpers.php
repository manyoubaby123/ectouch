<?php

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param int $type 转换类型
 * @param bool $ucfirst 首字母是否大写（驼峰规则）
 * @return string
 */
function parse_name(string $name, int $type = 0, bool $ucfirst = true)
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);

        return $ucfirst ? ucfirst($name) : lcfirst($name);
    }

    return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
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
        $base_path = app_path('modules/' . parse_name($module) . '/' . $sub . '/');

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
