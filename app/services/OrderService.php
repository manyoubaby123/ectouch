<?php

namespace app\services;

/**
 * Class OrderService
 * @package app\services
 */
class OrderService
{
    /**
     *  获取用户指定范围的订单列表
     *
     * @access  public
     * @param   int $user_id 用户ID号
     * @param   int $num 列表最大数量
     * @param   int $start 列表起始位置
     * @return  array       $order_list     订单列表
     */
    public function get_user_orders($user_id, $num = 10, $start = 0)
    {
        /* 取得订单列表 */
        $arr = [];

        $sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time, " .
            "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee " .
            " FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id = '$user_id' ORDER BY add_time DESC";
        $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

        foreach ($res as $row) {
            if ($row['order_status'] == OS_UNCONFIRMED) {
                $row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" . $row['order_id'] . "\" onclick=\"if (!confirm('" . $GLOBALS['_LANG']['confirm_cancel'] . "')) return false;\">" . $GLOBALS['_LANG']['cancel'] . "</a>";
            } elseif ($row['order_status'] == OS_SPLITED) {
                /* 对配送状态的处理 */
                if ($row['shipping_status'] == SS_SHIPPED) {
                    @$row['handler'] = "<a href=\"user.php?act=affirm_received&order_id=" . $row['order_id'] . "\" onclick=\"if (!confirm('" . $GLOBALS['_LANG']['confirm_received'] . "')) return false;\">" . $GLOBALS['_LANG']['received'] . "</a>";
                } elseif ($row['shipping_status'] == SS_RECEIVED) {
                    @$row['handler'] = '<span style="color:red">' . $GLOBALS['_LANG']['ss_received'] . '</span>';
                } else {
                    if ($row['pay_status'] == PS_UNPAYED) {
                        @$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" . $row['order_id'] . '">' . $GLOBALS['_LANG']['pay_money'] . '</a>';
                    } else {
                        @$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" . $row['order_id'] . '">' . $GLOBALS['_LANG']['view_order'] . '</a>';
                    }
                }
            } else {
                $row['handler'] = '<span style="color:red">' . $GLOBALS['_LANG']['os'][$row['order_status']] . '</span>';
            }

            $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
            $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

            $arr[] = ['order_id' => $row['order_id'],
                'order_sn' => $row['order_sn'],
                'order_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
                'order_status' => $row['order_status'],
                'total_fee' => price_format($row['total_fee'], false),
                'handler' => $row['handler']];
        }

        return $arr;
    }

    /**
     * 取消一个用户订单
     *
     * @access  public
     * @param   int $order_id 订单ID
     * @param   int $user_id 用户ID
     *
     * @return void
     */
    public function cancel_order($order_id, $user_id = 0)
    {
        /* 查询订单信息，检查状态 */
        $sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";
        $order = $GLOBALS['db']->getRow($sql);

        if (empty($order)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
            return false;
        }

        // 如果用户ID大于0，检查订单是否属于该用户
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);

            return false;
        }

        // 订单状态只能是“未确认”或“已确认”
        if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['current_os_not_unconfirmed']);

            return false;
        }

        //订单一旦确认，不允许用户取消
        if ($order['order_status'] == OS_CONFIRMED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['current_os_already_confirmed']);

            return false;
        }

        // 发货状态只能是“未发货”
        if ($order['shipping_status'] != SS_UNSHIPPED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['current_ss_not_cancel']);

            return false;
        }

        // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
        if ($order['pay_status'] != PS_UNPAYED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['current_ps_not_cancel']);

            return false;
        }

        //将用户订单设置为取消
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET order_status = '" . OS_CANCELED . "' WHERE order_id = '$order_id'";
        if ($GLOBALS['db']->query($sql)) {
            /* 记录log */
            order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $GLOBALS['_LANG']['buyer_cancel'], 'buyer');
            /* 退货用户余额、积分、红包 */
            if ($order['user_id'] > 0 && $order['surplus'] > 0) {
                $change_desc = sprintf($GLOBALS['_LANG']['return_surplus_on_cancel'], $order['order_sn']);
                log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
            }
            if ($order['user_id'] > 0 && $order['integral'] > 0) {
                $change_desc = sprintf($GLOBALS['_LANG']['return_integral_on_cancel'], $order['order_sn']);
                log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
            }
            if ($order['user_id'] > 0 && $order['bonus_id'] > 0) {
                change_user_bonus($order['bonus_id'], $order['order_id'], false);
            }

            /* 如果使用库存，且下订单时减库存，则增加库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                change_order_goods_storage($order['order_id'], false, 1);
            }

            /* 修改订单 */
            $arr = [
                'bonus_id' => 0,
                'bonus' => 0,
                'integral' => 0,
                'integral_money' => 0,
                'surplus' => 0
            ];
            update_order($order['order_id'], $arr);

            return true;
        } else {
            die($GLOBALS['db']->errorMsg());
        }
    }

    /**
     * 确认一个用户订单
     *
     * @access  public
     * @param   int $order_id 订单ID
     * @param   int $user_id 用户ID
     *
     * @return  bool        $bool
     */
    public function affirm_received($order_id, $user_id = 0)
    {
        /* 查询订单信息，检查状态 */
        $sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";

        $order = $GLOBALS['db']->getRow($sql);

        // 如果用户ID大于 0 。检查订单是否属于该用户
        if ($user_id > 0 && $order['user_id'] != $user_id) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);

            return false;
        } /* 检查订单 */
        elseif ($order['shipping_status'] == SS_RECEIVED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['order_already_received']);

            return false;
        } elseif ($order['shipping_status'] != SS_SHIPPED) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['order_invalid']);

            return false;
        } /* 修改订单发货状态为“确认收货” */
        else {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET shipping_status = '" . SS_RECEIVED . "' WHERE order_id = '$order_id'";
            if ($GLOBALS['db']->query($sql)) {
                /* 记录日志 */
                order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', $GLOBALS['_LANG']['buyer']);

                return true;
            } else {
                die($GLOBALS['db']->errorMsg());
            }
        }
    }

    /**
     *  获取指订单的详情
     *
     * @access  public
     * @param   int $order_id 订单ID
     * @param   int $user_id 用户ID
     *
     * @return   arr        $order          订单所有信息的数组
     */
    public function get_order_detail($order_id, $user_id = 0)
    {
        load_helper('order');

        $order_id = intval($order_id);
        if ($order_id <= 0) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['invalid_order_id']);

            return false;
        }
        $order = order_info($order_id);

        //检查订单是否属于该用户
        if ($user_id > 0 && $user_id != $order['user_id']) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);

            return false;
        }

        /* 对发货号处理 */
        if (!empty($order['invoice_no'])) {
            $shipping_code = $GLOBALS['db']->getOne("SELECT shipping_code FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE shipping_id = '$order[shipping_id]'");
            $plugin = '\\app\\plugins\\shipping\\' . parse_name($shipping_code, true);
            if (class_exists($plugin)) {
                $shipping = new $plugin;
                $order['invoice_no'] = $shipping->query($order['invoice_no']);
            }
        }

        /* 只有未确认才允许用户修改订单地址 */
        if ($order['order_status'] == OS_UNCONFIRMED) {
            $order['allow_update_address'] = 1; //允许修改收货地址
        } else {
            $order['allow_update_address'] = 0;
        }

        /* 获取订单中实体商品数量 */
        $order['exist_real_goods'] = exist_real_goods($order_id);

        /* 如果是未付款状态，生成支付按钮 */
        if ($order['pay_status'] == PS_UNPAYED &&
            ($order['order_status'] == OS_UNCONFIRMED ||
                $order['order_status'] == OS_CONFIRMED)) {
            /*
             * 在线支付按钮
             */
            //支付方式信息
            $payment_info = [];
            $payment_info = payment_info($order['pay_id']);

            //无效支付方式
            if ($payment_info === false) {
                $order['pay_online'] = '';
            } else {
                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //获取需要支付的log_id
                $order['log_id'] = get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
                $order['user_name'] = session('user_name');
                $order['pay_desc'] = $payment_info['pay_desc'];

                /* 调用相应的支付方式文件 */
                $pay_code = '\\app\\plugins\\payment\\' . parse_name($payment_info['pay_code'], true);

                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $pay_code;
                $order['pay_online'] = $pay_obj->get_code($order, $payment);
            }
        } else {
            $order['pay_online'] = '';
        }

        /* 无配送时的处理 */
        $order['shipping_id'] == -1 and $order['shipping_name'] = $GLOBALS['_LANG']['shipping_not_need'];

        /* 其他信息初始化 */
        $order['how_oos_name'] = $order['how_oos'];
        $order['how_surplus_name'] = $order['how_surplus'];

        /* 虚拟商品付款后处理 */
        if ($order['pay_status'] != PS_UNPAYED) {
            /* 取得已发货的虚拟商品信息 */
            $virtual_goods = get_virtual_goods($order_id, true);
            $virtual_card = [];
            foreach ($virtual_goods as $code => $goods_list) {
                /* 只处理虚拟卡 */
                if ($code == 'virtual_card') {
                    foreach ($goods_list as $goods) {
                        if ($info = virtual_card_result($order['order_sn'], $goods)) {
                            $virtual_card[] = ['goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info];
                        }
                    }
                }
                /* 处理超值礼包里面的虚拟卡 */
                if ($code == 'package_buy') {
                    foreach ($goods_list as $goods) {
                        $sql = 'SELECT g.goods_id FROM ' . $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                            "WHERE pg.goods_id = g.goods_id AND pg.package_id = '" . $goods['goods_id'] . "' AND extension_code = 'virtual_card'";
                        $vcard_arr = $GLOBALS['db']->getAll($sql);

                        foreach ($vcard_arr as $val) {
                            if ($info = virtual_card_result($order['order_sn'], $val)) {
                                $virtual_card[] = ['goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info];
                            }
                        }
                    }
                }
            }
            $var_card = deleteRepeat($virtual_card);
            $GLOBALS['smarty']->assign('virtual_card', $var_card);
        }

        /* 确认时间 支付时间 发货时间 */
        if ($order['confirm_time'] > 0 && ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART)) {
            $order['confirm_time'] = sprintf($GLOBALS['_LANG']['confirm_time'], local_date($GLOBALS['_CFG']['time_format'], $order['confirm_time']));
        } else {
            $order['confirm_time'] = '';
        }
        if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED) {
            $order['pay_time'] = sprintf($GLOBALS['_LANG']['pay_time'], local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']));
        } else {
            $order['pay_time'] = '';
        }
        if ($order['shipping_time'] > 0 && in_array($order['shipping_status'], [SS_SHIPPED, SS_RECEIVED])) {
            $order['shipping_time'] = sprintf($GLOBALS['_LANG']['shipping_time'], local_date($GLOBALS['_CFG']['time_format'], $order['shipping_time']));
        } else {
            $order['shipping_time'] = '';
        }

        return $order;
    }

    /**
     *  获取用户可以和并的订单数组
     *
     * @access  public
     * @param   int $user_id 用户ID
     *
     * @return  array       $merge          可合并订单数组
     */
    public function get_user_merge($user_id)
    {
        load_helper('order');
        $sql = "SELECT order_sn FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id  = '$user_id' " . order_query_sql('unprocessed') .
            "AND extension_code = '' " .
            " ORDER BY add_time DESC";
        $list = $GLOBALS['db']->GetCol($sql);

        $merge = [];
        foreach ($list as $val) {
            $merge[$val] = $val;
        }

        return $merge;
    }

    /**
     *  合并指定用户订单
     *
     * @access  public
     * @param   string $from_order 合并的从订单号
     * @param   string $to_order 合并的主订单号
     *
     * @return  boolen      $bool
     */
    public function merge_user_order($from_order, $to_order, $user_id = 0)
    {
        if ($user_id > 0) {
            /* 检查订单是否属于指定用户 */
            if (strlen($to_order) > 0) {
                $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('order_info') .
                    " WHERE order_sn = '$to_order'";
                $order_user = $GLOBALS['db']->getOne($sql);
                if ($order_user != $user_id) {
                    $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
                }
            } else {
                $GLOBALS['err']->add($GLOBALS['_LANG']['order_sn_empty']);
                return false;
            }
        }

        $result = merge_order($from_order, $to_order);
        if ($result === true) {
            return true;
        } else {
            $GLOBALS['err']->add($result);
            return false;
        }
    }

    /**
     *  将指定订单中的商品添加到购物车
     *
     * @access  public
     * @param   int $order_id
     *
     * @return  mix         $message        成功返回true, 错误返回出错信息
     */
    public function return_to_cart($order_id)
    {
        /* 初始化基本件数量 goods_id => goods_number */
        $basic_number = [];

        /* 查订单商品：不考虑赠品 */
        $sql = "SELECT goods_id, product_id,goods_number, goods_attr, parent_id, goods_attr_id" .
            " FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id' AND is_gift = 0 AND extension_code <> 'package_buy'" .
            " ORDER BY parent_id ASC";
        $res = $GLOBALS['db']->query($sql);

        $time = gmtime();
        foreach ($res as $row) {
            // 查该商品信息：是否删除、是否上架

            $sql = "SELECT goods_sn, goods_name, goods_number, market_price, " .
                "IF(is_promote = 1 AND '$time' BETWEEN promote_start_date AND promote_end_date, promote_price, shop_price) AS goods_price," .
                "is_real, extension_code, is_alone_sale, goods_type " .
                "FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id = '$row[goods_id]' " .
                " AND is_delete = 0 LIMIT 1";
            $goods = $GLOBALS['db']->getRow($sql);

            // 如果该商品不存在，处理下一个商品
            if (empty($goods)) {
                continue;
            }
            if ($row['product_id']) {
                $order_goods_product_id = $row['product_id'];
                $sql = "SELECT product_number from " . $GLOBALS['ecs']->table('products') . "where product_id='$order_goods_product_id'";
                $product_number = $GLOBALS['db']->getOne($sql);
            }
            // 如果使用库存，且库存不足，修改数量
            if ($GLOBALS['_CFG']['use_storage'] == 1 && ($row['product_id'] ? ($product_number < $row['goods_number']) : ($goods['goods_number'] < $row['goods_number']))) {
                if ($goods['goods_number'] == 0 || $product_number === 0) {
                    // 如果库存为0，处理下一个商品
                    continue;
                } else {
                    if ($row['product_id']) {
                        $row['goods_number'] = $product_number;
                    } else {
                        // 库存不为0，修改数量
                        $row['goods_number'] = $goods['goods_number'];
                    }
                }
            }

            //检查商品价格是否有会员价格
            $sql = "SELECT goods_number FROM" . $GLOBALS['ecs']->table('cart') . " " .
                "WHERE session_id = '" . SESS_ID . "' " .
                "AND goods_id = '" . $row['goods_id'] . "' " .
                "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
            $temp_number = $GLOBALS['db']->getOne($sql);
            $row['goods_number'] += $temp_number;

            $attr_array = empty($row['goods_attr_id']) ? [] : explode(',', $row['goods_attr_id']);
            $goods['goods_price'] = get_final_price($row['goods_id'], $row['goods_number'], true, $attr_array);

            // 要返回购物车的商品
            $return_goods = [
                'goods_id' => $row['goods_id'],
                'goods_sn' => addslashes($goods['goods_sn']),
                'goods_name' => addslashes($goods['goods_name']),
                'market_price' => $goods['market_price'],
                'goods_price' => $goods['goods_price'],
                'goods_number' => $row['goods_number'],
                'goods_attr' => empty($row['goods_attr']) ? '' : addslashes($row['goods_attr']),
                'goods_attr_id' => empty($row['goods_attr_id']) ? '' : addslashes($row['goods_attr_id']),
                'is_real' => $goods['is_real'],
                'extension_code' => addslashes($goods['extension_code']),
                'parent_id' => '0',
                'is_gift' => '0',
                'rec_type' => CART_GENERAL_GOODS
            ];

            // 如果是配件
            if ($row['parent_id'] > 0) {
                // 查询基本件信息：是否删除、是否上架、能否作为普通商品销售
                $sql = "SELECT goods_id " .
                    "FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE goods_id = '$row[parent_id]' " .
                    " AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 LIMIT 1";
                $parent = $GLOBALS['db']->getRow($sql);
                if ($parent) {
                    // 如果基本件存在，查询组合关系是否存在
                    $sql = "SELECT goods_price " .
                        "FROM " . $GLOBALS['ecs']->table('group_goods') .
                        " WHERE parent_id = '$row[parent_id]' " .
                        " AND goods_id = '$row[goods_id]' LIMIT 1";
                    $fitting_price = $GLOBALS['db']->getOne($sql);
                    if ($fitting_price) {
                        // 如果组合关系存在，取配件价格，取基本件数量，改parent_id
                        $return_goods['parent_id'] = $row['parent_id'];
                        $return_goods['goods_price'] = $fitting_price;
                        $return_goods['goods_number'] = $basic_number[$row['parent_id']];
                    }
                }
            } else {
                // 保存基本件数量
                $basic_number[$row['goods_id']] = $row['goods_number'];
            }

            // 返回购物车：看有没有相同商品
            $sql = "SELECT goods_id " .
                "FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' " .
                " AND goods_id = '$return_goods[goods_id]' " .
                " AND goods_attr = '$return_goods[goods_attr]' " .
                " AND parent_id = '$return_goods[parent_id]' " .
                " AND is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
            $cart_goods = $GLOBALS['db']->getOne($sql);
            if (empty($cart_goods)) {
                // 没有相同商品，插入
                $return_goods['session_id'] = SESS_ID;
                $return_goods['user_id'] = session('user_id');
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $return_goods, 'INSERT');
            } else {
                // 有相同商品，修改数量
                $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET " .
                    "goods_number = '" . $return_goods['goods_number'] . "' " .
                    ",goods_price = '" . $return_goods['goods_price'] . "' " .
                    "WHERE session_id = '" . SESS_ID . "' " .
                    "AND goods_id = '" . $return_goods['goods_id'] . "' " .
                    "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }
        }

        // 清空购物车的赠品
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND is_gift = 1";
        $GLOBALS['db']->query($sql);

        return true;
    }

    /**
     *  保存用户收货地址
     *
     * @access  public
     * @param   array $address array_keys(consignee string, email string, address string, zipcode string, tel string, mobile stirng, sign_building string, best_time string, order_id int)
     * @param   int $user_id 用户ID
     *
     * @return  boolen  $bool
     */
    public function save_order_address($address, $user_id)
    {
        $GLOBALS['err']->clean();
        /* 数据验证 */
        empty($address['consignee']) and $GLOBALS['err']->add($GLOBALS['_LANG']['consigness_empty']);
        empty($address['address']) and $GLOBALS['err']->add($GLOBALS['_LANG']['address_empty']);
        $address['order_id'] == 0 and $GLOBALS['err']->add($GLOBALS['_LANG']['order_id_empty']);
        if (empty($address['email'])) {
            $GLOBALS['err']->add($GLOBALS['email_empty']);
        } else {
            if (!is_email($address['email'])) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $address['email']));
            }
        }
        if ($GLOBALS['err']->error_no > 0) {
            return false;
        }

        /* 检查订单状态 */
        $sql = "SELECT user_id, order_status FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '" . $address['order_id'] . "'";
        $row = $GLOBALS['db']->getRow($sql);
        if ($row) {
            if ($user_id > 0 && $user_id != $row['user_id']) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
                return false;
            }
            if ($row['order_status'] != OS_UNCONFIRMED) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['require_unconfirmed']);
                return false;
            }
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $address, 'UPDATE', "order_id = '$address[order_id]'");
            return true;
        } else {
            /* 订单不存在 */
            $GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
            return false;
        }
    }

    /**
     *  通过订单ID取得订单商品名称
     * @param  string $order_id 订单ID
     */
    public function get_goods_name_by_id($order_id)
    {
        $sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '$order_id'";
        $goods_name = $GLOBALS['db']->getCol($sql);
        return implode(',', $goods_name);
    }

    /**
     * 取得订单信息
     * @param   int $order_id 订单id（如果order_id > 0 就按id查，否则按sn查）
     * @param   string $order_sn 订单号
     * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    public function order_info($order_id, $order_sn = '')
    {
        /* 计算订单各种费用之和的语句 */
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $sql = "SELECT *, " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE order_id = '$order_id'";
        } else {
            $sql = "SELECT *, " . $total_fee . "  FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE order_sn = '$order_sn'";
        }
        $order = $GLOBALS['db']->getRow($sql);

        /* 格式化金额字段 */
        if ($order) {
            $order['formated_goods_amount'] = price_format($order['goods_amount'], false);
            $order['formated_discount'] = price_format($order['discount'], false);
            $order['formated_tax'] = price_format($order['tax'], false);
            $order['formated_shipping_fee'] = price_format($order['shipping_fee'], false);
            $order['formated_insure_fee'] = price_format($order['insure_fee'], false);
            $order['formated_pay_fee'] = price_format($order['pay_fee'], false);
            $order['formated_pack_fee'] = price_format($order['pack_fee'], false);
            $order['formated_card_fee'] = price_format($order['card_fee'], false);
            $order['formated_total_fee'] = price_format($order['total_fee'], false);
            $order['formated_money_paid'] = price_format($order['money_paid'], false);
            $order['formated_bonus'] = price_format($order['bonus'], false);
            $order['formated_integral_money'] = price_format($order['integral_money'], false);
            $order['formated_surplus'] = price_format($order['surplus'], false);
            $order['formated_order_amount'] = price_format(abs($order['order_amount']), false);
            $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        }

        return $order;
    }

    /**
     * 判断订单是否已完成
     * @param   array $order 订单信息
     * @return  bool
     */
    public function order_finished($order)
    {
        return $order['order_status'] == OS_CONFIRMED &&
            ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
            ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING);
    }

    /**
     * 取得订单商品
     * @param   int $order_id 订单id
     * @return  array   订单商品数组
     */
    public function order_goods($order_id)
    {
        $sql = "SELECT rec_id, goods_id, goods_name, goods_sn, market_price, goods_number, " .
            "goods_price, goods_attr, is_real, parent_id, is_gift, " .
            "goods_price * goods_number AS subtotal, extension_code " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id'";

        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            if ($row['extension_code'] == 'package_buy') {
                $row['package_goods_list'] = get_package_goods($row['goods_id']);
            }
            $goods_list[] = $row;
        }

        //return $GLOBALS['db']->getAll($sql);
        return $goods_list;
    }

    /**
     * 取得订单总金额
     * @param   int $order_id 订单id
     * @param   bool $include_gift 是否包括赠品
     * @return  float   订单总金额
     */
    public function order_amount($order_id, $include_gift = true)
    {
        $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id'";
        if (!$include_gift) {
            $sql .= " AND is_gift = 0";
        }

        return floatval($GLOBALS['db']->getOne($sql));
    }

    /**
     * 取得某订单商品总重量和总金额（对应 cart_weight_price）
     * @param   int $order_id 订单id
     * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
     */
    public function order_weight_price($order_id)
    {
        $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
            "SUM(o.goods_price * o.goods_number) AS amount ," .
            "SUM(o.goods_number) AS number " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o, " .
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE o.order_id = '$order_id' " .
            "AND o.goods_id = g.goods_id";

        $row = $GLOBALS['db']->getRow($sql);
        $row['weight'] = floatval($row['weight']);
        $row['amount'] = floatval($row['amount']);
        $row['number'] = intval($row['number']);

        /* 格式化重量 */
        $row['formated_weight'] = formated_weight($row['weight']);

        return $row;
    }

    /**
     * 获得订单中的费用信息
     *
     * @access  public
     * @param   array $order
     * @param   array $goods
     * @param   array $consignee
     * @param   bool $is_gb_deposit 是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
     * @return  array
     */
    public function order_fee($order, $goods, $consignee)
    {
        /* 初始化订单的扩展code */
        if (!isset($order['extension_code'])) {
            $order['extension_code'] = '';
        }

        if ($order['extension_code'] == 'group_buy') {
            $group_buy = group_buy_info($order['extension_id']);
        }

        $total = ['real_goods_count' => 0,
            'gift_amount' => 0,
            'goods_price' => 0,
            'market_price' => 0,
            'discount' => 0,
            'pack_fee' => 0,
            'card_fee' => 0,
            'shipping_fee' => 0,
            'shipping_insure' => 0,
            'integral_money' => 0,
            'bonus' => 0,
            'surplus' => 0,
            'cod_fee' => 0,
            'pay_fee' => 0,
            'tax' => 0];
        $weight = 0;

        /* 商品总价 */
        foreach ($goods as $val) {
            /* 统计实体商品的个数 */
            if ($val['is_real']) {
                $total['real_goods_count']++;
            }

            $total['goods_price'] += $val['goods_price'] * $val['goods_number'];
            $total['market_price'] += $val['market_price'] * $val['goods_number'];
        }

        $total['saving'] = $total['market_price'] - $total['goods_price'];
        $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

        $total['goods_price_formated'] = price_format($total['goods_price'], false);
        $total['market_price_formated'] = price_format($total['market_price'], false);
        $total['saving_formated'] = price_format($total['saving'], false);

        /* 折扣 */
        if ($order['extension_code'] != 'group_buy') {
            $discount = compute_discount();
            $total['discount'] = $discount['discount'];
            if ($total['discount'] > $total['goods_price']) {
                $total['discount'] = $total['goods_price'];
            }
        }
        $total['discount_formated'] = price_format($total['discount'], false);

        /* 税额 */
        if (!empty($order['need_inv']) && $order['inv_type'] != '') {
            /* 查税率 */
            $rate = 0;
            foreach ($GLOBALS['_CFG']['invoice_type']['type'] as $key => $type) {
                if ($type == $order['inv_type']) {
                    $rate = floatval($GLOBALS['_CFG']['invoice_type']['rate'][$key]) / 100;
                    break;
                }
            }
            if ($rate > 0) {
                $total['tax'] = $rate * $total['goods_price'];
            }
        }
        $total['tax_formated'] = price_format($total['tax'], false);

        /* 包装费用 */
        if (!empty($order['pack_id'])) {
            $total['pack_fee'] = pack_fee($order['pack_id'], $total['goods_price']);
        }
        $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

        /* 贺卡费用 */
        if (!empty($order['card_id'])) {
            $total['card_fee'] = card_fee($order['card_id'], $total['goods_price']);
        }
        $total['card_fee_formated'] = price_format($total['card_fee'], false);

        /* 红包 */

        if (!empty($order['bonus_id'])) {
            $bonus = bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }
        $total['bonus_formated'] = price_format($total['bonus'], false);

        /* 线下红包 */
        if (!empty($order['bonus_kill'])) {
            $bonus = bonus_info(0, $order['bonus_kill']);
            $total['bonus_kill'] = $order['bonus_kill'];
            $total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
        }

        /* 配送费用 */
        $shipping_cod_fee = null;

        if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) {
            $region['country'] = $consignee['country'];
            $region['province'] = $consignee['province'];
            $region['city'] = $consignee['city'];
            $region['district'] = $consignee['district'];
            $shipping_info = shipping_area_info($order['shipping_id'], $region);

            if (!empty($shipping_info)) {
                if ($order['extension_code'] == 'group_buy') {
                    $weight_price = cart_weight_price(CART_GROUP_BUY_GOODS);
                } else {
                    $weight_price = cart_weight_price();
                }

                // 查看购物车中是否全为免运费商品，若是则把运费赋为零
                $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE  `session_id` = '" . SESS_ID . "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
                $shipping_count = $GLOBALS['db']->getOne($sql);

                $total['shipping_fee'] = ($shipping_count == 0 and $weight_price['free_shipping'] == 1) ? 0 : shipping_fee($shipping_info['shipping_code'], $shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);

                if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
                    $total['shipping_insure'] = shipping_insure_fee(
                        $shipping_info['shipping_code'],
                        $total['goods_price'],
                        $shipping_info['insure']
                    );
                } else {
                    $total['shipping_insure'] = 0;
                }

                if ($shipping_info['support_cod']) {
                    $shipping_cod_fee = $shipping_info['pay_fee'];
                }
            }
        }

        $total['shipping_fee_formated'] = price_format($total['shipping_fee'], false);
        $total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);

        // 购物车中的商品能享受红包支付的总额
        $bonus_amount = compute_discount_amount();
        // 红包和积分最多能支付的金额为商品总额
        $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;

        /* 计算订单总额 */
        if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
            $total['amount'] = $total['goods_price'];
        } else {
            $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +
                $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];

            // 减去红包金额
            $use_bonus = min($total['bonus'], $max_amount); // 实际减去的红包金额
            if (isset($total['bonus_kill'])) {
                $use_bonus_kill = min($total['bonus_kill'], $max_amount);
                $total['amount'] -= $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
            }

            $total['bonus'] = $use_bonus;
            $total['bonus_formated'] = price_format($total['bonus'], false);

            $total['amount'] -= $use_bonus; // 还需要支付的订单金额
            $max_amount -= $use_bonus; // 积分最多还能支付的金额
        }

        /* 余额 */
        $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
        if ($total['amount'] > 0) {
            if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
                $order['surplus'] = $total['amount'];
                $total['amount'] = 0;
            } else {
                $total['amount'] -= floatval($order['surplus']);
            }
        } else {
            $order['surplus'] = 0;
            $total['amount'] = 0;
        }
        $total['surplus'] = $order['surplus'];
        $total['surplus_formated'] = price_format($order['surplus'], false);

        /* 积分 */
        $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
        if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
            $integral_money = value_of_integral($order['integral']);

            // 使用积分支付
            $use_integral = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
            $total['amount'] -= $use_integral;
            $total['integral_money'] = $use_integral;
            $order['integral'] = integral_of_value($use_integral);
        } else {
            $total['integral_money'] = 0;
            $order['integral'] = 0;
        }
        $total['integral'] = $order['integral'];
        $total['integral_formated'] = price_format($total['integral_money'], false);

        /* 保存订单信息 */
        session('flow_order', $order);

        $se_flow_type = session('?flow_type') ? session('flow_type') : '';

        /* 支付费用 */
        if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
            $total['pay_fee'] = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
        }

        $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

        $total['amount'] += $total['pay_fee']; // 订单总额累加上支付费用
        $total['amount_formated'] = price_format($total['amount'], false);

        /* 取得可以得到的积分和红包 */
        if ($order['extension_code'] == 'group_buy') {
            $total['will_get_integral'] = $group_buy['gift_integral'];
        } elseif ($order['extension_code'] == 'exchange_goods') {
            $total['will_get_integral'] = 0;
        } else {
            $total['will_get_integral'] = get_give_integral($goods);
        }
        $total['will_get_bonus'] = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
        $total['formated_goods_price'] = price_format($total['goods_price'], false);
        $total['formated_market_price'] = price_format($total['market_price'], false);
        $total['formated_saving'] = price_format($total['saving'], false);

        if ($order['extension_code'] == 'exchange_goods') {
            $sql = 'SELECT SUM(eg.exchange_integral) ' .
                'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c,' . $GLOBALS['ecs']->table('exchange_goods') . 'AS eg ' .
                "WHERE c.goods_id = eg.goods_id AND c.session_id= '" . SESS_ID . "' " .
                "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
                '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
                'GROUP BY eg.goods_id';
            $exchange_integral = $GLOBALS['db']->getOne($sql);
            $total['exchange_integral'] = $exchange_integral;
        }

        return $total;
    }

    /**
     * 修改订单
     * @param   int $order_id 订单id
     * @param   array $order key => value
     * @return  bool
     */
    public function update_order($order_id, $order)
    {
        return $GLOBALS['db']->autoExecute(
            $GLOBALS['ecs']->table('order_info'),
            $order,
            'UPDATE',
            "order_id = '$order_id'"
        );
    }

    /**
     * 订单退款
     * @param   array $order 订单
     * @param   int $refund_type 退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
     * @param   string $refund_note 退款说明
     * @param   float $refund_amount 退款金额（如果为0，取订单已付款金额）
     * @return  bool
     */
    public function order_refund($order, $refund_type, $refund_note, $refund_amount = 0)
    {
        /* 检查参数 */
        $user_id = $order['user_id'];
        if ($user_id == 0 && $refund_type == 1) {
            die('anonymous, cannot return to account balance');
        }

        $amount = $refund_amount > 0 ? $refund_amount : $order['money_paid'];
        if ($amount <= 0) {
            return true;
        }

        if (!in_array($refund_type, [1, 2, 3])) {
            die('invalid params');
        }

        /* 备注信息 */
        if ($refund_note) {
            $change_desc = $refund_note;
        } else {
            load_lang('order', 'admin');
            $change_desc = sprintf($GLOBALS['_LANG']['order_refund'], $order['order_sn']);
        }

        /* 处理退款 */
        if (1 == $refund_type) {
            log_account_change($user_id, $amount, 0, 0, 0, $change_desc);

            return true;
        } elseif (2 == $refund_type) {
            /* 如果非匿名，退回余额 */
            if ($user_id > 0) {
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
            }

            /* user_account 表增加提款申请记录 */
            $account = [
                'user_id' => $user_id,
                'amount' => (-1) * $amount,
                'add_time' => gmtime(),
                'user_note' => $refund_note,
                'process_type' => SURPLUS_RETURN,
                'admin_user' => session('admin_name'),
                'admin_note' => sprintf($GLOBALS['_LANG']['order_refund'], $order['order_sn']),
                'is_paid' => 0
            ];
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_account'), $account, 'INSERT');

            return true;
        } else {
            return true;
        }
    }

    /**
     * 获得上一次用户采用的支付和配送方式
     *
     * @access  public
     * @return  void
     */
    public function last_shipping_and_payment()
    {
        $sql = "SELECT shipping_id, pay_id " .
            " FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id = '" . session('user_id') . "' " .
            " ORDER BY order_id DESC LIMIT 1";
        $row = $GLOBALS['db']->getRow($sql);

        if (empty($row)) {
            /* 如果获得是一个空数组，则返回默认值 */
            $row = ['shipping_id' => 0, 'pay_id' => 0];
        }

        return $row;
    }

    /**
     * 获得订单信息
     *
     * @access  private
     * @return  array
     */
    public function flow_order_info()
    {
        $order = session('?flow_order') ? session('flow_order') : [];

        /* 初始化配送和支付方式 */
        if (!isset($order['shipping_id']) || !isset($order['pay_id'])) {
            /* 如果还没有设置配送和支付 */
            if (session('user_id') > 0) {
                /* 用户已经登录了，则获得上次使用的配送和支付 */
                $arr = last_shipping_and_payment();

                if (!isset($order['shipping_id'])) {
                    $order['shipping_id'] = $arr['shipping_id'];
                }
                if (!isset($order['pay_id'])) {
                    $order['pay_id'] = $arr['pay_id'];
                }
            } else {
                if (!isset($order['shipping_id'])) {
                    $order['shipping_id'] = 0;
                }
                if (!isset($order['pay_id'])) {
                    $order['pay_id'] = 0;
                }
            }
        }

        if (!isset($order['pack_id'])) {
            $order['pack_id'] = 0;  // 初始化包装
        }
        if (!isset($order['card_id'])) {
            $order['card_id'] = 0;  // 初始化贺卡
        }
        if (!isset($order['bonus'])) {
            $order['bonus'] = 0;    // 初始化红包
        }
        if (!isset($order['integral'])) {
            $order['integral'] = 0; // 初始化积分
        }
        if (!isset($order['surplus'])) {
            $order['surplus'] = 0;  // 初始化余额
        }

        /* 扩展信息 */
        if (session('?flow_type') && intval(session('flow_type')) != CART_GENERAL_GOODS) {
            $order['extension_code'] = session('extension_code');
            $order['extension_id'] = session('extension_id');
        }

        return $order;
    }

    /**
     * 合并订单
     * @param   string $from_order_sn 从订单号
     * @param   string $to_order_sn 主订单号
     * @return  成功返回true，失败返回错误信息
     */
    public function merge_order($from_order_sn, $to_order_sn)
    {
        /* 订单号不能为空 */
        if (trim($from_order_sn) == '' || trim($to_order_sn) == '') {
            return $GLOBALS['_LANG']['order_sn_not_null'];
        }

        /* 订单号不能相同 */
        if ($from_order_sn == $to_order_sn) {
            return $GLOBALS['_LANG']['two_order_sn_same'];
        }

        /* 取得订单信息 */
        $from_order = order_info(0, $from_order_sn);
        $to_order = order_info(0, $to_order_sn);

        /* 检查订单是否存在 */
        if (!$from_order) {
            return sprintf($GLOBALS['_LANG']['order_not_exist'], $from_order_sn);
        } elseif (!$to_order) {
            return sprintf($GLOBALS['_LANG']['order_not_exist'], $to_order_sn);
        }

        /* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
        if ($from_order['extension_code'] != '' || $to_order['extension_code'] != 0) {
            return $GLOBALS['_LANG']['merge_invalid_order'];
        }

        /* 检查订单状态是否是已确认或未确认、未付款、未发货 */
        if ($from_order['order_status'] != OS_UNCONFIRMED && $from_order['order_status'] != OS_CONFIRMED) {
            return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $from_order_sn);
        } elseif ($from_order['pay_status'] != PS_UNPAYED) {
            return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $from_order_sn);
        } elseif ($from_order['shipping_status'] != SS_UNSHIPPED) {
            return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $from_order_sn);
        }

        if ($to_order['order_status'] != OS_UNCONFIRMED && $to_order['order_status'] != OS_CONFIRMED) {
            return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $to_order_sn);
        } elseif ($to_order['pay_status'] != PS_UNPAYED) {
            return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $to_order_sn);
        } elseif ($to_order['shipping_status'] != SS_UNSHIPPED) {
            return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $to_order_sn);
        }

        /* 检查订单用户是否相同 */
        if ($from_order['user_id'] != $to_order['user_id']) {
            return $GLOBALS['_LANG']['order_user_not_same'];
        }

        /* 合并订单 */
        $order = $to_order;
        $order['order_id'] = '';
        $order['add_time'] = gmtime();

        // 合并商品总额
        $order['goods_amount'] += $from_order['goods_amount'];

        // 合并折扣
        $order['discount'] += $from_order['discount'];

        if ($order['shipping_id'] > 0) {
            // 重新计算配送费用
            $weight_price = order_weight_price($to_order['order_id']);
            $from_weight_price = order_weight_price($from_order['order_id']);
            $weight_price['weight'] += $from_weight_price['weight'];
            $weight_price['amount'] += $from_weight_price['amount'];
            $weight_price['number'] += $from_weight_price['number'];

            $region_id_list = [$order['country'], $order['province'], $order['city'], $order['district']];
            $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);

            $order['shipping_fee'] = shipping_fee(
                $shipping_area['shipping_code'],
                unserialize($shipping_area['configure']),
                $weight_price['weight'],
                $weight_price['amount'],
                $weight_price['number']
            );

            // 如果保价了，重新计算保价费
            if ($order['insure_fee'] > 0) {
                $order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
            }
        }

        // 重新计算包装费、贺卡费
        if ($order['pack_id'] > 0) {
            $pack = pack_info($order['pack_id']);
            $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
        }
        if ($order['card_id'] > 0) {
            $card = card_info($order['card_id']);
            $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
        }

        // 红包不变，合并积分、余额、已付款金额
        $order['integral'] += $from_order['integral'];
        $order['integral_money'] = value_of_integral($order['integral']);
        $order['surplus'] += $from_order['surplus'];
        $order['money_paid'] += $from_order['money_paid'];

        // 计算应付款金额（不包括支付费用）
        $order['order_amount'] = $order['goods_amount'] - $order['discount']
            + $order['shipping_fee']
            + $order['insure_fee']
            + $order['pack_fee']
            + $order['card_fee']
            - $order['bonus']
            - $order['integral_money']
            - $order['surplus']
            - $order['money_paid'];

        // 重新计算支付费
        if ($order['pay_id'] > 0) {
            // 货到付款手续费
            $cod_fee = $shipping_area ? $shipping_area['pay_fee'] : 0;
            $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

            // 应付款金额加上支付费
            $order['order_amount'] += $order['pay_fee'];
        }

        /* 插入订单表 */
        do {
            $order['order_sn'] = get_order_sn();
            if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), addslashes_deep($order), 'INSERT')) {
                break;
            } else {
                if ($GLOBALS['db']->errno() != 1062) {
                    die($GLOBALS['db']->errorMsg());
                }
            }
        } while (true); // 防止订单号重复

        /* 订单号 */
        $order_id = $GLOBALS['db']->insert_id();

        /* 更新订单商品 */
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_goods') .
            " SET order_id = '$order_id' " .
            "WHERE order_id " . db_create_in([$from_order['order_id'], $to_order['order_id']]);
        $GLOBALS['db']->query($sql);

        load_helper('clips');
        /* 插入支付日志 */
        insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

        /* 删除原订单 */
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('order_info') .
            " WHERE order_id " . db_create_in([$from_order['order_id'], $to_order['order_id']]);
        $GLOBALS['db']->query($sql);

        /* 删除原订单支付日志 */
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('pay_log') .
            " WHERE order_id " . db_create_in([$from_order['order_id'], $to_order['order_id']]);
        $GLOBALS['db']->query($sql);

        /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
        if ($from_order['bonus_id'] > 0) {
            unuse_bonus($from_order['bonus_id']);
        }

        /* 返回成功 */
        return true;
    }

    /**
     * 改变订单中商品库存
     * @param   int $order_id 订单号
     * @param   bool $is_dec 是否减少库存
     * @param   bool $storage 减库存的时机，1，下订单时；0，发货时；
     */
    public function change_order_goods_storage($order_id, $is_dec = true, $storage = 0)
    {
        /* 查询订单商品信息 */
        switch ($storage) {
            case 0:
                $sql = "SELECT goods_id, SUM(send_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
                    " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                break;

            case 1:
                $sql = "SELECT goods_id, SUM(goods_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
                    " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                break;
        }

        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            if ($row['extension_code'] != "package_buy") {
                if ($is_dec) {
                    change_goods_storage($row['goods_id'], $row['product_id'], -$row['num']);
                } else {
                    change_goods_storage($row['goods_id'], $row['product_id'], $row['num']);
                }
                $GLOBALS['db']->query($sql);
            } else {
                $sql = "SELECT goods_id, goods_number" .
                    " FROM " . $GLOBALS['ecs']->table('package_goods') .
                    " WHERE package_id = '" . $row['goods_id'] . "'";
                $res_goods = $GLOBALS['db']->query($sql);
                foreach ($res_goods as $row_goods) {
                    $sql = "SELECT is_real" .
                        " FROM " . $GLOBALS['ecs']->table('goods') .
                        " WHERE goods_id = '" . $row_goods['goods_id'] . "'";
                    $real_goods = $GLOBALS['db']->query($sql);
                    $is_goods = $GLOBALS['db']->fetchRow($real_goods);

                    if ($is_dec) {
                        change_goods_storage($row_goods['goods_id'], $row['product_id'], -($row['num'] * $row_goods['goods_number']));
                    } elseif ($is_goods['is_real']) {
                        change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']));
                    }
                }
            }
        }
    }

    /**
     * 生成查询订单的sql
     * @param   string $type 类型
     * @param   string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function order_query_sql($type = 'finished', $alias = '')
    {
        /* 已完成订单 */
        if ($type == 'finished') {
            return " AND {$alias}order_status " . db_create_in([OS_CONFIRMED, OS_SPLITED]) .
                " AND {$alias}shipping_status " . db_create_in([SS_SHIPPED, SS_RECEIVED]) .
                " AND {$alias}pay_status " . db_create_in([PS_PAYED, PS_PAYING]) . " ";
        } /* 待发货订单 */
        elseif ($type == 'await_ship') {
            return " AND   {$alias}order_status " .
                db_create_in([OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART]) .
                " AND   {$alias}shipping_status " .
                db_create_in([SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING]) .
                " AND ( {$alias}pay_status " . db_create_in([PS_PAYED, PS_PAYING]) . " OR {$alias}pay_id " . db_create_in(payment_id_list(true)) . ") ";
        } /* 待付款订单 */
        elseif ($type == 'await_pay') {
            return " AND   {$alias}order_status " . db_create_in([OS_CONFIRMED, OS_SPLITED]) .
                " AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
                " AND ( {$alias}shipping_status " . db_create_in([SS_SHIPPED, SS_RECEIVED]) . " OR {$alias}pay_id " . db_create_in(payment_id_list(false)) . ") ";
        } /* 未确认订单 */
        elseif ($type == 'unconfirmed') {
            return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
        } /* 未处理订单：用户可操作 */
        elseif ($type == 'unprocessed') {
            return " AND {$alias}order_status " . db_create_in([OS_UNCONFIRMED, OS_CONFIRMED]) .
                " AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
                " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
        } /* 未付款未发货订单：管理员可操作 */
        elseif ($type == 'unpay_unship') {
            return " AND {$alias}order_status " . db_create_in([OS_UNCONFIRMED, OS_CONFIRMED]) .
                " AND {$alias}shipping_status " . db_create_in([SS_UNSHIPPED, SS_PREPARING]) .
                " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
        } /* 已发货订单：不论是否付款 */
        elseif ($type == 'shipped') {
            return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
                " AND {$alias}shipping_status " . db_create_in([SS_SHIPPED, SS_RECEIVED]) . " ";
        } else {
            die('函数 order_query_sql 参数错误');
        }
    }

    /**
     * 生成查询订单总金额的字段
     * @param   string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function order_amount_field($alias = '')
    {
        return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
            " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
            " + {$alias}card_fee ";
    }

    /**
     * 生成计算应付款金额的字段
     * @param   string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function order_due_field($alias = '')
    {
        return order_amount_field($alias) .
            " - {$alias}money_paid - {$alias}surplus - {$alias}integral_money" .
            " - {$alias}bonus - {$alias}discount ";
    }

    /**
     * 取得某订单应该赠送的积分数
     * @param   array $order 订单
     * @return  int     积分数
     */
    public function integral_to_give($order)
    {
        /* 判断是否团购 */
        if ($order['extension_code'] == 'group_buy') {
            load_helper('goods');
            $group_buy = group_buy_info(intval($order['extension_id']));

            return ['custom_points' => $group_buy['gift_integral'], 'rank_points' => $order['goods_amount']];
        } else {
            $sql = "SELECT SUM(og.goods_number * IF(g.give_integral > -1, g.give_integral, og.goods_price)) AS custom_points, SUM(og.goods_number * IF(g.rank_integral > -1, g.rank_integral, og.goods_price)) AS rank_points " .
                "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS og, " .
                $GLOBALS['ecs']->table('goods') . " AS g " .
                "WHERE og.goods_id = g.goods_id " .
                "AND og.order_id = '$order[order_id]' " .
                "AND og.goods_id > 0 " .
                "AND og.parent_id = 0 " .
                "AND og.is_gift = 0 AND og.extension_code != 'package_buy'";

            return $GLOBALS['db']->getRow($sql);
        }
    }

    /**
     * 记录订单操作记录
     *
     * @access  public
     * @param   string $order_sn 订单编号
     * @param   integer $order_status 订单状态
     * @param   integer $shipping_status 配送状态
     * @param   integer $pay_status 付款状态
     * @param   string $note 备注
     * @param   string $username 用户名，用户自己的操作则为 buyer
     * @return  void
     */
    public function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0)
    {
        if (is_null($username)) {
            $username = session('admin_name');
        }

        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('order_action') .
            ' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
            'SELECT ' .
            "order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" . gmtime() . "' " .
            'FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 返回订单中的虚拟商品
     *
     * @access  public
     * @param   int $order_id 订单id值
     * @param   bool $shipping 是否已经发货
     *
     * @return array()
     */
    public function get_virtual_goods($order_id, $shipping = false)
    {
        if ($shipping) {
            $sql = 'SELECT goods_id, goods_name, send_number AS num, extension_code FROM ' .
                $GLOBALS['ecs']->table('order_goods') .
                " WHERE order_id = '$order_id' AND extension_code > ''";
        } else {
            $sql = 'SELECT goods_id, goods_name, (goods_number - send_number) AS num, extension_code FROM ' .
                $GLOBALS['ecs']->table('order_goods') .
                " WHERE order_id = '$order_id' AND is_real = 0 AND (goods_number - send_number) > 0 AND extension_code > '' ";
        }
        $res = $GLOBALS['db']->getAll($sql);

        $virtual_goods = [];
        foreach ($res as $row) {
            $virtual_goods[$row['extension_code']][] = ['goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']];
        }

        return $virtual_goods;
    }

    /**
     *  虚拟商品发货
     *
     * @access  public
     * @param   array $virtual_goods 虚拟商品数组
     * @param   string $msg 错误信息
     * @param   string $order_sn 订单号。
     * @param   string $process 设定当前流程：split，发货分单流程；other，其他，默认。
     *
     * @return bool
     */
    public function virtual_goods_ship(&$virtual_goods, &$msg, $order_sn, $return_result = false, $process = 'other')
    {
        $virtual_card = [];
        foreach ($virtual_goods as $code => $goods_list) {
            /* 只处理虚拟卡 */
            if ($code == 'virtual_card') {
                foreach ($goods_list as $goods) {
                    if (virtual_card_shipping($goods, $order_sn, $msg, $process)) {
                        if ($return_result) {
                            $virtual_card[] = ['goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => virtual_card_result($order_sn, $goods)];
                        }
                    } else {
                        return false;
                    }
                }
                $GLOBALS['smarty']->assign('virtual_card', $virtual_card);
            }
        }

        return true;
    }

    /**
     *  虚拟卡发货
     *
     * @access  public
     * @param   string $goods 商品详情数组
     * @param   string $order_sn 本次操作的订单
     * @param   string $msg 返回信息
     * @param   string $process 设定当前流程：split，发货分单流程；other，其他，默认。
     *
     * @return  boolen
     */
    public function virtual_card_shipping($goods, $order_sn, &$msg, $process = 'other')
    {
        /* 包含加密解密函数所在文件 */
        load_helper('code');

        /* 检查有没有缺货 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('virtual_card') . " WHERE goods_id = '$goods[goods_id]' AND is_saled = 0 ";
        $num = $GLOBALS['db']->getOne($sql);

        if ($num < $goods['num']) {
            $msg .= sprintf($GLOBALS['_LANG']['virtual_card_oos'], $goods['goods_name']);

            return false;
        }

        /* 取出卡片信息 */
        $sql = "SELECT card_id, card_sn, card_password, end_date, crc32 FROM " . $GLOBALS['ecs']->table('virtual_card') . " WHERE goods_id = '$goods[goods_id]' AND is_saled = 0  LIMIT " . $goods['num'];
        $arr = $GLOBALS['db']->getAll($sql);

        $card_ids = [];
        $cards = [];

        foreach ($arr as $virtual_card) {
            $card_info = [];

            /* 卡号和密码解密 */
            if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY)) {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
                $card_info['card_password'] = decrypt($virtual_card['card_password']);
            } elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY)) {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
                $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
            } else {
                $msg .= 'error key';

                return false;
            }
            $card_info['end_date'] = date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
            $card_ids[] = $virtual_card['card_id'];
            $cards[] = $card_info;
        }

        /* 标记已经取出的卡片 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('virtual_card') . " SET " .
            "is_saled = 1 ," .
            "order_sn = '$order_sn' " .
            "WHERE " . db_create_in($card_ids, 'card_id');
        if (!$GLOBALS['db']->query($sql, 'SILENT')) {
            $msg .= $GLOBALS['db']->error();

            return false;
        }

        /* 更新库存 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_number = goods_number - '$goods[num]' WHERE goods_id = '$goods[goods_id]'";
        $GLOBALS['db']->query($sql);

        if (true) {
            /* 获取订单信息 */
            $sql = "SELECT order_id, order_sn, consignee, email FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
            $order = $GLOBALS['db']->getRow($sql);

            /* 更新订单信息 */
            if ($process == 'split') {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
                    SET send_number = send_number + '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
            } else {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
                    SET send_number = '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
            }

            if (!$GLOBALS['db']->query($sql, 'SILENT')) {
                $msg .= $GLOBALS['db']->error();

                return false;
            }
        }

        /* 发送邮件 */
        $GLOBALS['smarty']->assign('virtual_card', $cards);
        $GLOBALS['smarty']->assign('order', $order);
        $GLOBALS['smarty']->assign('goods', $goods);

        $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
        $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

        $tpl = get_mail_template('virtual_card');
        $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
        send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);

        return true;
    }

    /**
     *  返回虚拟卡信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function virtual_card_result($order_sn, $goods)
    {
        /* 包含加密解密函数所在文件 */
        load_helper('code');

        /* 获取已经发送的卡片数据 */
        $sql = "SELECT card_sn, card_password, end_date, crc32 FROM " . $GLOBALS['ecs']->table('virtual_card') . " WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";
        $res = $GLOBALS['db']->query($sql);

        $cards = [];

        foreach ($res as $row) {
            /* 卡号和密码解密 */
            if ($row['crc32'] == 0 || $row['crc32'] == crc32(AUTH_KEY)) {
                $row['card_sn'] = decrypt($row['card_sn']);
                $row['card_password'] = decrypt($row['card_password']);
            } elseif ($row['crc32'] == crc32(OLD_AUTH_KEY)) {
                $row['card_sn'] = decrypt($row['card_sn'], OLD_AUTH_KEY);
                $row['card_password'] = decrypt($row['card_password'], OLD_AUTH_KEY);
            } else {
                $row['card_sn'] = '***';
                $row['card_password'] = '***';
            }

            $cards[] = ['card_sn' => $row['card_sn'], 'card_password' => $row['card_password'], 'end_date' => date($GLOBALS['_CFG']['date_format'], $row['end_date'])];
        }

        return $cards;
    }
}
