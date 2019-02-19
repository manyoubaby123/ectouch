<?php

namespace app\shop\controller;

use app\libraries\Error;
use app\libraries\Mysql;
use app\libraries\Shop;
use app\services\CategoryService;
use app\services\ConfigService;
use app\services\LicenseService;
use app\services\ShopService;
use app\services\StatService;
use app\services\UserService;
use think\Controller;

class Init extends Controller
{
    /**
     * @var ShopService
     */
    protected $shopService;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var LicenseService
     */
    protected $licenseService;

    protected function initialize()
    {
        if (!file_exists(storage_path('install.lock'))) {
            // header("Location: ./install/index.php\n");
        }

        define('PHP_SELF', parse_name($this->request->controller()));

        load_helper(['time', 'base', 'common', 'main', 'insert', 'goods', 'article']);

        /* 对用户传入的变量进行转义操作。*/
        $_GET = $this->request->get();
        $_POST = $this->request->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = $this->request->request('act');

        $GLOBALS['ecs'] = new Shop();
        define('DATA_DIR', $GLOBALS['ecs']->data_dir());
        define('IMAGE_DIR', $GLOBALS['ecs']->image_dir());

        /* 初始化数据库类 */
        $GLOBALS['db'] = new Mysql();

        /* 创建错误处理对象 */
        $GLOBALS['err'] = new Error('message');

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

            session('user_id', 0);
            session('user_name', '');
            session('email', '');
            session('user_rank', 0);
            session('discount', 1.00);
        }

        if (!defined('INIT_NO_USERS')) {
            define('SESS_ID', session_id());
        }

        if (isset($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);
        }

        $this->assign('lang', $GLOBALS['_LANG']);
        $this->assign('ecs_charset', EC_CHARSET);
        if (!empty($GLOBALS['_CFG']['stylename'])) {
            $this->assign('ecs_css_path', asset('themes/' . $GLOBALS['_CFG']['template'] . '/style_' . $GLOBALS['_CFG']['stylename'] . '.css'));
        } else {
            $this->assign('ecs_css_path', asset('themes/' . $GLOBALS['_CFG']['template'] . '/style.css'));
        }

        if (!defined('INIT_NO_USERS')) {
            /* 会员信息 */
            $this->userService = new UserService();
            $GLOBALS['user'] = $this->userService->init_users();

            if (!session('?user_id')) {
                /* 获取投放站点的名称 */
                $site_name = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($GLOBALS['_LANG']['self_site']);
                $from_ad = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

                session('from_ad', $from_ad); // 用户点击的广告ID
                session('referer', stripslashes($site_name)); // 用户来源

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
                    session('user_id', 0);
                    session('user_name', '');
                    session('email', '');
                    session('user_rank', 0);
                    session('discount', 1.00);
                    if (!session('?login_fail')) {
                        session('login_fail', 0);
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
                    session('user_id', $row['user_id']);
                    session('user_name', $row['user_name']);
                    update_user_info();
                }
            }
        }

        $this->shopService = new ShopService();
    }

    /**
     * 模板公共变量赋值
     * @param string $cType
     * @param array $catList
     */
    protected function assign_template($cType = '', $catList = [])
    {
        $this->licenseService = new LicenseService();
        $this->categoryService = new CategoryService();

        $this->assign('image_width', $GLOBALS['_CFG']['image_width']);
        $this->assign('image_height', $GLOBALS['_CFG']['image_height']);
        $this->assign('points_name', $GLOBALS['_CFG']['integral_name']);
        $this->assign('qq', explode(',', $GLOBALS['_CFG']['qq']));
        $this->assign('ww', explode(',', $GLOBALS['_CFG']['ww']));
        $this->assign('ym', explode(',', $GLOBALS['_CFG']['ym']));
        $this->assign('msn', explode(',', $GLOBALS['_CFG']['msn']));
        $this->assign('skype', explode(',', $GLOBALS['_CFG']['skype']));
        $this->assign('stats_code', $GLOBALS['_CFG']['stats_code']);
        $this->assign('copyright', sprintf($GLOBALS['_LANG']['copyright'], date('Y'), $GLOBALS['_CFG']['shop_name']));
        $this->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $this->assign('service_email', $GLOBALS['_CFG']['service_email']);
        $this->assign('service_phone', $GLOBALS['_CFG']['service_phone']);
        $this->assign('shop_address', $GLOBALS['_CFG']['shop_address']);
        $this->assign('licensed', $this->licenseService->license_info());
        $this->assign('ecs_version', VERSION);
        $this->assign('icp_number', $GLOBALS['_CFG']['icp_number']);
        $this->assign('username', !empty(session('user_name')) ? session('user_name') : '');
        $this->assign('category_list', $this->categoryService->cat_list(0, 0, true, 2, false));
        $this->assign('catalog_list', $this->categoryService->cat_list(0, 0, false, 1, false));
        $this->assign('navigator_list', $this->shopService->get_navigator($cType, $catList));  //自定义导航栏

        if (!empty($GLOBALS['_CFG']['search_keywords'])) {
            $searchkeywords = explode(',', trim($GLOBALS['_CFG']['search_keywords']));
        } else {
            $searchkeywords = [];
        }
        $this->assign('searchkeywords', $searchkeywords);
    }
}
