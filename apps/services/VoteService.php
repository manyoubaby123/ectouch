<?php

namespace App\Services;

/**
 * Class VoteService
 * @package App\Services
 */
class VoteService
{

/**
 * 调用调查内容
 *
 * @access  public
 * @param   integer $id 调查的编号
 * @return  array
 */
    public function get_vote($id = '')
    {
        /* 随机取得一个调查的主题 */
        if (empty($id)) {
            $time = gmtime();
            $sql = 'SELECT vote_id, vote_name, can_multi, vote_count, RAND() AS rnd' .
            ' FROM ' . $GLOBALS['ecs']->table('vote') .
            " WHERE start_time <= '$time' AND end_time >= '$time' " .
            ' ORDER BY rnd LIMIT 1';
        } else {
            $sql = 'SELECT vote_id, vote_name, can_multi, vote_count' .
            ' FROM ' . $GLOBALS['ecs']->table('vote') .
            " WHERE vote_id = '$id'";
        }

        $vote_arr = $GLOBALS['db']->getRow($sql);

        if ($vote_arr !== false && !empty($vote_arr)) {
            /* 通过调查的ID,查询调查选项 */
            $sql_option = 'SELECT v.*, o.option_id, o.vote_id, o.option_name, o.option_count ' .
            'FROM ' . $GLOBALS['ecs']->table('vote') . ' AS v, ' .
            $GLOBALS['ecs']->table('vote_option') . ' AS o ' .
            "WHERE o.vote_id = v.vote_id AND o.vote_id = '$vote_arr[vote_id]' ORDER BY o.option_order ASC, o.option_id DESC";
            $res = $GLOBALS['db']->getAll($sql_option);

            /* 总票数 */
            $sql = 'SELECT SUM(option_count) AS all_option FROM ' . $GLOBALS['ecs']->table('vote_option') .
            " WHERE vote_id = '" . $vote_arr['vote_id'] . "' GROUP BY vote_id";
            $option_num = $GLOBALS['db']->getOne($sql);

            $arr = [];
            $count = 100;
            foreach ($res as $idx => $row) {
                if ($option_num > 0 && $idx == count($res) - 1) {
                    $percent = $count;
                } else {
                    $percent = ($row['vote_count'] > 0 && $option_num > 0) ? round(($row['option_count'] / $option_num) * 100) : 0;

                    $count -= $percent;
                }
                $arr[$row['vote_id']]['options'][$row['option_id']]['percent'] = $percent;

                $arr[$row['vote_id']]['vote_id'] = $row['vote_id'];
                $arr[$row['vote_id']]['vote_name'] = $row['vote_name'];
                $arr[$row['vote_id']]['can_multi'] = $row['can_multi'];
                $arr[$row['vote_id']]['vote_count'] = $row['vote_count'];

                $arr[$row['vote_id']]['options'][$row['option_id']]['option_id'] = $row['option_id'];
                $arr[$row['vote_id']]['options'][$row['option_id']]['option_name'] = $row['option_name'];
                $arr[$row['vote_id']]['options'][$row['option_id']]['option_count'] = $row['option_count'];
            }

            $vote_arr['vote_id'] = (!empty($vote_arr['vote_id'])) ? $vote_arr['vote_id'] : '';

            $vote = ['id' => $vote_arr['vote_id'], 'content' => $arr];

            return $vote;
        }
    }
}
