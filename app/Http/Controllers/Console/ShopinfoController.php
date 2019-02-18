<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Exchange;

class ShopinfoController extends InitController
{
    public function actionIndex()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("article"), $GLOBALS['db'], 'article_id', 'title');

        /*------------------------------------------------------ */
        //-- 文章列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['shop_info']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['shopinfo_add'], 'href' => 'shopinfo.php?act=add']);
            $GLOBALS['smarty']->assign('full_page', 1);
            $GLOBALS['smarty']->assign('list', $this->shopinfo_article_list());

            return $GLOBALS['smarty']->display('shopinfo_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $GLOBALS['smarty']->assign('list', $this->shopinfo_article_list());

            return make_json_result($GLOBALS['smarty']->fetch('shopinfo_list.htm'));
        }

        /*------------------------------------------------------ */
        //-- 添加新文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('shopinfo_manage');

            /* 创建 html editor */
            create_html_editor('FCKeditor1');

            /* 初始化 */
            $article['article_type'] = 0;

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['shopinfo_add']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['shopinfo_list'], 'href' => 'shopinfo.php?act=list']);
            $GLOBALS['smarty']->assign('form_action', 'insert');

            return $GLOBALS['smarty']->display('shopinfo_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('shopinfo_manage');

            /* 判断是否重名 */
            $is_only = $exc->is_only('title', $_POST['title']);

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_POST['title'])), 1);
            }

            /* 插入数据 */
            $add_time = gmtime();
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('article') . "(title, cat_id, content, add_time) VALUES('$_POST[title]', '0', '$_POST[FCKeditor1]','$add_time' )";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'shopinfo.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'shopinfo.php?act=list';

            /* 清除缓存 */
            clear_cache_files();

            admin_log($_POST['title'], 'add', 'shopinfo');
            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 文章编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('shopinfo_manage');

            /* 取得文章数据 */
            $sql = "SELECT article_id, title, content FROM " . $GLOBALS['ecs']->table('article') . "WHERE article_id =" . $_REQUEST['id'];
            $article = $GLOBALS['db']->getRow($sql);

            /* 创建 html editor */
            create_html_editor('FCKeditor1', $article['content']);

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['shopinfo_list'], 'href' => 'shopinfo.php?act=list']);
            $GLOBALS['smarty']->assign('article', $article);
            $GLOBALS['smarty']->assign('form_action', 'update');
            return $GLOBALS['smarty']->display('shopinfo_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('shopinfo_manage');

            /* 检查重名 */
            if ($_POST['title'] != $_POST['old_title']) {
                $is_only = $exc->is_only('title', $_POST['title'], $_POST['id']);

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_POST['title'])), 1);
                }
            }

            /* 更新数据 */
            $cur_time = gmtime();
            if ($exc->edit("title='$_POST[title]', content='$_POST[FCKeditor1]',add_time ='$cur_time'", $_POST['id'])) {
                /* 清除缓存 */
                clear_cache_files();

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'shopinfo.php?act=list';

                return sys_msg(sprintf($GLOBALS['_LANG']['articleedit_succeed'], $_POST['title']), 0, $link);
                admin_log($_POST['title'], 'edit', 'shopinfo');
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑文章主题
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_title') {
            return check_authz_json('shopinfo_manage');

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            /* 检查文章标题是否有重名 */
            if ($exc->num('title', $title, $id) == 0) {
                if ($exc->edit("title = '$title'", $id)) {
                    clear_cache_files();
                    admin_log($title, 'edit', 'shopinfo');
                    return make_json_result(stripslashes($title));
                }
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['title_exist'], $title));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('shopinfo_manage');

            $id = intval($_GET['id']);

            /* 获得文章主题 */
            $title = $exc->get_name($id);
            if ($exc->drop($id)) {
                clear_cache_files();
                admin_log(addslashes($title), 'remove', 'shopinfo');
            }

            $url = 'shopinfo.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }
    }

    /* 获取网店信息文章数据 */
    private function shopinfo_article_list()
    {
        $list = [];
        $sql = 'SELECT article_id, title ,add_time' .
            ' FROM ' . $GLOBALS['ecs']->table('article') .
            ' WHERE cat_id = 0 ORDER BY article_id';
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $rows) {
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

            $list[] = $rows;
        }

        return $list;
    }
}
