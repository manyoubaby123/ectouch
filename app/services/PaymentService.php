<?php

namespace app\services;

/**
 * Class PaymentService
 * @package app\services
 */
class PaymentService
{

/**
 * 取得已安装的支付方式列表
 * @return  array   已安装的配送方式列表
 */
    public function payment_list()
    {
        $sql = 'SELECT pay_id, pay_name ' .
        'FROM ' . $GLOBALS['ecs']->table('payment') .
        ' WHERE enabled = 1';

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 取得支付方式信息
     * @param   int $pay_id 支付方式id
     * @return  array   支付方式信息
     */
    public function payment_info($pay_id)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') .
        " WHERE pay_id = '$pay_id' AND enabled = 1";

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 获得订单需要支付的支付费用
     *
     * @access  public
     * @param   integer $payment_id
     * @param   float $order_amount
     * @param   mix $cod_fee
     * @return  float
     */
    public function pay_fee($payment_id, $order_amount, $cod_fee = null)
    {
        $pay_fee = 0;
        $payment = payment_info($payment_id);
        $rate = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

        if (strpos($rate, '%') !== false) {
            /* 支付费用是一个比例 */
            $val = floatval($rate) / 100;
            $pay_fee = $val > 0 ? $order_amount * $val / (1 - $val) : 0;
        } else {
            $pay_fee = floatval($rate);
        }

        return round($pay_fee, 2);
    }

    /**
     * 取得可用的支付方式列表
     * @param   bool $support_cod 配送方式是否支持货到付款
     * @param   int $cod_fee 货到付款手续费（当配送方式支持货到付款时才传此参数）
     * @param   int $is_online 是否支持在线支付
     * @return  array   配送方式数组
     */
    public function available_payment_list($support_cod, $cod_fee = 0, $is_online = false)
    {
        $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod' .
        ' FROM ' . $GLOBALS['ecs']->table('payment') .
        ' WHERE enabled = 1 ';
        if (!$support_cod) {
            $sql .= 'AND is_cod = 0 '; // 如果不支持货到付款
        }
        if ($is_online) {
            $sql .= "AND is_online = '1' ";
        }
        $sql .= 'ORDER BY pay_order'; // 排序
        $res = $GLOBALS['db']->query($sql);

        $pay_list = [];
        foreach ($res as $row) {
            if ($row['is_cod'] == '1') {
                $row['pay_fee'] = $cod_fee;
            }

            $row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
            price_format($row['pay_fee'], false);
            $modules[] = $row;
        }

        load_helper('compositor');

        if (isset($modules)) {
            return $modules;
        }
    }

    /**
     *  取得某支付方式信息
     * @param  string $code 支付方式代码
     */
    public function get_payment($code)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') .
        " WHERE pay_code = '$code' AND enabled = '1'";
        $payment = $GLOBALS['db']->getRow($sql);

        if ($payment) {
            $config_list = unserialize($payment['pay_config']);

            foreach ($config_list as $config) {
                $payment[$config['name']] = $config['value'];
            }
        }

        return $payment;
    }

    /**
     *  通过订单sn取得订单ID
     * @param  string $order_sn 订单sn
     * @param  blob $voucher 是否为会员充值
     */
    public function get_order_id_by_sn($order_sn, $voucher = 'false')
    {
        if ($voucher == 'true') {
            if (is_numeric($order_sn)) {
                return $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id=" . $order_sn . ' AND order_type=1');
            } else {
                return "";
            }
        } else {
            if (is_numeric($order_sn)) {
                $sql = 'SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
                $order_id = $GLOBALS['db']->getOne($sql);
            }
            if (!empty($order_id)) {
                $pay_log_id = $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id='" . $order_id . "'");
                return $pay_log_id;
            } else {
                return "";
            }
        }
    }

    /**
     * 检查支付的金额是否与订单相符
     *
     * @access  public
     * @param   string $log_id 支付编号
     * @param   float $money 支付接口返回的金额
     * @return  true
     */
    public function check_money($log_id, $money)
    {
        if (is_numeric($log_id)) {
            $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('pay_log') .
            " WHERE log_id = '$log_id'";
            $amount = $GLOBALS['db']->getOne($sql);
        } else {
            return false;
        }
        if ($money == $amount) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改订单的支付状态
     *
     * @access  public
     * @param   string $log_id 支付编号
     * @param   integer $pay_status 状态
     * @param   string $note 备注
     * @return  void
     */
    public function order_paid($log_id, $pay_status = PS_PAYED, $note = '')
    {
        /* 取得支付编号 */
        $log_id = intval($log_id);
        if ($log_id > 0) {
            /* 取得要修改的支付记录信息 */
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') .
            " WHERE log_id = '$log_id'";
            $pay_log = $GLOBALS['db']->getRow($sql);
            if ($pay_log && $pay_log['is_paid'] == 0) {
                /* 修改此次支付操作的状态为已付款 */
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('pay_log') .
                " SET is_paid = '1' WHERE log_id = '$log_id'";
                $GLOBALS['db']->query($sql);

                /* 根据记录类型做相应处理 */
                if ($pay_log['order_type'] == PAY_ORDER) {
                    /* 取得订单信息 */
                    $sql = 'SELECT order_id, user_id, order_sn, consignee, address, tel, shipping_id, extension_code, extension_id, goods_amount ' .
                    'FROM ' . $GLOBALS['ecs']->table('order_info') .
                    " WHERE order_id = '$pay_log[order_id]'";
                    $order = $GLOBALS['db']->getRow($sql);
                    $order_id = $order['order_id'];
                    $order_sn = $order['order_sn'];

                    /* 修改订单状态为已付款 */
                    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                    " SET order_status = '" . OS_CONFIRMED . "', " .
                    " confirm_time = '" . gmtime() . "', " .
                    " pay_status = '$pay_status', " .
                    " pay_time = '" . gmtime() . "', " .
                    " money_paid = order_amount," .
                    " order_amount = 0 " .
                    "WHERE order_id = '$order_id'";
                    $GLOBALS['db']->query($sql);

                    /* 记录订单操作记录 */
                    order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);

                    /* 如果需要，发短信 */
                    if ($GLOBALS['_CFG']['sms_order_payed'] == '1' && $GLOBALS['_CFG']['sms_shop_mobile'] != '') {
                        $sms = new sms();
                        $sms->send(
                        $GLOBALS['_CFG']['sms_shop_mobile'],
                        sprintf($GLOBALS['_LANG']['order_payed_sms'], $order_sn, $order['consignee'], $order['tel']),
                        '',
                        13,
                        1
                    );
                    }

                    /* 对虚拟商品的支持 */
                    $virtual_goods = get_virtual_goods($order_id);
                    if (!empty($virtual_goods)) {
                        $msg = '';
                        if (!virtual_goods_ship($virtual_goods, $msg, $order_sn, true)) {
                            $GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">' . $msg . '</div>' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
                        }

                        /* 如果订单没有配送方式，自动完成发货操作 */
                        if ($order['shipping_id'] == -1) {
                            /* 将订单标识为已发货状态，并记录发货记录 */
                            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                            " SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . gmtime() . "'" .
                            " WHERE order_id = '$order_id'";
                            $GLOBALS['db']->query($sql);

                            /* 记录订单操作记录 */
                            order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
                            $integral = integral_to_give($order);
                            log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));
                        }
                    }
                } elseif ($pay_log['order_type'] == PAY_SURPLUS) {
                    $sql = 'SELECT `id` FROM ' . $GLOBALS['ecs']->table('user_account') . " WHERE `id` = '$pay_log[order_id]' AND `is_paid` = 1  LIMIT 1";
                    $res_id = $GLOBALS['db']->getOne($sql);
                    if (empty($res_id)) {
                        /* 更新会员预付款的到款状态 */
                        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') .
                        " SET paid_time = '" . gmtime() . "', is_paid = 1" .
                        " WHERE id = '$pay_log[order_id]' LIMIT 1";
                        $GLOBALS['db']->query($sql);

                        /* 取得添加预付款的用户以及金额 */
                        $sql = "SELECT user_id, amount FROM " . $GLOBALS['ecs']->table('user_account') .
                        " WHERE id = '$pay_log[order_id]'";
                        $arr = $GLOBALS['db']->getRow($sql);

                        /* 修改会员帐户金额 */
                        $GLOBALS['_LANG'] = [];

                        load_lang('user');
                        log_account_change($arr['user_id'], $arr['amount'], 0, 0, 0, $GLOBALS['_LANG']['surplus_type_0'], ACT_SAVING);
                    }
                }
            } else {
                /* 取得已发货的虚拟商品信息 */
                $post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);

                /* 有已发货的虚拟商品 */
                if (!empty($post_virtual_goods)) {
                    $msg = '';
                    /* 检查两次刷新时间有无超过12小时 */
                    $sql = 'SELECT pay_time, order_sn FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$pay_log[order_id]'";
                    $row = $GLOBALS['db']->getRow($sql);
                    $intval_time = gmtime() - $row['pay_time'];
                    if ($intval_time >= 0 && $intval_time < 3600 * 12) {
                        $virtual_card = [];
                        foreach ($post_virtual_goods as $code => $goods_list) {
                            /* 只处理虚拟卡 */
                            if ($code == 'virtual_card') {
                                foreach ($goods_list as $goods) {
                                    if ($info = virtual_card_result($row['order_sn'], $goods)) {
                                        $virtual_card[] = ['goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info];
                                    }
                                }

                                $GLOBALS['smarty']->assign('virtual_card', $virtual_card);
                            }
                        }
                    } else {
                        $msg = '<div>' . $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
                    }

                    $GLOBALS['_LANG']['pay_success'] .= $msg;
                }

                /* 取得未发货虚拟商品 */
                $virtual_goods = get_virtual_goods($pay_log['order_id'], false);
                if (!empty($virtual_goods)) {
                    $GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
                }
            }
        }
    }

    /**
     * 取得支付方式id列表
     * @param   bool $is_cod 是否货到付款
     * @return  array
     */
    public function payment_id_list($is_cod)
    {
        $sql = "SELECT pay_id FROM " . $GLOBALS['ecs']->table('payment');
        if ($is_cod) {
            $sql .= " WHERE is_cod = 1";
        } else {
            $sql .= " WHERE is_cod = 0";
        }

        return $GLOBALS['db']->getCol($sql);
    }

    /**
     * 取得已安装的支付方式(其中不包括线下支付的)
     * @param   bool $include_balance 是否包含余额支付（冲值时不应包括）
     * @return  array   已安装的配送方式列表
     */
    public function get_online_payment_list($include_balance = true)
    {
        $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc ' .
        'FROM ' . $GLOBALS['ecs']->table('payment') .
        " WHERE enabled = 1 AND is_cod <> 1";
        if (!$include_balance) {
            $sql .= " AND pay_code <> 'balance' ";
        }

        $modules = $GLOBALS['db']->getAll($sql);

        load_helper('compositor');

        return $modules;
    }

    /**
     * 取得货到付款和非货到付款的支付方式
     * @return  array('is_cod' => '', 'is_not_cod' => '')
     */
    public function get_pay_ids()
    {
        $ids = ['is_cod' => '0', 'is_not_cod' => '0'];
        $sql = 'SELECT pay_id, is_cod FROM ' . $GLOBALS['ecs']->table('payment') . ' WHERE enabled = 1';
        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            if ($row['is_cod']) {
                $ids['is_cod'] .= ',' . $row['pay_id'];
            } else {
                $ids['is_not_cod'] .= ',' . $row['pay_id'];
            }
        }

        return $ids;
    }
}
