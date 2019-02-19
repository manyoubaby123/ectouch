<?php

namespace app\shop\controller;

use app\libraries\Captcha;

class User extends Init
{
    public function index()
    {
        load_lang('user');

        $user_id = session('user_id');
        $action = app('request')->get('act', 'default');

        $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
        $this->assign('affiliate', $affiliate);
        $back_act = '';

        // 不需要登录的操作或自己验证是否登录（如ajax处理）的act
        $not_login_arr =
            ['login', 'act_login', 'register', 'act_register', 'act_edit_password', 'get_password', 'send_pwd_email', 'password', 'signin', 'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered', 'check_email', 'clear_history', 'qpassword_name', 'get_passwd_question', 'check_answer'];

        /* 显示页面的action列表 */
        $ui_arr = ['register', 'login', 'profile', 'order_list', 'order_detail', 'address_list', 'collection_list',
            'message_list', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'add_booking', 'account_raply',
            'account_deposit', 'account_log', 'account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail', 'affiliate', 'comment_list', 'validate_email', 'track_packages', 'transform_points', 'qpassword_name', 'get_passwd_question', 'check_answer'];

        /* 未登录处理 */
        if (empty(session('user_id'))) {
            if (!in_array($action, $not_login_arr)) {
                if (in_array($action, $ui_arr)) {
                    /* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
                    if ($action == 'login')
                    {
                        if (isset($_REQUEST['back_act']))
                        {
                            $back_act = trim($_REQUEST['back_act']);
                        }
                    }
                    else
                    {}*/
                    if (!empty($_SERVER['QUERY_STRING'])) {
                        $back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
                    }
                    $action = 'login';
                } else {
                    //未登录提交数据。非正常途径提交数据！
                    return $GLOBALS['_LANG']['require_login'];
                }
            }
        }

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr)) {
            $this->assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_center']);
            $this->assign('page_title', $position['title']); // 页面标题
            $this->assign('ur_here', $position['ur_here']);
            $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE id = 419";
            $row = $GLOBALS['db']->getRow($sql);
            $car_off = $row['value'];
            $this->assign('car_off', $car_off);
            /* 是否显示积分兑换 */
            if (!empty($GLOBALS['_CFG']['points_rule']) && unserialize($GLOBALS['_CFG']['points_rule'])) {
                $this->assign('show_transform_points', 1);
            }
            $this->assign('helps', get_shop_help());        // 网店帮助
            $this->assign('data_dir', DATA_DIR);   // 数据目录
            $this->assign('action', $action);
            $this->assign('lang', $GLOBALS['_LANG']);
        }

        //用户中心欢迎页
        if ($action == 'default') {
            load_helper('clips');
            if ($rank = get_rank_info()) {
                $this->assign('rank_name', sprintf($GLOBALS['_LANG']['your_level'], $rank['rank_name']));
                if (!empty($rank['next_rank_name'])) {
                    $this->assign('next_rank_name', sprintf($GLOBALS['_LANG']['next_level'], $rank['next_rank'], $rank['next_rank_name']));
                }
            }
            $this->assign('info', get_user_default($user_id));
            $this->assign('user_notice', $GLOBALS['_CFG']['user_notice']);
            $this->assign('prompt', get_user_prompt($user_id));
            return $this->fetch('user_clips');
        }

        /* 显示会员注册界面 */
        if ($action == 'register') {
            if ((!isset($back_act) || empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
            }

            /* 取出注册扩展字段 */
            $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
            $extend_info_list = $GLOBALS['db']->getAll($sql);
            $this->assign('extend_info_list', $extend_info_list);

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }

            /* 密码提示问题 */
            $this->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            /* 增加是否关闭注册 */
            $this->assign('shop_reg_closed', $GLOBALS['_CFG']['shop_reg_closed']);
//    $this->assign('back_act', $back_act);
            return $this->fetch('user_passport');
        } /* 注册会员的处理 */
        elseif ($action == 'act_register') {
            /* 增加是否关闭注册 */
            if ($GLOBALS['_CFG']['shop_reg_closed']) {
                $this->assign('action', 'register');
                $this->assign('shop_reg_closed', $GLOBALS['_CFG']['shop_reg_closed']);
                return $this->fetch('user_passport');
            } else {
                load_helper('passport');

                $username = isset($_POST['username']) ? trim($_POST['username']) : '';
                $password = isset($_POST['password']) ? trim($_POST['password']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
                $other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
                $other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
                $other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
                $other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
                $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
                $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

                $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

                if (empty($_POST['agreement'])) {
                    return show_message($GLOBALS['_LANG']['passport_js']['agreement']);
                }
                if (strlen($username) < 3) {
                    return show_message($GLOBALS['_LANG']['passport_js']['username_shorter']);
                }

                if (strlen($password) < 6) {
                    return show_message($GLOBALS['_LANG']['passport_js']['password_shorter']);
                }

                if (strpos($password, ' ') > 0) {
                    return show_message($GLOBALS['_LANG']['passwd_balnk']);
                }

                /* 验证码检查 */
                if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
                    if (empty($_POST['captcha'])) {
                        return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['sign_up'], 'user.php?act=register', 'error');
                    }

                    /* 检查验证码 */
                    $validator = new Captcha();
                    if (!$validator->check_word($_POST['captcha'])) {
                        return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['sign_up'], 'user.php?act=register', 'error');
                    }
                }

                if (register($username, $password, $email, $other) !== false) {
                    /*把新注册用户的扩展信息插入数据库*/
                    $sql = 'SELECT id FROM ' . $GLOBALS['ecs']->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
                    $fields_arr = $GLOBALS['db']->getAll($sql);

                    $extend_field_str = '';    //生成扩展字段的内容字符串
                    foreach ($fields_arr as $val) {
                        $extend_field_index = 'extend_field' . $val['id'];
                        if (!empty($_POST[$extend_field_index])) {
                            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
                            $extend_field_str .= " ('" . session('user_id') . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";
                        }
                    }
                    $extend_field_str = substr($extend_field_str, 0, -1);

                    if ($extend_field_str) {      //插入注册扩展数据
                        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
                        $GLOBALS['db']->query($sql);
                    }

                    /* 写入密码提示问题和答案 */
                    if (!empty($passwd_answer) && !empty($sel_question)) {
                        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . session('user_id') . "'";
                        $GLOBALS['db']->query($sql);
                    }
                    /* 判断是否需要自动发送注册邮件 */
                    if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email']) {
                        send_regiter_hash(session('user_id'));
                    }
                    $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;
                    return show_message(sprintf($GLOBALS['_LANG']['register_success'], $username . $ucdata), [$GLOBALS['_LANG']['back_up_page'], $GLOBALS['_LANG']['profile_lnk']], [$back_act, 'user.php'], 'info');
                } else {
                    return $GLOBALS['err']->show($GLOBALS['_LANG']['sign_up'], 'user.php?act=register');
                }
            }
        } /* 验证用户注册邮件 */
        elseif ($action == 'validate_email') {
            $hash = empty($_GET['hash']) ? '' : trim($_GET['hash']);
            if ($hash) {
                load_helper('passport');
                $id = register_hash('decode', $hash);
                if ($id > 0) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET is_validated = 1 WHERE user_id='$id'";
                    $GLOBALS['db']->query($sql);
                    $sql = 'SELECT user_name, email FROM ' . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$id'";
                    $row = $GLOBALS['db']->getRow($sql);
                    return show_message(sprintf($GLOBALS['_LANG']['validate_ok'], $row['user_name'], $row['email']), $GLOBALS['_LANG']['profile_lnk'], 'user.php');
                }
            }
            return show_message($GLOBALS['_LANG']['validate_fail']);
        } /* 验证用户注册用户名是否可以注册 */
        elseif ($action == 'is_registered') {
            load_helper('passport');

            $username = trim($_GET['username']);
            $username = json_str_iconv($username);

            if ($GLOBALS['user']->check_user($username) || admin_registered($username)) {
                echo 'false';
            } else {
                echo 'true';
            }
        } /* 验证用户邮箱地址是否被注册 */
        elseif ($action == 'check_email') {
            $email = trim($_GET['email']);
            if ($GLOBALS['user']->check_email($email)) {
                echo 'false';
            } else {
                echo 'ok';
            }
        } /* 用户登录界面 */
        elseif ($action == 'login') {
            if (empty($back_act)) {
                if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                    $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
                } else {
                    $back_act = 'user.php';
                }
            }

            $captcha = intval($GLOBALS['_CFG']['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }

            $this->assign('back_act', $back_act);
            return $this->fetch('user_passport');
        } /* 处理会员的登录 */
        elseif ($action == 'act_login') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

            $captcha = intval($GLOBALS['_CFG']['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                if (empty($_POST['captcha'])) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['relogin_lnk'], 'user.php', 'error');
                }

                /* 检查验证码 */
                $validator = new Captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha'])) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['relogin_lnk'], 'user.php', 'error');
                }
            }

            if ($GLOBALS['user']->login($username, $password, isset($_POST['remember']))) {
                update_user_info();
                recalculate_price();

                $ucdata = isset($GLOBALS['user']->ucdata) ? $GLOBALS['user']->ucdata : '';
                return show_message($GLOBALS['_LANG']['login_success'] . $ucdata, [$GLOBALS['_LANG']['back_up_page'], $GLOBALS['_LANG']['profile_lnk']], [$back_act, 'user.php'], 'info');
            } else {
                session('login_fail', session('login_fail') + 1);
                return show_message($GLOBALS['_LANG']['login_failure'], $GLOBALS['_LANG']['relogin_lnk'], 'user.php', 'error');
            }
        } /* 处理 ajax 的登录请求 */
        elseif ($action == 'signin') {
            $username = !empty($_POST['username']) ? json_str_iconv(trim($_POST['username'])) : '';
            $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
            $captcha = !empty($_POST['captcha']) ? json_str_iconv(trim($_POST['captcha'])) : '';
            $result = ['error' => 0, 'content' => ''];

            $captcha = intval($GLOBALS['_CFG']['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                if (empty($captcha)) {
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['invalid_captcha'];
                    return json_encode($result);
                }

                /* 检查验证码 */
                $validator = new Captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha'])) {
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['invalid_captcha'];
                    return json_encode($result);
                }
            }

            if ($GLOBALS['user']->login($username, $password)) {
                update_user_info();  //更新用户信息
                recalculate_price(); // 重新计算购物车中的商品价格
                $this->assign('user_info', get_user_info());
                $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;
                $result['ucdata'] = $ucdata;
                $result['content'] = $GLOBALS['smarty']->display('library/member_info.lbi');
            } else {
                session('login_fail', session('login_fail') + 1);
                if (session('login_fail') > 2) {
                    $this->assign('enabled_captcha', 1);
                    $result['html'] = $GLOBALS['smarty']->display('library/member_info.lbi');
                }
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['login_failure'];
            }
            return json_encode($result);
        } /* 退出会员中心 */
        elseif ($action == 'logout') {
            if ((!isset($back_act) || empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
            }

            $GLOBALS['user']->logout();
            $ucdata = empty($GLOBALS['user']->ucdata) ? "" : $GLOBALS['user']->ucdata;
            return show_message($GLOBALS['_LANG']['logout'] . $ucdata, [$GLOBALS['_LANG']['back_up_page'], $GLOBALS['_LANG']['back_home_lnk']], [$back_act, 'index.php'], 'info');
        } /* 个人资料页面 */
        elseif ($action == 'profile') {
            load_helper('transaction');

            $user_info = get_profile($user_id);

            /* 取出注册扩展字段 */
            $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
            $extend_info_list = $GLOBALS['db']->getAll($sql);

            $sql = 'SELECT reg_field_id, content ' .
                'FROM ' . $GLOBALS['ecs']->table('reg_extend_info') .
                " WHERE user_id = $user_id";
            $extend_info_arr = $GLOBALS['db']->getAll($sql);

            $temp_arr = [];
            foreach ($extend_info_arr as $val) {
                $temp_arr[$val['reg_field_id']] = $val['content'];
            }

            foreach ($extend_info_list as $key => $val) {
                switch ($val['id']) {
                    case 1:
                        $extend_info_list[$key]['content'] = $user_info['msn'];
                        break;
                    case 2:
                        $extend_info_list[$key]['content'] = $user_info['qq'];
                        break;
                    case 3:
                        $extend_info_list[$key]['content'] = $user_info['office_phone'];
                        break;
                    case 4:
                        $extend_info_list[$key]['content'] = $user_info['home_phone'];
                        break;
                    case 5:
                        $extend_info_list[$key]['content'] = $user_info['mobile_phone'];
                        break;
                    default:
                        $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
                }
            }

            $this->assign('extend_info_list', $extend_info_list);

            /* 密码提示问题 */
            $this->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            $this->assign('profile', $user_info);
            return $this->fetch('user_transaction');
        } /* 修改个人资料的处理 */
        elseif ($action == 'act_edit_profile') {
            load_helper('transaction');

            $birthday = trim($_POST['birthdayYear']) . '-' . trim($_POST['birthdayMonth']) . '-' .
                trim($_POST['birthdayDay']);
            $email = trim($_POST['email']);
            $other['msn'] = $msn = isset($_POST['extend_field1']) ? trim($_POST['extend_field1']) : '';
            $other['qq'] = $qq = isset($_POST['extend_field2']) ? trim($_POST['extend_field2']) : '';
            $other['office_phone'] = $office_phone = isset($_POST['extend_field3']) ? trim($_POST['extend_field3']) : '';
            $other['home_phone'] = $home_phone = isset($_POST['extend_field4']) ? trim($_POST['extend_field4']) : '';
            $other['mobile_phone'] = $mobile_phone = isset($_POST['extend_field5']) ? trim($_POST['extend_field5']) : '';
            $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
            $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

            /* 更新用户扩展字段的数据 */
            $sql = 'SELECT id FROM ' . $GLOBALS['ecs']->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
            $fields_arr = $GLOBALS['db']->getAll($sql);

            foreach ($fields_arr as $val) {       //循环更新扩展用户信息
                $extend_field_index = 'extend_field' . $val['id'];
                if (isset($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
                    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                    if ($GLOBALS['db']->getOne($sql)) {      //如果之前没有记录，则插入
                        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                    } else {
                        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
                    }
                    $GLOBALS['db']->query($sql);
                }
            }

            /* 写入密码提示问题和答案 */
            if (!empty($passwd_answer) && !empty($sel_question)) {
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . session('user_id') . "'";
                $GLOBALS['db']->query($sql);
            }

            if (!empty($office_phone) && !preg_match('/^[\d|\_|\-|\s]+$/', $office_phone)) {
                return show_message($GLOBALS['_LANG']['passport_js']['office_phone_invalid']);
            }
            if (!empty($home_phone) && !preg_match('/^[\d|\_|\-|\s]+$/', $home_phone)) {
                return show_message($GLOBALS['_LANG']['passport_js']['home_phone_invalid']);
            }
            if (!is_email($email)) {
                return show_message($GLOBALS['_LANG']['msg_email_format']);
            }
            if (!empty($msn) && !is_email($msn)) {
                return show_message($GLOBALS['_LANG']['passport_js']['msn_invalid']);
            }
            if (!empty($qq) && !preg_match('/^\d+$/', $qq)) {
                return show_message($GLOBALS['_LANG']['passport_js']['qq_invalid']);
            }
            if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone)) {
                return show_message($GLOBALS['_LANG']['passport_js']['mobile_phone_invalid']);
            }

            $profile = [
                'user_id' => $user_id,
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'sex' => isset($_POST['sex']) ? intval($_POST['sex']) : 0,
                'birthday' => $birthday,
                'other' => isset($other) ? $other : []
            ];

            if (edit_profile($profile)) {
                return show_message($GLOBALS['_LANG']['edit_profile_success'], $GLOBALS['_LANG']['profile_lnk'], 'user.php?act=profile', 'info');
            } else {
                if ($GLOBALS['user']->error == ERR_EMAIL_EXISTS) {
                    $msg = sprintf($GLOBALS['_LANG']['email_exist'], $profile['email']);
                } else {
                    $msg = $GLOBALS['_LANG']['edit_profile_failed'];
                }
                return show_message($msg, '', '', 'info');
            }
        } /* 密码找回-->修改密码界面 */
        elseif ($action == 'get_password') {
            load_helper('passport');

            if (isset($_GET['code']) && isset($_GET['uid'])) { //从邮件处获得的act
                $code = trim($_GET['code']);
                $uid = intval($_GET['uid']);

                /* 判断链接的合法性 */
                $user_info = $GLOBALS['user']->get_profile_by_id($uid);
                if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $GLOBALS['_CFG']['hash_code'] . $user_info['reg_time']) != $code)) {
                    return show_message($GLOBALS['_LANG']['parm_error'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
                }

                $this->assign('uid', $uid);
                $this->assign('code', $code);
                $this->assign('action', 'reset_password');
                return $this->fetch('user_passport');
            } else {
                //显示用户名和email表单
                return $this->fetch('user_passport');
            }
        } /* 密码找回-->输入用户名界面 */
        elseif ($action == 'qpassword_name') {
            //显示输入要找回密码的账号表单
            return $this->fetch('user_passport');
        } /* 密码找回-->根据注册用户名取得密码提示问题界面 */
        elseif ($action == 'get_passwd_question') {
            if (empty($_POST['user_name'])) {
                return show_message($GLOBALS['_LANG']['no_passwd_question'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
            } else {
                $user_name = trim($_POST['user_name']);
            }

            //取出会员密码问题和答案
            $sql = 'SELECT user_id, user_name, passwd_question, passwd_answer FROM ' . $GLOBALS['ecs']->table('users') . " WHERE user_name = '" . $user_name . "'";
            $user_question_arr = $GLOBALS['db']->getRow($sql);

            //如果没有设置密码问题，给出错误提示
            if (empty($user_question_arr['passwd_answer'])) {
                return show_message($GLOBALS['_LANG']['no_passwd_question'], $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
            }

            session('temp_user', $user_question_arr['user_id']);  //设置临时用户，不具有有效身份
            session('temp_user_name', $user_question_arr['user_name']);  //设置临时用户，不具有有效身份
            session('passwd_answer', $user_question_arr['passwd_answer']);   //存储密码问题答案，减少一次数据库访问

            $captcha = intval($GLOBALS['_CFG']['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }

            $this->assign('passwd_question', $GLOBALS['_LANG']['passwd_questions'][$user_question_arr['passwd_question']]);
            return $this->fetch('user_passport');
        } /* 密码找回-->根据提交的密码答案进行相应处理 */
        elseif ($action == 'check_answer') {
            $captcha = intval($GLOBALS['_CFG']['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                if (empty($_POST['captcha'])) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
                }

                /* 检查验证码 */
                $validator = new Captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha'])) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
                }
            }

            if (empty($_POST['passwd_answer']) || $_POST['passwd_answer'] != session('passwd_answer')) {
                return show_message($GLOBALS['_LANG']['wrong_passwd_answer'], $GLOBALS['_LANG']['back_retry_answer'], 'user.php?act=qpassword_name', 'info');
            } else {
                session('user_id', session('temp_user'));
                session('user_name', session('temp_user_name'));
                session('temp_user', null);
                session('temp_user_name', null);
                $this->assign('uid', session('user_id'));
                $this->assign('action', 'reset_password');
                return $this->fetch('user_passport');
            }
        } /* 发送密码修改确认邮件 */
        elseif ($action == 'send_pwd_email') {
            load_helper('passport');

            /* 初始化会员用户名和邮件地址 */
            $user_name = !empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
            $email = !empty($_POST['email']) ? trim($_POST['email']) : '';

            //用户名和邮件地址是否匹配
            $user_info = $GLOBALS['user']->get_user_info($user_name);

            if ($user_info && $user_info['email'] == $email) {
                //生成code
                //$code = md5($user_info[0] . $user_info[1]);

                $code = md5($user_info['user_id'] . $GLOBALS['_CFG']['hash_code'] . $user_info['reg_time']);
                //发送邮件的函数
                if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
                    return show_message($GLOBALS['_LANG']['send_success'] . $email, $GLOBALS['_LANG']['back_home_lnk'], './', 'info');
                } else {
                    //发送邮件出错
                    return show_message($GLOBALS['_LANG']['fail_send_password'], $GLOBALS['_LANG']['back_page_up'], './', 'info');
                }
            } else {
                //用户名与邮件地址不匹配
                return show_message($GLOBALS['_LANG']['username_no_email'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
            }
        } /* 重置新密码 */
        elseif ($action == 'reset_password') {
            //显示重置密码的表单
            return $this->fetch('user_passport');
        } /* 修改会员密码 */
        elseif ($action == 'act_edit_password') {
            load_helper('passport');

            $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
            $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
            $user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $user_id;
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';

            if (strlen($new_password) < 6) {
                return show_message($GLOBALS['_LANG']['passport_js']['password_shorter']);
            }

            $user_info = $GLOBALS['user']->get_profile_by_id($user_id); //论坛记录

            if (($user_info && (!empty($code) && md5($user_info['user_id'] . $GLOBALS['_CFG']['hash_code'] . $user_info['reg_time']) == $code)) || (session('user_id') > 0 && session('user_id') == $user_id && $GLOBALS['user']->check_user(session('user_name'), $old_password))) {
                if ($GLOBALS['user']->edit_user(['username' => (empty($code) ? session('user_name') : $user_info['user_name']), 'old_password' => $old_password, 'password' => $new_password], empty($code) ? 0 : 1)) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . "SET `ec_salt`='0' WHERE user_id= '" . $user_id . "'";
                    $GLOBALS['db']->query($sql);
                    $GLOBALS['user']->logout();
                    return show_message($GLOBALS['_LANG']['edit_password_success'], $GLOBALS['_LANG']['relogin_lnk'], 'user.php?act=login', 'info');
                } else {
                    return show_message($GLOBALS['_LANG']['edit_password_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }
            } else {
                return show_message($GLOBALS['_LANG']['edit_password_failure'], $GLOBALS['_LANG']['back_page_up'], '', 'info');
            }
        } /* 添加一个红包 */
        elseif ($action == 'act_add_bonus') {
            load_helper('transaction');

            $bouns_sn = isset($_POST['bonus_sn']) ? intval($_POST['bonus_sn']) : '';

            if (add_bonus($user_id, $bouns_sn)) {
                return show_message($GLOBALS['_LANG']['add_bonus_sucess'], $GLOBALS['_LANG']['back_up_page'], 'user.php?act=bonus', 'info');
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['back_up_page'], 'user.php?act=bonus');
            }
        } /* 查看订单列表 */
        elseif ($action == 'order_list') {
            load_helper('transaction');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $record_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id = '$user_id'");

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $orders = get_user_orders($user_id, $pager['size'], $pager['start']);
            $merge = get_user_merge($user_id);

            $this->assign('merge', $merge);
            $this->assign('pager', $pager);
            $this->assign('orders', $orders);
            return $this->fetch('user_transaction');
        } /* 查看订单详情 */
        elseif ($action == 'order_detail') {
            load_helper('transaction');
            load_helper('payment');
            load_helper('order');
            load_helper('clips');

            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

            /* 订单详情 */
            $order = get_order_detail($order_id, $user_id);

            if ($order === false) {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['back_home_lnk'], './');
            }

            /* 是否显示添加到购物车 */
            if ($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods') {
                $this->assign('allow_to_cart', 1);
            }

            /* 订单商品 */
            $goods_list = order_goods($order_id);
            foreach ($goods_list as $key => $value) {
                $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
                $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
                $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
            }

            /* 设置能否修改使用余额数 */
            if ($order['order_amount'] > 0) {
                if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED) {
                    $user = user_info($order['user_id']);
                    if ($user['user_money'] + $user['credit_line'] > 0) {
                        $this->assign('allow_edit_surplus', 1);
                        $this->assign('max_surplus', sprintf($GLOBALS['_LANG']['max_surplus'], $user['user_money']));
                    }
                }
            }

            /* 未发货，未付款时允许更换支付方式 */
            if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED) {
                $payment_list = available_payment_list(false, 0, true);

                /* 过滤掉当前支付方式和余额支付方式 */
                if (is_array($payment_list)) {
                    foreach ($payment_list as $key => $payment) {
                        if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance') {
                            unset($payment_list[$key]);
                        }
                    }
                }
                $this->assign('payment_list', $payment_list);
            }

            /* 订单 支付 配送 状态语言项 */
            $order['order_status'] = $GLOBALS['_LANG']['os'][$order['order_status']];
            $order['pay_status'] = $GLOBALS['_LANG']['ps'][$order['pay_status']];
            $order['shipping_status'] = $GLOBALS['_LANG']['ss'][$order['shipping_status']];

            $this->assign('order', $order);
            $this->assign('goods_list', $goods_list);
            return $this->fetch('user_transaction');
        } /* 取消订单 */
        elseif ($action == 'cancel_order') {
            load_helper('transaction');
            load_helper('order');

            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

            if (cancel_order($order_id, $user_id)) {
                return ecs_header("Location: user.php?act=order_list\n");
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_list_lnk'], 'user.php?act=order_list');
            }
        } /* 收货地址列表界面*/
        elseif ($action == 'address_list') {
            load_helper('transaction');
            load_lang('shopping_flow');
            $this->assign('lang', $GLOBALS['_LANG']);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->assign('country_list', get_regions());
            $this->assign('shop_province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));

            /* 获得用户所有的收货人信息 */
            $consignee_list = get_consignee_list(session('user_id'));

            if (count($consignee_list) < 5 && session('user_id') > 0) {
                /* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 */
                $consignee_list[] = ['country' => $GLOBALS['_CFG']['shop_country'], 'email' => session('email', '')];
            }

            $this->assign('consignee_list', $consignee_list);

            //取得国家列表，如果有收货人列表，取得省市区列表
            foreach ($consignee_list as $region_id => $consignee) {
                $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
                $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
                $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

                $province_list[$region_id] = get_regions(1, $consignee['country']);
                $city_list[$region_id] = get_regions(2, $consignee['province']);
                $district_list[$region_id] = get_regions(3, $consignee['city']);
            }

            /* 获取默认收货ID */
            $address_id = $GLOBALS['db']->getOne("SELECT address_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='$user_id'");

            //赋值于模板
            $this->assign('real_goods_count', 1);
            $this->assign('shop_country', $GLOBALS['_CFG']['shop_country']);
            $this->assign('shop_province', get_regions(1, $GLOBALS['_CFG']['shop_country']));
            $this->assign('province_list', $province_list);
            $this->assign('address', $address_id);
            $this->assign('city_list', $city_list);
            $this->assign('district_list', $district_list);
            $this->assign('currency_format', $GLOBALS['_CFG']['currency_format']);
            $this->assign('integral_scale', $GLOBALS['_CFG']['integral_scale']);
            $this->assign('name_of_region', [$GLOBALS['_CFG']['name_of_region_1'], $GLOBALS['_CFG']['name_of_region_2'], $GLOBALS['_CFG']['name_of_region_3'], $GLOBALS['_CFG']['name_of_region_4']]);

            return $this->fetch('user_transaction');
        } /* 添加/编辑收货地址的处理 */
        elseif ($action == 'act_edit_address') {
            load_helper('transaction');
            load_lang('shopping_flow');
            $this->assign('lang', $GLOBALS['_LANG']);

            $address = [
                'user_id' => $user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => isset($_POST['country']) ? intval($_POST['country']) : 0,
                'province' => isset($_POST['province']) ? intval($_POST['province']) : 0,
                'city' => isset($_POST['city']) ? intval($_POST['city']) : 0,
                'district' => isset($_POST['district']) ? intval($_POST['district']) : 0,
                'address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '',
                'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '',
                'email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '',
                'tel' => isset($_POST['tel']) ? compile_str(make_semiangle(trim($_POST['tel']))) : '',
                'mobile' => isset($_POST['mobile']) ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
                'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '',
                'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
                'zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
            ];

            if (update_address($address)) {
                return show_message($GLOBALS['_LANG']['edit_address_success'], $GLOBALS['_LANG']['address_list_lnk'], 'user.php?act=address_list');
            }
        } /* 删除收货地址 */
        elseif ($action == 'drop_consignee') {
            load_helper('transaction');

            $consignee_id = intval($_GET['id']);

            if (drop_consignee($consignee_id)) {
                return ecs_header("Location: user.php?act=address_list\n");
            } else {
                return show_message($GLOBALS['_LANG']['del_address_false']);
            }
        } /* 显示收藏商品列表 */
        elseif ($action == 'collection_list') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $record_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('collect_goods') .
                " WHERE user_id='$user_id' ORDER BY add_time DESC");

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $this->assign('pager', $pager);
            $this->assign('goods_list', get_collection_goods($user_id, $pager['size'], $pager['start']));
            $this->assign('url', $GLOBALS['ecs']->url());
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];
            $this->assign('lang_list', $lang_list);
            $this->assign('user_id', $user_id);
            return $this->fetch('user_clips');
        } /* 删除收藏的商品 */
        elseif ($action == 'delete_collection') {
            load_helper('clips');

            $collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;

            if ($collection_id > 0) {
                $GLOBALS['db']->query('DELETE FROM ' . $GLOBALS['ecs']->table('collect_goods') . " WHERE rec_id='$collection_id' AND user_id ='$user_id'");
            }

            return ecs_header("Location: user.php?act=collection_list\n");
        } /* 添加关注商品 */
        elseif ($action == 'add_to_attention') {
            $rec_id = (int)$_GET['rec_id'];
            if ($rec_id) {
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('collect_goods') . "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
            }
            return ecs_header("Location: user.php?act=collection_list\n");
        } /* 取消关注商品 */
        elseif ($action == 'del_attention') {
            $rec_id = (int)$_GET['rec_id'];
            if ($rec_id) {
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('collect_goods') . "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
            }
            return ecs_header("Location: user.php?act=collection_list\n");
        } /* 显示留言列表 */
        elseif ($action == 'message_list') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
            $order_info = [];

            /* 获取用户留言的数量 */
            if ($order_id) {
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('feedback') .
                    " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
                $order_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
                $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
            } else {
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('feedback') .
                    " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . session('user_name') . "' AND order_id=0";
            }

            $record_count = $GLOBALS['db']->getOne($sql);
            $act = ['act' => $action];

            if ($order_id != '') {
                $act['order_id'] = $order_id;
            }

            $pager = get_pager('user.php', $act, $record_count, $page, 5);

            $this->assign('message_list', get_message_list($user_id, session('user_name'), $pager['size'], $pager['start'], $order_id));
            $this->assign('pager', $pager);
            $this->assign('order_info', $order_info);
            return $this->fetch('user_clips');
        } /* 显示评论列表 */
        elseif ($action == 'comment_list') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            /* 获取用户留言的数量 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('comment') .
                " WHERE parent_id = 0 AND user_id = '$user_id'";
            $record_count = $GLOBALS['db']->getOne($sql);
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page, 5);

            $this->assign('comment_list', get_comment_list($user_id, $pager['size'], $pager['start']));
            $this->assign('pager', $pager);
            return $this->fetch('user_clips');
        } /* 添加我的留言 */
        elseif ($action == 'act_add_message') {
            load_helper('clips');

            $message = [
                'user_id' => $user_id,
                'user_name' => session('user_name'),
                'user_email' => session('email'),
                'msg_type' => isset($_POST['msg_type']) ? intval($_POST['msg_type']) : 0,
                'msg_title' => isset($_POST['msg_title']) ? trim($_POST['msg_title']) : '',
                'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
                'order_id' => empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
                'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
                    ? $_FILES['message_img'] : []
            ];

            if (add_message($message)) {
                return show_message($GLOBALS['_LANG']['add_message_success'], $GLOBALS['_LANG']['message_list_lnk'], 'user.php?act=message_list&order_id=' . $message['order_id'], 'info');
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['message_list_lnk'], 'user.php?act=message_list');
            }
        } /* 标签云列表 */
        elseif ($action == 'tag_list') {
            load_helper('clips');

            $good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $this->assign('tags', get_user_tags($user_id));
            $this->assign('tags_from', 'user');
            return $this->fetch('user_clips');
        } /* 删除标签云的处理 */
        elseif ($action == 'act_del_tag') {
            load_helper('clips');

            $tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
            delete_tag($tag_words, $user_id);

            return ecs_header("Location: user.php?act=tag_list\n");
        } /* 显示缺货登记列表 */
        elseif ($action == 'booking_list') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            /* 获取缺货登记的数量 */
            $sql = "SELECT COUNT(*) " .
                "FROM " . $GLOBALS['ecs']->table('booking_goods') . " AS bg, " .
                $GLOBALS['ecs']->table('goods') . " AS g " .
                "WHERE bg.goods_id = g.goods_id AND user_id = '$user_id'";
            $record_count = $GLOBALS['db']->getOne($sql);
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $this->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
            $this->assign('pager', $pager);
            return $this->fetch('user_clips');
        } /* 添加缺货登记页面 */
        elseif ($action == 'add_booking') {
            load_helper('clips');

            $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($goods_id == 0) {
                return show_message($GLOBALS['_LANG']['no_goods_id'], $GLOBALS['_LANG']['back_page_up'], '', 'error');
            }

            /* 根据规格属性获取货品规格信息 */
            $goods_attr = '';
            if ($_GET['spec'] != '') {
                $goods_attr_id = $_GET['spec'];

                $attr_list = [];
                $sql = "SELECT a.attr_name, g.attr_value " .
                    "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " .
                    $GLOBALS['ecs']->table('attribute') . " AS a " .
                    "WHERE g.attr_id = a.attr_id " .
                    "AND g.goods_attr_id " . db_create_in($goods_attr_id);
                $res = $GLOBALS['db']->query($sql);
                foreach ($res as $row) {
                    $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
                }
                $goods_attr = join(chr(13) . chr(10), $attr_list);
            }
            $this->assign('goods_attr', $goods_attr);

            $this->assign('info', get_goodsinfo($goods_id));
            return $this->fetch('user_clips');
        } /* 添加缺货登记的处理 */
        elseif ($action == 'act_add_booking') {
            load_helper('clips');

            $booking = [
                'goods_id' => isset($_POST['id']) ? intval($_POST['id']) : 0,
                'goods_amount' => isset($_POST['number']) ? intval($_POST['number']) : 0,
                'desc' => isset($_POST['desc']) ? trim($_POST['desc']) : '',
                'linkman' => isset($_POST['linkman']) ? trim($_POST['linkman']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'tel' => isset($_POST['tel']) ? trim($_POST['tel']) : '',
                'booking_id' => isset($_POST['rec_id']) ? intval($_POST['rec_id']) : 0
            ];

            // 查看此商品是否已经登记过
            $rec_id = get_booking_rec($user_id, $booking['goods_id']);
            if ($rec_id > 0) {
                return show_message($GLOBALS['_LANG']['booking_rec_exist'], $GLOBALS['_LANG']['back_page_up'], '', 'error');
            }

            if (add_booking($booking)) {
                return show_message(
                    $GLOBALS['_LANG']['booking_success'],
                    $GLOBALS['_LANG']['back_booking_list'],
                    'user.php?act=booking_list',
                    'info'
                );
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['booking_list_lnk'], 'user.php?act=booking_list');
            }
        } /* 删除缺货登记 */
        elseif ($action == 'act_del_booking') {
            load_helper('clips');

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id == 0 || $user_id == 0) {
                return ecs_header("Location: user.php?act=booking_list\n");
            }

            $result = delete_booking($id, $user_id);
            if ($result) {
                return ecs_header("Location: user.php?act=booking_list\n");
            }
        } /* 确认收货 */
        elseif ($action == 'affirm_received') {
            load_helper('transaction');

            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

            if (affirm_received($order_id, $user_id)) {
                return ecs_header("Location: user.php?act=order_list\n");
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_list_lnk'], 'user.php?act=order_list');
            }
        } /* 会员退款申请界面 */
        elseif ($action == 'account_raply') {
            return $this->fetch('user_transaction');
        } /* 会员预付款界面 */
        elseif ($action == 'account_deposit') {
            load_helper('clips');

            $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $account = get_surplus_info($surplus_id);

            $this->assign('payment', get_online_payment_list(false));
            $this->assign('order', $account);
            return $this->fetch('user_transaction');
        } /* 会员账目明细界面 */
        elseif ($action == 'account_detail') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $account_type = 'user_money';

            /* 获取记录条数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('account_log') .
                " WHERE user_id = '$user_id'" .
                " AND $account_type <> 0 ";
            $record_count = $GLOBALS['db']->getOne($sql);

            //分页函数
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            //获取剩余余额
            $surplus_amount = get_user_surplus($user_id);
            if (empty($surplus_amount)) {
                $surplus_amount = 0;
            }

            //获取余额记录
            $account_log = [];
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('account_log') .
                " WHERE user_id = '$user_id'" .
                " AND $account_type <> 0 " .
                " ORDER BY log_id DESC";
            $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
            foreach ($res as $row) {
                $row['change_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['change_time']);
                $row['type'] = $row[$account_type] > 0 ? $GLOBALS['_LANG']['account_inc'] : $GLOBALS['_LANG']['account_dec'];
                $row['user_money'] = price_format(abs($row['user_money']), false);
                $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
                $row['rank_points'] = abs($row['rank_points']);
                $row['pay_points'] = abs($row['pay_points']);
                $row['short_change_desc'] = sub_str($row['change_desc'], 60);
                $row['amount'] = $row[$account_type];
                $account_log[] = $row;
            }

            //模板赋值
            $this->assign('surplus_amount', price_format($surplus_amount, false));
            $this->assign('account_log', $account_log);
            $this->assign('pager', $pager);
            return $this->fetch('user_transaction');
        } /* 会员充值和提现申请记录 */
        elseif ($action == 'account_log') {
            load_helper('clips');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            /* 获取记录条数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_account') .
                " WHERE user_id = '$user_id'" .
                " AND process_type " . db_create_in([SURPLUS_SAVE, SURPLUS_RETURN]);
            $record_count = $GLOBALS['db']->getOne($sql);

            //分页函数
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            //获取剩余余额
            $surplus_amount = get_user_surplus($user_id);
            if (empty($surplus_amount)) {
                $surplus_amount = 0;
            }

            //获取余额记录
            $account_log = get_account_log($user_id, $pager['size'], $pager['start']);

            //模板赋值
            $this->assign('surplus_amount', price_format($surplus_amount, false));
            $this->assign('account_log', $account_log);
            $this->assign('pager', $pager);
            return $this->fetch('user_transaction');
        } /* 对会员余额申请的处理 */
        elseif ($action == 'act_account') {
            load_helper('clips');
            load_helper('order');
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            if ($amount <= 0) {
                return show_message($GLOBALS['_LANG']['amount_gt_zero']);
            }

            /* 变量初始化 */
            $surplus = [
                'user_id' => $user_id,
                'rec_id' => !empty($_POST['rec_id']) ? intval($_POST['rec_id']) : 0,
                'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
                'payment_id' => isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0,
                'user_note' => isset($_POST['user_note']) ? trim($_POST['user_note']) : '',
                'amount' => $amount
            ];

            /* 退款申请的处理 */
            if ($surplus['process_type'] == 1) {
                /* 判断是否有足够的余额的进行退款的操作 */
                $sur_amount = get_user_surplus($user_id);
                if ($amount > $sur_amount) {
                    $content = $GLOBALS['_LANG']['surplus_amount_error'];
                    return show_message($content, $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }

                //插入会员账目明细
                $amount = '-' . $amount;
                $surplus['payment'] = '';
                $surplus['rec_id'] = insert_user_account($surplus, $amount);

                /* 如果成功提交 */
                if ($surplus['rec_id'] > 0) {
                    $content = $GLOBALS['_LANG']['surplus_appl_submit'];
                    return show_message($content, $GLOBALS['_LANG']['back_account_log'], 'user.php?act=account_log', 'info');
                } else {
                    $content = $GLOBALS['_LANG']['process_false'];
                    return show_message($content, $GLOBALS['_LANG']['back_page_up'], '', 'info');
                }
            } /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
            else {
                if ($surplus['payment_id'] <= 0) {
                    return show_message($GLOBALS['_LANG']['select_payment_pls']);
                }

                load_helper('payment');

                //获取支付方式名称
                $payment_info = [];
                $payment_info = payment_info($surplus['payment_id']);
                $surplus['payment'] = $payment_info['pay_name'];

                if ($surplus['rec_id'] > 0) {
                    //更新会员账目明细
                    $surplus['rec_id'] = update_user_account($surplus);
                } else {
                    //插入会员账目明细
                    $surplus['rec_id'] = insert_user_account($surplus, $amount);
                }

                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //生成伪订单号, 不足的时候补0
                $order = [];
                $order['order_sn'] = $surplus['rec_id'];
                $order['user_name'] = session('user_name');
                $order['surplus_amount'] = $amount;

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

                //计算此次预付款需要支付的总金额
                $order['order_amount'] = $amount + $payment_info['pay_fee'];

                //记录支付log
                $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type = PAY_SURPLUS, 0);

                /* 调用相应的支付方式文件 */
                $plugin = '\\app\\plugins\\payment\\' . parse_name($payment_info['pay_code'], true);

                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $plugin;
                $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

                /* 模板赋值 */
                $this->assign('payment', $payment_info);
                $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->assign('amount', price_format($amount, false));
                $this->assign('order', $order);
                return $this->fetch('user_transaction');
            }
        } /* 删除会员余额 */
        elseif ($action == 'cancel') {
            load_helper('clips');

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id == 0 || $user_id == 0) {
                return ecs_header("Location: user.php?act=account_log\n");
            }

            $result = del_user_account($id, $user_id);
            if ($result) {
                return ecs_header("Location: user.php?act=account_log\n");
            }
        } /* 会员通过帐目明细列表进行再付款的操作 */
        elseif ($action == 'pay') {
            load_helper('clips');
            load_helper('payment');
            load_helper('order');

            //变量初始化
            $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

            if ($surplus_id == 0) {
                return ecs_header("Location: user.php?act=account_log\n");
            }

            //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
            if ($payment_id == 0) {
                return ecs_header("Location: user.php?act=account_deposit&id=" . $surplus_id . "\n");
            }

            //获取单条会员帐目信息
            $order = [];
            $order = get_surplus_info($surplus_id);

            //支付方式的信息
            $payment_info = [];
            $payment_info = payment_info($payment_id);

            /* 如果当前支付方式没有被禁用，进行支付的操作 */
            if (!empty($payment_info)) {
                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //生成伪订单号
                $order['order_sn'] = $surplus_id;

                //获取需要支付的log_id
                $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

                $order['user_name'] = session('user_name');
                $order['surplus_amount'] = $order['amount'];

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

                //计算此次预付款需要支付的总金额
                $order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];

                //如果支付费用改变了，也要相应的更改pay_log表的order_amount
                $order_amount = $GLOBALS['db']->getOne("SELECT order_amount FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE log_id = '$order[log_id]'");
                if ($order_amount <> $order['order_amount']) {
                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('pay_log') .
                        " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
                }

                /* 调用相应的支付方式文件 */
                $plugin = '\\app\\plugins\\payment\\' . parse_name($payment_info['pay_code'], true);

                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $plugin;
                $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

                /* 模板赋值 */
                $this->assign('payment', $payment_info);
                $this->assign('order', $order);
                $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
                $this->assign('amount', price_format($order['surplus_amount'], false));
                $this->assign('action', 'act_account');
                return $this->fetch('user_transaction');
            } /* 重新选择支付方式 */
            else {
                load_helper('clips');

                $this->assign('payment', get_online_payment_list());
                $this->assign('order', $order);
                $this->assign('action', 'account_deposit');
                return $this->fetch('user_transaction');
            }
        } /* 添加标签(ajax) */
        elseif ($action == 'add_tag') {
            load_helper('clips');

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $tag = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['tag_anonymous'];
            } else {
                add_tag($id, $tag); // 添加tag
                clear_cache_files('goods'); // 删除缓存

                /* 重新获得该商品的所有缓存 */
                $arr = get_tags($id);

                foreach ($arr as $row) {
                    $result['content'][] = ['word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']];
                }
            }

            echo json_encode($result);
        } /* 添加收藏商品(ajax) */
        elseif ($action == 'collect') {
            $result = ['error' => 0, 'message' => ''];
            $goods_id = $_GET['id'];

            if (!session('?user_id') || session('user_id') == 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return json_encode($result);
            } else {
                /* 检查是否已经存在于用户的收藏夹 */
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('collect_goods') .
                    " WHERE user_id='" . session('user_id') . "' AND goods_id = '$goods_id'";
                if ($GLOBALS['db']->getOne($sql) > 0) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['collect_existed'];
                    return json_encode($result);
                } else {
                    $time = gmtime();
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('collect_goods') . " (user_id, goods_id, add_time)" .
                        "VALUES ('" . session('user_id') . "', '$goods_id', '$time')";

                    if ($GLOBALS['db']->query($sql) === false) {
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['db']->errorMsg();
                        return json_encode($result);
                    } else {
                        $result['error'] = 0;
                        $result['message'] = $GLOBALS['_LANG']['collect_success'];
                        return json_encode($result);
                    }
                }
            }
        } /* 删除留言 */
        elseif ($action == 'del_msg') {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

            if ($id > 0) {
                $sql = 'SELECT user_id, message_img FROM ' . $GLOBALS['ecs']->table('feedback') . " WHERE msg_id = '$id' LIMIT 1";
                $row = $GLOBALS['db']->getRow($sql);
                if ($row && $row['user_id'] == $user_id) {
                    /* 验证通过，删除留言，回复，及相应文件 */
                    if ($row['message_img']) {
                        @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/' . $row['message_img']);
                    }
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('feedback') . " WHERE msg_id = '$id' OR parent_id = '$id'";
                    $GLOBALS['db']->query($sql);
                }
            }
            return ecs_header("Location: user.php?act=message_list&order_id=$order_id\n");
        } /* 删除评论 */
        elseif ($action == 'del_cmt') {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id > 0) {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_id = '$id' AND user_id = '$user_id'";
                $GLOBALS['db']->query($sql);
            }
            return ecs_header("Location: user.php?act=comment_list\n");
        } /* 合并订单 */
        elseif ($action == 'merge_order') {
            load_helper('transaction');
            load_helper('order');
            $from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
            $to_order = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
            if (merge_user_order($from_order, $to_order, $user_id)) {
                return show_message($GLOBALS['_LANG']['merge_order_success'], $GLOBALS['_LANG']['order_list_lnk'], 'user.php?act=order_list', 'info');
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_list_lnk']);
            }
        } /* 将指定订单中商品添加到购物车 */
        elseif ($action == 'return_to_cart') {
            load_helper('transaction');

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            if ($order_id == 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_id_empty'];
                return json_encode($result);
            }

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return json_encode($result);
            }

            /* 检查订单是否属于该用户 */
            $order_user = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'");
            if (empty($order_user)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_exist'];
                return json_encode($result);
            } else {
                if ($order_user != $user_id) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['no_priv'];
                    return json_encode($result);
                }
            }

            $message = return_to_cart($order_id);

            if ($message === true) {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['return_to_cart_success'];
                return json_encode($result);
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_exist'];
                return json_encode($result);
            }
        } /* 编辑使用余额支付的处理 */
        elseif ($action == 'act_edit_surplus') {
            /* 检查是否登录 */
            if (session('user_id') <= 0) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单号 */
            $order_id = intval($_POST['order_id']);
            if ($order_id <= 0) {
                return ecs_header("Location: ./\n");
            }

            /* 检查余额 */
            $surplus = floatval($_POST['surplus']);
            if ($surplus <= 0) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['error_surplus_invalid']);
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
            }

            load_helper('order');

            /* 取得订单 */
            $order = order_info($order_id);
            if (empty($order)) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单用户跟当前用户是否一致 */
            if (session('user_id') != $order['user_id']) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单是否未付款，检查应付款金额是否大于0 */
            if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['error_order_is_paid']);
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
            }

            /* 计算应付款金额（减去支付费用） */
            $order['order_amount'] -= $order['pay_fee'];

            /* 余额是否超过了应付款金额，改为应付款金额 */
            if ($surplus > $order['order_amount']) {
                $surplus = $order['order_amount'];
            }

            /* 取得用户信息 */
            $user = user_info(session('user_id'));

            /* 用户帐户余额是否足够 */
            if ($surplus > $user['user_money'] + $user['credit_line']) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['error_surplus_not_enough']);
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
            }

            /* 修改订单，重新计算支付费用 */
            $order['surplus'] += $surplus;
            $order['order_amount'] -= $surplus;
            if ($order['order_amount'] > 0) {
                $cod_fee = 0;
                if ($order['shipping_id'] > 0) {
                    $regions = [$order['country'], $order['province'], $order['city'], $order['district']];
                    $shipping = shipping_area_info($order['shipping_id'], $regions);
                    if ($shipping['support_cod'] == '1') {
                        $cod_fee = $shipping['pay_fee'];
                    }
                }

                $pay_fee = 0;
                if ($order['pay_id'] > 0) {
                    $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
                }

                $order['pay_fee'] = $pay_fee;
                $order['order_amount'] += $pay_fee;
            }

            /* 如果全部支付，设为已确认、已付款 */
            if ($order['order_amount'] == 0) {
                if ($order['order_status'] == OS_UNCONFIRMED) {
                    $order['order_status'] = OS_CONFIRMED;
                    $order['confirm_time'] = gmtime();
                }
                $order['pay_status'] = PS_PAYED;
                $order['pay_time'] = gmtime();
            }
            $order = addslashes_deep($order);
            update_order($order_id, $order);

            /* 更新用户余额 */
            $change_desc = sprintf($GLOBALS['_LANG']['pay_order_by_surplus'], $order['order_sn']);
            log_account_change($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);

            /* 跳转 */
            return ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
        } /* 编辑使用余额支付的处理 */
        elseif ($action == 'act_edit_payment') {
            /* 检查是否登录 */
            if (session('user_id') <= 0) {
                return ecs_header("Location: ./\n");
            }

            /* 检查支付方式 */
            $pay_id = intval($_POST['pay_id']);
            if ($pay_id <= 0) {
                return ecs_header("Location: ./\n");
            }

            load_helper('order');
            $payment_info = payment_info($pay_id);
            if (empty($payment_info)) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单号 */
            $order_id = intval($_POST['order_id']);
            if ($order_id <= 0) {
                return ecs_header("Location: ./\n");
            }

            /* 取得订单 */
            $order = order_info($order_id);
            if (empty($order)) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单用户跟当前用户是否一致 */
            if (session('user_id') != $order['user_id']) {
                return ecs_header("Location: ./\n");
            }

            /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
            if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id) {
                return ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
            }

            $order_amount = $order['order_amount'] - $order['pay_fee'];
            $pay_fee = pay_fee($pay_id, $order_amount);
            $order_amount += $pay_fee;

            $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
                " SET pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'" .
                " WHERE order_id = '$order_id'";
            $GLOBALS['db']->query($sql);

            /* 跳转 */
            return ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
        } /* 保存订单详情收货地址 */
        elseif ($action == 'save_order_address') {
            load_helper('transaction');

            $address = [
                'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '',
                'email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '',
                'address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '',
                'zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
                'tel' => isset($_POST['tel']) ? compile_str(trim($_POST['tel'])) : '',
                'mobile' => isset($_POST['mobile']) ? compile_str(trim($_POST['mobile'])) : '',
                'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
                'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '',
                'order_id' => isset($_POST['order_id']) ? intval($_POST['order_id']) : 0
            ];
            if (save_order_address($address, $user_id)) {
                return ecs_header('Location: user.php?act=order_detail&order_id=' . $address['order_id'] . "\n");
            } else {
                return $GLOBALS['err']->show($GLOBALS['_LANG']['order_list_lnk'], 'user.php?act=order_list');
            }
        } /* 我的红包列表 */
        elseif ($action == 'bonus') {
            load_helper('transaction');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $record_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_bonus') . " WHERE user_id = '$user_id'");

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $bonus = get_user_bouns_list($user_id, $pager['size'], $pager['start']);

            $this->assign('pager', $pager);
            $this->assign('bonus', $bonus);
            return $this->fetch('user_transaction');
        } /* 我的团购列表 */
        elseif ($action == 'group_buy') {
            load_helper('transaction');

            //待议
            return $this->fetch('user_transaction');
        } /* 团购订单详情 */
        elseif ($action == 'group_buy_detail') {
            load_helper('transaction');

            //待议
            return $this->fetch('user_transaction');
        } // 用户推荐页面
        elseif ($action == 'affiliate') {
            $goodsid = intval(isset($_REQUEST['goodsid']) ? $_REQUEST['goodsid'] : 0);
            if (empty($goodsid)) {
                //我的推荐页面

                $page = !empty($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
                $size = !empty($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

                empty($affiliate) && $affiliate = [];

                if (empty($affiliate['config']['separate_by'])) {
                    //推荐注册分成
                    $affdb = [];
                    $num = count($affiliate['item']);
                    $up_uid = "'$user_id'";
                    $all_uid = "'$user_id'";
                    for ($i = 1; $i <= $num; $i++) {
                        $count = 0;
                        if ($up_uid) {
                            $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE parent_id IN($up_uid)";
                            $query = $GLOBALS['db']->query($sql);
                            $up_uid = '';
                            foreach ($query as $rt) {
                                $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                                if ($i < $num) {
                                    $all_uid .= ", '$rt[user_id]'";
                                }
                                $count++;
                            }
                        }
                        $affdb[$i]['num'] = $count;
                        $affdb[$i]['point'] = $affiliate['item'][$i - 1]['level_point'];
                        $affdb[$i]['money'] = $affiliate['item'][$i - 1]['level_money'];
                    }
                    $this->assign('affdb', $affdb);

                    $sqlcount = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                        " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";

                    $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                        " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" .
                        " ORDER BY order_id DESC";

                    /*
                        SQL解释：

                        订单、用户、分成记录关联
                        一个订单可能有多个分成记录

                        1、订单有效 o.user_id > 0
                        2、满足以下之一：
                            a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
                                其中$all_uid为该ID及其下线(不包含最后一层下线)
                            b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

                    */

                    $affiliate_intro = nl2br(sprintf($GLOBALS['_LANG']['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $GLOBALS['_LANG']['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
                } else {
                    //推荐订单分成
                    $sqlcount = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                        " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                        " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";

                    $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                        " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                        " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" .
                        " ORDER BY order_id DESC";

                    /*
                        SQL解释：

                        订单、用户、分成记录关联
                        一个订单可能有多个分成记录

                        1、订单有效 o.user_id > 0
                        2、满足以下之一：
                            a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
                            b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

                    */

                    $affiliate_intro = nl2br(sprintf($GLOBALS['_LANG']['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $GLOBALS['_LANG']['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
                }

                $count = $GLOBALS['db']->getOne($sqlcount);

                $max_page = ($count > 0) ? ceil($count / $size) : 1;
                if ($page > $max_page) {
                    $page = $max_page;
                }

                $res = $GLOBALS['db']->SelectLimit($sql, $size, ($page - 1) * $size);
                $logdb = [];
                foreach ($res as $rt) {
                    if (!empty($rt['suid'])) {
                        //在affiliate_log有记录
                        if ($rt['separate_type'] == -1 || $rt['separate_type'] == -2) {
                            //已被撤销
                            $rt['is_separate'] = 3;
                        }
                    }
                    $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], -2, 2);
                    $logdb[] = $rt;
                }

                $url_format = "user.php?act=affiliate&page=";

                $pager = [
                    'page' => $page,
                    'size' => $size,
                    'sort' => '',
                    'order' => '',
                    'record_count' => $count,
                    'page_count' => $max_page,
                    'page_first' => $url_format . '1',
                    'page_prev' => $page > 1 ? $url_format . ($page - 1) : "javascript:;",
                    'page_next' => $page < $max_page ? $url_format . ($page + 1) : "javascript:;",
                    'page_last' => $url_format . $max_page,
                    'array' => []
                ];
                for ($i = 1; $i <= $max_page; $i++) {
                    $pager['array'][$i] = $i;
                }

                $this->assign('url_format', $url_format);
                $this->assign('pager', $pager);

                $this->assign('affiliate_intro', $affiliate_intro);
                $this->assign('affiliate_type', $affiliate['config']['separate_by']);

                $this->assign('logdb', $logdb);
            } else {
                //单个商品推荐
                $this->assign('userid', $user_id);
                $this->assign('goodsid', $goodsid);

                $types = [1, 2, 3, 4, 5];
                $this->assign('types', $types);

                $goods = get_goods_info($goodsid);
                $shopurl = $GLOBALS['ecs']->url();
                $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
                $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
                $goods['shop_price'] = price_format($goods['shop_price']);

                $this->assign('goods', $goods);
            }

            $this->assign('shopname', $GLOBALS['_CFG']['shop_name']);
            $this->assign('userid', $user_id);
            $this->assign('shopurl', $GLOBALS['ecs']->url());
            $this->assign('logosrc', 'themes/' . $GLOBALS['_CFG']['template'] . '/images/logo.gif');

            return $this->fetch('user_clips');
        } //首页邮件订阅ajax操做和验证操作
        elseif ($action == 'email_list') {
            $job = $_GET['job'];

            if ($job == 'add' || $job == 'del') {
                if (session('?last_email_query')) {
                    if (time() - session('last_email_query') <= 30) {
                        return $GLOBALS['_LANG']['order_query_toofast'];
                    }
                }
                session('last_email_query', time());
            }

            $email = trim($_GET['email']);
            $email = htmlspecialchars($email);

            if (!is_email($email)) {
                $info = sprintf($GLOBALS['_LANG']['email_invalid'], $email);
                return $info;
            }
            $ck = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('email_list') . " WHERE email = '$email'");
            if ($job == 'add') {
                if (empty($ck)) {
                    $hash = substr(md5(time()), 1, 10);
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('email_list') . " (email, stat, hash) VALUES ('$email', 0, '$hash')";
                    $GLOBALS['db']->query($sql);
                    $info = $GLOBALS['_LANG']['email_check'];
                    $url = $GLOBALS['ecs']->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
                    send_mail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $GLOBALS['_CFG']['shop_name'], $url, $url, $GLOBALS['_CFG']['shop_name'], local_date('Y-m-d')), 1);
                } elseif ($ck['stat'] == 1) {
                    $info = sprintf($GLOBALS['_LANG']['email_alreadyin_list'], $email);
                } else {
                    $hash = substr(md5(time()), 1, 10);
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
                    $GLOBALS['db']->query($sql);
                    $info = $GLOBALS['_LANG']['email_re_check'];
                    $url = $GLOBALS['ecs']->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
                    send_mail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $GLOBALS['_CFG']['shop_name'], $url, $url, $GLOBALS['_CFG']['shop_name'], local_date('Y-m-d')), 1);
                }
                return $info;
            } elseif ($job == 'del') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_notin_list'], $email);
                } elseif ($ck['stat'] == 1) {
                    $hash = substr(md5(time()), 1, 10);
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
                    $GLOBALS['db']->query($sql);
                    $info = $GLOBALS['_LANG']['email_check'];
                    $url = $GLOBALS['ecs']->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
                    send_mail('', $email, $GLOBALS['_LANG']['check_mail'], sprintf($GLOBALS['_LANG']['check_mail_content'], $email, $GLOBALS['_CFG']['shop_name'], $url, $url, $GLOBALS['_CFG']['shop_name'], local_date('Y-m-d')), 1);
                } else {
                    $info = $GLOBALS['_LANG']['email_not_alive'];
                }
                return $info;
            } elseif ($job == 'add_check') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_notin_list'], $email);
                } elseif ($ck['stat'] == 1) {
                    $info = $GLOBALS['_LANG']['email_checked'];
                } else {
                    if ($_GET['hash'] == $ck['hash']) {
                        $sql = "UPDATE " . $GLOBALS['ecs']->table('email_list') . "SET stat = 1 WHERE email = '$email'";
                        $GLOBALS['db']->query($sql);
                        $info = $GLOBALS['_LANG']['email_checked'];
                    } else {
                        $info = $GLOBALS['_LANG']['hash_wrong'];
                    }
                }
                return show_message($info, $GLOBALS['_LANG']['back_home_lnk'], 'index.php');
            } elseif ($job == 'del_check') {
                if (empty($ck)) {
                    $info = sprintf($GLOBALS['_LANG']['email_invalid'], $email);
                } elseif ($ck['stat'] == 1) {
                    if ($_GET['hash'] == $ck['hash']) {
                        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('email_list') . "WHERE email = '$email'";
                        $GLOBALS['db']->query($sql);
                        $info = $GLOBALS['_LANG']['email_canceled'];
                    } else {
                        $info = $GLOBALS['_LANG']['hash_wrong'];
                    }
                } else {
                    $info = $GLOBALS['_LANG']['email_not_alive'];
                }
                return show_message($info, $GLOBALS['_LANG']['back_home_lnk'], 'index.php');
            }
        } /* ajax 发送验证邮件 */
        elseif ($action == 'send_hash_mail') {
            load_helper('passport');

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return json_encode($result);
            }

            if (send_regiter_hash($user_id)) {
                $result['message'] = $GLOBALS['_LANG']['validate_mail_ok'];
                return json_encode($result);
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['err']->last_message();
            }

            return json_encode($result);
        } elseif ($action == 'track_packages') {
            load_helper('transaction');
            load_helper('order');

            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $orders = [];

            $sql = "SELECT order_id,order_sn,invoice_no,shipping_id FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE user_id = '$user_id' AND shipping_status = '" . SS_SHIPPED . "'";
            $res = $GLOBALS['db']->query($sql);
            $record_count = 0;
            foreach ($res as $item) {
                $shipping = get_shipping_object($item['shipping_id']);

                if (method_exists($shipping, 'query')) {
                    $query_link = $shipping->query($item['invoice_no']);
                } else {
                    $query_link = $item['invoice_no'];
                }

                if ($query_link != $item['invoice_no']) {
                    $item['query_link'] = $query_link;
                    $orders[] = $item;
                    $record_count += 1;
                }
            }
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $this->assign('pager', $pager);
            $this->assign('orders', $orders);
            return $this->fetch('user_transaction');
        } elseif ($action == 'order_query') {
            $_GET['order_sn'] = trim(substr($_GET['order_sn'], 1));
            $order_sn = empty($_GET['order_sn']) ? '' : addslashes($_GET['order_sn']);

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if (session('?last_order_query')) {
                if (time() - session('last_order_query') <= 10) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['order_query_toofast'];
                    return json_encode($result);
                }
            }
            session('last_order_query', time());

            if (empty($order_sn)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_order_sn'];
                return json_encode($result);
            }

            $sql = "SELECT order_id, order_status, shipping_status, pay_status, " .
                " shipping_time, shipping_id, invoice_no, user_id " .
                " FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE order_sn = '$order_sn' LIMIT 1";

            $row = $GLOBALS['db']->getRow($sql);
            if (empty($row)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_order_sn'];
                return json_encode($result);
            }

            $order_query = [];
            $order_query['order_sn'] = $order_sn;
            $order_query['order_id'] = $row['order_id'];
            $order_query['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

            if ($row['invoice_no'] && $row['shipping_id'] > 0) {
                $sql = "SELECT shipping_code FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE shipping_id = '$row[shipping_id]'";
                $shipping_code = $GLOBALS['db']->getOne($sql);
                $plugin = '\\app\\plugins\\payment\\' . parse_name($shipping_code, true);
                if (class_exists($plugin)) {
                    $shipping = new $plugin;
                    $order_query['invoice_no'] = $shipping->query((string)$row['invoice_no']);
                } else {
                    $order_query['invoice_no'] = (string)$row['invoice_no'];
                }
            }

            $order_query['user_id'] = $row['user_id'];
            /* 如果是匿名用户显示发货时间 */
            if ($row['user_id'] == 0 && $row['shipping_time'] > 0) {
                $order_query['shipping_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['shipping_time']);
            }
            $this->assign('order_query', $order_query);
            $result['content'] = $GLOBALS['smarty']->display('library/order_query.lbi');
            return json_encode($result);
        } elseif ($action == 'transform_points') {
            $rule = [];
            if (!empty($GLOBALS['_CFG']['points_rule'])) {
                $rule = unserialize($GLOBALS['_CFG']['points_rule']);
            }
            $cfg = [];
            if (!empty($GLOBALS['_CFG']['integrate_config'])) {
                $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);
                $GLOBALS['_LANG']['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $GLOBALS['_LANG']['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
                $GLOBALS['_LANG']['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $GLOBALS['_LANG']['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
            }
            $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='$user_id'";
            $row = $GLOBALS['db']->getRow($sql);
            if ($GLOBALS['_CFG']['integrate_code'] == 'ucenter') {
                $exchange_type = 'ucenter';
                $to_credits_options = [];
                $out_exchange_allow = [];
                foreach ($rule as $credit) {
                    $out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
                    if (!array_key_exists($credit['appiddesc'] . '|' . $credit['creditdesc'], $to_credits_options)) {
                        $to_credits_options[$credit['appiddesc'] . '|' . $credit['creditdesc']] = $credit['title'];
                    }
                }
                $this->assign('selected_org', $rule[0]['creditsrc']);
                $this->assign('selected_dst', $rule[0]['appiddesc'] . '|' . $rule[0]['creditdesc']);
                $this->assign('descreditunit', $rule[0]['unit']);
                $this->assign('orgcredittitle', $GLOBALS['_LANG']['exchange_points'][$rule[0]['creditsrc']]);
                $this->assign('descredittitle', $rule[0]['title']);
                $this->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
                $this->assign('to_credits_options', $to_credits_options);
                $this->assign('out_exchange_allow', $out_exchange_allow);
            } else {
                $exchange_type = 'other';

                $bbs_points_name = $GLOBALS['user']->get_points_name();
                $total_bbs_points = $GLOBALS['user']->get_points($row['user_name']);

                /* 论坛积分 */
                $bbs_points = [];
                foreach ($bbs_points_name as $key => $val) {
                    $bbs_points[$key] = ['title' => $GLOBALS['_LANG']['bbs'] . $val['title'], 'value' => $total_bbs_points[$key]];
                }

                /* 兑换规则 */
                $rule_list = [];
                foreach ($rule as $key => $val) {
                    $rule_key = substr($key, 0, 1);
                    $bbs_key = substr($key, 1);
                    $rule_list[$key]['rate'] = $val;
                    switch ($rule_key) {
                        case TO_P:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['pay_points'];
                            break;
                        case TO_R:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['rank_points'];
                            break;
                        case FROM_P:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['pay_points'];
                            $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            break;
                        case FROM_R:
                            $rule_list[$key]['from'] = $GLOBALS['_LANG']['rank_points'];
                            $rule_list[$key]['to'] = $GLOBALS['_LANG']['bbs'] . $bbs_points_name[$bbs_key]['title'];
                            break;
                    }
                }
                $this->assign('bbs_points', $bbs_points);
                $this->assign('rule_list', $rule_list);
            }
            $this->assign('shop_points', $row);
            $this->assign('exchange_type', $exchange_type);
            $this->assign('action', $action);
            $this->assign('lang', $GLOBALS['_LANG']);
            return $this->fetch('user_transaction');
        } elseif ($action == 'act_transform_points') {
            $rule_index = empty($_POST['rule_index']) ? '' : trim($_POST['rule_index']);
            $num = empty($_POST['num']) ? 0 : intval($_POST['num']);

            if ($num <= 0 || $num != floor($num)) {
                return show_message($GLOBALS['_LANG']['invalid_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }

            $num = floor($num); //格式化为整数

            $bbs_key = substr($rule_index, 1);
            $rule_key = substr($rule_index, 0, 1);

            $max_num = 0;

            /* 取出用户数据 */
            $sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='$user_id'";
            $row = $GLOBALS['db']->getRow($sql);
            $bbs_points = $GLOBALS['user']->get_points($row['user_name']);
            $points_name = $GLOBALS['user']->get_points_name();

            $rule = [];
            if ($GLOBALS['_CFG']['points_rule']) {
                $rule = unserialize($GLOBALS['_CFG']['points_rule']);
            }
            list($from, $to) = explode(':', $rule[$rule_index]);

            $max_points = 0;
            switch ($rule_key) {
                case TO_P:
                    $max_points = $bbs_points[$bbs_key];
                    break;
                case TO_R:
                    $max_points = $bbs_points[$bbs_key];
                    break;
                case FROM_P:
                    $max_points = $row['pay_points'];
                    break;
                case FROM_R:
                    $max_points = $row['rank_points'];
            }

            /* 检查积分是否超过最大值 */
            if ($max_points <= 0 || $num > $max_points) {
                return show_message($GLOBALS['_LANG']['overflow_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }

            switch ($rule_key) {
                case TO_P:
                    $result_points = floor($num * $to / $from);
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => 0 - $num]); //调整论坛积分
                    log_account_change($row['user_id'], 0, 0, 0, $result_points, $GLOBALS['_LANG']['transform_points'], ACT_OTHER);
                    return show_message(sprintf($GLOBALS['_LANG']['to_pay_points'], $num, $points_name[$bbs_key]['title'], $result_points), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case TO_R:
                    $result_points = floor($num * $to / $from);
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => 0 - $num]); //调整论坛积分
                    log_account_change($row['user_id'], 0, 0, $result_points, 0, $GLOBALS['_LANG']['transform_points'], ACT_OTHER);
                    return show_message(sprintf($GLOBALS['_LANG']['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case FROM_P:
                    $result_points = floor($num * $to / $from);
                    log_account_change($row['user_id'], 0, 0, 0, 0 - $num, $GLOBALS['_LANG']['transform_points'], ACT_OTHER); //调整商城积分
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => $result_points]); //调整论坛积分
                    return show_message(sprintf($GLOBALS['_LANG']['from_pay_points'], $num, $result_points, $points_name[$bbs_key]['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');

                case FROM_R:
                    $result_points = floor($num * $to / $from);
                    log_account_change($row['user_id'], 0, 0, 0 - $num, 0, $GLOBALS['_LANG']['transform_points'], ACT_OTHER); //调整商城积分
                    $GLOBALS['user']->set_points($row['user_name'], [$bbs_key => $result_points]); //调整论坛积分
                    return show_message(sprintf($GLOBALS['_LANG']['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
        } elseif ($action == 'act_transform_ucenter_points') {
            $rule = [];
            if ($GLOBALS['_CFG']['points_rule']) {
                $rule = unserialize($GLOBALS['_CFG']['points_rule']);
            }
            $shop_points = [0 => 'rank_points', 1 => 'pay_points'];
            $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='$user_id'";
            $row = $GLOBALS['db']->getRow($sql);
            $exchange_amount = intval($_POST['amount']);
            $fromcredits = intval($_POST['fromcredits']);
            $tocredits = trim($_POST['tocredits']);
            $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);
            if (!empty($cfg)) {
                $GLOBALS['_LANG']['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $GLOBALS['_LANG']['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
                $GLOBALS['_LANG']['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $GLOBALS['_LANG']['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
            }
            list($appiddesc, $creditdesc) = explode('|', $tocredits);
            $ratio = 0;

            if ($exchange_amount <= 0) {
                return show_message($GLOBALS['_LANG']['invalid_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            if ($exchange_amount > $row[$shop_points[$fromcredits]]) {
                return show_message($GLOBALS['_LANG']['overflow_points'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            foreach ($rule as $credit) {
                if ($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits) {
                    $ratio = $credit['ratio'];
                    break;
                }
            }
            if ($ratio == 0) {
                return show_message($GLOBALS['_LANG']['exchange_deny'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
            $netamount = floor($exchange_amount / $ratio);
            load_helper('uc');
            $result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
            if ($result === true) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET {$shop_points[$fromcredits]}={$shop_points[$fromcredits]}-'$exchange_amount' WHERE user_id='{$row['user_id']}'";
                $GLOBALS['db']->query($sql);
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('account_log') . "(user_id, {$shop_points[$fromcredits]}, change_time, change_desc, change_type)" . " VALUES ('{$row['user_id']}', '-$exchange_amount', '" . gmtime() . "', '" . $cfg['uc_lang']['exchange'] . "', '98')";
                $GLOBALS['db']->query($sql);
                return show_message(sprintf($GLOBALS['_LANG']['exchange_success'], $exchange_amount, $GLOBALS['_LANG']['exchange_points'][$fromcredits], $netamount, $credit['title']), $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            } else {
                return show_message($GLOBALS['_LANG']['exchange_error_1'], $GLOBALS['_LANG']['transform_points'], 'user.php?act=transform_points');
            }
        } /* 清除商品浏览历史 */
        elseif ($action == 'clear_history') {
            setcookie('ECS[history]', '', 1);
        }
    }
}
