<?php

namespace app\services;

/**
 * Class RegionService
 * @package app\services
 */
class RegionService
{

/**
 * 查询配送区域属于哪个办事处管辖
 * @param   array $regions 配送区域（1、2、3、4级按顺序）
 * @return  int     办事处id，可能为0
 */
    public function get_agency_by_regions($regions)
    {
        if (!is_array($regions) || empty($regions)) {
            return 0;
        }

        $arr = [];
        $sql = "SELECT region_id, agency_id " .
        "FROM " . $GLOBALS['ecs']->table('region') .
        " WHERE region_id " . db_create_in($regions) .
        " AND region_id > 0 AND agency_id > 0";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $arr[$row['region_id']] = $row['agency_id'];
        }
        if (empty($arr)) {
            return 0;
        }

        $agency_id = 0;
        for ($i = count($regions) - 1; $i >= 0; $i--) {
            if (isset($arr[$regions[$i]])) {
                return $arr[$regions[$i]];
            }
        }
    }

    /**
     * 获取地区列表的函数。
     *
     * @access  public
     * @param   int $region_id 上级地区id
     * @return  void
     */
    public function area_list($region_id)
    {
        $area_arr = [];

        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('region') .
        " WHERE parent_id = '$region_id' ORDER BY region_id";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $row['type'] = ($row['region_type'] == 0) ? $GLOBALS['_LANG']['country'] : '';
            $row['type'] .= ($row['region_type'] == 1) ? $GLOBALS['_LANG']['province'] : '';
            $row['type'] .= ($row['region_type'] == 2) ? $GLOBALS['_LANG']['city'] : '';
            $row['type'] .= ($row['region_type'] == 3) ? $GLOBALS['_LANG']['cantonal'] : '';

            $area_arr[] = $row;
        }

        return $area_arr;
    }

    /**
     * 获得指定国家的所有省份
     *
     * @access      public
     * @param       int     country    国家的编号
     * @return      array
     */
    public function get_regions($type = 0, $parent = 0)
    {
        $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['ecs']->table('region') .
        " WHERE region_type = '$type' AND parent_id = '$parent'";

        return $GLOBALS['db']->getAll($sql);
    }
}
