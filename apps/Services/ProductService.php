<?php

namespace App\Services;

/**
 * Class ProductService
 * @package App\Services
 */
class ProductService
{

/**
 * 获得商品的货品列表
 *
 * @access  public
 * @params  integer $goods_id
 * @params  string  $conditions
 * @return  array
 */
    public function product_list($goods_id, $conditions = '')
    {
        /* 过滤条件 */
        $param_str = '-' . $goods_id;
        $result = get_filter($param_str);
        if ($result === false) {
            $day = getdate();
            $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

            $filter['goods_id'] = $goods_id;
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $filter['stock_warning'] = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);

            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
            $filter['page_count'] = isset($filter['page_count']) ? $filter['page_count'] : 1;

            $where = '';

            /* 库存警告 */
            if ($filter['stock_warning']) {
                $where .= ' AND goods_number <= warn_number ';
            }

            /* 关键字 */
            if (!empty($filter['keyword'])) {
                $where .= " AND (product_sn LIKE '%" . $filter['keyword'] . "%')";
            }

            $where .= $conditions;

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('products') . " AS p WHERE goods_id = $goods_id $where";
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $sql = "SELECT product_id, goods_id, goods_attr, product_sn, product_number
                FROM " . $GLOBALS['ecs']->table('products') . " AS g
                WHERE goods_id = $goods_id $where
                ORDER BY $filter[sort_by] $filter[sort_order]";

            $filter['keyword'] = stripslashes($filter['keyword']);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $GLOBALS['db']->getAll($sql);

        /* 处理规格属性 */
        $goods_attr = product_goods_attr_list($goods_id);
        foreach ($row as $key => $value) {
            $_goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($_goods_attr_array)) {
                $_temp = '';
                foreach ($_goods_attr_array as $_goods_attr_value) {
                    $_temp[] = $goods_attr[$_goods_attr_value];
                }
                $row[$key]['goods_attr'] = $_temp;
            }
        }

        return ['product' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 检测商品是否有货品
     *
     * @access      public
     * @params      integer     $goods_id       商品id
     * @params      string      $conditions     sql条件，AND语句开头
     * @return      string number               -1，错误；1，存在；0，不存在
     */
    public function check_goods_product_exist($goods_id, $conditions = '')
    {
        if (empty($goods_id)) {
            return -1;  //$goods_id不能为空
        }

        $sql = "SELECT goods_id
            FROM " . $GLOBALS['ecs']->table('products') . "
            WHERE goods_id = '$goods_id'
            " . $conditions . "
            LIMIT 0, 1";
        $result = $GLOBALS['db']->getRow($sql);

        if (empty($result)) {
            return 0;
        }

        return 1;
    }

    /**
     * 获得商品的货品总库存
     *
     * @access      public
     * @params      integer     $goods_id       商品id
     * @params      string      $conditions     sql条件，AND语句开头
     * @return      string number
     */
    public function product_number_count($goods_id, $conditions = '')
    {
        if (empty($goods_id)) {
            return -1;  //$goods_id不能为空
        }

        $sql = "SELECT SUM(product_number)
            FROM " . $GLOBALS['ecs']->table('products') . "
            WHERE goods_id = '$goods_id'
            " . $conditions;
        $nums = $GLOBALS['db']->getOne($sql);
        $nums = empty($nums) ? 0 : $nums;

        return $nums;
    }

    /**
     * 取货品信息
     *
     * @access  public
     * @param   int $product_id 货品id
     * @param   int $filed 字段
     * @return  array
     */
    public function get_product_info($product_id, $filed = '')
    {
        $return_array = [];

        if (empty($product_id)) {
            return $return_array;
        }

        $filed = trim($filed);
        if (empty($filed)) {
            $filed = '*';
        }

        $sql = "SELECT $filed FROM  " . $GLOBALS['ecs']->table('products') . " WHERE product_id = '$product_id'";
        $return_array = $GLOBALS['db']->getRow($sql);

        return $return_array;
    }

    /**
     * 商品的货品规格是否存在
     *
     * @param   string $goods_attr 商品的货品规格
     * @param   string $goods_id 商品id
     * @param   int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function check_goods_attr_exist($goods_attr, $goods_id, $product_id = 0)
    {
        $goods_id = intval($goods_id);
        if (strlen($goods_attr) == 0 || empty($goods_id)) {
            return true;    //重复
        }

        if (empty($product_id)) {
            $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table('products') . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'";
        } else {
            $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table('products') . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'
                AND product_id <> '$product_id'";
        }

        $res = $GLOBALS['db']->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }
    /**
     * 商品的货品货号是否重复
     *
     * @param   string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param   int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function check_product_sn_exist($product_sn, $product_id = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }
        $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_sn='$product_sn'";
        if ($GLOBALS['db']->getOne($sql)) {
            return true;    //重复
        }

        if (empty($product_id)) {
            $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table('products') . "
                WHERE product_sn = '$product_sn'";
        } else {
            $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table('products') . "
                WHERE product_sn = '$product_sn'
                AND product_id <> '$product_id'";
        }

        $res = $GLOBALS['db']->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 取商品的货品列表
     *
     * @param       mixed $goods_id 单个商品id；多个商品id数组；以逗号分隔商品id字符串
     * @param       string $conditions sql条件
     *
     * @return  array
     */
    public function get_good_products($goods_id, $conditions = '')
    {
        if (empty($goods_id)) {
            return [];
        }

        switch (gettype($goods_id)) {
        case 'integer':

            $_goods_id = "goods_id = '" . intval($goods_id) . "'";

            break;

        case 'string':
        case 'array':

            $_goods_id = db_create_in($goods_id, 'goods_id');

            break;
    }

        /* 取货品 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('products') . " WHERE $_goods_id $conditions";
        $result_products = $GLOBALS['db']->getAll($sql);

        /* 取商品属性 */
        $sql = "SELECT goods_attr_id, attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE $_goods_id";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = [];
        foreach ($result_goods_attr as $value) {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }

        /* 过滤货品 */
        foreach ($result_products as $key => $value) {
            $goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($goods_attr_array)) {
                $goods_attr = [];
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = $_goods_attr[$_attr];
                }

                $goods_attr_str = implode('，', $goods_attr);
            }

            $result_products[$key]['goods_attr_str'] = $goods_attr_str;
        }

        return $result_products;
    }

    /**
     * 取商品的下拉框Select列表
     *
     * @param       int $goods_id 商品id
     *
     * @return  array
     */
    public function get_good_products_select($goods_id)
    {
        $return_array = [];
        $products = get_good_products($goods_id);

        if (empty($products)) {
            return $return_array;
        }

        foreach ($products as $value) {
            $return_array[$value['product_id']] = $value['goods_attr_str'];
        }

        return $return_array;
    }
}
