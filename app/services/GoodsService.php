<?php

namespace app\services;

/**
 * Class GoodsService
 * @package app\services
 */
class GoodsService
{
    /**
     * 调用当前分类的销售排行榜
     *
     * @access  public
     * @param   string $cats 查询的分类
     * @return  array
     */
    public function get_top10($cats = '')
    {
        $cats = get_children($cats);
        $where = !empty($cats) ? "AND ($cats OR " . get_extension_goods($cats) . ") " : '';

        /* 排行统计的时间 */
        switch ($GLOBALS['_CFG']['top10_time']) {
            case 1: // 一年
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 365 * 86400) . "'";
                break;
            case 2: // 半年
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 180 * 86400) . "'";
                break;
            case 3: // 三个月
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 90 * 86400) . "'";
                break;
            case 4: // 一个月
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 30 * 86400) . "'";
                break;
            default:
                $top10_time = '';
        }

        $sql = 'SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_thumb, SUM(og.goods_number) as goods_number ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g, ' .
            $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
            $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
            "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 $where $top10_time ";
        //判断是否启用库存，库存数量是否大于0
        if ($GLOBALS['_CFG']['use_storage'] == 1) {
            $sql .= " AND g.goods_number > 0 ";
        }
        $sql .= ' AND og.order_id = o.order_id AND og.goods_id = g.goods_id ' .
            "AND (o.order_status = '" . OS_CONFIRMED . "' OR o.order_status = '" . OS_SPLITED . "') " .
            "AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
            "AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
            'GROUP BY g.goods_id ORDER BY goods_number DESC, g.goods_id DESC LIMIT ' . $GLOBALS['_CFG']['top_number'];

        $arr = $GLOBALS['db']->getAll($sql);

        for ($i = 0, $count = count($arr); $i < $count; $i++) {
            $arr[$i]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($arr[$i]['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $arr[$i]['goods_name'];
            $arr[$i]['url'] = build_uri('goods', ['gid' => $arr[$i]['goods_id']], $arr[$i]['goods_name']);
            $arr[$i]['thumb'] = get_image_path($arr[$i]['goods_id'], $arr[$i]['goods_thumb'], true);
            $arr[$i]['price'] = price_format($arr[$i]['shop_price']);
        }

        return $arr;
    }

    /**
     * 获得推荐商品
     *
     * @access  public
     * @param   string $type 推荐类型，可以是 best, new, hot
     * @return  array
     */
    public function get_recommend_goods($type = '', $cats = '')
    {
        if (!in_array($type, ['best', 'new', 'hot'])) {
            return [];
        }

        //取不同推荐对应的商品
        static $type_goods = [];
        if (empty($type_goods[$type])) {
            //初始化数据
            $type_goods['best'] = [];
            $type_goods['new'] = [];
            $type_goods['hot'] = [];
            $data = read_static_cache('recommend_goods');
            if ($data === false) {
                $sql = 'SELECT g.goods_id, g.is_best, g.is_new, g.is_hot, g.is_promote, b.brand_name,g.sort_order ' .
                    ' FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                    ' LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
                    ' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND (g.is_best = 1 OR g.is_new =1 OR g.is_hot = 1)' .
                    ' ORDER BY g.sort_order, g.last_update DESC';
                $goods_res = $GLOBALS['db']->getAll($sql);
                //定义推荐,最新，热门，促销商品
                $goods_data['best'] = [];
                $goods_data['new'] = [];
                $goods_data['hot'] = [];
                $goods_data['brand'] = [];
                if (!empty($goods_res)) {
                    foreach ($goods_res as $data) {
                        if ($data['is_best'] == 1) {
                            $goods_data['best'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                        }
                        if ($data['is_new'] == 1) {
                            $goods_data['new'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                        }
                        if ($data['is_hot'] == 1) {
                            $goods_data['hot'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                        }
                        if ($data['brand_name'] != '') {
                            $goods_data['brand'][$data['goods_id']] = $data['brand_name'];
                        }
                    }
                }
                write_static_cache('recommend_goods', $goods_data);
            } else {
                $goods_data = $data;
            }

            $time = gmtime();
            $order_type = $GLOBALS['_CFG']['recommend_order'];

            //按推荐数量及排序取每一项推荐显示的商品 order_type可以根据后台设定进行各种条件显示
            static $type_array = [];
            $type2lib = ['best' => 'recommend_best', 'new' => 'recommend_new', 'hot' => 'recommend_hot'];
            if (empty($type_array)) {
                foreach ($type2lib as $key => $data) {
                    if (!empty($goods_data[$key])) {
                        $num = get_library_number($data);
                        $data_count = count($goods_data[$key]);
                        $num = $data_count > $num ? $num : $data_count;
                        if ($order_type == 0) {
                            $rand_key = array_slice($goods_data[$key], 0, $num);
                            foreach ($rand_key as $key_data) {
                                $type_array[$key][] = $key_data['goods_id'];
                            }
                        } else {
                            $rand_key = array_rand($goods_data[$key], $num);
                            if ($num == 1) {
                                $type_array[$key][] = $goods_data[$key][$rand_key]['goods_id'];
                            } else {
                                foreach ($rand_key as $key_data) {
                                    $type_array[$key][] = $goods_data[$key][$key_data]['goods_id'];
                                }
                            }
                        }
                    } else {
                        $type_array[$key] = [];
                    }
                }
            }

            //取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
            $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " .
                'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' ";
            $type_merge = array_merge($type_array['new'], $type_array['best'], $type_array['hot']);
            $type_merge = array_unique($type_merge);
            $sql .= ' WHERE g.goods_id ' . db_create_in($type_merge);
            $sql .= ' ORDER BY g.sort_order, g.last_update DESC';

            $result = $GLOBALS['db']->getAll($sql);
            foreach ($result as $idx => $row) {
                if ($row['promote_price'] > 0) {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
                } else {
                    $goods[$idx]['promote_price'] = '';
                }

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['brand_name'] = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
                $goods[$idx]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);

                $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
                $goods[$idx]['market_price'] = price_format($row['market_price']);
                $goods[$idx]['shop_price'] = price_format($row['shop_price']);
                $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
                $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
                $goods[$idx]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                if (in_array($row['goods_id'], $type_array['best'])) {
                    $type_goods['best'][] = $goods[$idx];
                }
                if (in_array($row['goods_id'], $type_array['new'])) {
                    $type_goods['new'][] = $goods[$idx];
                }
                if (in_array($row['goods_id'], $type_array['hot'])) {
                    $type_goods['hot'][] = $goods[$idx];
                }
            }
        }
        return $type_goods[$type];
    }

    /**
     * 获得促销商品
     *
     * @access  public
     * @return  array
     */
    public function get_promote_goods($cats = '')
    {
        $time = gmtime();
        $order_type = $GLOBALS['_CFG']['recommend_order'];

        /* 取得促销lbi的数量限制 */
        $num = get_library_number("recommend_promotion");
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
            "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, " .
            "g.is_best, g.is_new, g.is_hot, g.is_promote, RAND() AS rnd " .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
        $sql .= $order_type == 0 ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY rnd';
        $sql .= " LIMIT $num ";
        $result = $GLOBALS['db']->getAll($sql);

        $goods = [];
        foreach ($result as $idx => $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['brand_name'] = $row['brand_name'];
            $goods[$idx]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
        }

        return $goods;
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @access  public
     * @param   string $type 推荐类型，可以是 best, new, hot, promote
     * @param   string $cats 分类的ID
     * @param   integer $brand 品牌的ID
     * @param   integer $min 商品价格下限
     * @param   integer $max 商品价格上限
     * @param   string $ext 商品扩展查询
     * @return  array
     */
    public function get_category_recommend_goods($type = '', $cats = '', $brand = 0, $min = 0, $max = 0, $ext = '')
    {
        $brand_where = ($brand > 0) ? " AND g.brand_id = '$brand'" : '';

        $price_where = ($min > 0) ? " AND g.shop_price >= $min " : '';
        $price_where .= ($max > 0) ? " AND g.shop_price <= $max " : '';

        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
            'promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' . $brand_where . $price_where . $ext;
        $num = 0;
        $type2lib = ['best' => 'recommend_best', 'new' => 'recommend_new', 'hot' => 'recommend_hot', 'promote' => 'recommend_promotion'];
        $num = get_library_number($type2lib[$type]);

        switch ($type) {
            case 'best':
                $sql .= ' AND is_best = 1';
                break;
            case 'new':
                $sql .= ' AND is_new = 1';
                break;
            case 'hot':
                $sql .= ' AND is_hot = 1';
                break;
            case 'promote':
                $time = gmtime();
                $sql .= " AND is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time'";
                break;
        }

        if (!empty($cats)) {
            $sql .= " AND (" . $cats . " OR " . get_extension_goods($cats) . ")";
        }

        $order_type = $GLOBALS['_CFG']['recommend_order'];
        $sql .= ($order_type == 0) ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY RAND()';
        $res = $GLOBALS['db']->selectLimit($sql, $num);

        $idx = 0;
        $goods = [];
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['brand_name'] = $row['brand_name'];
            $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
            $idx++;
        }

        return $goods;
    }

    /**
     * 获得商品的详细信息
     *
     * @access  public
     * @param   integer $goods_id
     * @return  void
     */
    public function get_goods_info($goods_id)
    {
        $time = gmtime();
        $sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, m.type_money AS bonus_money, ' .
            'IFNULL(AVG(r.comment_rank), 0) AS comment_rank, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS rank_price " .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS c ON g.cat_id = c.cat_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON g.brand_id = b.brand_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('comment') . ' AS r ' .
            'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('bonus_type') . ' AS m ' .
            "ON g.bonus_type_id = m.type_id AND m.send_start_date <= '$time' AND m.send_end_date >= '$time'" .
            " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            "WHERE g.goods_id = '$goods_id' AND g.is_delete = 0 " .
            "GROUP BY g.goods_id";
        $row = $GLOBALS['db']->getRow($sql);

        if ($row !== false) {
            /* 用户评论级别取整 */
            $row['comment_rank'] = ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);

            /* 获得商品的销售价格 */
            $row['market_price'] = price_format($row['market_price']);
            $row['shop_price_formated'] = price_format($row['shop_price']);

            /* 修正促销价格 */
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0) {
                $watermark_img = "watermark_promote";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }

            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            $row['promote_price_org'] = $promote_price;
            $row['promote_price'] = price_format($promote_price);

            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
                ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

            /* 修正上架时间显示 */
            $row['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

            /* 促销时间倒计时 */
            $time = gmtime();
            if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date']) {
                $row['gmt_end_time'] = $row['promote_end_date'];
            } else {
                $row['gmt_end_time'] = 0;
            }

            /* 是否显示商品库存数量 */
            $row['goods_number'] = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';

            /* 修正积分：转换为可使用多少积分（原来是可以使用多少钱的积分） */
            $row['integral'] = $GLOBALS['_CFG']['integral_scale'] ? round($row['integral'] * 100 / $GLOBALS['_CFG']['integral_scale']) : 0;

            /* 修正优惠券 */
            $row['bonus_money'] = ($row['bonus_money'] == 0) ? 0 : price_format($row['bonus_money'], false);

            /* 修正商品图片 */
            $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
            $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);

            return $row;
        } else {
            return false;
        }
    }

    /**
     * 获得商品的属性和规格
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    public function get_goods_properties($goods_id)
    {
        /* 对属性进行重新排序和分组 */
        $sql = "SELECT attr_group " .
            "FROM " . $GLOBALS['ecs']->table('goods_type') . " AS gt, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE g.goods_id='$goods_id' AND gt.cat_id=g.goods_type";
        $grp = $GLOBALS['db']->getOne($sql);

        if (!empty($grp)) {
            $groups = explode("\n", strtr($grp, "\r", ''));
        }

        /* 获得商品的规格 */
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_group, a.is_linked, a.attr_type, " .
            "g.goods_attr_id, g.attr_value, g.attr_price " .
            'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
            "WHERE g.goods_id = '$goods_id' " .
            'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';
        $res = $GLOBALS['db']->getAll($sql);

        $arr['pro'] = [];     // 属性
        $arr['spe'] = [];     // 规格
        $arr['lnk'] = [];     // 关联的属性

        foreach ($res as $row) {
            $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);

            if ($row['attr_type'] == 0) {
                $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : $GLOBALS['_LANG']['goods_attr'];

                $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
            } else {
                $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['spe'][$row['attr_id']]['values'][] = [
                    'label' => $row['attr_value'],
                    'price' => $row['attr_price'],
                    'format_price' => price_format(abs($row['attr_price']), false),
                    'id' => $row['goods_attr_id']];
            }

            if ($row['is_linked'] == 1) {
                /* 如果该属性需要关联，先保存下来 */
                $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
            }
        }

        return $arr;
    }

    /**
     * 获得属性相同的商品
     *
     * @access  public
     * @param   array $attr // 包含了属性名称,ID的数组
     * @return  array
     */
    public function get_same_attribute_goods($attr)
    {
        $lnk = [];

        if (!empty($attr)) {
            foreach ($attr['lnk'] as $key => $val) {
                $lnk[$key]['title'] = sprintf($GLOBALS['_LANG']['same_attrbiute_goods'], $val['name'], $val['value']);

                /* 查找符合条件的商品 */
                $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                    "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
                    'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
                    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_attr') . ' as a ON g.goods_id = a.goods_id ' .
                    "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
                    "WHERE a.attr_id = '$key' AND g.is_on_sale=1 AND a.attr_value = '$val[value]' AND g.goods_id <> '$_REQUEST[id]' " .
                    'LIMIT ' . $GLOBALS['_CFG']['attr_related_number'];
                $res = $GLOBALS['db']->getAll($sql);

                foreach ($res as $row) {
                    $lnk[$key]['goods'][$row['goods_id']]['goods_id'] = $row['goods_id'];
                    $lnk[$key]['goods'][$row['goods_id']]['goods_name'] = $row['goods_name'];
                    $lnk[$key]['goods'][$row['goods_id']]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                        sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                    $lnk[$key]['goods'][$row['goods_id']]['goods_thumb'] = (empty($row['goods_thumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
                    $lnk[$key]['goods'][$row['goods_id']]['market_price'] = price_format($row['market_price']);
                    $lnk[$key]['goods'][$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
                    $lnk[$key]['goods'][$row['goods_id']]['promote_price'] = bargain_price(
                        $row['promote_price'],
                        $row['promote_start_date'],
                        $row['promote_end_date']
                    );
                    $lnk[$key]['goods'][$row['goods_id']]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                }
            }
        }

        return $lnk;
    }

    /**
     * 获得指定商品的相册
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    public function get_goods_gallery($goods_id)
    {
        $sql = 'SELECT img_id, img_url, thumb_url, img_desc' .
            ' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
            " WHERE goods_id = '$goods_id' LIMIT " . $GLOBALS['_CFG']['goods_gallery_number'];
        $row = $GLOBALS['db']->getAll($sql);
        /* 格式化相册图片路径 */
        foreach ($row as $key => $gallery_img) {
            $row[$key]['img_url'] = get_image_path($goods_id, $gallery_img['img_url'], false, 'gallery');
            $row[$key]['thumb_url'] = get_image_path($goods_id, $gallery_img['thumb_url'], true, 'gallery');
        }
        return $row;
    }

    /**
     * 获得指定分类下的商品
     *
     * @access  public
     * @param   integer $cat_id 分类ID
     * @param   integer $num 数量
     * @param   string $from 来自web/wap的调用
     * @param   string $order_rule 指定商品排序规则
     * @return  array
     */
    public function assign_cat_goods($cat_id, $num = 0, $from = 'web', $order_rule = '')
    {
        $children = get_children($cat_id);

        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
            'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
            "FROM " . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ' .
            'g.is_delete = 0 AND (' . $children . 'OR ' . get_extension_goods($children) . ') ';

        $order_rule = empty($order_rule) ? 'ORDER BY g.sort_order, g.goods_id DESC' : $order_rule;
        $sql .= $order_rule;
        if ($num > 0) {
            $sql .= ' LIMIT ' . $num;
        }
        $res = $GLOBALS['db']->getAll($sql);

        $goods = [];
        foreach ($res as $idx => $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
        }

        if ($from == 'web') {
            $GLOBALS['smarty']->assign('cat_goods_' . $cat_id, $goods);
        } elseif ($from == 'wap') {
            $cat['goods'] = $goods;
        }

        /* 分类信息 */
        $sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
        $cat['name'] = $GLOBALS['db']->getOne($sql);
        $cat['url'] = build_uri('category', ['cid' => $cat_id], $cat['name']);
        $cat['id'] = $cat_id;

        return $cat;
    }

    /**
     * 获得指定的品牌下的商品
     *
     * @access  public
     * @param   integer $brand_id 品牌的ID
     * @param   integer $num 数量
     * @param   integer $cat_id 分类编号
     * @param   string $order_rule 指定商品排序规则
     * @return  void
     */
    public function assign_brand_goods($brand_id, $num = 0, $cat_id = 0, $order_rule = '')
    {
        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price, " .
            'g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.brand_id = '$brand_id'";

        if ($cat_id > 0) {
            $sql .= get_children($cat_id);
        }

        $order_rule = empty($order_rule) ? ' ORDER BY g.sort_order, g.goods_id DESC' : $order_rule;
        $sql .= $order_rule;
        if ($num > 0) {
            $res = $GLOBALS['db']->selectLimit($sql, $num);
        } else {
            $res = $GLOBALS['db']->query($sql);
        }

        $idx = 0;
        $goods = [];
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            $idx++;
        }

        /* 分类信息 */
        $sql = 'SELECT brand_name FROM ' . $GLOBALS['ecs']->table('brand') . " WHERE brand_id = '$brand_id'";

        $brand['id'] = $brand_id;
        $brand['name'] = $GLOBALS['db']->getOne($sql);
        $brand['url'] = build_uri('brand', ['bid' => $brand_id], $brand['name']);

        $brand_goods = ['brand' => $brand, 'goods' => $goods];

        return $brand_goods;
    }

    /**
     * 获得所有扩展分类属于指定分类的所有商品ID
     *
     * @access  public
     * @param   string $cat_id 分类查询字符串
     * @return  string
     */
    public function get_extension_goods($cats)
    {
        $extension_goods_array = '';
        $sql = 'SELECT goods_id FROM ' . $GLOBALS['ecs']->table('goods_cat') . " AS g WHERE $cats";
        $extension_goods_array = $GLOBALS['db']->getCol($sql);
        return db_create_in($extension_goods_array, 'g.goods_id');
    }

    /**
     * 获得指定的规格的价格
     *
     * @access  public
     * @param   mix $spec 规格ID的数组或者逗号分隔的字符串
     * @return  void
     */
    public function spec_price($spec)
    {
        if (!empty($spec)) {
            if (is_array($spec)) {
                foreach ($spec as $key => $val) {
                    $spec[$key] = addslashes($val);
                }
            } else {
                $spec = addslashes($spec);
            }

            $where = db_create_in($spec, 'goods_attr_id');

            $sql = 'SELECT SUM(attr_price) AS attr_price FROM ' . $GLOBALS['ecs']->table('goods_attr') . " WHERE $where";
            $price = floatval($GLOBALS['db']->getOne($sql));
        } else {
            $price = 0;
        }

        return $price;
    }

    /**
     * 取得商品信息
     * @param   int $goods_id 商品id
     * @return  array
     */
    public function goods_info($goods_id)
    {
        $sql = "SELECT g.*, b.brand_name " .
            "FROM " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE g.goods_id = '$goods_id'";
        $row = $GLOBALS['db']->getRow($sql);
        if (!empty($row)) {
            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
                ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

            /* 修正图片 */
            $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
        }

        return $row;
    }

    /**
     * 取得商品属性
     * @param   int $goods_id 商品id
     * @return  array
     */
    public function get_goods_attr($goods_id)
    {
        $attr_list = [];
        $sql = "SELECT a.attr_id, a.attr_name " .
            "FROM " . $GLOBALS['ecs']->table('goods') . " AS g, " . $GLOBALS['ecs']->table('attribute') . " AS a " .
            "WHERE g.goods_id = '$goods_id' " .
            "AND g.goods_type = a.cat_id " .
            "AND a.attr_type = 1";
        $attr_id_list = $GLOBALS['db']->getCol($sql);
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $attr) {
            if (defined('ECS_ADMIN')) {
                $attr['goods_attr_list'] = [0 => $GLOBALS['_LANG']['select_please']];
            } else {
                $attr['goods_attr_list'] = [];
            }
            $attr_list[$attr['attr_id']] = $attr;
        }

        $sql = "SELECT attr_id, goods_attr_id, attr_value " .
            "FROM " . $GLOBALS['ecs']->table('goods_attr') .
            " WHERE goods_id = '$goods_id' " .
            "AND attr_id " . db_create_in($attr_id_list);
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $goods_attr) {
            $attr_list[$goods_attr['attr_id']]['goods_attr_list'][$goods_attr['goods_attr_id']] = $goods_attr['attr_value'];
        }

        return $attr_list;
    }

    /**
     * 获得购物车中商品的配件
     *
     * @access  public
     * @param   array $goods_list
     * @return  array
     */
    public function get_goods_fittings($goods_list = [])
    {
        $temp_index = 0;
        $arr = [];

        $sql = 'SELECT gg.parent_id, ggg.goods_name AS parent_name, gg.goods_id, gg.goods_price, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price " .
            'FROM ' . $GLOBALS['ecs']->table('group_goods') . ' AS gg ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . 'AS g ON g.goods_id = gg.goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = gg.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS ggg ON ggg.goods_id = gg.parent_id " .
            "WHERE gg.parent_id " . db_create_in($goods_list) . " AND g.is_delete = 0 AND g.is_on_sale = 1 " .
            "ORDER BY gg.parent_id, gg.goods_id";

        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $arr[$temp_index]['parent_id'] = $row['parent_id'];//配件的基本件ID
            $arr[$temp_index]['parent_name'] = $row['parent_name'];//配件的基本件的名称
            $arr[$temp_index]['parent_short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['parent_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['parent_name'];//配件的基本件显示的名称
            $arr[$temp_index]['goods_id'] = $row['goods_id'];//配件的商品ID
            $arr[$temp_index]['goods_name'] = $row['goods_name'];//配件的名称
            $arr[$temp_index]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];//配件显示的名称
            $arr[$temp_index]['fittings_price'] = price_format($row['goods_price']);//配件价格
            $arr[$temp_index]['shop_price'] = price_format($row['shop_price']);//配件原价格
            $arr[$temp_index]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$temp_index]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$temp_index]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $temp_index++;
        }

        return $arr;
    }

    /**
     * 取指定规格的货品信息
     *
     * @access      public
     * @param       string $goods_id
     * @param       array $spec_goods_attr_id
     * @return      array
     */
    public function get_products_info($goods_id, $spec_goods_attr_id)
    {
        $return_array = [];

        if (empty($spec_goods_attr_id) || !is_array($spec_goods_attr_id) || empty($goods_id)) {
            return $return_array;
        }

        $goods_attr_array = sort_goods_attr_id_array($spec_goods_attr_id);

        if (isset($goods_attr_array['sort'])) {
            $goods_attr = implode('|', $goods_attr_array['sort']);

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('products') . " WHERE goods_id = '$goods_id' AND goods_attr = '$goods_attr' LIMIT 0, 1";
            $return_array = $GLOBALS['db']->getRow($sql);
        }
        return $return_array;
    }

    /**
     * 获得指定的商品属性
     *
     * @access      public
     * @param       array $arr 规格、属性ID数组
     * @param       type $type 设置返回结果类型：pice，显示价格，默认；no，不显示价格
     *
     * @return      string
     */
    public function get_goods_attr_info($arr, $type = 'pice')
    {
        $attr = '';

        if (!empty($arr)) {
            $fmt = "%s:%s[%s] \n";

            $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price " .
                "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS ga, " .
                $GLOBALS['ecs']->table('attribute') . " AS a " .
                "WHERE " . db_create_in($arr, 'ga.goods_attr_id') . " AND a.attr_id = ga.attr_id";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $attr_price = round(floatval($row['attr_price']), 2);
                $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
            }

            $attr = str_replace('[0]', '', $attr);
        }

        return $attr;
    }

    /**
     * 商品库存增与减 货品库存增与减
     *
     * @param   int $good_id 商品ID
     * @param   int $product_id 货品ID
     * @param   int $number 增减数量，默认0；
     *
     * @return  bool               true，成功；false，失败；
     */
    public function change_goods_storage($good_id, $product_id, $number = 0)
    {
        if ($number == 0) {
            return true; // 值为0即不做、增减操作，返回true
        }

        if (empty($good_id) || empty($number)) {
            return false;
        }

        $number = ($number > 0) ? '+ ' . $number : $number;

        /* 处理货品库存 */
        $products_query = true;
        if (!empty($product_id)) {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "
                SET product_number = product_number $number
                WHERE goods_id = '$good_id'
                AND product_id = '$product_id'
                LIMIT 1";
            $products_query = $GLOBALS['db']->query($sql);
        }

        /* 处理商品库存 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "
            SET goods_number = goods_number $number
            WHERE goods_id = '$good_id'
            LIMIT 1";
        $query = $GLOBALS['db']->query($sql);

        if ($query && $products_query) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查单个商品是否存在规格
     *
     * @param   int $goods_id 商品id
     * @return  bool                          true，存在；false，不存在
     */
    public function check_goods_specifications_exist($goods_id)
    {
        $goods_id = intval($goods_id);

        $sql = "SELECT COUNT(a.attr_id)
            FROM " . $GLOBALS['ecs']->table('attribute') . " AS a, " . $GLOBALS['ecs']->table('goods') . " AS g
            WHERE a.cat_id = g.goods_type
            AND g.goods_id = '$goods_id'";

        $count = $GLOBALS['db']->getOne($sql);

        if ($count > 0) {
            return true;    //存在
        } else {
            return false;    //不存在
        }
    }

    /**
     * 获得商品的规格属性值列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    public function product_goods_attr_list($goods_id)
    {
        if (empty($goods_id)) {
            return [];  //$goods_id不能为空
        }

        $sql = "SELECT goods_attr_id, attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id = '$goods_id'";
        $results = $GLOBALS['db']->getAll($sql);

        $return_arr = [];
        foreach ($results as $value) {
            $return_arr[$value['goods_attr_id']] = $value['attr_value'];
        }

        return $return_arr;
    }

    /**
     * 获得商品已添加的规格列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    public function get_goods_specifications_list($goods_id)
    {
        if (empty($goods_id)) {
            return [];  //$goods_id不能为空
        }

        $sql = "SELECT g.goods_attr_id, g.attr_value, g.attr_id, a.attr_name
            FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g
                LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a
                    ON a.attr_id = g.attr_id
            WHERE goods_id = '$goods_id'
            AND a.attr_type = 1
            ORDER BY g.attr_id ASC";
        $results = $GLOBALS['db']->getAll($sql);

        return $results;
    }

    /**
     * 获得商品列表
     *
     * @access  public
     * @params  integer $isdelete
     * @params  integer $real_goods
     * @params  integer $conditions
     * @return  array
     */
    public function goods_list($is_delete, $real_goods = 1, $conditions = '')
    {
        /* 过滤条件 */
        $param_str = '-' . $is_delete . '-' . $real_goods;
        $result = get_filter($param_str);
        if ($result === false) {
            $day = getdate();
            $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

            $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $filter['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
            $filter['is_promote'] = empty($_REQUEST['is_promote']) ? 0 : intval($_REQUEST['is_promote']);
            $filter['stock_warning'] = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);
            $filter['brand_id'] = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) ? (empty($_REQUEST['suppliers_id']) ? '' : trim($_REQUEST['suppliers_id'])) : '';
            $filter['is_on_sale'] = isset($_REQUEST['is_on_sale']) ? ((empty($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] === 0) ? '' : trim($_REQUEST['is_on_sale'])) : '';
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $filter['is_delete'] = $is_delete;
            $filter['real_goods'] = $real_goods;

            $where = $filter['cat_id'] > 0 ? " AND " . get_children($filter['cat_id']) : '';

            /* 推荐类型 */
            switch ($filter['intro_type']) {
                case 'is_best':
                    $where .= " AND is_best=1";
                    break;
                case 'is_hot':
                    $where .= ' AND is_hot=1';
                    break;
                case 'is_new':
                    $where .= ' AND is_new=1';
                    break;
                case 'is_promote':
                    $where .= " AND is_promote = 1 AND promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today'";
                    break;
                case 'all_type':
                    $where .= " AND (is_best=1 OR is_hot=1 OR is_new=1 OR (is_promote = 1 AND promote_price > 0 AND promote_start_date <= '" . $today . "' AND promote_end_date >= '" . $today . "'))";
            }

            /* 库存警告 */
            if ($filter['stock_warning']) {
                $where .= ' AND goods_number <= warn_number ';
            }

            /* 品牌 */
            if ($filter['brand_id']) {
                $where .= " AND brand_id='$filter[brand_id]'";
            }

            /* 扩展 */
            if ($filter['extension_code']) {
                $where .= " AND extension_code='$filter[extension_code]'";
            }

            /* 关键字 */
            if (!empty($filter['keyword'])) {
                $where .= " AND (goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' OR goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
            }

            if ($real_goods > -1) {
                $where .= " AND is_real='$real_goods'";
            }

            /* 上架 */
            if ($filter['is_on_sale'] !== '') {
                $where .= " AND (is_on_sale = '" . $filter['is_on_sale'] . "')";
            }

            /* 供货商 */
            if (!empty($filter['suppliers_id'])) {
                $where .= " AND (suppliers_id = '" . $filter['suppliers_id'] . "')";
            }

            $where .= $conditions;

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE is_delete='$is_delete' $where";
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT goods_id, goods_name, goods_type, goods_sn, shop_price, is_on_sale, is_best, is_new, is_hot, sort_order, goods_number, integral, " .
                " (promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today') AS is_promote " .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE is_delete='$is_delete' $where" .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql, $param_str);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $GLOBALS['db']->getAll($sql);

        return ['goods' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得指定商品相关的商品
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    public function get_linked_goods($goods_id)
    {
        $sql = "SELECT lg.link_goods_id AS goods_id, g.goods_name, lg.is_double " .
            "FROM " . $GLOBALS['ecs']->table('link_goods') . " AS lg, " .
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE lg.goods_id = '$goods_id' " .
            "AND lg.link_goods_id = g.goods_id ";
        if ($goods_id == 0) {
            $sql .= " AND lg.admin_id = '" . session('admin_id') . "'";
        }
        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $linked_type = $val['is_double'] == 0 ? $GLOBALS['_LANG']['single'] : $GLOBALS['_LANG']['double'];

            $row[$key]['goods_name'] = $val['goods_name'] . " -- [$linked_type]";

            unset($row[$key]['is_double']);
        }

        return $row;
    }

    /**
     * 获得指定商品的配件
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    public function get_group_goods($goods_id)
    {
        $sql = "SELECT gg.goods_id, CONCAT(g.goods_name, ' -- [', gg.goods_price, ']') AS goods_name " .
            "FROM " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " .
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE gg.parent_id = '$goods_id' " .
            "AND gg.goods_id = g.goods_id ";
        if ($goods_id == 0) {
            $sql .= " AND gg.admin_id = '" . session('admin_id') . "'";
        }
        $row = $GLOBALS['db']->getAll($sql);

        return $row;
    }

    /**
     * 获得商品的关联文章
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    public function get_goods_articles($goods_id)
    {
        $sql = "SELECT g.article_id, a.title " .
            "FROM " . $GLOBALS['ecs']->table('goods_article') . " AS g, " .
            $GLOBALS['ecs']->table('article') . " AS a " .
            "WHERE g.goods_id = '$goods_id' " .
            "AND g.article_id = a.article_id ";
        if ($goods_id == 0) {
            $sql .= " AND g.admin_id = '" . session('admin_id') . "'";
        }
        $row = $GLOBALS['db']->getAll($sql);

        return $row;
    }

    /**
     * 取得某商品的会员价格列表
     * @param   int $goods_id 商品编号
     * @return  array   会员价格列表 user_rank => user_price
     */
    public function get_member_price_list($goods_id)
    {
        /* 取得会员价格 */
        $price_list = [];
        $sql = "SELECT user_rank, user_price FROM " .
            $GLOBALS['ecs']->table('member_price') .
            " WHERE goods_id = '$goods_id'";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $price_list[$row['user_rank']] = $row['user_price'];
        }

        return $price_list;
    }

    /**
     * 插入或更新商品属性
     *
     * @param   int $goods_id 商品编号
     * @param   array $id_list 属性编号数组
     * @param   array $is_spec_list 是否规格数组 'true' | 'false'
     * @param   array $value_price_list 属性值数组
     * @return  array                       返回受到影响的goods_attr_id数组
     */
    public function handle_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list)
    {
        $goods_attr_id = [];

        /* 循环处理每个属性 */
        foreach ($id_list as $key => $id) {
            $is_spec = $is_spec_list[$key];
            if ($is_spec == 'false') {
                $value = $value_price_list[$key];
                $price = '';
            } else {
                $value_list = [];
                $price_list = [];
                if ($value_price_list[$key]) {
                    $vp_list = explode(chr(13), $value_price_list[$key]);
                    foreach ($vp_list as $v_p) {
                        $arr = explode(chr(9), $v_p);
                        $value_list[] = $arr[0];
                        $price_list[] = $arr[1];
                    }
                }
                $value = join(chr(13), $value_list);
                $price = join(chr(13), $price_list);
            }

            // 插入或更新记录
            $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$id' AND attr_value = '$value' LIMIT 0, 1";
            $result_id = $GLOBALS['db']->getOne($sql);
            if (!empty($result_id)) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_attr') . "
                    SET attr_value = '$value'
                    WHERE goods_id = '$goods_id'
                    AND attr_id = '$id'
                    AND goods_attr_id = '$result_id'";

                $goods_attr_id[$id] = $result_id;
            } else {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_attr') . " (goods_id, attr_id, attr_value, attr_price) " .
                    "VALUES ('$goods_id', '$id', '$value', '$price')";
            }

            $GLOBALS['db']->query($sql);

            if ($goods_attr_id[$id] == '') {
                $goods_attr_id[$id] = $GLOBALS['db']->insert_id();
            }
        }

        return $goods_attr_id;
    }

    /**
     * 保存某商品的会员价格
     * @param   int $goods_id 商品编号
     * @param   array $rank_list 等级列表
     * @param   array $price_list 价格列表
     * @return  void
     */
    public function handle_member_price($goods_id, $rank_list, $price_list)
    {
        /* 循环处理每个会员等级 */
        foreach ($rank_list as $key => $rank) {
            /* 会员等级对应的价格 */
            $price = $price_list[$key];

            // 插入或更新记录
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('member_price') .
                " WHERE goods_id = '$goods_id' AND user_rank = '$rank'";
            if ($GLOBALS['db']->getOne($sql) > 0) {
                /* 如果会员价格是小于0则删除原来价格，不是则更新为新的价格 */
                if ($price < 0) {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('member_price') .
                        " WHERE goods_id = '$goods_id' AND user_rank = '$rank' LIMIT 1";
                } else {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('member_price') .
                        " SET user_price = '$price' " .
                        "WHERE goods_id = '$goods_id' " .
                        "AND user_rank = '$rank' LIMIT 1";
                }
            } else {
                if ($price == -1) {
                    $sql = '';
                } else {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('member_price') . " (goods_id, user_rank, user_price) " .
                        "VALUES ('$goods_id', '$rank', '$price')";
                }
            }

            if ($sql) {
                $GLOBALS['db']->query($sql);
            }
        }
    }

    /**
     * 保存某商品的扩展分类
     * @param   int $goods_id 商品编号
     * @param   array $cat_list 分类编号数组
     * @return  void
     */
    public function handle_other_cat($goods_id, $cat_list)
    {
        /* 查询现有的扩展分类 */
        $sql = "SELECT cat_id FROM " . $GLOBALS['ecs']->table('goods_cat') .
            " WHERE goods_id = '$goods_id'";
        $exist_list = $GLOBALS['db']->getCol($sql);

        /* 删除不再有的分类 */
        $delete_list = array_diff($exist_list, $cat_list);
        if ($delete_list) {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_cat') .
                " WHERE goods_id = '$goods_id' " .
                "AND cat_id " . db_create_in($delete_list);
            $GLOBALS['db']->query($sql);
        }

        /* 添加新加的分类 */
        $add_list = array_diff($cat_list, $exist_list, [0]);
        foreach ($add_list as $cat_id) {
            // 插入记录
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_cat') .
                " (goods_id, cat_id) " .
                "VALUES ('$goods_id', '$cat_id')";
            $GLOBALS['db']->query($sql);
        }
    }

    /**
     * 保存某商品的关联商品
     * @param   int $goods_id
     * @return  void
     */
    public function handle_link_goods($goods_id)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('link_goods') . " SET " .
            " goods_id = '$goods_id' " .
            " WHERE goods_id = '0'" .
            " AND admin_id = '" . session('admin_id') . "'";
        $GLOBALS['db']->query($sql);

        $sql = "UPDATE " . $GLOBALS['ecs']->table('link_goods') . " SET " .
            " link_goods_id = '$goods_id' " .
            " WHERE link_goods_id = '0'" .
            " AND admin_id = '" . session('admin_id') . "'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 保存某商品的配件
     * @param   int $goods_id
     * @return  void
     */
    public function handle_group_goods($goods_id)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('group_goods') . " SET " .
            " parent_id = '$goods_id' " .
            " WHERE parent_id = '0'" .
            " AND admin_id = '" . session('admin_id') . "'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 保存某商品的关联文章
     * @param   int $goods_id
     * @return  void
     */
    public function handle_goods_article($goods_id)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_article') . " SET " .
            " goods_id = '$goods_id' " .
            " WHERE goods_id = '0'" .
            " AND admin_id = '" . session('admin_id') . "'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 保存某商品的相册图片
     * @param   int $goods_id
     * @param   array $image_files
     * @param   array $image_descs
     * @return  void
     */
    public function handle_gallery_image($goods_id, $image_files, $image_descs, $image_urls)
    {
        /* 是否处理缩略图 */
        $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
        foreach ($image_descs as $key => $img_desc) {
            /* 是否成功上传 */
            $flag = false;
            if (isset($image_files['error'])) {
                if ($image_files['error'][$key] == 0) {
                    $flag = true;
                }
            } else {
                if ($image_files['tmp_name'][$key] != 'none') {
                    $flag = true;
                }
            }

            if ($flag) {
                // 生成缩略图
                if ($proc_thumb) {
                    $thumb_url = $GLOBALS['image']->make_thumb($image_files['tmp_name'][$key], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                    $thumb_url = is_string($thumb_url) ? $thumb_url : '';
                }

                $upload = [
                    'name' => $image_files['name'][$key],
                    'type' => $image_files['type'][$key],
                    'tmp_name' => $image_files['tmp_name'][$key],
                    'size' => $image_files['size'][$key],
                ];
                if (isset($image_files['error'])) {
                    $upload['error'] = $image_files['error'][$key];
                }
                $img_original = $GLOBALS['image']->upload_image($upload);
                if ($img_original === false) {
                    sys_msg($GLOBALS['image']->error_msg(), 1, [], false);
                }
                $img_url = $img_original;

                if (!$proc_thumb) {
                    $thumb_url = $img_original;
                }
                // 如果服务器支持GD 则添加水印
                if ($proc_thumb && gd_version() > 0) {
                    $pos = strpos(basename($img_original), '.');
                    $newname = dirname($img_original) . '/' . $GLOBALS['image']->random_filename() . substr(basename($img_original), $pos);
                    copy('../' . $img_original, '../' . $newname);
                    $img_url = $newname;

                    $GLOBALS['image']->add_watermark('../' . $img_url, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
                }

                /* 重新格式化图片名称 */
                $img_original = reformat_image_name('gallery', $goods_id, $img_original, 'source');
                $img_url = reformat_image_name('gallery', $goods_id, $img_url, 'goods');
                $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
                $GLOBALS['db']->query($sql);
                /* 不保留商品原图的时候删除原图 */
                if ($proc_thumb && !$GLOBALS['_CFG']['retain_original_img'] && !empty($img_original)) {
                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods_gallery') . " SET img_original='' WHERE `goods_id`='{$goods_id}'");
                    @unlink('../' . $img_original);
                }
            } elseif (!empty($image_urls[$key]) && ($image_urls[$key] != $GLOBALS['_LANG']['img_file']) && ($image_urls[$key] != 'http://') && copy(trim($image_urls[$key]), ROOT_PATH . 'temp/' . basename($image_urls[$key]))) {
                $image_url = trim($image_urls[$key]);

                //定义原图路径
                $down_img = ROOT_PATH . 'temp/' . basename($image_url);

                // 生成缩略图
                if ($proc_thumb) {
                    $thumb_url = $GLOBALS['image']->make_thumb($down_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                    $thumb_url = is_string($thumb_url) ? $thumb_url : '';
                    $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                }

                if (!$proc_thumb) {
                    $thumb_url = htmlspecialchars($image_url);
                }

                /* 重新格式化图片名称 */
                $img_url = $img_original = htmlspecialchars($image_url);
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
                $GLOBALS['db']->query($sql);

                @unlink($down_img);
            }
        }
    }

    /**
     * 修改商品某字段值
     * @param   string $goods_id 商品编号，可以为多个，用 ',' 隔开
     * @param   string $field 字段名
     * @param   string $value 字段值
     * @return  bool
     */
    public function update_goods($goods_id, $field, $value)
    {
        if ($goods_id) {
            /* 清除缓存 */
            clear_cache_files();

            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') .
                " SET $field = '$value' , last_update = '" . gmtime() . "' " .
                "WHERE goods_id " . db_create_in($goods_id);
            return $GLOBALS['db']->query($sql);
        } else {
            return false;
        }
    }

    /**
     * 从回收站删除多个商品
     * @param   mix $goods_id 商品id列表：可以逗号格开，也可以是数组
     * @return  void
     */
    public function delete_goods($goods_id)
    {
        if (empty($goods_id)) {
            return;
        }

        /* 取得有效商品id */
        $sql = "SELECT DISTINCT goods_id FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id " . db_create_in($goods_id) . " AND is_delete = 1";
        $goods_id = $GLOBALS['db']->getCol($sql);
        if (empty($goods_id)) {
            return;
        }

        /* 删除商品图片和轮播图片文件 */
        $sql = "SELECT goods_thumb, goods_img, original_img " .
            "FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id " . db_create_in($goods_id);
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $goods) {
            if (!empty($goods['goods_thumb'])) {
                @unlink('../' . $goods['goods_thumb']);
            }
            if (!empty($goods['goods_img'])) {
                @unlink('../' . $goods['goods_img']);
            }
            if (!empty($goods['original_img'])) {
                @unlink('../' . $goods['original_img']);
            }
        }

        /* 删除商品 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);

        /* 删除商品的货品记录 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('products') .
            " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);

        /* 删除商品相册的图片文件 */
        $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . $GLOBALS['ecs']->table('goods_gallery') .
            " WHERE goods_id " . db_create_in($goods_id);
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            if (!empty($row['img_url'])) {
                @unlink('../' . $row['img_url']);
            }
            if (!empty($row['thumb_url'])) {
                @unlink('../' . $row['thumb_url']);
            }
            if (!empty($row['img_original'])) {
                @unlink('../' . $row['img_original']);
            }
        }

        /* 删除商品相册 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_gallery') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);

        /* 删除相关表记录 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('collect_goods') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_cat') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('member_price') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('group_goods') . " WHERE parent_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('link_goods') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('link_goods') . " WHERE link_goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('tag') . " WHERE goods_id " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_type = 0 AND id_value " . db_create_in($goods_id);
        $GLOBALS['db']->query($sql);

        /* 删除相应虚拟商品记录 */
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('virtual_card') . " WHERE goods_id " . db_create_in($goods_id);
        if (!$GLOBALS['db']->query($sql, 'SILENT') && $GLOBALS['db']->errno() != 1146) {
            abort(503, $GLOBALS['db']->error());
        }

        /* 清除缓存 */
        clear_cache_files();
    }

    /**
     * 为某商品生成唯一的货号
     * @param   int $goods_id 商品编号
     * @return  string  唯一的货号
     */
    public function generate_goods_sn($goods_id)
    {
        $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

        $sql = "SELECT goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
        $sn_list = $GLOBALS['db']->getCol($sql);
        if (in_array($goods_sn, $sn_list)) {
            $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
            $new_sn = $goods_sn . mt_rand(0, $max);
            while (in_array($new_sn, $sn_list)) {
                $new_sn = $goods_sn . mt_rand(0, $max);
            }
            $goods_sn = $new_sn;
        }

        return $goods_sn;
    }

    /**
     * 商品货号是否重复
     *
     * @param   string $goods_sn 商品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param   int $goods_id 商品id；默认值为：0，没有商品id
     * @return  bool                        true，重复；false，不重复
     */
    public function check_goods_sn_exist($goods_sn, $goods_id = 0)
    {
        $goods_sn = trim($goods_sn);
        $goods_id = intval($goods_id);
        if (strlen($goods_sn) == 0) {
            return true;    //重复
        }

        if (empty($goods_id)) {
            $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . "
                WHERE goods_sn = '$goods_sn'";
        } else {
            $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . "
                WHERE goods_sn = '$goods_sn'
                AND goods_id <> '$goods_id'";
        }

        $res = $GLOBALS['db']->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 取得通用属性和某分类的属性，以及某商品的属性值
     * @param   int $cat_id 分类编号
     * @param   int $goods_id 商品编号
     * @return  array   规格与属性列表
     */
    public function get_attr_list($cat_id, $goods_id = 0)
    {
        if (empty($cat_id)) {
            return [];
        }

        // 查询属性值及商品的属性值
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values, v.attr_value, v.attr_price " .
            "FROM " . $GLOBALS['ecs']->table('attribute') . " AS a " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS v " .
            "ON v.attr_id = a.attr_id AND v.goods_id = '$goods_id' " .
            "WHERE a.cat_id = " . intval($cat_id) . " OR a.cat_id = 0 " .
            "ORDER BY a.sort_order, a.attr_type, a.attr_id, v.attr_price, v.goods_attr_id";

        $row = $GLOBALS['db']->getAll($sql);

        return $row;
    }

    /**
     * 获取商品类型中包含规格的类型列表
     *
     * @access  public
     * @return  array
     */
    public function get_goods_type_specifications()
    {
        // 查询
        $sql = "SELECT DISTINCT cat_id
            FROM " . $GLOBALS['ecs']->table('attribute') . "
            WHERE attr_type = 1";
        $row = $GLOBALS['db']->getAll($sql);

        $return_arr = [];
        if (!empty($row)) {
            foreach ($row as $value) {
                $return_arr[$value['cat_id']] = $value['cat_id'];
            }
        }
        return $return_arr;
    }

    /**
     * 取得商品列表：用于把商品添加到组合、关联类、赠品类
     * @param   object $filters 过滤条件
     */
    public function get_goods_list($filter)
    {
        $filter->keyword = json_str_iconv($filter->keyword);
        $where = get_where_sql($filter); // 取得过滤条件

        /* 取得数据 */
        $sql = 'SELECT goods_id, goods_name, shop_price ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . $where .
            'LIMIT 50';
        $row = $GLOBALS['db']->getAll($sql);

        return $row;
    }

    /**
     * 生成过滤条件：用于 get_goodslist 和 get_goods_list
     * @param   object $filter
     * @return  string
     */
    public function get_where_sql($filter)
    {
        $time = date('Y-m-d');

        $where = isset($filter->is_delete) && $filter->is_delete == '1' ?
            ' WHERE is_delete = 1 ' : ' WHERE is_delete = 0 ';
        $where .= (isset($filter->real_goods) && ($filter->real_goods > -1)) ? ' AND is_real = ' . intval($filter->real_goods) : '';
        $where .= isset($filter->cat_id) && $filter->cat_id > 0 ? ' AND ' . get_children($filter->cat_id) : '';
        $where .= isset($filter->brand_id) && $filter->brand_id > 0 ? " AND brand_id = '" . $filter->brand_id . "'" : '';
        $where .= isset($filter->intro_type) && $filter->intro_type != '0' ? ' AND ' . $filter->intro_type . " = '1'" : '';
        $where .= isset($filter->intro_type) && $filter->intro_type == 'is_promote' ?
            " AND promote_start_date <= '$time' AND promote_end_date >= '$time' " : '';
        $where .= isset($filter->keyword) && trim($filter->keyword) != '' ?
            " AND (goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_id LIKE '%" . mysql_like_quote($filter->keyword) . "%') " : '';
        $where .= isset($filter->suppliers_id) && trim($filter->suppliers_id) != '' ?
            " AND (suppliers_id = '" . $filter->suppliers_id . "') " : '';

        $where .= isset($filter->in_ids) ? ' AND goods_id ' . db_create_in($filter->in_ids) : '';
        $where .= isset($filter->exclude) ? ' AND goods_id NOT ' . db_create_in($filter->exclude) : '';
        $where .= isset($filter->stock_warning) ? ' AND goods_number <= warn_number' : '';

        return $where;
    }

    /**
     * 获得商品类型的列表
     *
     * @access  public
     * @param   integer $selected 选定的类型编号
     * @return  string
     */
    public function goods_type_list($selected)
    {
        $sql = 'SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('goods_type') . ' WHERE enabled = 1';
        $res = $GLOBALS['db']->query($sql);

        $lst = '';
        foreach ($res as $row) {
            $lst .= "<option value='$row[cat_id]'";
            $lst .= ($selected == $row['cat_id']) ? ' selected="true"' : '';
            $lst .= '>' . htmlspecialchars($row['cat_name']) . '</option>';
        }

        return $lst;
    }

    /**
     * 获得指定的商品类型下所有的属性分组
     *
     * @param   integer $cat_id 商品类型ID
     *
     * @return  array
     */
    public function get_attr_groups($cat_id)
    {
        $sql = "SELECT attr_group FROM " . $GLOBALS['ecs']->table('goods_type') . " WHERE cat_id='$cat_id'";
        $grp = str_replace("\r", '', $GLOBALS['db']->getOne($sql));

        if ($grp) {
            return explode("\n", $grp);
        } else {
            return [];
        }
    }

    /**
     * 取得商品优惠价格列表
     *
     * @param   string $goods_id 商品编号
     * @param   string $price_type 价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    public function get_volume_price_list($goods_id, $price_type = '1')
    {
        $volume_price = [];
        $temp_index = '0';

        $sql = "SELECT `volume_number` , `volume_price`" .
            " FROM " . $GLOBALS['ecs']->table('volume_price') . "" .
            " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'" .
            " ORDER BY `volume_number`";

        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res as $k => $v) {
            $volume_price[$temp_index] = [];
            $volume_price[$temp_index]['number'] = $v['volume_number'];
            $volume_price[$temp_index]['price'] = $v['volume_price'];
            $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
            $temp_index++;
        }
        return $volume_price;
    }

    /**
     * 取得商品最终使用价格
     *
     * @param   string $goods_id 商品编号
     * @param   string $goods_num 购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix $spec 规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = [])
    {
        $final_price = '0'; //商品最终购买价格
        $volume_price = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price = '0'; //商品会员价格

        //取得商品优惠价格列表
        $price_list = get_volume_price_list($goods_id, '1');

        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        //取得商品促销价格列表
        /* 取得商品信息 */
        $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, " .
            "IFNULL(mp.user_price, g.shop_price * '" . session('discount') . "') AS shop_price " .
            " FROM " . $GLOBALS['ecs']->table('goods') . " AS g " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . session('user_rank') . "' " .
            " WHERE g.goods_id = '" . $goods_id . "'" .
            " AND g.is_delete = 0";
        $goods = $GLOBALS['db']->getRow($sql);

        /* 计算商品的促销价格 */
        if ($goods['promote_price'] > 0) {
            $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        } else {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price)) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && empty($promote_price)) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && !empty($promote_price)) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && !empty($promote_price)) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($spec)) {
                $spec_price = spec_price($spec);
                $final_price += $spec_price;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 将 goods_attr_id 的序列按照 attr_id 重新排序
     *
     * 注意：非规格属性的id会被排除
     *
     * @access      public
     * @param       array $goods_attr_id_array 一维数组
     * @param       string $sort 序号：asc|desc，默认为：asc
     *
     * @return      string
     */
    public function sort_goods_attr_id_array($goods_attr_id_array, $sort = 'asc')
    {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        //重新排序
        $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " . $GLOBALS['ecs']->table('attribute') . " AS a
            LEFT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
        $row = $GLOBALS['db']->getAll($sql);

        $return_arr = [];
        foreach ($row as $value) {
            $return_arr['sort'][] = $value['goods_attr_id'];

            $return_arr['row'][$value['goods_attr_id']] = $value;
        }

        return $return_arr;
    }

    /**
     *
     * 是否存在规格
     *
     * @access      public
     * @param       array $goods_attr_id_array 一维数组
     *
     * @return      string
     */
    public function is_spec($goods_attr_id_array, $sort = 'asc')
    {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        //重新排序
        $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " . $GLOBALS['ecs']->table('attribute') . " AS a
            LEFT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
        $row = $GLOBALS['db']->getAll($sql);

        $return_arr = [];
        foreach ($row as $value) {
            $return_arr['sort'][] = $value['goods_attr_id'];

            $return_arr['row'][$value['goods_attr_id']] = $value;
        }

        if (!empty($return_arr)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取商品的规格列表
     *
     * @param       int $goods_id 商品id
     * @param       string $conditions sql条件
     *
     * @return  array
     */
    public function get_specifications_list($goods_id, $conditions = '')
    {
        /* 取商品属性 */
        $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value, a.attr_name
            FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS ga, " . $GLOBALS['ecs']->table('attribute') . " AS a
            WHERE ga.attr_id = a.attr_id
            AND ga.goods_id = '$goods_id'
            $conditions";
        $result = $GLOBALS['db']->getAll($sql);

        $return_array = [];
        foreach ($result as $value) {
            $return_array[$value['goods_attr_id']] = $value;
        }

        return $return_array;
    }
}
