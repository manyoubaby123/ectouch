<?php

namespace App\Http\Console\Controller;

use App\Libraries\Captcha;
use App\Libraries\Exchange;

class PrivilegeController extends InitController
{
    public function indexAction()
    {
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'login';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化 $exc 对象 */
        $exc = new Exchange($GLOBALS['ecs']->table("admin_user"), $GLOBALS['db'], 'user_id', 'user_name');

        /*------------------------------------------------------ */
        //-- 退出登录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'logout') {
            /* 清除cookie */
            setcookie('ECSCP[admin_id]', '', 1);
            setcookie('ECSCP[admin_pass]', '', 1);

            $GLOBALS['sess']->destroy_session();

            $_REQUEST['act'] = 'login';
        }

        /*------------------------------------------------------ */
        //-- 登陆界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
                $GLOBALS['smarty']->assign('gd_version', gd_version());
                $GLOBALS['smarty']->assign('random', mt_rand());
            }

            return $GLOBALS['smarty']->display('login.htm');
        }

        /*------------------------------------------------------ */
        //-- 验证登陆信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'signin') {
            if (intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) {
                /* 检查验证码是否正确 */
                $validator = new Captcha();
                if (!empty($_POST['captcha']) && !$validator->check_word($_POST['captcha'])) {
                    sys_msg($GLOBALS['_LANG']['captcha_error'], 1);
                }
            }

            $_POST['username'] = isset($_POST['username']) ? trim($_POST['username']) : '';
            $_POST['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';

            $sql = "SELECT `ec_salt` FROM " . $GLOBALS['ecs']->table('admin_user') . "WHERE user_name = '" . $_POST['username'] . "'";
            $ec_salt = $GLOBALS['db']->getOne($sql);
            if (!empty($ec_salt)) {
                /* 检查密码是否正确 */
                $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt" .
                    " FROM " . $GLOBALS['ecs']->table('admin_user') .
                    " WHERE user_name = '" . $_POST['username'] . "' AND password = '" . md5(md5($_POST['password']) . $ec_salt) . "'";
            } else {
                /* 检查密码是否正确 */
                $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt" .
                    " FROM " . $GLOBALS['ecs']->table('admin_user') .
                    " WHERE user_name = '" . $_POST['username'] . "' AND password = '" . md5($_POST['password']) . "'";
            }
            $row = $GLOBALS['db']->getRow($sql);
            if ($row) {
                // 检查是否为供货商的管理员 所属供货商是否有效
                if (!empty($row['suppliers_id'])) {
                    $supplier_is_check = suppliers_list_info(' is_check = 1 AND suppliers_id = ' . $row['suppliers_id']);
                    if (empty($supplier_is_check)) {
                        sys_msg($GLOBALS['_LANG']['login_disable'], 1);
                    }
                }

                // 登录成功
                set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_login']);
                session('suppliers_id', $row['suppliers_id']);
                if (empty($row['ec_salt'])) {
                    $ec_salt = rand(1, 9999);
                    $new_possword = md5(md5($_POST['password']) . $ec_salt);
                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('admin_user') .
                        " SET ec_salt='" . $ec_salt . "', password='" . $new_possword . "'" .
                        " WHERE user_id='". session('admin_id') ."'");
                }

                if ($row['action_list'] == 'all' && empty($row['last_login'])) {
                    session('shop_guide', true);
                }

                // 更新最后登录时间和IP
                $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('admin_user') .
                    " SET last_login='" . gmtime() . "', last_ip='" . real_ip() . "'" .
                    " WHERE user_id='". session('admin_id') ."'");

                if (isset($_POST['remember'])) {
                    $time = gmtime() + 3600 * 24 * 365;
                    setcookie('ECSCP[admin_id]', $row['user_id'], $time);
                    setcookie('ECSCP[admin_pass]', md5($row['password'] . $GLOBALS['_CFG']['hash_code']), $time);
                }

                // 清除购物车中过期的数据
                $this->clear_cart();

                return ecs_header("Location: ./index.php\n");
            } else {
                sys_msg($GLOBALS['_LANG']['login_faild'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 管理员列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_list']);
            $GLOBALS['smarty']->assign('action_link', ['href' => 'privilege.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add']]);
            $GLOBALS['smarty']->assign('full_page', 1);
            $GLOBALS['smarty']->assign('admin_list', $this->get_admin_userlist());

            /* 显示页面 */

            return $GLOBALS['smarty']->display('privilege_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $GLOBALS['smarty']->assign('admin_list', $this->get_admin_userlist());

            return make_json_result($GLOBALS['smarty']->fetch('privilege_list.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('admin_manage');

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_add']);
            $GLOBALS['smarty']->assign('action_link', ['href' => 'privilege.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list']]);
            $GLOBALS['smarty']->assign('form_act', 'insert');
            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('select_role', $this->get_role_list());

            /* 显示页面 */

            return $GLOBALS['smarty']->display('privilege_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 添加管理员的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            admin_priv('admin_manage');
            if ($_POST['token'] != $GLOBALS['_CFG']['token']) {
                sys_msg('add_error', 1);
            }
            /* 判断管理员是否已经存在 */
            if (!empty($_POST['user_name'])) {
                $is_only = $exc->is_only('user_name', $_POST['user_name']);

                if (!$is_only) {
                    sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($_POST['user_name'])), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($_POST['email'])) {
                $is_only = $exc->is_only('email', $_POST['email']);

                if (!$is_only) {
                    sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($_POST['email'])), 1);
                }
            }

            /* 获取添加日期及密码 */
            $add_time = gmtime();

            $password = md5($_POST['password']);
            $role_id = '';
            $action_list = '';
            if (!empty($_POST['select_role'])) {
                $sql = "SELECT action_list FROM " . $GLOBALS['ecs']->table('role') . " WHERE role_id = '" . $_POST['select_role'] . "'";
                $row = $GLOBALS['db']->getRow($sql);
                $action_list = $row['action_list'];
                $role_id = $_POST['select_role'];
            }

            $sql = "SELECT nav_list FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE action_list = 'all'";
            $row = $GLOBALS['db']->getRow($sql);

            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('admin_user') . " (user_name, email, password, add_time, nav_list, action_list, role_id) " .
                "VALUES ('" . trim($_POST['user_name']) . "', '" . trim($_POST['email']) . "', '$password', '$add_time', '$row[nav_list]', '$action_list', '$role_id')";

            $GLOBALS['db']->query($sql);
            /* 转入权限分配列表 */
            $new_id = $GLOBALS['db']->Insert_ID();

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['go_allot_priv'];
            $link[0]['href'] = 'privilege.php?act=allot&id=' . $new_id . '&user=' . $_POST['user_name'] . '';

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'privilege.php?act=add';

            sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'privilege');
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 不能编辑demo这个管理员 */
            if (session('admin_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'privilege.php?act=list'];
                sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('admin_id') != $_REQUEST['id']) {
                admin_priv('admin_manage');
            }

            /* 获取管理员信息 */
            $sql = "SELECT user_id, user_name, email, password, agency_id, role_id FROM " . $GLOBALS['ecs']->table('admin_user') .
                " WHERE user_id = '" . $_REQUEST['id'] . "'";
            $user_info = $GLOBALS['db']->getRow($sql);

            /* 取得该管理员负责的办事处名称 */
            if ($user_info['agency_id'] > 0) {
                $sql = "SELECT agency_name FROM " . $GLOBALS['ecs']->table('agency') . " WHERE agency_id = '$user_info[agency_id]'";
                $user_info['agency_name'] = $GLOBALS['db']->getOne($sql);
            }

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['admin_edit']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['admin_list'], 'href' => 'privilege.php?act=list']);
            $GLOBALS['smarty']->assign('user', $user_info);

            /* 获得该管理员的权限 */
            $priv_str = $GLOBALS['db']->getOne("SELECT action_list FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$_GET[id]'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str != 'all') {
                $GLOBALS['smarty']->assign('select_role', $this->get_role_list());
            }
            $GLOBALS['smarty']->assign('form_act', 'update');
            $GLOBALS['smarty']->assign('action', 'edit');

            return $GLOBALS['smarty']->display('privilege_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update' || $_REQUEST['act'] == 'update_self') {

            /* 变量初始化 */
            $admin_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $admin_name = !empty($_REQUEST['user_name']) ? trim($_REQUEST['user_name']) : '';
            $admin_email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
            $ec_salt = rand(1, 9999);
            $password = !empty($_POST['new_password']) ? ", password = '" . md5(md5($_POST['new_password']) . $ec_salt) . "'" : '';
            if ($_POST['token'] != $GLOBALS['_CFG']['token']) {
                sys_msg('update_error', 1);
            }
            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('admin_id') != $_REQUEST['id']) {
                    admin_priv('admin_manage');
                }
                $g_link = 'privilege.php?act=list';
                $nav_list = '';
            } else {
                $nav_list = !empty($_POST['nav_list']) ? ", nav_list = '" . @join(",", $_POST['nav_list']) . "'" : '';
                $admin_id = session('admin_id');
                $g_link = 'privilege.php?act=modif';
            }
            /* 判断管理员是否已经存在 */
            if (!empty($admin_name)) {
                $is_only = $exc->num('user_name', $admin_name, $admin_id);
                if ($is_only == 1) {
                    sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($admin_name)), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($admin_email)) {
                $is_only = $exc->num('email', $admin_email, $admin_id);

                if ($is_only == 1) {
                    sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($admin_email)), 1);
                }
            }

            //如果要修改密码
            $pwd_modified = false;

            if (!empty($_POST['new_password'])) {
                /* 查询旧密码并与输入的旧密码比较是否相同 */
                $sql = "SELECT password FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$admin_id'";
                $old_password = $GLOBALS['db']->getOne($sql);
                $sql = "SELECT ec_salt FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$admin_id'";
                $old_ec_salt = $GLOBALS['db']->getOne($sql);
                if (empty($old_ec_salt)) {
                    $old_ec_password = md5($_POST['old_password']);
                } else {
                    $old_ec_password = md5(md5($_POST['old_password']) . $old_ec_salt);
                }
                if ($old_password <> $old_ec_password) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    sys_msg($GLOBALS['_LANG']['pwd_error'], 0, $link);
                }

                /* 比较新密码和确认密码是否相同 */
                if ($_POST['new_password'] <> $_POST['pwd_confirm']) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    sys_msg($GLOBALS['_LANG']['js_languages']['password_error'], 0, $link);
                } else {
                    $pwd_modified = true;
                }
            }

            $role_id = '';
            $action_list = '';
            if (!empty($_POST['select_role'])) {
                $sql = "SELECT action_list FROM " . $GLOBALS['ecs']->table('role') . " WHERE role_id = '" . $_POST['select_role'] . "'";
                $row = $GLOBALS['db']->getRow($sql);
                $action_list = ', action_list = \'' . $row['action_list'] . '\'';
                $role_id = ', role_id = ' . $_POST['select_role'] . ' ';
            }
            //更新管理员信息
            if ($pwd_modified) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email', " .
                    "ec_salt = '$ec_salt' " .
                    $action_list .
                    $role_id .
                    $password .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            } else {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email' " .
                    $action_list .
                    $role_id .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            }

            $GLOBALS['db']->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified && $_REQUEST['act'] == 'update_self') {
                $GLOBALS['sess']->delete_spec_admin_session(session('admin_id'));
                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = ['text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['modif_info'], 'href' => $g_link];
            sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑个人资料
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'modif') {
            /* 不能编辑demo这个管理员 */
            if (session('admin_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
                sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            load_helper(['menu', 'priv'], 'admin');

            /* 包含插件菜单语言项 */
            $sql = "SELECT code FROM " . $GLOBALS['ecs']->table('plugins');
            $rs = $GLOBALS['db']->query($sql);
            foreach ($rs as $row) {
                /* 取得语言项 */
                if (file_exists(ROOT_PATH . 'plugins/' . $row['code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php')) {
                    include_once(ROOT_PATH . 'plugins/' . $row['code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
                }

                /* 插件的菜单项 */
                if (file_exists(ROOT_PATH . 'plugins/' . $row['code'] . '/languages/inc_menu.php')) {
                    include_once(ROOT_PATH . 'plugins/' . $row['code'] . '/languages/inc_menu.php');
                }
            }

            foreach ($modules as $key => $value) {
                ksort($modules[$key]);
            }
            ksort($modules);

            foreach ($modules as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        if (is_array($purview[$k])) {
                            $boole = false;
                            foreach ($purview[$k] as $action) {
                                $boole = $boole || admin_priv($action, '', false);
                            }
                            if (!$boole) {
                                unset($modules[$key][$k]);
                            }
                        } elseif (!admin_priv($purview[$k], '', false)) {
                            unset($modules[$key][$k]);
                        }
                    }
                }
            }

            /* 获得当前管理员数据信息 */
            $sql = "SELECT user_id, user_name, email, nav_list " .
                "FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '" . session('admin_id') . "'";
            $user_info = $GLOBALS['db']->getRow($sql);

            /* 获取导航条 */
            $nav_arr = (trim($user_info['nav_list']) == '') ? [] : explode(",", $user_info['nav_list']);
            $nav_lst = [];
            foreach ($nav_arr as $val) {
                $arr = explode('|', $val);
                $nav_lst[$arr[1]] = $arr[0];
            }

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['modif_info']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['admin_list'], 'href' => 'privilege.php?act=list']);
            $GLOBALS['smarty']->assign('user', $user_info);
            $GLOBALS['smarty']->assign('menus', $modules);
            $GLOBALS['smarty']->assign('nav_arr', $nav_lst);

            $GLOBALS['smarty']->assign('form_act', 'update_self');
            $GLOBALS['smarty']->assign('action', 'modif');

            /* 显示页面 */

            return $GLOBALS['smarty']->display('privilege_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 为管理员分配权限
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'allot') {
            load_lang('priv_action', 'admin');

            admin_priv('allot_priv');
            if (session('admin_id') == $_GET['id']) {
                admin_priv('all');
            }

            /* 获得该管理员的权限 */
            $priv_str = $GLOBALS['db']->getOne("SELECT action_list FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$_GET[id]'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
                sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id = 0";
            $res = $GLOBALS['db']->query($sql_query);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            /* 按权限组查询底级的权限名称 */
            $sql = "SELECT action_id, parent_id, action_code,relevance FROM " . $GLOBALS['ecs']->table('admin_action') .
                " WHERE parent_id " . db_create_in(array_keys($priv_arr));
            $result = $GLOBALS['db']->query($sql);
            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            // 将同一组的权限使用 "," 连接起来，供JS全选
            foreach ($priv_arr as $action_id => $action_group) {
                $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

                foreach ($action_group['priv'] as $key => $val) {
                    $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                }
            }

            /* 赋值 */
            $GLOBALS['smarty']->assign('lang', $GLOBALS['_LANG']);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['allot_priv'] . ' [ ' . $_GET['user'] . ' ] ');
            $GLOBALS['smarty']->assign('action_link', ['href' => 'privilege.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list']]);
            $GLOBALS['smarty']->assign('priv_arr', $priv_arr);
            $GLOBALS['smarty']->assign('form_act', 'update_allot');
            $GLOBALS['smarty']->assign('user_id', $_GET['id']);

            /* 显示页面 */

            return $GLOBALS['smarty']->display('privilege_allot.htm');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update_allot') {
            admin_priv('admin_manage');
            if ($_POST['token'] != $GLOBALS['_CFG']['token']) {
                sys_msg('update_allot_error', 1);
            }
            /* 取得当前管理员用户名 */
            $admin_name = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$_POST[id]'");

            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_user') . " SET action_list = '$act_list', role_id = '' " .
                "WHERE user_id = '$_POST[id]'";

            $GLOBALS['db']->query($sql);
            /* 动态更新管理员的SESSION */
            if (session('"admin_id"') == $_POST['id']) {
                session("action_list", $act_list);
            }

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
            sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个管理员
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('admin_drop');

            $id = intval($_GET['id']);

            /* 获得管理员用户名 */
            $admin_name = $GLOBALS['db']->getOne('SELECT user_name FROM ' . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id='$id'");

            /* demo这个管理员不允许删除 */
            if ($admin_name == 'demo') {
                return make_json_error($GLOBALS['_LANG']['edit_remove_cannot']);
            }

            /* ID为1的不允许删除 */
            if ($id == 1) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot']);
            }

            /* 管理员不能删除自己 */
            if ($id == session('admin_id')) {
                return make_json_error($GLOBALS['_LANG']['remove_self_cannot']);
            }

            if ($exc->drop($id)) {
                $GLOBALS['sess']->delete_spec_admin_session($id); // 删除session中该管理员的记录

                admin_log(addslashes($admin_name), 'remove', 'privilege');
                clear_cache_files();
            }

            $url = 'privilege.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }
    }

    /* 获取管理员列表 */
    private function get_admin_userlist()
    {
        $list = [];
        $sql = 'SELECT user_id, user_name, email, add_time, last_login ' .
            'FROM ' . $GLOBALS['ecs']->table('admin_user') . ' ORDER BY user_id DESC';
        $list = $GLOBALS['db']->getAll($sql);

        foreach ($list as $key => $val) {
            $list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
            $list[$key]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $val['last_login']);
        }

        return $list;
    }

    /* 清除购物车中过期的数据 */
    private function clear_cart()
    {
        /* 取得有效的session */
        $sql = "SELECT DISTINCT session_id " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " .
            $GLOBALS['ecs']->table('sessions') . " AS s " .
            "WHERE c.session_id = s.sesskey ";
        $valid_sess = ''; // $GLOBALS['db']->getCol($sql);

        // 删除cart中无效的数据
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id NOT " . db_create_in($valid_sess);
        $GLOBALS['db']->query($sql);
    }

    /* 获取角色列表 */
    private function get_role_list()
    {
        $list = [];
        $sql = 'SELECT role_id, role_name, action_list ' .
            'FROM ' . $GLOBALS['ecs']->table('role');
        $list = $GLOBALS['db']->getAll($sql);
        return $list;
    }
}
