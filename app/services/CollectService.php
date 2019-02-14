<?php

namespace app\services;

/**
 * Class CollectService
 * @package app\services
 */
class CollectService
{
    /**
     *  获取指定用户的收藏商品列表
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   int $num 列表最大数量
     * @param   int $start 列表其实位置
     *
     * @return  array   $arr
     */
    public function get_collection_goods($user_id, $num = 10, $start = 0)
    {
        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
            'g.promote_price, g.promote_start_date,g.promote_end_date, c.rec_id, c.is_attention' .
            ' FROM ' . $GLOBALS['ecs']->table('collect_goods') . ' AS c' .
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "ON g.goods_id = c.goods_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            " WHERE c.user_id = '$user_id' ORDER BY c.rec_id DESC";
        $res = $GLOBALS['db']->selectLimit($sql, $num, $start);

        $goods_list = [];
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods_list[$row['goods_id']]['rec_id'] = $row['rec_id'];
            $goods_list[$row['goods_id']]['is_attention'] = $row['is_attention'];
            $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];
            $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
            $goods_list[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $goods_list[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods_list[$row['goods_id']]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
        }

        return $goods_list;
    }
}
