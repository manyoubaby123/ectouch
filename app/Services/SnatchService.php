<?php

namespace App\Services;

/**
 * Class SnatchService
 * @package App\Services
 */
class SnatchService
{

/**
 * 获取指定 id snatch 活动的结果
 *
 * @access  public
 * @param   int $id snatch_id
 *
 * @return  array           array(user_name, bie_price, bid_time, num)
 *                          num通常为1，如果为2表示有2个用户取到最小值，但结果只返回最早出价用户。
 */
    public function get_snatch_result($id)
    {
        $sql = 'SELECT u.user_id, u.user_name, u.email, lg.bid_price, lg.bid_time, count(*) as num' .
        ' FROM ' . $GLOBALS['ecs']->table('snatch_log') . ' AS lg ' .
        ' LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON lg.user_id = u.user_id' .
        " WHERE lg.snatch_id = '$id'" .
        ' GROUP BY lg.bid_price' .
        ' ORDER BY num ASC, lg.bid_price ASC, lg.bid_time ASC LIMIT 1';
        $rec = $GLOBALS['db']->getRow($sql);

        if ($rec) {
            $rec['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $rec['bid_time']);
            $rec['formated_bid_price'] = price_format($rec['bid_price'], false);

            /* 活动信息 */
            $sql = 'SELECT ext_info " .
               " FROM ' . $GLOBALS['ecs']->table('goods_activity') .
            " WHERE act_id= '$id' AND act_type=" . GAT_SNATCH .
            " LIMIT 1";
            $row = $GLOBALS['db']->getOne($sql);
            $info = unserialize($row);

            if (!empty($info['max_price'])) {
                $rec['buy_price'] = ($rec['bid_price'] > $info['max_price']) ? $info['max_price'] : $rec['bid_price'];
            } else {
                $rec['buy_price'] = $rec['bid_price'];
            }

            /* 检查订单 */
            $sql = "SELECT COUNT(*)" .
            " FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE extension_code = 'snatch'" .
            " AND extension_id = '$id'" .
            " AND order_status " . db_create_in([OS_CONFIRMED, OS_UNCONFIRMED]);

            $rec['order_count'] = $GLOBALS['db']->getOne($sql);
        }

        return $rec;
    }
}
