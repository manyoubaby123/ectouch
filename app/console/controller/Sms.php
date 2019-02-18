<?php

namespace app\console\controller;

use app\libraries\Sms;

class Sms extends Init
{
    public function index()
    {
        $action = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'display_my_info';
        if (isset($_POST['sms_sign_update'])) {
            $action = 'sms_sign_update';
        } elseif (isset($_POST['sms_sign_default'])) {
            $action = 'sms_sign_default';
        }

        $sms = new Sms();

        switch ($action) {

            /* 显示短信发送界面，如果尚未注册或启用短信服务则显示注册界面。 */
            case 'display_send_ui':
                /* 检查权限 */
                admin_priv('sms_send');

                if ($sms->has_registered()) {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['03_sms_send']);
                    $special_ranks = get_rank_list();
                    $send_rank['1_0'] = $GLOBALS['_LANG']['user_list'];
                    foreach ($special_ranks as $rank_key => $rank_value) {
                        $send_rank['2_' . $rank_key] = $rank_value;
                    }

                    $GLOBALS['smarty']->assign('send_rank', $send_rank);
                    return $GLOBALS['smarty']->display('sms_send_ui.htm');
                } else {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['register_sms']);
                    $GLOBALS['smarty']->assign('sms_site_info', $sms->get_site_info());

                    return $GLOBALS['smarty']->display('sms_register_ui.htm');
                }

                break;
            case 'sms_sign':
                admin_priv('sms_send');

                if ($sms->has_registered()) {
                    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shop_config') . "WHERE  code='sms_sign'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if (!empty($row['id'])) {
                        $sms_sign = unserialize($row['value']);
                        $t = [];
                        if (is_array($sms_sign) && isset($sms_sign[$GLOBALS['_CFG'][ent_id]])) {
                            foreach ($sms_sign[$GLOBALS['_CFG'][ent_id]] as $key => $val) {
                                $t[$GLOBALS['_CFG'][ent_id]][$key]['key'] = $key;
                                $t[$GLOBALS['_CFG'][ent_id]][$key]['value'] = $val;
                            }
                            $GLOBALS['smarty']->assign('sms_sign', $t[$GLOBALS['_CFG'][ent_id]]);
                        }
                    } else {
                        $this->shop_config_update('sms_sign', '');
                        $this->shop_config_update('default_sms_sign', '');
                    }
                    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shop_config') . "WHERE  code='default_sms_sign'";
                    $default_sms_sign = $GLOBALS['db']->getRow($sql);
                    $GLOBALS['smarty']->assign('default_sign', $default_sms_sign['value']);

                    return $GLOBALS['smarty']->display('sms_sign.htm');
                } else {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['register_sms']);
                    $GLOBALS['smarty']->assign('sms_site_info', $sms->get_site_info());

                    return $GLOBALS['smarty']->display('sms_register_ui.htm');
                }
                break;

            case 'sms_sign_add':
                admin_priv('sms_send');

                if ($sms->has_registered()) {
                    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shop_config') . "WHERE  code='sms_sign'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if (empty($_POST['sms_sign'])) {
                        return sys_msg($GLOBALS['_LANG']['insert_sign'], 1, [], false);
                    }

                    if (!empty($row['id'])) {
                        $sms_sign = unserialize($row['value']);
                        $GLOBALS['smarty']->assign('sms_sign', $sms_sign);
                        $data = [];
                        $data['shop_id'] = $GLOBALS['_CFG']['ent_id'];
                        $data['passwd'] = $GLOBALS['_CFG']['ent_ac'];

                        $content_t = $content_y = trim($_POST['sms_sign']);
                        if (EC_CHARSET != 'utf-8') {
                            $content_t = iconv('gb2312', 'utf-8', $content_y);
                        }

                        $url = 'https://www.ectouch.cn';
                        $key = 'qufoxtpr';
                        $secret = 't66moqjixb2nntiy2io2';
                        $c = new prism_client($url, $key, $secret);
                        $params = [
                            'shop_id' => $GLOBALS['_CFG']['ent_id'],
                            'passwd' => $GLOBALS['_CFG']['ent_ac'],
                            'content' => $content_t,
                            'content-type' => 'application/x-www-form-urlencoded'
                        ];
                        $result = $c->post('api/addcontent/new', $params);
                        $result = json_decode($result, true);
                        if ($result['res'] == 'succ' && !empty($result['data']['extend_no'])) {
                            $extend_no = $result['data']['extend_no'];
                            $sms_sign[$GLOBALS['_CFG']['ent_id']][$extend_no] = $content_y;
                            $sms_sign = serialize($sms_sign);
                            if (empty($GLOBALS['_CFG']['default_sms_sign'])) {
                                $this->shop_config_update('default_sms_sign', $content_y);
                            }
                            $this->shop_config_update('sms_sign', $sms_sign);
                            /* 清除缓存 */
                            clear_all_files();
                            return sys_msg($GLOBALS['_LANG']['insert_succ'], 1, [], false);
                        } else {
                            $error_smg = $result['data'];
                            if (EC_CHARSET != 'utf-8') {
                                $error_smg = iconv('utf-8', 'gb2312', $error_smg);
                            }
                            return sys_msg($error_smg, 1, [], false);
                        }
                    } else {
                        $this->shop_config_update('default_sms_sign', $content_y);
                        $this->shop_config_update('sms_sign', '');
                        /* 清除缓存 */
                        clear_all_files();
                        return sys_msg($GLOBALS['_LANG']['error_smg'], 1, [], false);
                    }
                } else {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['register_sms']);
                    $GLOBALS['smarty']->assign('sms_site_info', $sms->get_site_info());

                    return $GLOBALS['smarty']->display('sms_register_ui.htm');
                }
                break;

            case 'sms_sign_update':
                admin_priv('sms_send');
                if ($sms->has_registered()) {
                    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shop_config') . "WHERE  code='sms_sign'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if (!empty($row['id'])) {
                        $sms_sign = unserialize($row['value']);
                        $GLOBALS['smarty']->assign('sms_sign', $sms_sign);
                        $data = [];
                        $data['shop_id'] = $GLOBALS['_CFG']['ent_id'];
                        $data['passwd'] = $GLOBALS['_CFG']['ent_ac'];

                        $extend_no = $_POST['extend_no'];

                        $content_t = $content_y = $sms_sign[$GLOBALS['_CFG']['ent_id']][$extend_no];
                        $new_content_t = $new_content_y = $_POST['new_sms_sign'];

                        if (!isset($sms_sign[$GLOBALS['_CFG'][ent_id]][$extend_no]) || empty($extend_no)) {
                            return sys_msg($GLOBALS['_LANG']['error_smg'], 1, [], false);
                        }
                        if (EC_CHARSET != 'utf-8') {
                            $content_t = iconv('gb2312', 'utf-8', $content_y);
                            $new_content_t = iconv('gb2312', 'utf-8', $new_content_y);
                        }
                        $url = 'https://www.ectouch.cn';
                        $key = 'qufoxtpr';
                        $secret = 't66moqjixb2nntiy2io2';
                        $c = new prism_client($url, $key, $secret);
                        $params = [
                            'shop_id' => $GLOBALS['_CFG']['ent_id'],
                            'passwd' => $GLOBALS['_CFG']['ent_ac'],
                            'old_content' => $content_t,
                            'new_content' => $new_content_t,
                            'content-type' => 'application/x-www-form-urlencoded'
                        ];
                        $result = $c->post('api/addcontent/update', $params);
                        $result = json_decode($result, true);

                        if ($result['res'] == 'succ' && !empty($result['data']['new_extend_no'])) {
                            $new_extend_no = $result['data']['new_extend_no'];
                            unset($sms_sign[$GLOBALS['_CFG']['ent_id']][$extend_no]);
                            $sms_sign[$GLOBALS['_CFG']['ent_id']][$new_extend_no] = $new_content_y;

                            $sms_sign = serialize($sms_sign);
                            if (empty($GLOBALS['_CFG']['default_sms_sign'])) {
                                $this->shop_config_update('default_sms_sign', $new_content_y);
                            }
                            $this->shop_config_update('sms_sign', $sms_sign);

                            /* 清除缓存 */
                            clear_all_files();
                            return sys_msg($GLOBALS['_LANG']['edit_succ'], 1, [], false);
                        } else {
                            $error_smg = $result['data'];
                            if (EC_CHARSET != 'utf-8') {
                                $error_smg = iconv('utf-8', 'gb2312', $error_smg);
                            }
                            return sys_msg($error_smg, 1, [], false);
                        }
                    } else {
                        $this->shop_config_update('default_sms_sign', $content_y);
                        $this->shop_config_update('sms_sign', '');
                        /* 清除缓存 */
                        clear_all_files();
                        return sys_msg($GLOBALS['_LANG']['error_smg'], 1, [], false);
                    }
                } else {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['register_sms']);
                    $GLOBALS['smarty']->assign('sms_site_info', $sms->get_site_info());

                    return $GLOBALS['smarty']->display('sms_register_ui.htm');
                }
                break;

            case 'sms_sign_default':
                admin_priv('sms_send');
                if ($sms->has_registered()) {
                    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shop_config') . "WHERE  code='sms_sign'";
                    $row = $GLOBALS['db']->getRow($sql);
                    if (!empty($row['id'])) {
                        $sms_sign = unserialize($row['value']);
                        $GLOBALS['smarty']->assign('sms_sign', $sms_sign);
                        $data = [];
                        $data['shop_id'] = $GLOBALS['_CFG']['ent_id'];
                        $data['passwd'] = $GLOBALS['_CFG']['ent_ac'];

                        $extend_no = $_POST['extend_no'];

                        $sms_sign_default = $sms_sign[$GLOBALS['_CFG'][ent_id]][$extend_no];
                        if (!empty($sms_sign_default)) {
                            $this->shop_config_update('default_sms_sign', $sms_sign_default);
                            /* 清除缓存 */
                            clear_all_files();
                            return sys_msg($GLOBALS['_LANG']['default_succ'], 1, [], false);
                        } else {
                            return sys_msg($GLOBALS['_LANG']['no_default'], 1, [], false);
                        }
                    } else {
                        $this->shop_config_update('default_sms_sign', $content_y);
                        $this->shop_config_update('sms_sign', '');
                        /* 清除缓存 */
                        clear_all_files();
                        return sys_msg($GLOBALS['_LANG']['error_smg'], 1, [], false);
                    }
                } else {
                    $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['register_sms']);
                    $GLOBALS['smarty']->assign('sms_site_info', $sms->get_site_info());

                    return $GLOBALS['smarty']->display('sms_register_ui.htm');
                }
                break;

            /* 发送短信 */
            case 'send_sms':
                $send_num = isset($_POST['send_num']) ? $_POST['send_num'] : '';

                if (isset($send_num)) {
                    $phone = $send_num . ',';
                }

                $send_rank = isset($_POST['send_rank']) ? $_POST['send_rank'] : 0;

                if ($send_rank != 0) {
                    $rank_array = explode('_', $send_rank);

                    if ($rank_array['0'] == 1) {
                        $sql = 'SELECT mobile_phone FROM ' . $GLOBALS['ecs']->table('users') . "WHERE mobile_phone <>'' ";
                        $row = $GLOBALS['db']->query($sql);
                        foreach ($row as $rank_rs) {
                            $value[] = $rank_rs['mobile_phone'];
                        }
                    } else {
                        $rank_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '" . $rank_array['1'] . "'";
                        $rank_row = $GLOBALS['db']->getRow($rank_sql);
                        //$sql = 'SELECT mobile_phone FROM ' . $GLOBALS['ecs']->table('users') . "WHERE mobile_phone <>'' AND rank_points > " .$rank_row['min_points']." AND rank_points < ".$rank_row['max_points']." ";

                        if ($rank_row['special_rank'] == 1) {
                            $sql = 'SELECT mobile_phone FROM ' . $GLOBALS['ecs']->table('users') . " WHERE mobile_phone <>'' AND user_rank = '" . $rank_array['1'] . "'";
                        } else {
                            $sql = 'SELECT mobile_phone FROM ' . $GLOBALS['ecs']->table('users') . "WHERE mobile_phone <>'' AND rank_points > " . $rank_row['min_points'] . " AND rank_points < " . $rank_row['max_points'] . " ";
                        }

                        $row = $GLOBALS['db']->query($sql);

                        foreach ($row as $rank_rs) {
                            $value[] = $rank_rs['mobile_phone'];
                        }
                    }
                    if (isset($value)) {
                        $phone .= implode(',', $value);
                    }
                }

                $msg = isset($_POST['msg']) ? $_POST['msg'] : '';

                $send_date = isset($_POST['send_date']) ? $_POST['send_date'] : '';

                $result = $sms->send($phone, $msg, $send_date, $send_num = 13);

                $link[] = ['text' => $GLOBALS['_LANG']['back'] . $GLOBALS['_LANG']['03_sms_send'],
                    'href' => 'sms.php?act=display_send_ui'];

                if ($result === true) {//发送成功
                    return sys_msg($GLOBALS['_LANG']['send_ok'], 0, $link);
                } else {
                    @$error_detail = $GLOBALS['_LANG']['server_errors'][$sms->errors['server_errors']['error_no']]
                        . $GLOBALS['_LANG']['api_errors']['send'][$sms->errors['api_errors']['error_no']];
                    return sys_msg($GLOBALS['_LANG']['send_error'] . $error_detail, 1, $link);
                }

                break;
        }
    }

    private function shop_config_update($config_code, $config_value)
    {
        $sql = "SELECT `id` FROM " . $GLOBALS['ecs']->table(shop_config) . " WHERE `code`='$config_code'";
        $c_node_id = $GLOBALS['db']->getOne($sql);
        if (empty($c_node_id)) {
            for ($i = 247; $i <= 270; $i++) {
                $sql = "SELECT `id` FROM " . $GLOBALS['ecs']->table(shop_config) . " WHERE `id`='$i'";
                $c_id = $GLOBALS['db']->getOne($sql);
                if (empty($c_id)) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table(shop_config) . "(`id`,`parent_id`,`code`,`type`,`value`,`sort_order`) VALUES ('$i','2','$config_code','hidden','$config_value','1')";
                    $GLOBALS['db']->query($sql);
                    break;
                }
            }
        } else {
            $sql = "UPDATE " . $GLOBALS['ecs']->table(shop_config) . " SET `value`='$config_value'  WHERE `code`='$config_code'";
            $GLOBALS['db']->query($sql);
        }
    }
}
