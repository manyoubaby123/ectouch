<?php

namespace app\services;

/**
 * Class AdService
 * @package app\services
 */
class AdService
{
    /**
     * 取得广告位置数组（用于生成下拉列表）
     *
     * @return  array       分类数组 position_id => position_name
     */
    public function get_position_list()
    {
        $position_list = [];
        $sql = 'SELECT position_id, position_name, ad_width, ad_height ' .
            'FROM ' . $GLOBALS['ecs']->table('ad_position');
        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $position_list[$row['position_id']] = addslashes($row['position_name']) . ' [' . $row['ad_width'] . 'x' . $row['ad_height'] . ']';
        }

        return $position_list;
    }
}
