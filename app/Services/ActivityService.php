<?php

namespace App\Services;

/**
 * Class ActivityService
 * @package App\Services
 */
class ActivityService
{
    /**
     * 取得优惠活动信息
     * @param   int $act_id 活动id
     * @return  array
     */
    public function favourable_info($act_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE act_id = '$act_id'";
        $row = $GLOBALS['db']->getRow($sql);
        if (!empty($row)) {
            $row['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
            $row['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
            $row['formated_min_amount'] = price_format($row['min_amount']);
            $row['formated_max_amount'] = price_format($row['max_amount']);
            $row['gift'] = unserialize($row['gift']);
            if ($row['act_type'] == FAT_GOODS) {
                $row['act_type_ext'] = round($row['act_type_ext']);
            }
        }

        return $row;
    }

    /**
     *  所有的促销活动信息
     *
     * @access  public
     * @return  array
     */
    public function get_promotion_info($goods_id = '')
    {
        $snatch = [];
        $group = [];
        $auction = [];
        $package = [];
        $favourable = [];

        $gmtime = gmtime();
        $sql = 'SELECT act_id, act_name, act_type, start_time, end_time FROM ' . $GLOBALS['ecs']->table('goods_activity') . " WHERE is_finished=0 AND start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND goods_id = '$goods_id'";
        }
        $res = $GLOBALS['db']->getAll($sql);
        foreach ($res as $data) {
            switch ($data['act_type']) {
                case GAT_SNATCH: //夺宝奇兵
                    $snatch[$data['act_id']]['act_name'] = $data['act_name'];
                    $snatch[$data['act_id']]['url'] = build_uri('snatch', ['sid' => $data['act_id']]);
                    $snatch[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                    $snatch[$data['act_id']]['sort'] = $data['start_time'];
                    $snatch[$data['act_id']]['type'] = 'snatch';
                    break;

                case GAT_GROUP_BUY: //团购
                    $group[$data['act_id']]['act_name'] = $data['act_name'];
                    $group[$data['act_id']]['url'] = build_uri('group_buy', ['gbid' => $data['act_id']]);
                    $group[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                    $group[$data['act_id']]['sort'] = $data['start_time'];
                    $group[$data['act_id']]['type'] = 'group_buy';
                    break;

                case GAT_AUCTION: //拍卖
                    $auction[$data['act_id']]['act_name'] = $data['act_name'];
                    $auction[$data['act_id']]['url'] = build_uri('auction', ['auid' => $data['act_id']]);
                    $auction[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                    $auction[$data['act_id']]['sort'] = $data['start_time'];
                    $auction[$data['act_id']]['type'] = 'auction';
                    break;

                case GAT_PACKAGE: //礼包
                    $package[$data['act_id']]['act_name'] = $data['act_name'];
                    $package[$data['act_id']]['url'] = 'package.php#' . $data['act_id'];
                    $package[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                    $package[$data['act_id']]['sort'] = $data['start_time'];
                    $package[$data['act_id']]['type'] = 'package';
                    break;
            }
        }

        $user_rank = ',' . session('user_rank') . ',';
        $favourable = [];
        $sql = 'SELECT act_id, act_range, act_range_ext, act_name, start_time, end_time FROM ' . $GLOBALS['ecs']->table('favourable_activity') . " WHERE start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'";
        }
        $res = $GLOBALS['db']->getAll($sql);

        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = 'activity.php';
                $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
            }
        } else {
            $sql = "SELECT cat_id, brand_id FROM " . $GLOBALS['ecs']->table('goods') .
                "WHERE goods_id = '$goods_id'";
            $row = $GLOBALS['db']->getRow($sql);
            $category_id = $row['cat_id'];
            $brand_id = $row['brand_id'];

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = [];
                    $raw_id_list = explode(',', $rows['act_range_ext']);
                    foreach ($raw_id_list as $id) {
                        $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                    }
                    $ids = join(',', array_unique($id_list));

                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = 'activity.php';
                        $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = 'activity.php';
                        $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = 'activity.php';
                        $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                    }
                }
            }
        }

        $sort_time = [];
        $arr = array_merge($snatch, $group, $auction, $package, $favourable);
        foreach ($arr as $key => $value) {
            $sort_time[] = $value['sort'];
        }
        array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

        return $arr;
    }
}
