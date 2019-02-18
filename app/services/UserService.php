<?php

namespace app\services;

use app\repositories\UserRepository;

/**
 * Class UserService
 * @package app\services
 */
class UserService
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * UserService constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getInfo($id)
    {
        return $this->userRepository->getUserById($id);
    }

    /**
     * 修改个人资料（Email, 性别，生日)
     *
     * @access  public
     * @param   array $profile array_keys(user_id int, email string, sex int, birthday string);
     *
     * @return  boolen      $bool
     */
    public function edit_profile($profile)
    {
        if (empty($profile['user_id'])) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);

            return false;
        }

        $cfg = [];
        $cfg['username'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='" . $profile['user_id'] . "'");
        if (isset($profile['sex'])) {
            $cfg['gender'] = intval($profile['sex']);
        }
        if (!empty($profile['email'])) {
            if (!is_email($profile['email'])) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $profile['email']));

                return false;
            }
            $cfg['email'] = $profile['email'];
        }
        if (!empty($profile['birthday'])) {
            $cfg['bday'] = $profile['birthday'];
        }

        if (!$GLOBALS['user']->edit_user($cfg)) {
            if ($GLOBALS['user']->error == ERR_EMAIL_EXISTS) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_exist'], $profile['email']));
            } else {
                $GLOBALS['err']->add('DB ERROR!');
            }

            return false;
        }

        /* 过滤非法的键值 */
        $other_key_array = ['msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone'];
        foreach ($profile['other'] as $key => $val) {
            //删除非法key值
            if (!in_array($key, $other_key_array)) {
                unset($profile['other'][$key]);
            } else {
                $profile['other'][$key] = htmlspecialchars(trim($val)); //防止用户输入javascript代码
            }
        }
        /* 修改在其他资料 */
        if (!empty($profile['other'])) {
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $profile['other'], 'UPDATE', "user_id = '$profile[user_id]'");
        }

        return true;
    }

    /**
     * 获取用户帐号信息
     *
     * @access  public
     * @param   int $user_id 用户user_id
     *
     * @return void
     */
    public function get_profile($user_id)
    {

        /* 会员帐号信息 */
        $info = [];
        $infos = [];
        $sql = "SELECT user_name, birthday, sex, question, answer, rank_points, pay_points,user_money, user_rank," .
            " msn, qq, office_phone, home_phone, mobile_phone, passwd_question, passwd_answer " .
            "FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
        $infos = $GLOBALS['db']->getRow($sql);
        $infos['user_name'] = addslashes($infos['user_name']);

        $row = $GLOBALS['user']->get_profile_by_name($infos['user_name']); //获取用户帐号信息
        session(['email' => $row['email']]);    //注册SESSION

        /* 会员等级 */
        if ($infos['user_rank'] > 0) {
            $sql = "SELECT rank_id, rank_name, discount FROM " . $GLOBALS['ecs']->table('user_rank') .
                " WHERE rank_id = '$infos[user_rank]'";
        } else {
            $sql = "SELECT rank_id, rank_name, discount, min_points" .
                " FROM " . $GLOBALS['ecs']->table('user_rank') .
                " WHERE min_points<= " . intval($infos['rank_points']) . " ORDER BY min_points DESC";
        }

        if ($row = $GLOBALS['db']->getRow($sql)) {
            $info['rank_name'] = $row['rank_name'];
        } else {
            $info['rank_name'] = $GLOBALS['_LANG']['undifine_rank'];
        }

        $cur_date = date('Y-m-d H:i:s');

        /* 会员红包 */
        $bonus = [];
        $sql = "SELECT type_name, type_money " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') . " AS t1, " . $GLOBALS['ecs']->table('user_bonus') . " AS t2 " .
            "WHERE t1.type_id = t2.bonus_type_id AND t2.user_id = '$user_id' AND t1.use_start_date <= '$cur_date' " .
            "AND t1.use_end_date > '$cur_date' AND t2.order_id = 0";
        $bonus = $GLOBALS['db']->getAll($sql);
        if ($bonus) {
            for ($i = 0, $count = count($bonus); $i < $count; $i++) {
                $bonus[$i]['type_money'] = price_format($bonus[$i]['type_money'], false);
            }
        }

        $info['discount'] = session('discount') * 100 . "%";
        $info['email'] = session('email');
        $info['user_name'] = session('user_name');
        $info['rank_points'] = isset($infos['rank_points']) ? $infos['rank_points'] : '';
        $info['pay_points'] = isset($infos['pay_points']) ? $infos['pay_points'] : 0;
        $info['user_money'] = isset($infos['user_money']) ? $infos['user_money'] : 0;
        $info['sex'] = isset($infos['sex']) ? $infos['sex'] : 0;
        $info['birthday'] = isset($infos['birthday']) ? $infos['birthday'] : '';
        $info['question'] = isset($infos['question']) ? htmlspecialchars($infos['question']) : '';

        $info['user_money'] = price_format($info['user_money'], false);
        $info['pay_points'] = $info['pay_points'] . $GLOBALS['_CFG']['integral_name'];
        $info['bonus'] = $bonus;
        $info['qq'] = $infos['qq'];
        $info['msn'] = $infos['msn'];
        $info['office_phone'] = $infos['office_phone'];
        $info['home_phone'] = $infos['home_phone'];
        $info['mobile_phone'] = $infos['mobile_phone'];
        $info['passwd_question'] = $infos['passwd_question'];
        $info['passwd_answer'] = $infos['passwd_answer'];

        return $info;
    }

    /**
     * 用户注册，登录函数
     *
     * @access  public
     * @param   string $username 注册用户名
     * @param   string $password 用户密码
     * @param   string $email 注册email
     * @param   array $other 注册的其他信息
     *
     * @return  bool         $bool
     */
    public function register($username, $password, $email, $other = [])
    {
        /* 检查注册是否关闭 */
        if (!empty($GLOBALS['_CFG']['shop_reg_closed'])) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['shop_register_closed']);
        }
        /* 检查username */
        if (empty($username)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['username_empty']);
        } else {
            if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username)) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_invalid'], htmlspecialchars($username)));
            }
        }

        /* 检查email */
        if (empty($email)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['email_empty']);
        } else {
            if (!is_email($email)) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], htmlspecialchars($email)));
            }
        }

        if ($GLOBALS['err']->error_no > 0) {
            return false;
        }

        /* 检查是否和管理员重名 */
        if (admin_registered($username)) {
            $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_exist'], $username));
            return false;
        }

        if (!$GLOBALS['user']->add_user($username, $password, $email)) {
            if ($GLOBALS['user']->error == ERR_INVALID_USERNAME) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_invalid'], $username));
            } elseif ($GLOBALS['user']->error == ERR_USERNAME_NOT_ALLOW) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_not_allow'], $username));
            } elseif ($GLOBALS['user']->error == ERR_USERNAME_EXISTS) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_exist'], $username));
            } elseif ($GLOBALS['user']->error == ERR_INVALID_EMAIL) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $email));
            } elseif ($GLOBALS['user']->error == ERR_EMAIL_NOT_ALLOW) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_not_allow'], $email));
            } elseif ($GLOBALS['user']->error == ERR_EMAIL_EXISTS) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_exist'], $email));
            } else {
                $GLOBALS['err']->add('UNKNOWN ERROR!');
            }

            //注册失败
            return false;
        } else {
            //注册成功

            /* 设置成登录状态 */
            $GLOBALS['user']->set_session($username);
            $GLOBALS['user']->set_cookie($username);

            /* 注册送积分 */
            if (!empty($GLOBALS['_CFG']['register_points'])) {
                log_account_change(session('user_id'), 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $GLOBALS['_LANG']['register_points']);
            }

            /*推荐处理*/
            $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
            if (isset($affiliate['on']) && $affiliate['on'] == 1) {
                // 推荐开关开启
                $up_uid = get_affiliate();
                empty($affiliate) && $affiliate = [];
                $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
                $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
                if ($up_uid) {
                    if (!empty($affiliate['config']['level_register_all'])) {
                        if (!empty($affiliate['config']['level_register_up'])) {
                            $rank_points = $GLOBALS['db']->getOne("SELECT rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$up_uid'");
                            if ($rank_points + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up']) {
                                log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf($GLOBALS['_LANG']['register_affiliate'], session('user_id'), $username));
                            }
                        } else {
                            log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, $GLOBALS['_LANG']['register_affiliate']);
                        }
                    }

                    //设置推荐人
                    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET parent_id = ' . $up_uid . ' WHERE user_id = ' . session('user_id');

                    $GLOBALS['db']->query($sql);
                }
            }

            //定义other合法的变量数组
            $other_key_array = ['msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone'];
            $update_data['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));
            if ($other) {
                foreach ($other as $key => $val) {
                    //删除非法key值
                    if (!in_array($key, $other_key_array)) {
                        unset($other[$key]);
                    } else {
                        $other[$key] = htmlspecialchars(trim($val)); //防止用户输入javascript代码
                    }
                }
                $update_data = array_merge($update_data, $other);
            }
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $update_data, 'UPDATE', 'user_id = ' . session('user_id'));

            update_user_info();      // 更新用户信息
            recalculate_price();     // 重新计算购物车中的商品价格

            return true;
        }
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function logout()
    {
        /* todo */
    }

    /**
     *  将指定user_id的密码修改为new_password。可以通过旧密码和验证字串验证修改。
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   string $new_password 用户新密码
     * @param   string $old_password 用户旧密码
     * @param   string $code 验证码（md5($user_id . md5($password))）
     *
     * @return  boolen  $bool
     */
    public function edit_password($user_id, $old_password, $new_password = '', $code = '')
    {
        if (empty($user_id)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);
        }

        if ($GLOBALS['user']->edit_password($user_id, $old_password, $new_password, $code)) {
            return true;
        } else {
            $GLOBALS['err']->add($GLOBALS['_LANG']['edit_password_failure']);

            return false;
        }
    }

    /**
     *  会员找回密码时，对输入的用户名和邮件地址匹配
     *
     * @access  public
     * @param   string $user_name 用户帐号
     * @param   string $email 用户Email
     *
     * @return  boolen
     */
    public function check_userinfo($user_name, $email)
    {
        if (empty($user_name) || empty($email)) {
            return ecs_header("Location: user.php?act=get_password\n");
        }

        /* 检测用户名和邮件地址是否匹配 */
        $user_info = $GLOBALS['user']->check_pwd_info($user_name, $email);
        if (!empty($user_info)) {
            return $user_info;
        } else {
            return false;
        }
    }

    /**
     *  用户进行密码找回操作时，发送一封确认邮件
     *
     * @access  public
     * @param   string $uid 用户ID
     * @param   string $user_name 用户帐号
     * @param   string $email 用户Email
     * @param   string $code key
     *
     * @return  boolen  $result;
     */
    public function send_pwd_email($uid, $user_name, $email, $code)
    {
        if (empty($uid) || empty($user_name) || empty($email) || empty($code)) {
            return ecs_header("Location: user.php?act=get_password\n");
        }

        /* 设置重置邮件模板所需要的内容信息 */
        $template = get_mail_template('send_password');
        $reset_email = $GLOBALS['ecs']->url() . 'user.php?act=get_password&uid=' . $uid . '&code=' . $code;

        $GLOBALS['smarty']->assign('user_name', $user_name);
        $GLOBALS['smarty']->assign('reset_email', $reset_email);
        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
        $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

        $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

        /* 发送确认重置密码的确认邮件 */
        if (send_mail($user_name, $email, $template['template_subject'], $content, $template['is_html'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  发送激活验证邮件
     *
     * @access  public
     * @param   int $user_id 用户ID
     *
     * @return boolen
     */
    public function send_regiter_hash($user_id)
    {
        /* 设置验证邮件模板所需要的内容信息 */
        $template = get_mail_template('register_validate');
        $hash = register_hash('encode', $user_id);
        $validate_email = $GLOBALS['ecs']->url() . 'user.php?act=validate_email&hash=' . $hash;

        $sql = "SELECT user_name, email FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
        $row = $GLOBALS['db']->getRow($sql);

        $GLOBALS['smarty']->assign('user_name', $row['user_name']);
        $GLOBALS['smarty']->assign('validate_email', $validate_email);
        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('send_date', date($GLOBALS['_CFG']['date_format']));

        $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

        /* 发送激活验证邮件 */
        if (send_mail($row['user_name'], $row['email'], $template['template_subject'], $content, $template['is_html'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  生成邮件验证hash
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function register_hash($operation, $key)
    {
        if ($operation == 'encode') {
            $user_id = intval($key);
            $sql = "SELECT reg_time " .
                " FROM " . $GLOBALS['ecs']->table('users') .
                " WHERE user_id = '$user_id' LIMIT 1";
            $reg_time = $GLOBALS['db']->getOne($sql);

            $hash = substr(md5($user_id . $GLOBALS['_CFG']['hash_code'] . $reg_time), 16, 4);

            return base64_encode($user_id . ',' . $hash);
        } else {
            $hash = base64_decode(trim($key));
            $row = explode(',', $hash);
            if (count($row) != 2) {
                return 0;
            }
            $user_id = intval($row[0]);
            $salt = trim($row[1]);

            if ($user_id <= 0 || strlen($salt) != 4) {
                return 0;
            }

            $sql = "SELECT reg_time " .
                " FROM " . $GLOBALS['ecs']->table('users') .
                " WHERE user_id = '$user_id' LIMIT 1";
            $reg_time = $GLOBALS['db']->getOne($sql);

            $pre_salt = substr(md5($user_id . $GLOBALS['_CFG']['hash_code'] . $reg_time), 16, 4);

            if ($pre_salt == $salt) {
                return $user_id;
            } else {
                return 0;
            }
        }
    }

    /**
     * 取得用户信息
     * @param   int $user_id 用户id
     * @return  array   用户信息
     */
    public function user_info($user_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('users') .
            " WHERE user_id = '$user_id'";
        $user = $GLOBALS['db']->getRow($sql);

        unset($user['question']);
        unset($user['answer']);

        /* 格式化帐户余额 */
        if ($user) {
//        if ($user['user_money'] < 0)
//        {
//            $user['user_money'] = 0;
//        }
            $user['formated_user_money'] = price_format($user['user_money'], false);
            $user['formated_frozen_money'] = price_format($user['frozen_money'], false);
        }

        return $user;
    }

    /**
     * 修改用户
     * @param   int $user_id 订单id
     * @param   array $user key => value
     * @return  bool
     */
    public function update_user($user_id, $user)
    {
        return $GLOBALS['db']->autoExecute(
            $GLOBALS['ecs']->table('users'),
            $user,
            'UPDATE',
            "user_id = '$user_id'"
        );
    }

    /**
     * 更新用户SESSION,COOKIE及登录时间、登录次数。
     *
     * @access  public
     * @return  void
     */
    public function update_user_info()
    {
        if (!session('user_id')) {
            return false;
        }

        /* 查询会员信息 */
        $time = date('Y-m-d');
        $sql = 'SELECT u.user_money,u.email, u.pay_points, u.user_rank, u.rank_points, ' .
            ' IFNULL(b.type_money, 0) AS user_bonus, u.last_login, u.last_ip' .
            ' FROM ' . $GLOBALS['ecs']->table('users') . ' AS u ' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('user_bonus') . ' AS ub' .
            ' ON ub.user_id = u.user_id AND ub.used_time = 0 ' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('bonus_type') . ' AS b' .
            " ON b.type_id = ub.bonus_type_id AND b.use_start_date <= '$time' AND b.use_end_date >= '$time' " .
            " WHERE u.user_id = '" . session('user_id') . "'";
        if ($row = $GLOBALS['db']->getRow($sql)) {
            /* 更新SESSION */
            session(['last_time' => $row['last_login']]);
            session(['last_ip' => $row['last_ip']]);
            session(['login_fail' => 0]);
            session(['email' => $row['email']]);

            /*判断是否是特殊等级，可能后台把特殊会员组更改普通会员组*/
            if ($row['user_rank'] > 0) {
                $sql = "SELECT special_rank from " . $GLOBALS['ecs']->table('user_rank') . "where rank_id='$row[user_rank]'";
                if ($GLOBALS['db']->getOne($sql) === '0' || $GLOBALS['db']->getOne($sql) === null) {
                    $sql = "update " . $GLOBALS['ecs']->table('users') . "set user_rank='0' where user_id='" . session('user_id') . "'";
                    $GLOBALS['db']->query($sql);
                    $row['user_rank'] = 0;
                }
            }

            /* 取得用户等级和折扣 */
            if ($row['user_rank'] == 0) {
                // 非特殊等级，根据等级积分计算用户等级（注意：不包括特殊等级）
                $sql = 'SELECT rank_id, discount FROM ' . $GLOBALS['ecs']->table('user_rank') . " WHERE special_rank = '0' AND min_points <= " . intval($row['rank_points']) . ' AND max_points > ' . intval($row['rank_points']);
                if ($row = $GLOBALS['db']->getRow($sql)) {
                    session(['user_rank' => $row['rank_id']]);
                    session(['discount' => $row['discount'] / 100.00]);
                } else {
                    session(['user_rank' => 0]);
                    session(['discount' => 1]);
                }
            } else {
                // 特殊等级
                $sql = 'SELECT rank_id, discount FROM ' . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '$row[user_rank]'";
                if ($row = $GLOBALS['db']->getRow($sql)) {
                    session(['user_rank' => $row['rank_id']]);
                    session(['discount' => $row['discount'] / 100.00]);
                } else {
                    session(['user_rank' => 0]);
                    session(['discount' => 1]);
                }
            }
        }

        /* 更新登录时间，登录次数及登录ip */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET" .
            " visit_count = visit_count + 1, " .
            " last_ip = '" . real_ip() . "'," .
            " last_login = '" . gmtime() . "'" .
            " WHERE user_id = '" . session('user_id') . "'";
        $GLOBALS['db']->query($sql);
    }

    /**
     *  获取用户信息数组
     *
     * @access  public
     * @param
     *
     * @return array        $user       用户信息数组
     */
    public function get_user_info($id = 0)
    {
        if ($id == 0) {
            $id = session('user_id');
        }
        $time = date('Y-m-d');
        $sql = 'SELECT u.user_id, u.email, u.user_name, u.user_money, u.pay_points' .
            ' FROM ' . $GLOBALS['ecs']->table('users') . ' AS u ' .
            " WHERE u.user_id = '$id'";
        $user = $GLOBALS['db']->getRow($sql);
        $bonus = get_user_bonus($id);

        $user['username'] = $user['user_name'];
        $user['user_points'] = $user['pay_points'] . $GLOBALS['_CFG']['integral_name'];
        $user['user_money'] = price_format($user['user_money'], false);
        $user['user_bonus'] = price_format($bonus['bonus_value'], false);

        return $user;
    }

    /**
     * 保存推荐uid
     *
     * @access  public
     * @param   void
     *
     * @return void
     * @author xuanyan
     **/
    public function set_affiliate()
    {
        $config = unserialize($GLOBALS['_CFG']['affiliate']);
        if (!empty($_GET['u']) && $config['on'] == 1) {
            if (!empty($config['config']['expire'])) {
                if ($config['config']['expire_unit'] == 'hour') {
                    $c = 1;
                } elseif ($config['config']['expire_unit'] == 'day') {
                    $c = 24;
                } elseif ($config['config']['expire_unit'] == 'week') {
                    $c = 24 * 7;
                } else {
                    $c = 1;
                }
                setcookie('ectouch_affiliate_uid', intval($_GET['u']), gmtime() + 3600 * $config['config']['expire'] * $c);
            } else {
                setcookie('ectouch_affiliate_uid', intval($_GET['u']), gmtime() + 3600 * 24); // 过期时间为 1 天
            }
        }
    }

    /**
     * 获取推荐uid
     *
     * @access  public
     * @param   void
     *
     * @return int
     * @author xuanyan
     **/
    public function get_affiliate()
    {
        if (!empty($_COOKIE['ectouch_affiliate_uid'])) {
            $uid = intval($_COOKIE['ectouch_affiliate_uid']);
            if ($GLOBALS['db']->getOne('SELECT user_id FROM ' . $GLOBALS['ecs']->table('users') . "WHERE user_id = '$uid'")) {
                return $uid;
            } else {
                setcookie('ectouch_affiliate_uid', '', 1);
            }
        }

        return 0;
    }

    /**
     * 获取用户中心默认页面所需的数据
     *
     * @access  public
     * @param   int $user_id 用户ID
     *
     * @return  array       $info               默认页面所需资料数组
     */
    public function get_user_default($user_id)
    {
        $user_bonus = get_user_bonus();

        $sql = "SELECT pay_points, user_money, credit_line, last_login, is_validated FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
        $row = $GLOBALS['db']->getRow($sql);
        $info = [];
        $info['username'] = stripslashes(session('user_name'));
        $info['shop_name'] = $GLOBALS['_CFG']['shop_name'];
        $info['integral'] = $row['pay_points'] . $GLOBALS['_CFG']['integral_name'];
        /* 增加是否开启会员邮件验证开关 */
        $info['is_validate'] = ($GLOBALS['_CFG']['member_email_validate'] && !$row['is_validated']) ? 0 : 1;
        $info['credit_line'] = $row['credit_line'];
        $info['formated_credit_line'] = price_format($info['credit_line'], false);

        //如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
        $last_time = session(['last_time' => $row['last_login']]);

        if ($last_time == 0) {
            $last_time = gmtime();
            session(['last_time' => $last_time]);
        }

        $info['last_time'] = local_date($GLOBALS['_CFG']['time_format'], $last_time);
        $info['surplus'] = price_format($row['user_money'], false);
        $info['bonus'] = sprintf($GLOBALS['_LANG']['user_bonus_info'], $user_bonus['bonus_count'], price_format($user_bonus['bonus_value'], false));

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id = '" . $user_id . "' AND add_time > '" . local_strtotime('-1 months') . "'";
        $info['order_count'] = $GLOBALS['db']->getOne($sql);

        load_helper('order');
        $sql = "SELECT order_id, order_sn " .
            " FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id = '" . $user_id . "' AND shipping_time > '" . $last_time . "'" . order_query_sql('shipped');
        $info['shipped_order'] = $GLOBALS['db']->getAll($sql);

        return $info;
    }

    /**
     * 取得用户等级信息
     * @access   public
     * @author   Xuan Yan
     *
     * @return array
     */
    public function get_rank_info()
    {
        if (!empty(session('user_rank'))) {
            $sql = "SELECT rank_name, special_rank FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '" . session('user_rank') . "'";
            $row = $GLOBALS['db']->getRow($sql);
            if (empty($row)) {
                return [];
            }
            $rank_name = $row['rank_name'];
            if ($row['special_rank']) {
                return ['rank_name' => $rank_name];
            } else {
                $user_rank = $GLOBALS['db']->getOne("SELECT rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . session('user_id') . "'");
                $sql = "SELECT rank_name,min_points FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE min_points > '$user_rank' ORDER BY min_points ASC LIMIT 1";
                $rt = $GLOBALS['db']->getRow($sql);
                $next_rank_name = $rt['rank_name'];
                $next_rank = $rt['min_points'] - $user_rank;
                return ['rank_name' => $rank_name, 'next_rank_name' => $next_rank_name, 'next_rank' => $next_rank];
            }
        } else {
            return [];
        }
    }

    /**
     *  获取用户参与活动信息
     *
     * @access  public
     * @param   int $user_id 用户id
     *
     * @return  array
     */
    public function get_user_prompt($user_id)
    {
        $prompt = [];
        $now = gmtime();
        /* 夺宝奇兵 */
        $sql = "SELECT act_id, goods_name, end_time " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            " WHERE act_type = '" . GAT_SNATCH . "'" .
            " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $act_id = $row['act_id'];
            $result = get_snatch_result($act_id);
            if (isset($result['order_count']) && $result['order_count'] == 0 && $result['user_id'] == $user_id) {
                $prompt[] = [
                    'text' => sprintf($GLOBALS['_LANG']['your_snatch'], $row['goods_name'], $row['act_id']),
                    'add_time' => $row['end_time']
                ];
            }
            if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                $prompt[] = [
                    'text' => sprintf($GLOBALS['_LANG']['your_auction'], $row['goods_name'], $row['act_id']),
                    'add_time' => $row['end_time']
                ];
            }
        }

        /* 竞拍 */

        $sql = "SELECT act_id, goods_name, end_time " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            " WHERE act_type = '" . GAT_AUCTION . "'" .
            " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $act_id = $row['act_id'];
            $auction = auction_info($act_id);
            if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                $prompt[] = [
                    'text' => sprintf($GLOBALS['_LANG']['your_auction'], $row['goods_name'], $row['act_id']),
                    'add_time' => $row['end_time']
                ];
            }
        }

        /* 排序 */
        usort($prompt, function ($a, $b) {
            if ($a["add_time"] == $b["add_time"]) {
                return 0;
            };
            return $a["add_time"] < $b["add_time"] ? 1 : -1;
        });

        /* 格式化时间 */
        foreach ($prompt as $key => $val) {
            $prompt[$key]['formated_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
        }

        return $prompt;
    }

    /**
     * 取得会员等级列表
     * @return  array   会员等级列表
     */
    public function get_user_rank_list()
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_rank') .
            " ORDER BY min_points";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 取得用户等级数组,按用户级别排序
     * @param   bool $is_special 是否只显示特殊会员组
     * @return  array     rank_id=>rank_name
     */
    public function get_rank_list($is_special = false)
    {
        $rank_list = [];
        $sql = 'SELECT rank_id, rank_name, min_points FROM ' . $GLOBALS['ecs']->table('user_rank');
        if ($is_special) {
            $sql .= ' WHERE special_rank = 1';
        }
        $sql .= ' ORDER BY min_points';

        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $rank_list[$row['rank_id']] = $row['rank_name'];
        }

        return $rank_list;
    }

    /**
     * 按等级取得用户列表（用于生成下拉列表）
     *
     * @return  array       分类数组 user_id => user_name
     */
    public function get_user_rank($rankid, $where)
    {
        $user_list = [];
        $sql = 'SELECT user_id, user_name FROM ' . $GLOBALS['ecs']->table('users') . $where .
            ' ORDER BY user_id DESC';
        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $user_list[$row['user_id']] = $row['user_name'];
        }

        return $user_list;
    }

    /**
     * 初始化会员数据整合类
     *
     * @access  public
     * @return  object
     */
    public function init_users()
    {
        $set_modules = false;
        static $cls = null;
        if ($cls != null) {
            return $cls;
        }

        $integrate = '\\App\\Plugins\\Integrates\\' . ucfirst($GLOBALS['_CFG']['integrate_code']);
        $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);
        $cls = new $integrate($cfg);

        return $cls;
    }
}
