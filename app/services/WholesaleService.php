<?php

namespace app\services;

/**
 * Class WholesaleService
 * @package app\services
 */
class WholesaleService
{

/**
 * 批发信息
 * @param   int $act_id 活动id
 * @return  array
 */
    public function wholesale_info($act_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('wholesale') .
        " WHERE act_id = '$act_id'";
        $row = $GLOBALS['db']->getRow($sql);
        if (!empty($row)) {
            $row['price_list'] = unserialize($row['prices']);
        }

        return $row;
    }
}
