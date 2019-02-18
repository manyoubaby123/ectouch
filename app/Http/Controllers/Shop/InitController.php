<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Services\ConfigService;
use App\Services\ShopService;
use App\Services\StatService;
use App\Services\UserService;

class InitController extends Controller
{
    /**
     * @var ShopService
     */
    protected $shopService;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * InitController constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    protected function initialize()
    {
        if (!file_exists(storage_path('install.lock'))) {
            // header("Location: ./install/index.php\n");
        }

        $PHP_SELF = request()->getPathInfo();
        define('PHP_SELF', empty($PHP_SELF) ? 'index.php' : $PHP_SELF);

        load_helper(['time', 'base', 'common', 'main', 'insert', 'goods', 'article']);

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
        $GLOBALS['err'] = new Error('message.dwt');

        /* 载入系统参数 */
        $configService = new ConfigService();
        $GLOBALS['_CFG'] = $configService->load_config();

        /* 载入语言文件 */
        load_lang('common');

        if ($GLOBALS['_CFG']['shop_closed'] == 1) {
            /* 商店关闭了，输出关闭的消息 */
            header('Content-type: text/html; charset=' . EC_CHARSET);

            return '<div style="margin: 150px; text-align: center; font-size: 14px"><p>' . $GLOBALS['_LANG']['shop_closed'] . '</p><p>' . $GLOBALS['_CFG']['close_comment'] . '</p></div>';
        }

        if (is_spider()) {
            /* 如果是蜘蛛的访问，那么默认为访客方式，并且不记录到日志中 */
            if (!defined('INIT_NO_USERS')) {
                define('INIT_NO_USERS', true);
                /* 整合UC后，如果是蜘蛛访问，初始化UC需要的常量 */
                if ($GLOBALS['_CFG']['integrate_code'] == 'ucenter') {
                    $GLOBALS['user'] = &init_users();
                }
            }

            session(['user_id' => 0]);
            session(['user_name' => '']);
            session(['email' => '']);
            session(['user_rank' => 0]);
            session(['discount' => 1.00]);
        }

        if (!defined('INIT_NO_USERS')) {
            define('SESS_ID', session_id());
        }

        if (isset($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);
        }

        if (!defined('INIT_NO_SMARTY')) {
            header('Cache-control: private');
            header('Content-type: text/html; charset=' . EC_CHARSET);

            /* 创建 Smarty 对象。*/
            $GLOBALS['smarty'] = new Template();

            $GLOBALS['smarty']->cache_lifetime = $GLOBALS['_CFG']['cache_time'];
            $GLOBALS['smarty']->template_dir = resource_path('views/' . $GLOBALS['_CFG']['template']);
            $GLOBALS['smarty']->cache_dir = storage_path('temp/caches');
            $GLOBALS['smarty']->compile_dir = storage_path('temp/compiled');

            if (config('app.debug')) {
                $GLOBALS['smarty']->direct_output = true;
                $GLOBALS['smarty']->force_compile = true;
            } else {
                $GLOBALS['smarty']->direct_output = false;
                $GLOBALS['smarty']->force_compile = false;
            }

            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('ecs_charset', EC_CHARSET);
            if (!empty($GLOBALS['_CFG']['stylename'])) {
                $GLOBALS['smarty']->assign('ecs_css_path', asset('themes/' . $GLOBALS['_CFG']['template'] . '/style_' . $GLOBALS['_CFG']['stylename'] . '.css'));
            } else {
                $GLOBALS['smarty']->assign('ecs_css_path', asset('themes/' . $GLOBALS['_CFG']['template'] . '/style.css'));
            }
        }

        if (!defined('INIT_NO_USERS')) {
            /* 会员信息 */
            $GLOBALS['user'] = $this->userService->init_users();

            if (!session()->has('user_id')) {
                /* 获取投放站点的名称 */
                $site_name = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($GLOBALS['_LANG']['self_site']);
                $from_ad = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

                session(['from_ad' => $from_ad]); // 用户点击的广告ID
                session(['referer' => stripslashes($site_name)]); // 用户来源

                unset($site_name);

                if (!defined('INGORE_VISIT_STATS')) {
                    $statService = new StatService();
                    $statService->visit_stats();
                }
            }

            if (empty(session('user_id'))) {
                if ($GLOBALS['user']->get_cookie()) {
                    /* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
                    if (session('user_id') > 0) {
                        update_user_info();
                    }
                } else {
                    session(['user_id' => 0]);
                    session(['user_name' => '']);
                    session(['email' => '']);
                    session(['user_rank' => 0]);
                    session(['discount' => 1.00]);
                    if (!session()->has('login_fail')) {
                        session(['login_fail' => 0]);
                    }
                }
            }

            /* 设置推荐会员 */
            if (isset($_GET['u'])) {
                set_affiliate();
            }

            /* session 不存在，检查cookie */
            if (!empty($_COOKIE['ECS']['user_id']) && !empty($_COOKIE['ECS']['password'])) {
                // 找到了cookie, 验证cookie信息
                $sql = 'SELECT user_id, user_name, password ' .
                    ' FROM ' . $GLOBALS['ecs']->table('users') .
                    " WHERE user_id = '" . intval($_COOKIE['ECS']['user_id']) . "' AND password = '" . $_COOKIE['ECS']['password'] . "'";

                $row = $GLOBALS['db']->getRow($sql);

                if (!$row) {
                    // 没有找到这个记录
                    $time = time() - 3600;
                    setcookie("ECS[user_id]", '', $time, '/');
                    setcookie("ECS[password]", '', $time, '/');
                } else {
                    session(['user_id' => $row['user_id']]);
                    session(['user_name' => $row['user_name']]);
                    update_user_info();
                }
            }
        }

        $this->shopService = new ShopService();
    }
}
