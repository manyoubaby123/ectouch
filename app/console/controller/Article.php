<?php

namespace app\console\controller;

use app\libraries\Exchange;
use app\libraries\Image;

class Article extends Init
{
    public function index()
    {
        /*初始化数据交换对象 */
        $exc = new Exchange($GLOBALS['ecs']->table("article"), $GLOBALS['db'], 'article_id', 'title');

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';

        /*------------------------------------------------------ */
        //-- 文章列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 取得过滤条件 */
            $filter = [];
            $this->assign('cat_select', article_cat_list(0));
            $this->assign('ur_here', $GLOBALS['_LANG']['03_article_list']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['article_add'], 'href' => 'article.php?act=add']);
            $this->assign('full_page', 1);
            $this->assign('filter', $filter);

            $article_list = $this->get_articleslist();

            $this->assign('article_list', $article_list['arr']);
            $this->assign('filter', $article_list['filter']);
            $this->assign('record_count', $article_list['record_count']);
            $this->assign('page_count', $article_list['page_count']);

            $sort_flag = sort_flag($article_list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return $GLOBALS['smarty']->display('article_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            return check_authz_json('article_manage');

            $article_list = $this->get_articleslist();

            $this->assign('article_list', $article_list['arr']);
            $this->assign('filter', $article_list['filter']);
            $this->assign('record_count', $article_list['record_count']);
            $this->assign('page_count', $article_list['page_count']);

            $sort_flag = sort_flag($article_list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('article_list.htm'),
                '',
                ['filter' => $article_list['filter'], 'page_count' => $article_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('article_manage');

            /* 创建 html editor */
            create_html_editor('FCKeditor1');

            /*初始化*/
            $article = [];
            $article['is_open'] = 1;

            /* 取得分类、品牌 */
            $this->assign('goods_cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());

            /* 清理关联商品 */
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') . " WHERE article_id = 0";
            $GLOBALS['db']->query($sql);

            if (isset($_GET['id'])) {
                $this->assign('cur_id', $_GET['id']);
            }
            $this->assign('article', $article);
            $this->assign('cat_select', article_cat_list(0));
            $this->assign('ur_here', $GLOBALS['_LANG']['article_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['03_article_list'], 'href' => 'article.php?act=list']);
            $this->assign('form_action', 'insert');

            return $GLOBALS['smarty']->display('article_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 添加文章
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('article_manage');

            /*检查是否重复*/
            $is_only = $exc->is_only('title', $_POST['title'], 0, " cat_id ='$_POST[article_cat]'");

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_POST['title'])), 1);
            }

            /* 取得文件地址 */
            $file_url = '';
            if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_file']);
                }

                // 复制文件
                $res = $this->upload_article_file($_FILES['file']);
                if ($res != false) {
                    $file_url = $res;
                }
            }

            if ($file_url == '') {
                $file_url = $_POST['file_url'];
            }

            /* 计算文章打开方式 */
            if ($file_url == '') {
                $open_type = 0;
            } else {
                $open_type = $_POST['FCKeditor1'] == '' ? 1 : 2;
            }

            /*插入数据*/
            $add_time = gmtime();
            if (empty($_POST['cat_id'])) {
                $_POST['cat_id'] = 0;
            }
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('article') . "(title, cat_id, article_type, is_open, author, " .
                "author_email, keywords, content, add_time, file_url, open_type, link, description) " .
                "VALUES ('$_POST[title]', '$_POST[article_cat]', '$_POST[article_type]', '$_POST[is_open]', " .
                "'$_POST[author]', '$_POST[author_email]', '$_POST[keywords]', '$_POST[FCKeditor1]', " .
                "'$add_time', '$file_url', '$open_type', '$_POST[link_url]', '$_POST[description]')";
            $GLOBALS['db']->query($sql);

            /* 处理关联商品 */
            $article_id = $GLOBALS['db']->insert_id();
            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_article') . " SET article_id = '$article_id' WHERE article_id = 0";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'article.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'article.php?act=list';

            admin_log($_POST['title'], 'add', 'article');

            clear_cache_files(); // 清除相关的缓存文件

            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('article_manage');

            /* 取文章数据 */
            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id='$_REQUEST[id]'";
            $article = $GLOBALS['db']->getRow($sql);

            /* 创建 html editor */
            create_html_editor('FCKeditor1', $article['content']);

            /* 取得分类、品牌 */
            $this->assign('goods_cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());

            /* 取得关联商品 */
            $goods_list = $this->get_article_goods($_REQUEST['id']);
            $this->assign('goods_list', $goods_list);

            $this->assign('article', $article);
            $this->assign('cat_select', article_cat_list(0, $article['cat_id']));
            $this->assign('ur_here', $GLOBALS['_LANG']['article_edit']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['03_article_list'], 'href' => 'article.php?act=list&' . list_link_postfix()]);
            $this->assign('form_action', 'update');

            return $GLOBALS['smarty']->display('article_info.htm');
        }

        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('article_manage');

            /*检查文章名是否相同*/
            $is_only = $exc->is_only('title', $_POST['title'], $_POST['id'], "cat_id = '$_POST[article_cat]'");

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_POST['title'])), 1);
            }

            if (empty($_POST['cat_id'])) {
                $_POST['cat_id'] = 0;
            }

            /* 取得文件地址 */
            $file_url = '';
            if (empty($_FILES['file']['error']) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['invalid_file']);
                }

                // 复制文件
                $res = $this->upload_article_file($_FILES['file']);
                if ($res != false) {
                    $file_url = $res;
                }
            }

            if ($file_url == '') {
                $file_url = $_POST['file_url'];
            }

            /* 计算文章打开方式 */
            if ($file_url == '') {
                $open_type = 0;
            } else {
                $open_type = $_POST['FCKeditor1'] == '' ? 1 : 2;
            }

            /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
            $sql = "SELECT file_url FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id = '$_POST[id]'";
            $old_url = $GLOBALS['db']->getOne($sql);
            if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                @unlink(ROOT_PATH . $old_url);
            }

            if ($exc->edit("title='$_POST[title]', cat_id='$_POST[article_cat]', article_type='$_POST[article_type]', is_open='$_POST[is_open]', author='$_POST[author]', author_email='$_POST[author_email]', keywords ='$_POST[keywords]', file_url ='$file_url', open_type='$open_type', content='$_POST[FCKeditor1]', link='$_POST[link_url]', description = '$_POST[description]'", $_POST['id'])) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'article.php?act=list&' . list_link_postfix();

                $note = sprintf($GLOBALS['_LANG']['articleedit_succeed'], stripslashes($_POST['title']));
                admin_log($_POST['title'], 'edit', 'article');

                clear_cache_files();

                return sys_msg($note, 0, $link);
            } else {
                return $GLOBALS['db']->error();
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑文章主题
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_title') {
            return check_authz_json('article_manage');

            $id = intval($_POST['id']);
            $title = json_str_iconv(trim($_POST['val']));

            /* 检查文章标题是否重复 */
            if ($exc->num("title", $title, $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['title_exist'], $title));
            } else {
                if ($exc->edit("title = '$title'", $id)) {
                    clear_cache_files();
                    admin_log($title, 'edit', 'article');
                    return make_json_result(stripslashes($title));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'toggle_show') {
            return check_authz_json('article_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_open = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换文章重要性
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'toggle_type') {
            return check_authz_json('article_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("article_type = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 删除文章主题
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('article_manage');

            $id = intval($_GET['id']);

            /* 删除原来的文件 */
            $sql = "SELECT file_url FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id = '$id'";
            $old_url = $GLOBALS['db']->getOne($sql);
            if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                @unlink(ROOT_PATH . $old_url);
            }

            $name = $exc->get_name($id);
            if ($exc->drop($id)) {
                $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('comment') . " WHERE " . "comment_type = 1 AND id_value = $id");

                admin_log(addslashes($name), 'remove', 'article');
                clear_cache_files();
            }

            $url = 'article.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 将商品加入关联
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_link_goods') {
            return check_authz_json('article_manage');

            $add_ids = json_decode($_GET['add_ids']);
            $args = json_decode($_GET['JSON']);
            $article_id = $args[0];

            if ($article_id == 0) {
                $article_id = $GLOBALS['db']->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' . $GLOBALS['ecs']->table('article'));
            }

            foreach ($add_ids as $key => $val) {
                $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('goods_article') . ' (goods_id, article_id) ' .
                    "VALUES ('$val', '$article_id')";
                $GLOBALS['db']->query($sql, 'SILENT');
            }

            /* 重新载入 */
            $arr = $this->get_article_goods($article_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 将商品删除关联
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'drop_link_goods') {
            return check_authz_json('article_manage');

            $drop_goods = json_decode($_GET['drop_ids']);
            $arguments = json_decode($_GET['JSON']);
            $article_id = $arguments[0];

            if ($article_id == 0) {
                $article_id = $GLOBALS['db']->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' . $GLOBALS['ecs']->table('article'));
            }

            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') .
                " WHERE article_id = '$article_id' AND goods_id " . db_create_in($drop_goods);
            $GLOBALS['db']->query($sql, 'SILENT');

            /* 重新载入 */
            $arr = $this->get_article_goods($article_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_goods_list') {
            $filters = json_decode($_GET['JSON']);

            $arr = get_goods_list($filters);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];
            }

            return make_json_result($opt);
        }
        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'batch') {
            /* 批量删除 */
            if (isset($_POST['type'])) {
                if ($_POST['type'] == 'button_remove') {
                    admin_priv('article_manage');

                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    /* 删除原来的文件 */
                    $sql = "SELECT file_url FROM " . $GLOBALS['ecs']->table('article') .
                        " WHERE article_id " . db_create_in(join(',', $_POST['checkboxes'])) .
                        " AND file_url <> ''";

                    $res = $GLOBALS['db']->query($sql);
                    foreach ($res as $row) {
                        $old_url = $row['file_url'];
                        if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                            @unlink(ROOT_PATH . $old_url);
                        }
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        if ($exc->drop($id)) {
                            $name = $exc->get_name($id);
                            admin_log(addslashes($name), 'remove', 'article');
                        }
                    }
                }

                /* 批量隐藏 */
                if ($_POST['type'] == 'button_hide') {
                    return check_authz_json('article_manage');
                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        $exc->edit("is_open = '0'", $id);
                    }
                }

                /* 批量显示 */
                if ($_POST['type'] == 'button_show') {
                    return check_authz_json('article_manage');
                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        $exc->edit("is_open = '1'", $id);
                    }
                }

                /* 批量移动分类 */
                if ($_POST['type'] == 'move_to') {
                    return check_authz_json('article_manage');
                    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                        return sys_msg($GLOBALS['_LANG']['no_select_article'], 1);
                    }

                    if (!$_POST['target_cat']) {
                        return sys_msg($GLOBALS['_LANG']['no_select_act'], 1);
                    }

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        $exc->edit("cat_id = '" . $_POST['target_cat'] . "'", $id);
                    }
                }
            }

            /* 清除缓存 */
            clear_cache_files();
            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'article.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['batch_handle_ok'], 0, $lnk);
        }
    }

    /* 把商品删除关联 */
    private function drop_link_goods($goods_id, $article_id)
    {
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') .
            " WHERE goods_id = '$goods_id' AND article_id = '$article_id' LIMIT 1";
        $GLOBALS['db']->query($sql);
        create_result(true, '', $goods_id);
    }

    /* 取得文章关联商品 */
    private function get_article_goods($article_id)
    {
        $list = [];
        $sql = 'SELECT g.goods_id, g.goods_name' .
            ' FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS ga' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id' .
            " WHERE ga.article_id = '$article_id'";
        $list = $GLOBALS['db']->getAll($sql);

        return $list;
    }

    /* 获得文章列表 */
    private function get_articleslist()
    {
        $result = get_filter();
        if ($result === false) {
            $filter = [];
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'a.article_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = '';
            if (!empty($filter['keyword'])) {
                $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            if ($filter['cat_id']) {
                $where .= " AND a." . get_article_children($filter['cat_id']);
            }

            /* 文章总数 */
            $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('article') . ' AS a ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('article_cat') . ' AS ac ON ac.cat_id = a.cat_id ' .
                'WHERE 1 ' . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 获取文章数据 */
            $sql = 'SELECT a.* , ac.cat_name ' .
                'FROM ' . $GLOBALS['ecs']->table('article') . ' AS a ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('article_cat') . ' AS ac ON ac.cat_id = a.cat_id ' .
                'WHERE 1 ' . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $arr = [];
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $rows) {
            $rows['date'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);

            $arr[] = $rows;
        }
        return ['arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /* 上传文件 */
    private function upload_article_file($upload)
    {
        if (!make_dir("../" . DATA_DIR . "/article")) {
            /* 创建目录失败 */
            return false;
        }

        $filename = Image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = ROOT_PATH . DATA_DIR . "/article/" . $filename;

        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/article/" . $filename;
        } else {
            return false;
        }
    }
}
