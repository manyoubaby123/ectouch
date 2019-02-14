<?php

namespace app\services;

/**
 * Class BrandService
 * @package app\services
 */
class BrandService
{
    public function brand_exists($brand_name)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('brand') .
            " WHERE brand_name = '" . $brand_name . "'";
        return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
    }

    /**
     * 取得品牌列表
     * @return array 品牌列表 id => name
     */
    public function get_brand_list()
    {
        $sql = 'SELECT brand_id, brand_name FROM ' . $GLOBALS['ecs']->table('brand') . ' ORDER BY sort_order';
        $res = $GLOBALS['db']->getAll($sql);

        $brand_list = [];
        foreach ($res as $row) {
            $brand_list[$row['brand_id']] = addslashes($row['brand_name']);
        }

        return $brand_list;
    }

    /**
     * 获得某个分类下
     *
     * @access  public
     * @param   int $cat
     * @return  array
     */
    public function get_brands($cat = 0, $app = 'brand')
    {
        global $page_libs;

        $template = basename(PHP_SELF);
        $template = substr($template, 0, strrpos($template, '.'));
        load_helper('template', 'admin');
        static $static_page_libs = null;
        if ($static_page_libs == null) {
            $static_page_libs = $page_libs;
        }

        $children = ($cat > 0) ? ' AND ' . get_children($cat) : '';

        $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, b.brand_desc, COUNT(*) AS goods_num, IF(b.brand_logo > '', '1', '0') AS tag " .
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, " .
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE g.brand_id = b.brand_id $children AND is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY tag DESC, b.sort_order ASC";
        if (isset($static_page_libs[$template]['/library/brands.lbi'])) {
            $num = get_library_number("brands");
            $sql .= " LIMIT $num ";
        }
        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['url'] = build_uri($app, ['cid' => $cat, 'bid' => $val['brand_id']], $val['brand_name']);
            $row[$key]['brand_desc'] = htmlspecialchars($val['brand_desc'], ENT_QUOTES);
        }

        return $row;
    }
}
