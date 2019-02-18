<?php

namespace App\Http\Controllers\Console;

use think\Controller;
use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Services\ConfigService;

class InitController extends Controller
{
    protected function initialize()
    {
        define('ECS_ADMIN', true);

        $PHP_SELF = request()->getPathInfo();
        define('PHP_SELF', empty($PHP_SELF) ? 'index.php' : $PHP_SELF);

        load_helper(['time', 'base', 'common']);
        load_helper('main', 'admin');

        /* 对用户传入的变量进行转义操作。*/
        $_GET = request()->query();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = request()->get('act');

        $GLOBALS['ecs'] = new Shop();
        define('DATA_DIR', $GLOBALS['ecs']->data_dir());
        define('IMAGE_DIR', $GLOBALS['ecs']->image_dir());

        /* 初始化数据库类 */
        $GLOBALS['db'] = new Mysql();

        /* 创建错误处理对象 */
        $GLOBALS['err'] = new Error('message.htm');

        /* 载入系统参数 */
        $configService = new ConfigService();
        $GLOBALS['_CFG'] = $configService->load_config();

        load_lang(['common', 'log_action', basename(PHP_SELF, '.php')], 'admin');

        /* 创建 Smarty 对象。*/
        $GLOBALS['smarty'] = new Template();

        $GLOBALS['smarty']->template_dir = resource_path('views/admin');
        $GLOBALS['smarty']->compile_dir = storage_path('temp/compiled/admin');
        if (config('app.debug')) {
            $GLOBALS['smarty']->force_compile = true;
        }

        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
        $GLOBALS['smarty']->assign('help_open', $GLOBALS['_CFG']['help_open']);

        if (isset($GLOBALS['_CFG']['enable_order_check'])) {
            $GLOBALS['smarty']->assign('enable_order_check', $GLOBALS['_CFG']['enable_order_check']);
        } else {
            $GLOBALS['smarty']->assign('enable_order_check', 0);
        }

        /* 验证管理员身份 */
        if ((!session()->has('admin_id') || intval(session('admin_id')) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order') {
            /* session 不存在，检查cookie */
            if (!empty($_COOKIE['ECSCP']['admin_id']) && !empty($_COOKIE['ECSCP']['admin_pass'])) {
                // 找到了cookie, 验证cookie信息
                $sql = 'SELECT user_id, user_name, password, action_list, last_login ' .
                    ' FROM ' . $GLOBALS['ecs']->table('admin_user') .
                    " WHERE user_id = '" . intval($_COOKIE['ECSCP']['admin_id']) . "'";
                $row = $GLOBALS['db']->getRow($sql);

                if (!$row) {
                    // 没有找到这个记录
                    setcookie($_COOKIE['ECSCP']['admin_id'], '', 1);
                    setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1);

                    if (!empty($_REQUEST['is_ajax'])) {
                        return make_json_error($GLOBALS['_LANG']['priv_error']);
                    } else {
                        return ecs_header("Location: privilege.php?act=login\n");
                    }
                } else {
                    // 检查密码是否正确
                    if (md5($row['password'] . $GLOBALS['_CFG']['hash_code']) == $_COOKIE['ECSCP']['admin_pass']) {
                        !isset($row['last_time']) && $row['last_time'] = '';
                        set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_time']);

                        // 更新最后登录时间和IP
                        $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('admin_user') .
                            " SET last_login = '" . gmtime() . "', last_ip = '" . real_ip() . "'" .
                            " WHERE user_id = '" . session('admin_id') . "'");
                    } else {
                        setcookie($_COOKIE['ECSCP']['admin_id'], '', 1);
                        setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1);

                        if (!empty($_REQUEST['is_ajax'])) {
                            return make_json_error($GLOBALS['_LANG']['priv_error']);
                        } else {
                            return ecs_header("Location: privilege.php?act=login\n");
                        }
                    }
                }
            } else {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return ecs_header("Location: privilege.php?act=login\n");
                }
            }
        }

        $GLOBALS['smarty']->assign('token', $GLOBALS['_CFG']['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order') {
            $admin_path = preg_replace('/:\d+/', '', $GLOBALS['ecs']->url()) . ADMIN_PATH;
            if (!empty($_SERVER['HTTP_REFERER']) &&
                strpos(preg_replace('/:\d+/', '', $_SERVER['HTTP_REFERER']), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return ecs_header("Location: privilege.php?act=login\n");
                }
            }
        }
    }
}
