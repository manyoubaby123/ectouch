<?php

namespace App\Services;

/**
 * Class AccountService
 * @package App\Services
 */
class AccountService
{
    /**
     * 插入会员账目明细
     *
     * @access  public
     * @param   array $surplus 会员余额信息
     * @param   string $amount 余额
     *
     * @return  int
     */
    public function insert_user_account($surplus, $amount)
    {
        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('user_account') .
            ' (user_id, admin_user, amount, add_time, paid_time, admin_note, user_note, process_type, payment, is_paid)' .
            " VALUES ('$surplus[user_id]', '', '$amount', '" . gmtime() . "', 0, '', '$surplus[user_note]', '$surplus[process_type]', '$surplus[payment]', 0)";
        $GLOBALS['db']->query($sql);

        return $GLOBALS['db']->insert_id();
    }

    /**
     * 更新会员账目明细
     *
     * @access  public
     * @param   array $surplus 会员余额信息
     *
     * @return  int
     */
    public function update_user_account($surplus)
    {
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') . ' SET ' .
            "amount     = '$surplus[amount]', " .
            "user_note  = '$surplus[user_note]', " .
            "payment    = '$surplus[payment]' " .
            "WHERE id   = '$surplus[rec_id]'";
        $GLOBALS['db']->query($sql);

        return $surplus['rec_id'];
    }

    /**
     * 根据ID获取当前余额操作信息
     *
     * @access  public
     * @param   int $surplus_id 会员余额的ID
     *
     * @return  int
     */
    public function get_surplus_info($surplus_id)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('user_account') .
            " WHERE id = '$surplus_id'";

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 查询会员余额的操作记录
     *
     * @access  public
     * @param   int $user_id 会员ID
     * @param   int $num 每页显示数量
     * @param   int $start 开始显示的条数
     * @return  array
     */
    public function get_account_log($user_id, $num, $start)
    {
        $account_log = [];
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('user_account') .
            " WHERE user_id = '$user_id'" .
            " AND process_type " . db_create_in([SURPLUS_SAVE, SURPLUS_RETURN]) .
            " ORDER BY add_time DESC";
        $res = $GLOBALS['db']->selectLimit($sql, $num, $start);

        if ($res) {
            foreach ($res as $rows) {
                $rows['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $rows['add_time']);
                $rows['admin_note'] = nl2br(htmlspecialchars($rows['admin_note']));
                $rows['short_admin_note'] = ($rows['admin_note'] > '') ? sub_str($rows['admin_note'], 30) : 'N/A';
                $rows['user_note'] = nl2br(htmlspecialchars($rows['user_note']));
                $rows['short_user_note'] = ($rows['user_note'] > '') ? sub_str($rows['user_note'], 30) : 'N/A';
                $rows['pay_status'] = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirm'] : $GLOBALS['_LANG']['is_confirm'];
                $rows['amount'] = price_format(abs($rows['amount']), false);

                /* 会员的操作类型： 冲值，提现 */
                if ($rows['process_type'] == 0) {
                    $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
                } else {
                    $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
                }

                /* 支付方式的ID */
                $sql = 'SELECT pay_id FROM ' . $GLOBALS['ecs']->table('payment') .
                    " WHERE pay_name = '$rows[payment]' AND enabled = 1";
                $pid = $GLOBALS['db']->getOne($sql);

                /* 如果是预付款而且还没有付款, 允许付款 */
                if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0)) {
                    $rows['handle'] = '<a href="user.php?act=pay&id=' . $rows['id'] . '&pid=' . $pid . '">' . $GLOBALS['_LANG']['pay'] . '</a>';
                }

                $account_log[] = $rows;
            }

            return $account_log;
        } else {
            return false;
        }
    }

    /**
     *  删除未确认的会员帐目信息
     *
     * @access  public
     * @param   int $rec_id 会员余额记录的ID
     * @param   int $user_id 会员的ID
     * @return  boolen
     */
    public function del_user_account($rec_id, $user_id)
    {
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('user_account') .
            " WHERE is_paid = 0 AND id = '$rec_id' AND user_id = '$user_id'";

        return $GLOBALS['db']->query($sql);
    }

    /**
     * 查询会员余额的数量
     * @access  public
     * @param   int $user_id 会员ID
     * @return  int
     */
    public function get_user_surplus($user_id)
    {
        $sql = "SELECT SUM(user_money) FROM " . $GLOBALS['ecs']->table('account_log') .
            " WHERE user_id = '$user_id'";

        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 记录帐户变动
     * @param   int $user_id 用户id
     * @param   float $user_money 可用余额变动
     * @param   float $frozen_money 冻结余额变动
     * @param   int $rank_points 等级积分变动
     * @param   int $pay_points 消费积分变动
     * @param   string $change_desc 变动说明
     * @param   int $change_type 变动类型：参见常量文件
     * @return  void
     */
    public function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
    {
        /* 插入帐户变动记录 */
        $account_log = [
            'user_id' => $user_id,
            'user_money' => $user_money,
            'frozen_money' => $frozen_money,
            'rank_points' => $rank_points,
            'pay_points' => $pay_points,
            'change_time' => gmtime(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        ];
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

        /* 更新用户信息 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
            " SET user_money = user_money + ('$user_money')," .
            " frozen_money = frozen_money + ('$frozen_money')," .
            " rank_points = rank_points + ('$rank_points')," .
            " pay_points = pay_points + ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
        $GLOBALS['db']->query($sql);
    }
}
