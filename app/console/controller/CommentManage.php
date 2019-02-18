<?php

namespace App\Http\Controllers\Console;

class CommentManage extends Init
{
    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 获取没有回复的评论列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['05_comment_manage']);
            $GLOBALS['smarty']->assign('full_page', 1);

            $list = $this->get_comment_list();

            $GLOBALS['smarty']->assign('comment_list', $list['item']);
            $GLOBALS['smarty']->assign('filter', $list['filter']);
            $GLOBALS['smarty']->assign('record_count', $list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return $GLOBALS['smarty']->display('comment_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $list = $this->get_comment_list();

            $GLOBALS['smarty']->assign('comment_list', $list['item']);
            $GLOBALS['smarty']->assign('filter', $list['filter']);
            $GLOBALS['smarty']->assign('record_count', $list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('comment_list.htm'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 回复用户评论(同时查看评论详情)
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'reply') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $comment_info = [];
            $reply_info = [];
            $id_value = [];

            /* 获取评论详细信息并进行字符处理 */
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_id = '$_REQUEST[id]'";
            $comment_info = $GLOBALS['db']->getRow($sql);
            $comment_info['content'] = str_replace('\r\n', '<br />', htmlspecialchars($comment_info['content']));
            $comment_info['content'] = nl2br(str_replace('\n', '<br />', $comment_info['content']));
            $comment_info['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $comment_info['add_time']);

            /* 获得评论回复内容 */
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('comment') . " WHERE parent_id = '$_REQUEST[id]'";
            $reply_info = $GLOBALS['db']->getRow($sql);

            if (empty($reply_info)) {
                $reply_info['content'] = '';
                $reply_info['add_time'] = '';
            } else {
                $reply_info['content'] = nl2br(htmlspecialchars($reply_info['content']));
                $reply_info['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $reply_info['add_time']);
            }
            /* 获取管理员的用户名和Email地址 */
            $sql = "SELECT user_name, email FROM " . $GLOBALS['ecs']->table('admin_user') .
                " WHERE user_id = '". session('admin_id') ."'";
            $admin_info = $GLOBALS['db']->getRow($sql);

            /* 取得评论的对象(文章或者商品) */
            if ($comment_info['comment_type'] == 0) {
                $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE goods_id = '$comment_info[id_value]'";
                $id_value = $GLOBALS['db']->getOne($sql);
            } else {
                $sql = "SELECT title FROM " . $GLOBALS['ecs']->table('article') .
                    " WHERE article_id='$comment_info[id_value]'";
                $id_value = $GLOBALS['db']->getOne($sql);
            }

            /* 模板赋值 */
            $GLOBALS['smarty']->assign('msg', $comment_info); //评论信息
            $GLOBALS['smarty']->assign('admin_info', $admin_info);   //管理员信息
            $GLOBALS['smarty']->assign('reply_info', $reply_info);   //回复的内容
            $GLOBALS['smarty']->assign('id_value', $id_value);  //评论的对象
            $GLOBALS['smarty']->assign('send_fail', !empty($_REQUEST['send_ok']));

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['comment_info']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['05_comment_manage'],
                'href' => 'comment_manage.php?act=list']);

            /* 页面显示 */

            return $GLOBALS['smarty']->display('comment_info.htm');
        }
        /*------------------------------------------------------ */
        //-- 处理 回复用户评论
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'action') {
            admin_priv('comment_priv');

            /* 获取IP地址 */
            $ip = real_ip();

            /* 获得评论是否有回复 */
            $sql = "SELECT comment_id, content, parent_id FROM " . $GLOBALS['ecs']->table('comment') .
                " WHERE parent_id = '$_REQUEST[comment_id]'";
            $reply_info = $GLOBALS['db']->getRow($sql);

            if (!empty($reply_info['content'])) {
                /* 更新回复的内容 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('comment') . " SET " .
                    "email     = '$_POST[email]', " .
                    "user_name = '$_POST[user_name]', " .
                    "content   = '$_POST[content]', " .
                    "add_time  =  '" . gmtime() . "', " .
                    "ip_address= '$ip', " .
                    "status    = 0" .
                    " WHERE comment_id = '" . $reply_info['comment_id'] . "'";
            } else {
                /* 插入回复的评论内容 */
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('comment') . " (comment_type, id_value, email, user_name , " .
                    "content, add_time, ip_address, status, parent_id) " .
                    "VALUES('$_POST[comment_type]', '$_POST[id_value]','$_POST[email]', " .
                    "'" . session('admin_name') . "','$_POST[content]','" . gmtime() . "', '$ip', '0', '$_POST[comment_id]')";
            }
            $GLOBALS['db']->query($sql);

            /* 更新当前的评论状态为已回复并且可以显示此条评论 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('comment') . " SET status = 1 WHERE comment_id = '$_POST[comment_id]'";
            $GLOBALS['db']->query($sql);

            /* 邮件通知处理流程 */
            if (!empty($_POST['send_email_notice']) or isset($_POST['remail'])) {
                //获取邮件中的必要内容
                $sql = 'SELECT user_name, email, content ' .
                    'FROM ' . $GLOBALS['ecs']->table('comment') .
                    " WHERE comment_id ='$_REQUEST[comment_id]'";
                $comment_info = $GLOBALS['db']->getRow($sql);

                /* 设置留言回复模板所需要的内容信息 */
                $template = get_mail_template('recomment');

                $GLOBALS['smarty']->assign('user_name', $comment_info['user_name']);
                $GLOBALS['smarty']->assign('recomment', $_POST['content']);
                $GLOBALS['smarty']->assign('comment', $comment_info['content']);
                $GLOBALS['smarty']->assign('shop_name', "<a href='" . $GLOBALS['ecs']->url() . "'>" . $GLOBALS['_CFG']['shop_name'] . '</a>');
                $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));

                $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

                /* 发送邮件 */
                if (send_mail($comment_info['user_name'], $comment_info['email'], $template['template_subject'], $content, $template['is_html'])) {
                    $send_ok = 0;
                } else {
                    $send_ok = 1;
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['reply']), 'edit', 'users_comment');

            return ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[comment_id]&send_ok=$send_ok\n");
        }
        /*------------------------------------------------------ */
        //-- 更新评论的状态为显示或者禁止
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'check') {
            if ($_REQUEST['check'] == 'allow') {
                /* 允许评论显示 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('comment') . " SET status = 1 WHERE comment_id = '$_REQUEST[id]'";
                $GLOBALS['db']->query($sql);

                //add_feed($_REQUEST['id'], COMMENT_GOODS);

                /* 清除缓存 */
                clear_cache_files();

                return ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
            } else {
                /* 禁止评论显示 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('comment') . " SET status = 0 WHERE comment_id = '$_REQUEST[id]'";
                $GLOBALS['db']->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                return ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 删除某一条评论
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('comment_priv');

            $id = intval($_GET['id']);

            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_id = '$id'";
            $res = $GLOBALS['db']->query($sql);
            if ($res) {
                $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE parent_id = '$id'");
            }

            admin_log('', 'remove', 'ads');

            $url = 'comment_manage.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量删除用户评论
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            admin_priv('comment_priv');
            $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';

            if (isset($_POST['checkboxes'])) {
                switch ($action) {
                    case 'remove':
                        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'parent_id'));
                        break;

                    case 'allow':
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('comment') . " SET status = 1  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        break;

                    case 'deny':
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('comment') . " SET status = 0  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        break;

                    default:
                        break;
                }

                clear_cache_files();
                $action = ($action == 'remove') ? 'remove' : 'edit';
                admin_log('', $action, 'adminlog');

                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'comment_manage.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($_POST['checkboxes'])), 0, $link);
            } else {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'comment_manage.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_comment'], 0, $link);
            }
        }
    }

    /**
     * 获取评论列表
     * @access  public
     * @return  array
     */
    private function get_comment_list()
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = (!empty($filter['keywords'])) ? " AND content LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';

        $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " WHERE parent_id = 0 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */
        $arr = [];
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('comment') . " WHERE parent_id = 0 $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $sql = ($row['comment_type'] == 0) ?
                "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id='$row[id_value]'" :
                "SELECT title FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id='$row[id_value]'";
            $row['title'] = $GLOBALS['db']->getOne($sql);

            /* 标记是否回复过 */
//        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('comment'). " WHERE parent_id = '$row[comment_id]'";
//        $row['is_reply'] =  ($GLOBALS['db']->getOne($sql) > 0) ?
//            $GLOBALS['_LANG']['yes_reply'] : $GLOBALS['_LANG']['no_reply'];

            $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);

            $arr[] = $row;
        }
        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
