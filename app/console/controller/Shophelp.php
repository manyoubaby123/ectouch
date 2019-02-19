<?php

namespace app\console\controller;

use app\libraries\Exchange;

class Shophelp extends Init
{
    public function index()
    {
        /*初始化数据交换对象 */
        $exc_article = new Exchange($GLOBALS['ecs']->table("article"), $GLOBALS['db'], 'article_id', 'title');
        $exc_cat = new Exchange($GLOBALS['ecs']->table("article_cat"), $GLOBALS['db'], 'cat_id', 'cat_name');

        /*------------------------------------------------------ */
        //-- 列出所有文章分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_cat') {
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'shophelp.php?act=add']);
            $this->assign('ur_here', $GLOBALS['_LANG']['cat_list']);
            $this->assign('full_page', 1);
            $this->assign('list', $this->get_shophelp_list());

            return $this->fetch('shophelp_cat_list');
        }

        /*------------------------------------------------------ */
        //-- 分类下的文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_article') {
            $this->assign('ur_here', $GLOBALS['_LANG']['article_list']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'shophelp.php?act=add&cat_id=' . $_REQUEST['cat_id']]);
            $this->assign('full_page', 1);
            $this->assign('cat', article_cat_list($_REQUEST['cat_id'], true, 'cat_id', 0, "onchange=\"location.href='?act=list_article&cat_id='+this.value\""));
            $this->assign('list', $this->shophelp_article_list($_REQUEST['cat_id']));

            return $this->fetch('shophelp_article_list');
        }

        /*------------------------------------------------------ */
        //-- 查询分类下的文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query_art') {
            $cat_id = intval($_GET['cat']);

            $this->assign('list', $this->shophelp_article_list($cat_id));
            return make_json_result($GLOBALS['smarty']->display('shophelp_article_list'));
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $this->assign('list', $this->get_shophelp_list());

            return make_json_result($GLOBALS['smarty']->display('shophelp_cat_list'));
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            /* 创建 html editor */
            create_html_editor('FCKeditor1');

            if (empty($_REQUEST['cat_id'])) {
                $selected = 0;
            } else {
                $selected = $_REQUEST['cat_id'];
            }
            $cat_list = article_cat_list($selected, true, 'cat_id', 0);
            $cat_list = str_replace('select please', $GLOBALS['_LANG']['select_plz'], $cat_list);
            $this->assign('cat_list', $cat_list);
            $this->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['cat_list'], 'href' => 'shophelp.php?act=list_cat']);
            $this->assign('form_action', 'insert');
            return $this->fetch('shophelp_info');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            /* 判断是否重名 */
            $exc_article->is_only('title', $_POST['title'], $GLOBALS['_LANG']['title_exist']);

            /* 插入数据 */
            $add_time = gmtime();
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('article') . "(title, cat_id, article_type, content, add_time, author) VALUES('$_POST[title]', '$_POST[cat_id]', '$_POST[article_type]','$_POST[FCKeditor1]','$add_time', '_SHOPHELP' )";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'shophelp.php?act=list_article&cat_id=' . $_POST['cat_id'];
            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'shophelp.php?act=add&cat_id=' . $_POST['cat_id'];

            /* 清除缓存 */
            clear_cache_files();

            admin_log($_POST['title'], 'add', 'shophelp');
            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            /* 取文章数据 */
            $sql = "SELECT article_id,title, cat_id, article_type, is_open, author, author_email, keywords, content FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id='$_REQUEST[id]'";
            $article = $GLOBALS['db']->getRow($sql);

            /* 创建 html editor */
            create_html_editor('FCKeditor1', $article['content']);

            $this->assign('cat_list', article_cat_list($article['cat_id'], true, 'cat_id', 0));
            $this->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['article_list'], 'href' => 'shophelp.php?act=list_article&cat_id=' . $article['cat_id']]);
            $this->assign('article', $article);
            $this->assign('form_action', 'update');

            return $this->fetch('shophelp_info');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('shophelp_manage');

            /* 检查重名 */
            if ($_POST['title'] != $_POST['old_title']) {
                $exc_article->is_only('title', $_POST['title'], $GLOBALS['_LANG']['articlename_exist'], $_POST['id']);
            }
            /* 更新 */
            if ($exc_article->edit("title = '$_POST[title]', cat_id = '$_POST[cat_id]', article_type = '$_POST[article_type]', content = '$_POST[FCKeditor1]'", $_POST['id'])) {
                /* 清除缓存 */
                clear_cache_files();

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'shophelp.php?act=list_article&cat_id=' . $_POST['cat_id'];

                return sys_msg(sprintf($GLOBALS['_LANG']['articleedit_succeed'], $_POST['title']), 0, $link);
                admin_log($_POST['title'], 'edit', 'shophelp');
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑分类的名称
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_catname') {
            return check_authz_json('shophelp_manage');

            $id = intval($_POST['id']);
            $cat_name = json_str_iconv(trim($_POST['val']));

            /* 检查分类名称是否重复 */
            if ($exc_cat->num("cat_name", $cat_name, $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['catname_exist'], $cat_name));
            } else {
                if ($exc_cat->edit("cat_name = '$cat_name'", $id)) {
                    clear_cache_files();
                    admin_log($cat_name, 'edit', 'shophelpcat');
                    return make_json_result(stripslashes($cat_name));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑分类的排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_cat_order') {
            return check_authz_json('shophelp_manage');

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_result('', sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                if ($exc_cat->edit("sort_order = '$order'", $id)) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('shophelp_manage');

            $id = intval($_GET['id']);

            /* 非空的分类不允许删除 */
            if ($exc_article->num('cat_id', $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['not_emptycat']));
            } else {
                $exc_cat->drop($id);
                clear_cache_files();
                admin_log('', 'remove', 'shophelpcat');
            }

            $url = 'shophelp.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除分类下的某文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove_art') {
            return check_authz_json('shophelp_manage');

            $id = intval($_GET['id']);
            $cat_id = $GLOBALS['db']->getOne('SELECT cat_id FROM ' . $GLOBALS['ecs']->table('article') . " WHERE article_id='$id'");

            if ($exc_article->drop($id)) {
                /* 清除缓存 */
                clear_cache_files();
                admin_log('', 'remove', 'shophelp');
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['remove_fail']));
            }

            $url = 'shophelp.php?act=query_art&cat=' . $cat_id . '&' . str_replace('act=remove_art', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 添加一个新分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_catname') {
            return check_authz_json('shophelp_manage');

            $cat_name = trim($_POST['cat_name']);

            if (!empty($cat_name)) {
                if ($exc_cat->num("cat_name", $cat_name) != 0) {
                    return make_json_error($GLOBALS['_LANG']['catname_exist']);
                } else {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('article_cat') . " (cat_name, cat_type) VALUES ('$cat_name', 0)";
                    $GLOBALS['db']->query($sql);

                    admin_log($cat_name, 'add', 'shophelpcat');

                    return ecs_header("Location: shophelp.php?act=query\n");
                }
            } else {
                return make_json_error($GLOBALS['_LANG']['js_languages']['no_catname']);
            }

            return ecs_header("Location: shophelp.php?act=list_cat\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑文章标题
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_title') {
            return check_authz_json('shophelp_manage');

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            /* 检查文章标题是否有重名 */
            if ($exc_article->num('title', $title, $id) == 0) {
                if ($exc_article->edit("title = '$title'", $id)) {
                    clear_cache_files();
                    admin_log($title, 'edit', 'shophelp');
                    return make_json_result(stripslashes($title));
                }
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['articlename_exist'], $title));
            }
        }
    }

    /* 获得网店帮助文章分类 */
    private function get_shophelp_list()
    {
        $list = [];
        $sql = 'SELECT cat_id, cat_name, sort_order' .
            ' FROM ' . $GLOBALS['ecs']->table('article_cat') .
            ' WHERE cat_type = 0 ORDER BY sort_order';
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $rows) {
            $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('article') . " WHERE cat_id = '$rows[cat_id]'";
            $rows['num'] = $GLOBALS['db']->getOne($sql);

            $list[] = $rows;
        }

        return $list;
    }

    /* 获得网店帮助某分类下的文章 */
    private function shophelp_article_list($cat_id)
    {
        $list = [];
        $sql = 'SELECT article_id, title, article_type , add_time' .
            ' FROM ' . $GLOBALS['ecs']->table('article') .
            " WHERE cat_id = '$cat_id' ORDER BY article_type DESC";
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $rows) {
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

            $list[] = $rows;
        }

        return $list;
    }
}
