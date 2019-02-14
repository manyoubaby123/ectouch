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
    return storage_path('plugins' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * 获取应用主体
 * @param null $component
 * @return bool|mixed
 */
function app($component = null)
{
    if (!is_null($component) && isset(Yii::$app->{$component})) {
        return Yii::$app->{$component};
    }

    return false;
}

/**
 * 读取配置文件
 * @param null $key
 * @param null $default
 * @return null
 */
function config($key = null, $default = null)
{
    static $_config = [];
    $item = 'params';

    // 指定参数来源
    if (strpos($key, '.')) {
        list($item, $key) = explode('.', $key, 2);
    }

    if (!isset($_config[$item])) {
        $_config[$item] = require config_path($item . '.php');
    }

    return isset($_config[$item][$key]) ? $_config[$item][$key] : $default;
}

/**
 * 读取（写入）缓存
 * @return bool|mixed
 * @throws Exception
 */
function cache()
{
    $arguments = func_get_args();

    if (empty($arguments)) {
        return app('cache');
    }

    if (is_string($arguments[0])) {
        return app('cache')->get($arguments[0], isset($arguments[1]) ? $arguments[1] : null);
    }

    if (!is_array($arguments[0])) {
        throw new Exception(
            'When setting a value in the cache, you must pass an array of key / value pairs.'
        );
    }

    if (!isset($arguments[1])) {
        $arguments[1] = 0;
    }

    return app('cache')->set(key($arguments[0]), reset($arguments[0]), $arguments[1]);
}

/**
 * Session 会话操作
 * @param $name
 * @param string $value
 * @return mixed
 */
function session($name, $value = '')
{
    if (is_null($name)) {
        // 清除
        app('session')->destroy();
    } elseif ('' === $value) {
        // 判断或获取
        return 0 === strpos($name, '?') ? app('session')->has(substr($name, 1)) : app('session')->get($name);
    } elseif (is_null($value)) {
        // 删除
        return app('session')->remove($name);
    } else {
        // 设置
        return app('session')->set($name, $value);
    }
}

/**
 * Cookie 操作
 * @param $name
 * @param string $value
 * @param null $option
 */
function cookie($name, $value = '', $option = null)
{
    if (is_null($name)) {
        // 清除指定前缀的所有cookie
        if (empty($_COOKIE)) {
            return;
        }
        foreach ($_COOKIE as $key => $val) {
            setcookie($key, '', $_SERVER['REQUEST_TIME'] - 3600);
            unset($_COOKIE[$key]);
        }
    } elseif ('' === $value) {
        // 获取
        return 0 === strpos($name, '?') ? app('request')->cookies->has(substr($name, 1)) : app('request')->cookies->getValue($name);
    } elseif (is_null($value)) {
        // 删除
        return app('response')->cookies->remove($name);
    } else {
        // 设置
        $options = [
            'name' => $name,
            'value' => $value,
        ];

        if (!is_null($option)) {
            $options['expire'] = local_gettime() + $option * 60;
        }

        return app('response')->cookies->add(new \yii\web\Cookie($options));
    }
}

/**
 * 获取 Request 参数
 * @param string $name
 * @param null $default
 * @return array|mixed
 */
function input($name = '', $default = null)
{
    return Yii::$app->request->get($name, $default);
}

/**
 * 生成 URL 链接
 * @param $url
 * @param array $param
 * @return string
 */
function url($url, $param = [])
{
    return yii\helpers\Url::toRoute(array_merge([$url], $param));
}

/**
 * 返回静态资源文件 URL
 * @param string $path
 * @return string
 */
function asset($path = '/')
{
    if (is_valid_url($path)) {
        return $path;
    }

    $root = str_replace('/index.php', '', yii\helpers\Url::home());

    return rtrim($root, '/') . '/' . trim($path, '/');
}

/**
 * Determine if the given path is a valid URL.
 *
 * @param  string $path
 * @return bool
 */
function is_valid_url($path)
{
    if (!preg_match('~^(#|//|https?://|mailto:|tel:)~', $path)) {
        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    return true;
}

/**
 * CSRF Token
 * @return mixed
 */
function csrf_token()
{
    return app('request')->getCsrfToken();
}

/**
 * CSRF Meta Tag
 * @return string
 */
function csrf_meta()
{
    return '<meta name="' . app('request')->csrfParam . '" content="' . app('request')->getCsrfToken() . '" />';
}

/**
 * 表单令牌生成
 * @return string
 */
function csrf_field()
{
    return '<input type="hidden" name="' . app('request')->csrfParam . '" value="' . app('request')->getCsrfToken() . '" />';
}

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
 */
function load_helper($files = [], $module = '')
{
    if (!is_array($files)) {
        $files = [$files];
    }

    if (empty($module)) {
        $base_path = app_path('helpers/');
    } else {
        $base_path = app_path(strtolower($module) . '/common/');
    }

    foreach ($files as $vo) {
        $helper = $base_path . $vo . '.php';
        if (file_exists($helper)) {
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

    if (empty($module)) {
        $base_path = resource_path('lang/' . $GLOBALS['_CFG']['lang'] . '/');
    } else {
        $base_path = app_path(strtolower($module) . '/lang/' . $GLOBALS['_CFG']['lang'] . '/');
    }

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

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dd($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        die($output);
    } else {
        return $output;
    }
}
