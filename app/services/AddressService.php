<?php

namespace app\services;

/**
 * Class AddressService
 * @package app\services
 */
class AddressService
{
    /**
     * 取得收货人地址列表
     * @param   int $user_id 用户编号
     * @return  array
     */
    public function get_consignee_list($user_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE user_id = '$user_id' LIMIT 5";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 保存用户的收货人信息
     * 如果收货人信息中的 id 为 0 则新增一个收货人信息
     *
     * @access  public
     * @param   array $consignee
     * @param   boolean $default 是否将该收货人信息设置为默认收货人信息
     * @return  boolean
     */
    public function save_consignee($consignee, $default = false)
    {
        if ($consignee['address_id'] > 0) {
            /* 修改地址 */
            $res = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $consignee, 'UPDATE', 'address_id = ' . $consignee['address_id'] . " AND `user_id`= '" . session('user_id') . "'");
        } else {
            /* 添加地址 */
            $res = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $consignee, 'INSERT');
            $consignee['address_id'] = $GLOBALS['db']->insert_id();
        }

        if ($default) {
            /* 保存为用户的默认收货地址 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
                " SET address_id = '$consignee[address_id]' WHERE user_id = '" . session('user_id') . "'";

            $res = $GLOBALS['db']->query($sql);
        }

        return $res !== false;
    }

    /**
     * 删除一个收货地址
     *
     * @access  public
     * @param   integer $id
     * @return  boolean
     */
    public function drop_consignee($id)
    {
        $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('user_address') . " WHERE address_id = '$id'";
        $uid = $GLOBALS['db']->getOne($sql);

        if ($uid != session('user_id')) {
            return false;
        } else {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_address') . " WHERE address_id = '$id'";
            $res = $GLOBALS['db']->query($sql);

            return $res;
        }
    }

    /**
     *  添加或更新指定用户收货地址
     *
     * @access  public
     * @param   array $address
     * @return  bool
     */
    public function update_address($address)
    {
        $address_id = intval($address['address_id']);
        unset($address['address_id']);

        if ($address_id > 0) {
            /* 更新指定记录 */
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $address, 'UPDATE', 'address_id = ' . $address_id . ' AND user_id = ' . $address['user_id']);
        } else {
            /* 插入一条新记录 */
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $address, 'INSERT');
            $address_id = $GLOBALS['db']->insert_id();
        }

        if (isset($address['defalut']) && $address['default'] > 0 && isset($address['user_id'])) {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
                " SET address_id = '" . $address_id . "' " .
                " WHERE user_id = '" . $address['user_id'] . "'";
            $GLOBALS['db']->query($sql);
        }

        return true;
    }

    /**
     * 取得用户地址列表
     * @param   int $user_id 用户id
     * @return  array
     */
    public function address_list($user_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE user_id = '$user_id'";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 取得用户地址信息
     * @param   int $address_id 地址id
     * @return  array
     */
    public function address_info($address_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE address_id = '$address_id'";

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 取得收货人信息
     * @param   int $user_id 用户编号
     * @return  array
     */
    public function get_consignee($user_id)
    {
        if (session('?flow_consignee')) {
            /* 如果存在session，则直接返回session中的收货人信息 */

            return session('flow_consignee');
        } else {
            /* 如果不存在，则取得用户的默认收货人信息 */
            $arr = [];

            if ($user_id > 0) {
                /* 取默认地址 */
                $sql = "SELECT ua.*" .
                    " FROM " . $GLOBALS['ecs']->table('user_address') . "AS ua, " . $GLOBALS['ecs']->table('users') . ' AS u ' .
                    " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";

                $arr = $GLOBALS['db']->getRow($sql);
            }

            return $arr;
        }
    }

    /**
     * 检查收货人信息是否完整
     * @param   array $consignee 收货人信息
     * @param   int $flow_type 购物流程类型
     * @return  bool    true 完整 false 不完整
     */
    public function check_consignee_info($consignee, $flow_type)
    {
        if (exist_real_goods(0, $flow_type)) {
            /* 如果存在实体商品 */
            $res = !empty($consignee['consignee']) &&
                !empty($consignee['country']) &&
                !empty($consignee['email']) &&
                !empty($consignee['tel']);

            if ($res) {
                if (empty($consignee['province'])) {
                    /* 没有设置省份，检查当前国家下面有没有设置省份 */
                    $pro = get_regions(1, $consignee['country']);
                    $res = empty($pro);
                } elseif (empty($consignee['city'])) {
                    /* 没有设置城市，检查当前省下面有没有城市 */
                    $city = get_regions(2, $consignee['province']);
                    $res = empty($city);
                } elseif (empty($consignee['district'])) {
                    $dist = get_regions(3, $consignee['city']);
                    $res = empty($dist);
                }
            }

            return $res;
        } else {
            /* 如果不存在实体商品 */
            return !empty($consignee['consignee']) &&
                !empty($consignee['email']) &&
                !empty($consignee['tel']);
        }
    }
}
