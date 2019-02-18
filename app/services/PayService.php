<?php

namespace App\Services;

/**
 * Class PayService
 * @package App\Services
 */
class PayService
{

/**
 * 将支付LOG插入数据表
 *
 * @access  public
 * @param   integer $id 订单编号
 * @param   float $amount 订单金额
 * @param   integer $type 支付类型
 * @param   integer $is_paid 是否已支付
 *
 * @return  int
 */
    public function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0)
    {
        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
        " VALUES  ('$id', '$amount', '$type', '$is_paid')";
        $GLOBALS['db']->query($sql);

        return $GLOBALS['db']->insert_id();
    }

    /**
     * 取得上次未支付的pay_lig_id
     *
     * @access  public
     * @param   array $surplus_id 余额记录的ID
     * @param   array $pay_type 支付的类型：预付款/订单支付
     *
     * @return  int
     */
    public function get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS)
    {
        $sql = 'SELECT log_id FROM' . $GLOBALS['ecs']->table('pay_log') .
        " WHERE order_id = '$surplus_id' AND order_type = '$pay_type' AND is_paid = 0";

        return $GLOBALS['db']->getOne($sql);
    }
}
