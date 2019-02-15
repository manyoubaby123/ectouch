<?php

namespace app\modules\console\controllers;

use app\libraries\Error;
use app\libraries\Mysql;
use app\libraries\Shop;
use app\libraries\Template;
use app\libraries\Transport;
use app\services\ConfigService;
use yii\web\Controller;

class InitController extends Controller
{
    public function init()
    {
        define('ECS_ADMIN', true);
        dd(\Yii::$app->request);
        define('PHP_SELF', parse_name(CONTROLLER_NAME) . '.php');

        load_helper(['time', 'base', 'common']);
        load_helper('main', 'admin');

        /* 对用户传入的变量进行转义操作。*/
        $_GET = app('request')->get();
        $_POST = app('request')->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

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

        load_lang(['common', 'log_action', PHP_SELF], 'admin');

        /* 创建 Smarty 对象。*/
        $GLOBALS['smarty'] = new Template();

        $GLOBALS['smarty']->template_dir = resource_path('views/admin');
        $GLOBALS['smarty']->compile_dir = storage_path('temp/compiled/admin');
        if (config('app_debug')) {
            $GLOBALS['smarty']->force_compile = true;
        }

        $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
        $GLOBALS['smarty']->assign('help_open', $GLOBALS['_CFG']['help_open']);

        if (isset($GLOBALS['_CFG']['enable_order_check'])) {
            $GLOBALS['smarty']->assign('enable_order_check', $GLOBALS['_CFG']['enable_order_check']);
        } else {
            $GLOBALS['smarty']->assign('enable_order_check', 0);
        }

        /* 验证通行证信息 */
        if (isset($_GET['ent_id']) && isset($_GET['ent_ac']) && isset($_GET['ent_sign']) && isset($_GET['ent_email'])) {
            $ent_id = trim($_GET['ent_id']);
            $ent_ac = trim($_GET['ent_ac']);
            $ent_sign = trim($_GET['ent_sign']);
            $ent_email = trim($_GET['ent_email']);
            $certificate_id = trim($GLOBALS['_CFG']['certificate_id']);
            $domain_url = $GLOBALS['ecs']->url();
            $token = $_GET['token'];
            if ($token == md5(md5($GLOBALS['_CFG']['token']) . $domain_url . ADMIN_PATH)) {
                $t = new Transport('-1', 5);
                $apiget = "act=ent_sign&ent_id= $ent_id & certificate_id=$certificate_id";

                $t->request('http://www.ectouch.cn/api.php', $apiget);
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('shop_config') . ' SET value = "' . $ent_id . '" WHERE code = "ent_id"');
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('shop_config') . ' SET value = "' . $ent_ac . '" WHERE code = "ent_ac"');
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('shop_config') . ' SET value = "' . $ent_sign . '" WHERE code = "ent_sign"');
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('shop_config') . ' SET value = "' . $ent_email . '" WHERE code = "ent_email"');
                clear_cache_files();
                return ecs_header("Location: ./index.php\n");
            }
        }

        /* 验证管理员身份 */
        if ((!session('?admin_id') || intval(session('admin_id')) <= 0) &&
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
