<?php

namespace App\Services;

/**
 * Class LicenseService
 * @package App\Services
 */
class LicenseService
{

    /**
     * 授权信息内容
     *
     * @return  str
     */
    public function license_info()
    {
        if ($GLOBALS['_CFG']['licensed'] > 0) {
            /* 获取HOST */
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                $host = $_SERVER['HTTP_HOST'];
            }
            $url_domain = url_domain();
            $host = 'http://' . $host . $url_domain;
            $license = '<a href="http://www.ectouch.cn/license.php?product=ectouch_b2c&url=' . urlencode($host) . '" target="_blank"
>&nbsp;&nbsp;Licensed</a>';
            return $license;
        } else {
            return '';
        }
    }

    /**
     * 获得网店 license 信息
     *
     * @access  public
     * @param   integer $size
     *
     * @return  array
     */
    public function get_shop_license()
    {
        // 取出网店 license
        $sql = "SELECT code, value
            FROM " . $GLOBALS['ecs']->table('shop_config') . "
            WHERE code IN ('certificate_id', 'token', 'certi')
            LIMIT 0,3";
        $license_info = $GLOBALS['db']->getAll($sql);
        $license_info = is_array($license_info) ? $license_info : [];
        $license = [];
        foreach ($license_info as $value) {
            $license[$value['code']] = $value['value'];
        }

        return $license;
    }

    /**
     * 功能：生成certi_ac验证字段
     * @param   string     POST传递参数
     * @param   string     证书token
     * @return  string
     */
    public function make_shop_ac($post_params, $token)
    {
        if (!is_array($post_params)) {
            return;
        }

        // core
        ksort($post_params);
        $str = '';
        foreach ($post_params as $key => $value) {
            if ($key != 'certi_ac') {
                $str .= $value;
            }
        }

        return md5($str . $token);
    }

    /**
     * 功能：与 ECTouch 交换数据
     *
     * @param   array $certi 登录参数
     * @param   array $license 网店license信息
     * @param   bool $use_lib 使用哪一个json库，0为ec，1为shop
     * @return  array
     */
    public function exchange_shop_license($certi, $license, $use_lib = 0)
    {
        if (!is_array($certi)) {
            return [];
        }

        $params = '';
        foreach ($certi as $key => $value) {
            $params .= '&' . $key . '=' . $value;
        }
        $params = trim($params, '&');

        $transport = new transport;
        //$transport->connect_timeout = 1;
        $request = $transport->request($license['certi'], $params, 'POST');
        $request_str = json_str_iconv($request['body']);

        $request_arr = json_decode($request_str, 1);

        return $request_arr;
    }

    /**
     * 功能：处理登录返回结果
     *
     * @param   array $cert_auth 登录返回的用户信息
     * @return  array
     */
    public function process_login_license($cert_auth)
    {
        if (!is_array($cert_auth)) {
            return [];
        }

        $cert_auth['auth_str'] = trim($cert_auth['auth_str']);
        if (!empty($cert_auth['auth_str'])) {
            $cert_auth['auth_str'] = $GLOBALS['_LANG']['license_' . $cert_auth['auth_str']];
        }

        $cert_auth['auth_type'] = trim($cert_auth['auth_type']);
        if (!empty($cert_auth['auth_type'])) {
            $cert_auth['auth_type'] = $GLOBALS['_LANG']['license_' . $cert_auth['auth_type']];
        }

        return $cert_auth;
    }

    /**
     * 功能：license 登录
     *
     * @param   array $certi_added 配置信息补充数组 array_key 登录信息的key；array_key => array_value；
     * @return  array     $return_array['flag'] = login_succ、login_fail、login_ping_fail、login_param_fail；
     *                    $return_array['request']；
     */
    public function license_login($certi_added = '')
    {
        // 登录信息配置
        $certi['certi_app'] = ''; // 证书方法
        $certi['app_id'] = 'ectouch_b2c'; // 说明客户端来源
        $certi['app_instance_id'] = ''; // 应用服务ID
        $certi['version'] = LICENSE_VERSION; // license接口版本号
        $certi['shop_version'] = VERSION . '#' . RELEASE; // 网店软件版本号
        $certi['certi_url'] = sprintf($GLOBALS['ecs']->url()); // 网店URL
        $certi['certi_session'] = $GLOBALS['sess']->get_session_id(); // 网店SESSION标识
        $certi['certi_validate_url'] = sprintf($GLOBALS['ecs']->url() . 'certi.php'); // 网店提供于官方反查接口
        $certi['format'] = 'json'; // 官方返回数据格式
        $certi['certificate_id'] = ''; // 网店证书ID
        // 标识
        $certi_back['succ'] = 'succ';
        $certi_back['fail'] = 'fail';
        // return 返回数组
        $return_array = [];

        if (is_array($certi_added)) {
            foreach ($certi_added as $key => $value) {
                $certi[$key] = $value;
            }
        }

        // 取出网店 license
        $license = get_shop_license();

        // 检测网店 license
        if (!empty($license['certificate_id']) && !empty($license['token']) && !empty($license['certi'])) {
            // 登录
            $certi['certi_app'] = 'certi.login'; // 证书方法
            $certi['app_instance_id'] = 'cert_auth'; // 应用服务ID
            $certi['certificate_id'] = $license['certificate_id']; // 网店证书ID
            $certi['certi_ac'] = make_shop_ac($certi, $license['token']); // 网店验证字符串

            $request_arr = exchange_shop_license($certi, $license);
            if (is_array($request_arr) && $request_arr['res'] == $certi_back['succ']) {
                $return_array['flag'] = 'login_succ';
                $return_array['request'] = $request_arr;
            } elseif (is_array($request_arr) && $request_arr['res'] == $certi_back['fail']) {
                $return_array['flag'] = 'login_fail';
                $return_array['request'] = $request_arr;
            } else {
                $return_array['flag'] = 'login_ping_fail';
                $return_array['request'] = ['res' => 'fail'];
            }
        } else {
            $return_array['flag'] = 'login_param_fail';
            $return_array['request'] = ['res' => 'fail'];
        }

        return $return_array;
    }

    /**
     * 功能：license 注册
     *
     * @param   array $certi_added 配置信息补充数组 array_key 登录信息的key；array_key => array_value；
     * @return  array     $return_array['flag'] = reg_succ、reg_fail、reg_ping_fail；
     *                    $return_array['request']；
     */
    public function license_reg($certi_added = '')
    {
        // 登录信息配置
        $certi['certi_app'] = ''; // 证书方法
        $certi['app_id'] = 'ectouch_b2c'; // 说明客户端来源
        $certi['app_instance_id'] = ''; // 应用服务ID
        $certi['version'] = LICENSE_VERSION; // license接口版本号
        $certi['shop_version'] = VERSION . '#' . RELEASE; // 网店软件版本号
        $certi['certi_url'] = sprintf($GLOBALS['ecs']->url()); // 网店URL
        $certi['certi_session'] = $GLOBALS['sess']->get_session_id(); // 网店SESSION标识
        $certi['certi_validate_url'] = sprintf($GLOBALS['ecs']->url() . 'certi.php'); // 网店提供于官方反查接口
        $certi['format'] = 'json'; // 官方返回数据格式
        $certi['certificate_id'] = ''; // 网店证书ID
        // 标识
        $certi_back['succ'] = 'succ';
        $certi_back['fail'] = 'fail';
        // return 返回数组
        $return_array = [];

        if (is_array($certi_added)) {
            foreach ($certi_added as $key => $value) {
                $certi[$key] = $value;
            }
        }

        // 取出网店 license
        $license = get_shop_license();

        // 注册
        $certi['certi_app'] = 'certi.reg'; // 证书方法
        $certi['certi_ac'] = make_shop_ac($certi, ''); // 网店验证字符串
        unset($certi['certificate_id']);

        $request_arr = exchange_shop_license($certi, $license);
        if (is_array($request_arr) && $request_arr['res'] == $certi_back['succ']) {
            // 注册信息入库
            $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . "
                SET value = '" . $request_arr['info']['certificate_id'] . "' WHERE code = 'certificate_id'";
            $GLOBALS['db']->query($sql);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . "
                SET value = '" . $request_arr['info']['token'] . "' WHERE code = 'token'";
            $GLOBALS['db']->query($sql);

            $return_array['flag'] = 'reg_succ';
            $return_array['request'] = $request_arr;
            clear_cache_files();
        } elseif (is_array($request_arr) && $request_arr['res'] == $certi_back['fail']) {
            $return_array['flag'] = 'reg_fail';
            $return_array['request'] = $request_arr;
        } else {
            $return_array['flag'] = 'reg_ping_fail';
            $return_array['request'] = ['res' => 'fail'];
        }

        return $return_array;
    }
}
