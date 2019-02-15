<?php

namespace App\Services;

/**
 * Class GroupBuyService
 * @package App\Services
 */
class GroupBuyService
{

    /**
     * 取得团购活动信息
     * @param   int $group_buy_id 团购活动id
     * @param   int $current_num 本次购买数量（计算当前价时要加上的数量）
     * @return  array
     *                  status          状态：
     */
    public function group_buy_info($group_buy_id, $current_num = 0)
    {
        /* 取得团购活动信息 */
        $group_buy_id = intval($group_buy_id);
        $sql = "SELECT *, act_id AS group_buy_id, act_desc AS group_buy_desc, start_time AS start_date, end_time AS end_date " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            "WHERE act_id = '$group_buy_id' " .
            "AND act_type = '" . GAT_GROUP_BUY . "'";
        $group_buy = $GLOBALS['db']->getRow($sql);

        /* 如果为空，返回空数组 */
        if (empty($group_buy)) {
            return [];
        }

        $ext_info = unserialize($group_buy['ext_info']);
        $group_buy = array_merge($group_buy, $ext_info);

        /* 格式化时间 */
        $group_buy['formated_start_date'] = local_date('Y-m-d H:i', $group_buy['start_time']);
        $group_buy['formated_end_date'] = local_date('Y-m-d H:i', $group_buy['end_time']);

        /* 格式化保证金 */
        $group_buy['formated_deposit'] = price_format($group_buy['deposit'], false);

        /* 处理价格阶梯 */
        $price_ladder = $group_buy['price_ladder'];
        if (!is_array($price_ladder) || empty($price_ladder)) {
            $price_ladder = [['amount' => 0, 'price' => 0]];
        } else {
            foreach ($price_ladder as $key => $amount_price) {
                $price_ladder[$key]['formated_price'] = price_format($amount_price['price'], false);
            }
        }
        $group_buy['price_ladder'] = $price_ladder;

        /* 统计信息 */
        $stat = group_buy_stat($group_buy_id, $group_buy['deposit']);
        $group_buy = array_merge($group_buy, $stat);

        /* 计算当前价 */
        $cur_price = $price_ladder[0]['price']; // 初始化
        $cur_amount = $stat['valid_goods'] + $current_num; // 当前数量
        foreach ($price_ladder as $amount_price) {
            if ($cur_amount >= $amount_price['amount']) {
                $cur_price = $amount_price['price'];
            } else {
                break;
            }
        }
        $group_buy['cur_price'] = $cur_price;
        $group_buy['formated_cur_price'] = price_format($cur_price, false);

        /* 最终价 */
        $group_buy['trans_price'] = $group_buy['cur_price'];
        $group_buy['formated_trans_price'] = $group_buy['formated_cur_price'];
        $group_buy['trans_amount'] = $group_buy['valid_goods'];

        /* 状态 */
        $group_buy['status'] = group_buy_status($group_buy);
        if (isset($GLOBALS['_LANG']['gbs'][$group_buy['status']])) {
            $group_buy['status_desc'] = $GLOBALS['_LANG']['gbs'][$group_buy['status']];
        }

        $group_buy['start_time'] = $group_buy['formated_start_date'];
        $group_buy['end_time'] = $group_buy['formated_end_date'];

        return $group_buy;
    }

    /*
     * 取得某团购活动统计信息
     * @param   int     $group_buy_id   团购活动id
     * @param   float   $deposit        保证金
     * @return  array   统计信息
     *                  total_order     总订单数
     *                  total_goods     总商品数
     *                  valid_order     有效订单数
     *                  valid_goods     有效商品数
     */
    public function group_buy_stat($group_buy_id, $deposit)
    {
        $group_buy_id = intval($group_buy_id);

        /* 取得团购活动商品ID */
        $sql = "SELECT goods_id " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') .
            "WHERE act_id = '$group_buy_id' " .
            "AND act_type = '" . GAT_GROUP_BUY . "'";
        $group_buy_goods_id = $GLOBALS['db']->getOne($sql);

        /* 取得总订单数和总商品数 */
        $sql = "SELECT COUNT(*) AS total_order, SUM(g.goods_number) AS total_goods " .
            "FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
            $GLOBALS['ecs']->table('order_goods') . " AS g " .
            " WHERE o.order_id = g.order_id " .
            "AND o.extension_code = 'group_buy' " .
            "AND o.extension_id = '$group_buy_id' " .
            "AND g.goods_id = '$group_buy_goods_id' " .
            "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "')";
        $stat = $GLOBALS['db']->getRow($sql);
        if ($stat['total_order'] == 0) {
            $stat['total_goods'] = 0;
        }

        /* 取得有效订单数和有效商品数 */
        $deposit = floatval($deposit);
        if ($deposit > 0 && $stat['total_order'] > 0) {
            $sql .= " AND (o.money_paid + o.surplus) >= '$deposit'";
            $row = $GLOBALS['db']->getRow($sql);
            $stat['valid_order'] = $row['total_order'];
            if ($stat['valid_order'] == 0) {
                $stat['valid_goods'] = 0;
            } else {
                $stat['valid_goods'] = $row['total_goods'];
            }
        } else {
            $stat['valid_order'] = $stat['total_order'];
            $stat['valid_goods'] = $stat['total_goods'];
        }

        return $stat;
    }

    /**
     * 获得团购的状态
     *
     * @access  public
     * @param   array
     * @return  integer
     */
    public function group_buy_status($group_buy)
    {
        $now = gmtime();
        if ($group_buy['is_finished'] == 0) {
            /* 未处理 */
            if ($now < $group_buy['start_time']) {
                $status = GBS_PRE_START;
            } elseif ($now > $group_buy['end_time']) {
                $status = GBS_FINISHED;
            } else {
                if ($group_buy['restrict_amount'] == 0 || $group_buy['valid_goods'] < $group_buy['restrict_amount']) {
                    $status = GBS_UNDER_WAY;
                } else {
                    $status = GBS_FINISHED;
                }
            }
        } elseif ($group_buy['is_finished'] == GBS_SUCCEED) {
            /* 已处理，团购成功 */
            $status = GBS_SUCCEED;
        } elseif ($group_buy['is_finished'] == GBS_FAIL) {
            /* 已处理，团购失败 */
            $status = GBS_FAIL;
        }

        return $status;
    }

    /**
     * 获得会员的团购活动列表
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   int $num 列表显示条数
     * @param   int $start 显示起始位置
     *
     * @return  array       $arr             团购活动列表
     */
    public function get_user_group_buy($user_id, $num = 10, $start = 0)
    {
        return true;
    }

    /**
     * 获得团购详细信息(团购订单信息)
     *
     *
     */
    public function get_group_buy_detail($user_id, $group_buy_id)
    {
        return true;
    }
}
