<?php

namespace app\services;

/**
 * Class CommentService
 * @package app\services
 */
class CommentService
{
    /**
     * 查询评论内容
     *
     * @access  public
     * @params  integer     $id
     * @params  integer     $type
     * @params  integer     $page
     * @return  array
     */
    public function assign_comment($id, $type, $page = 1)
    {
        /* 取得评论列表 */
        $count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('comment') .
            " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0");
        $size = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;

        $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
            " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" .
            ' ORDER BY comment_id DESC';
        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

        $arr = [];
        $ids = '';
        foreach ($res as $row) {
            $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
            $arr[$row['comment_id']]['id'] = $row['comment_id'];
            $arr[$row['comment_id']]['email'] = $row['email'];
            $arr[$row['comment_id']]['username'] = $row['user_name'];
            $arr[$row['comment_id']]['content'] = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
            $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
            $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
        }
        /* 取得已有回复的评论 */
        if ($ids) {
            $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
                " WHERE parent_id IN( $ids )";
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $row) {
                $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $arr[$row['parent_id']]['re_email'] = $row['email'];
                $arr[$row['parent_id']]['re_username'] = $row['user_name'];
            }
        }
        /* 分页样式 */
        //$pager['styleid'] = isset($GLOBALS['_CFG']['page_style'])? intval($GLOBALS['_CFG']['page_style']) : 0;
        $pager['page'] = $page;
        $pager['size'] = $size;
        $pager['record_count'] = $count;
        $pager['page_count'] = $page_count;
        $pager['page_first'] = "javascript:gotoPage(1,$id,$type)";
        $pager['page_prev'] = $page > 1 ? "javascript:gotoPage(" . ($page - 1) . ",$id,$type)" : 'javascript:;';
        $pager['page_next'] = $page < $page_count ? 'javascript:gotoPage(' . ($page + 1) . ",$id,$type)" : 'javascript:;';
        $pager['page_last'] = $page < $page_count ? 'javascript:gotoPage(' . $page_count . ",$id,$type)" : 'javascript:;';

        $cmt = ['comments' => $arr, 'pager' => $pager];

        return $cmt;
    }

    /**
     *  获取用户评论
     *
     * @access  public
     * @param   int $user_id 用户id
     * @param   int $page_size 列表最大数量
     * @param   int $start 列表起始页
     * @return  array
     */
    public function get_comment_list($user_id, $page_size, $start)
    {
        $sql = "SELECT c.*, g.goods_name AS cmt_name, r.content AS reply_content, r.add_time AS reply_time " .
            " FROM " . $GLOBALS['ecs']->table('comment') . " AS c " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('comment') . " AS r " .
            " ON r.parent_id = c.comment_id AND r.parent_id > 0 " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g " .
            " ON c.comment_type=0 AND c.id_value = g.goods_id " .
            " WHERE c.user_id='$user_id'";
        $res = $GLOBALS['db']->SelectLimit($sql, $page_size, $start);

        $comments = [];
        $to_article = [];
        foreach ($res as $row) {
            $row['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            if ($row['reply_time']) {
                $row['formated_reply_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['reply_time']);
            }
            if ($row['comment_type'] == 1) {
                $to_article[] = $row["id_value"];
            }
            $comments[] = $row;
        }

        if ($to_article) {
            $sql = "SELECT article_id , title FROM " . $GLOBALS['ecs']->table('article') . " WHERE " . db_create_in($to_article, 'article_id');
            $arr = $GLOBALS['db']->getAll($sql);
            $to_cmt_name = [];
            foreach ($arr as $row) {
                $to_cmt_name[$row['article_id']] = $row['title'];
            }

            foreach ($comments as $key => $row) {
                if ($row['comment_type'] == 1) {
                    $comments[$key]['cmt_name'] = isset($to_cmt_name[$row['id_value']]) ? $to_cmt_name[$row['id_value']] : '';
                }
            }
        }

        return $comments;
    }
}
