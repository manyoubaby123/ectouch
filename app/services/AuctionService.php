<?php

namespace app\services;

/**
 * Class AuctionService
 * @package app\services
 */
class AuctionService
{
    /**
     * 取得拍卖活动信息
     * @param   int $act_id 活动id
     * @return  array
     */
    public function auction_info($act_id, $config = false)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_id = '$act_id'";
        $auction = $GLOBALS['db']->getRow($sql);
        if ($auction['act_type'] != GAT_AUCTION) {
            return [];
        }
        $auction['status_no'] = auction_status($auction);
        if ($config == true) {
            $auction['start_time'] = local_date('Y-m-d H:i', $auction['start_time']);
            $auction['end_time'] = local_date('Y-m-d H:i', $auction['end_time']);
        } else {
            $auction['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $auction['start_time']);
            $auction['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $auction['end_time']);
        }
        $ext_info = unserialize($auction['ext_info']);
        $auction = array_merge($auction, $ext_info);
        $auction['formated_start_price'] = price_format($auction['start_price']);
        $auction['formated_end_price'] = price_format($auction['end_price']);
        $auction['formated_amplitude'] = price_format($auction['amplitude']);
        $auction['formated_deposit'] = price_format($auction['deposit']);

        /* 查询出价用户数和最后出价 */
        $sql = "SELECT COUNT(DISTINCT bid_user) FROM " . $GLOBALS['ecs']->table('auction_log') .
            " WHERE act_id = '$act_id'";
        $auction['bid_user_count'] = $GLOBALS['db']->getOne($sql);
        if ($auction['bid_user_count'] > 0) {
            $sql = "SELECT a.*, u.user_name " .
                "FROM " . $GLOBALS['ecs']->table('auction_log') . " AS a, " .
                $GLOBALS['ecs']->table('users') . " AS u " .
                "WHERE a.bid_user = u.user_id " .
                "AND act_id = '$act_id' " .
                "ORDER BY a.log_id DESC";
            $row = $GLOBALS['db']->getRow($sql);
            $row['formated_bid_price'] = price_format($row['bid_price'], false);
            $row['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['bid_time']);
            $auction['last_bid'] = $row;
        }

        /* 查询已确认订单数 */
        if ($auction['status_no'] > 1) {
            $sql = "SELECT COUNT(*)" .
                " FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE extension_code = 'auction'" .
                " AND extension_id = '$act_id'" .
                " AND order_status " . db_create_in([OS_CONFIRMED, OS_UNCONFIRMED]);
            $auction['order_count'] = $GLOBALS['db']->getOne($sql);
        } else {
            $auction['order_count'] = 0;
        }

        /* 当前价 */
        $auction['current_price'] = isset($auction['last_bid']) ? $auction['last_bid']['bid_price'] : $auction['start_price'];
        $auction['formated_current_price'] = price_format($auction['current_price'], false);

        return $auction;
    }

    /**
     * 取得拍卖活动出价记录
     * @param   int $act_id 活动id
     * @return  array
     */
    public function auction_log($act_id)
    {
        $log = [];
        $sql = "SELECT a.*, u.user_name " .
            "FROM " . $GLOBALS['ecs']->table('auction_log') . " AS a," .
            $GLOBALS['ecs']->table('users') . " AS u " .
            "WHERE a.bid_user = u.user_id " .
            "AND act_id = '$act_id' " .
            "ORDER BY a.log_id DESC";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $row['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['bid_time']);
            $row['formated_bid_price'] = price_format($row['bid_price'], false);
            $log[] = $row;
        }

        return $log;
    }

    /**
     * 计算拍卖活动状态（注意参数一定是原始信息）
     * @param   array $auction 拍卖活动原始信息
     * @return  int
     */
    public function auction_status($auction)
    {
        $now = gmtime();
        if ($auction['is_finished'] == 0) {
            if ($now < $auction['start_time']) {
                return PRE_START; // 未开始
            } elseif ($now > $auction['end_time']) {
                return FINISHED; // 已结束，未处理
            } else {
                return UNDER_WAY; // 进行中
            }
        } elseif ($auction['is_finished'] == 1) {
            return FINISHED; // 已结束，未处理
        } else {
            return SETTLED; // 已结束，已处理
        }
    }
}
