<?php

namespace App\Services;

/**
 * Class AdminService
 * @package App\Services
 */
class AdminService
{
    /**
     * 判断超级管理员用户名是否存在
     * @param   string $adminname 超级管理员用户名
     * @return  boolean
     */
    public function admin_registered($adminname)
    {
        $res = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('admin_user') .
            " WHERE user_name = '$adminname'");
        return $res;
    }

    /**
     * 记录管理员的操作内容
     *
     * @access  public
     * @param   string $sn 数据的唯一值
     * @param   string $action 操作的类型
     * @param   string $content 操作的内容
     * @return  void
     */
    public function admin_log($sn = '', $action, $content)
    {
        $log_info = $GLOBALS['_LANG']['log_action'][$action] . $GLOBALS['_LANG']['log_action'][$content] . ': ' . addslashes($sn);

        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('admin_log') . ' (log_time, user_id, log_info, ip_address) ' .
            " VALUES ('" . gmtime() . "', session('admin_id'), '" . stripslashes($log_info) . "', '" . real_ip() . "')";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 设置管理员的session内容
     *
     * @access  public
     * @param   integer $user_id 管理员编号
     * @param   string $username 管理员姓名
     * @param   string $action_list 权限列表
     * @param   string $last_time 最后登录时间
     * @return  void
     */
    public function set_admin_session($user_id, $username, $action_list, $last_time)
    {
        session('admin_id', $user_id);
        session('admin_name', $username);
        session('action_list', $action_list);
        session('last_check', $last_time); // 用于保存最后一次检查订单的时间
    }

    /**
     * 获取当前管理员信息
     *
     * @access  public
     * @param
     *
     * @return  Array
     */
    public function admin_info()
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('admin_user') . "
            WHERE user_id = '" . session('admin_id') . "'
            LIMIT 0, 1";
        $admin_info = $GLOBALS['db']->getRow($sql);

        if (empty($admin_info)) {
            return $admin_info = [];
        }

        return $admin_info;
    }
}
