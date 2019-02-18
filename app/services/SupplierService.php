<?php

namespace app\services;

/**
 * Class SupplierService
 * @package app\services
 */
class SupplierService
{

/**
 * 供货商列表信息
 *
 * @param       string $conditions
 * @return      array
 */
    public function suppliers_list_info($conditions = '')
    {
        $where = '';
        if (!empty($conditions)) {
            $where .= 'WHERE ';
            $where .= $conditions;
        }

        /* 查询 */
        $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc
            FROM " . $GLOBALS['ecs']->table("suppliers") . "
            $where";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 供货商名
     *
     * @return  array
     */
    public function suppliers_list_name()
    {
        /* 查询 */
        $suppliers_list = suppliers_list_info(' is_check = 1 ');

        /* 供货商名字 */
        $suppliers_name = [];
        if (count($suppliers_list) > 0) {
            foreach ($suppliers_list as $suppliers) {
                $suppliers_name[$suppliers['suppliers_id']] = $suppliers['suppliers_name'];
            }
        }

        return $suppliers_name;
    }
}
