<?php

namespace App\Services;

/**
 * Class BookingService
 * @package App\Services
 */
class BookingService
{
    /**
     *  查看此商品是否已进行过缺货登记
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   int $goods_id 商品ID
     *
     * @return  int
     */
    public function get_booking_rec($user_id, $goods_id)
    {
        $sql = 'SELECT COUNT(*) ' .
            'FROM ' . $GLOBALS['ecs']->table('booking_goods') .
            "WHERE user_id = '$user_id' AND goods_id = '$goods_id' AND is_dispose = 0";

        return $GLOBALS['db']->getOne($sql);
    }

    /**
     *  获取某用户的缺货登记列表
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   int $num 列表最大数量
     * @param   int $start 列表其实位置
     *
     * @return  array   $booking
     */
    public function get_booking_list($user_id, $num, $start)
    {
        $booking = [];
        $sql = "SELECT bg.rec_id, bg.goods_id, bg.goods_number, bg.booking_time, bg.dispose_note, g.goods_name " .
            "FROM " . $GLOBALS['ecs']->table('booking_goods') . " AS bg , " . $GLOBALS['ecs']->table('goods') . " AS g" . " WHERE bg.goods_id = g.goods_id AND bg.user_id = '$user_id' ORDER BY bg.booking_time DESC";
        $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

        foreach ($res as $row) {
            if (empty($row['dispose_note'])) {
                $row['dispose_note'] = 'N/A';
            }
            $booking[] = ['rec_id' => $row['rec_id'],
                'goods_name' => $row['goods_name'],
                'goods_number' => $row['goods_number'],
                'booking_time' => local_date($GLOBALS['_CFG']['date_format'], $row['booking_time']),
                'dispose_note' => $row['dispose_note'],
                'url' => build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name'])];
        }

        return $booking;
    }

    /**
     *  验证删除某个收藏商品
     *
     * @access  public
     * @param   int $booking_id 缺货登记的ID
     * @param   int $user_id 会员的ID
     * @return  boolen      $bool
     */
    public function delete_booking($booking_id, $user_id)
    {
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('booking_goods') .
            " WHERE rec_id = '$booking_id' AND user_id = '$user_id'";

        return $GLOBALS['db']->query($sql);
    }

    /**
     * 添加缺货登记记录到数据表
     * @access  public
     * @param   array $booking
     *
     * @return void
     */
    public function add_booking($booking)
    {
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('booking_goods') .
            " VALUES ('', '" . session('user_id') . "', '$booking[email]', '$booking[linkman]', " .
            "'$booking[tel]', '$booking[goods_id]', '$booking[desc]', " .
            "'$booking[goods_amount]', '" . gmtime() . "', 0, '', 0, '')";
        $GLOBALS['db']->query($sql);

        return $GLOBALS['db']->insert_id();
    }

    /**
     *  获取某用户的缺货登记列表
     *
     * @access  public
     * @param   int $goods_id 商品ID
     *
     * @return  array   $info
     */
    public function get_goodsinfo($goods_id)
    {
        $info = [];
        $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$goods_id'";

        $info['goods_name'] = $GLOBALS['db']->getOne($sql);
        $info['goods_number'] = 1;
        $info['id'] = $goods_id;

        if (!empty(session('user_id'))) {
            $row = [];
            $sql = "SELECT ua.consignee, ua.email, ua.tel, ua.mobile " .
                "FROM " . $GLOBALS['ecs']->table('user_address') . " AS ua, " . $GLOBALS['ecs']->table('users') . " AS u" .
                " WHERE u.address_id = ua.address_id AND u.user_id = '" . session('user_id') . "'";
            $row = $GLOBALS['db']->getRow($sql);
            $info['consignee'] = empty($row['consignee']) ? '' : $row['consignee'];
            $info['email'] = empty($row['email']) ? '' : $row['email'];
            $info['tel'] = empty($row['mobile']) ? (empty($row['tel']) ? '' : $row['tel']) : $row['mobile'];
        }

        return $info;
    }
}
