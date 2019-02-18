<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Exchange;

class Navigator extends Init
{
    public function index()
    {
        admin_priv('navigator');

        $exc = new Exchange($GLOBALS['ecs']->table("nav"), $GLOBALS['db'], 'id', 'name');

        /*------------------------------------------------------ */
        //-- 自定义导航栏列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['navigator']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'navigator.php?act=add']);
            $GLOBALS['smarty']->assign('full_page', 1);

            $navdb = $this->get_nav();

            $GLOBALS['smarty']->assign('navdb', $navdb['navdb']);
            $GLOBALS['smarty']->assign('filter', $navdb['filter']);
            $GLOBALS['smarty']->assign('record_count', $navdb['record_count']);
            $GLOBALS['smarty']->assign('page_count', $navdb['page_count']);

            return $GLOBALS['smarty']->display('navigator.htm');
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏列表Ajax
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $navdb = $this->get_nav();
            $GLOBALS['smarty']->assign('navdb', $navdb['navdb']);
            $GLOBALS['smarty']->assign('filter', $navdb['filter']);
            $GLOBALS['smarty']->assign('record_count', $navdb['record_count']);
            $GLOBALS['smarty']->assign('page_count', $navdb['page_count']);

            $sort_flag = sort_flag($navdb['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($GLOBALS['smarty']->fetch('navigator.htm'), '', ['filter' => $navdb['filter'], 'page_count' => $navdb['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏增加
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'add'];

                $sysmain = $this->get_sysnav();

                $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'navigator.php?act=list']);
                $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $GLOBALS['smarty']->assign('sysmain', $sysmain);
                $GLOBALS['smarty']->assign('rt', $rt);
                return $GLOBALS['smarty']->display('navigator_add.htm');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];

                $vieworder = $GLOBALS['db']->getOne("SELECT max(vieworder) FROM " . $GLOBALS['ecs']->table('nav') . " WHERE type = '" . $item_type . "'");

                $item_vieworder = empty($_REQUEST['item_vieworder']) ? $vieworder + 1 : $_REQUEST['item_vieworder'];

                if ($item_ifshow == 1 && $item_type == 'middle') {
                    //如果设置为在中部显示

                    $arr = $this->analyse_uri($item_url);  //分析URI
                    if ($arr) {
                        //如果为分类
                        $this->set_show_in_nav($arr['type'], $arr['id'], 1);   //设置显示
                        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('nav') . " (name,ctype,cid,ifshow,vieworder,opennew,url,type) VALUES('$item_name','" . $arr['type'] . "','" . $arr['id'] . "','$item_ifshow','$item_vieworder','$item_opennew','$item_url','$item_type')";
                    }
                }

                if (empty($sql)) {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('nav') . " (name,ifshow,vieworder,opennew,url,type) VALUES('$item_name','$item_ifshow','$item_vieworder','$item_opennew','$item_url','$item_type')";
                }
                $GLOBALS['db']->query($sql);
                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'navigator.php?act=list'];
                $links[] = ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'navigator.php?act=add'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            $id = $_REQUEST['id'];
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'edit', 'id' => $id];
                $row = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('nav') . " WHERE id='$id'");
                $rt['item_name'] = $row['name'];
                $rt['item_url'] = $row['url'];
                $rt['item_vieworder'] = $row['vieworder'];
                $rt['item_ifshow_' . $row['ifshow']] = 'selected';
                $rt['item_opennew_' . $row['opennew']] = 'selected';
                $rt['item_type_' . $row['type']] = 'selected';

                $sysmain = $this->get_sysnav();

                $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'navigator.php?act=list']);
                $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $GLOBALS['smarty']->assign('sysmain', $sysmain);
                $GLOBALS['smarty']->assign('rt', $rt);
                return $GLOBALS['smarty']->display('navigator_add.htm');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_vieworder = (int)$_REQUEST['item_vieworder'];

                $row = $GLOBALS['db']->getRow("SELECT ctype,cid,ifshow,type FROM " . $GLOBALS['ecs']->table('nav') . " WHERE id = '$id'");
                $arr = $this->analyse_uri($item_url);

                if ($arr) {
                    //目标为分类
                    if ($row['ctype'] == $arr['type'] && $row['cid'] == $arr['id']) {
                        //没有修改分类
                        if ($item_type != 'middle') {
                            //位置不在中部
                            $this->set_show_in_nav($arr['type'], $arr['id'], 0);
                        }
                    } else {
                        //修改了分类
                        if ($row['ifshow'] == 1 && $row['type'] == 'middle') {
                            //原来在中部显示
                            $this->set_show_in_nav($row['ctype'], $row['cid'], 0); //设置成不显示
                        } elseif ($row['ifshow'] == 0 && $row['type'] == 'middle') {
                            //原来不显示
                        }
                    }

                    //分类判断
                    if ($item_ifshow != $this->is_show_in_nav($arr['type'], $arr['id']) && $item_type == 'middle') {
                        $this->set_show_in_nav($arr['type'], $arr['id'], $item_ifshow);
                    }
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('nav') .
                        " SET name='$item_name',ctype='" . $arr['type'] . "',cid='" . $arr['id'] . "',ifshow='$item_ifshow',vieworder='$item_vieworder',opennew='$item_opennew',url='$item_url',type='$item_type' WHERE id='$id'";
                } else {
                    //目标不是分类
                    if ($row['ctype'] && $row['cid']) {
                        //原来是分类
                        $this->set_show_in_nav($row['ctype'], $row['cid'], 0);
                    }

                    $sql = "UPDATE " . $GLOBALS['ecs']->table('nav') .
                        " SET name='$item_name',ctype='',cid='',ifshow='$item_ifshow',vieworder='$item_vieworder',opennew='$item_opennew',url='$item_url',type='$item_type' WHERE id='$id'";
                }

                $GLOBALS['db']->query($sql);
                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'navigator.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏删除
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'del') {
            $id = (int)$_GET['id'];
            $row = $GLOBALS['db']->getRow("SELECT ctype,cid,type FROM " . $GLOBALS['ecs']->table('nav') . " WHERE id = '$id' LIMIT 1");

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->set_show_in_nav($row['ctype'], $row['cid'], 0);
            }

            $sql = " DELETE FROM " . $GLOBALS['ecs']->table('nav') . " WHERE id='$id' LIMIT 1";
            $GLOBALS['db']->query($sql);
            clear_cache_files();
            return ecs_header("Location: navigator.php?act=list\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_sort_order') {
            return check_authz_json('nav');

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                if ($exc->edit("vieworder = '$order'", $id)) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_ifshow') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $row = $GLOBALS['db']->getRow("SELECT type,ctype,cid FROM " . $GLOBALS['ecs']->table('nav') . " WHERE id = '$id' LIMIT 1");

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->set_show_in_nav($row['ctype'], $row['cid'], $val);
            }

            if ($this->nav_update($id, ['ifshow' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否新窗口
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_opennew') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->nav_update($id, ['opennew' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }
    }

    private function get_nav()
    {
        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'type DESC, vieworder' : 'type DESC, ' . trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('nav');
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT id, name, ifshow, vieworder, opennew, url, type" .
                " FROM " . $GLOBALS['ecs']->table('nav') .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $navdb = $GLOBALS['db']->getAll($sql);

        $type = "";
        $navdb2 = [];
        foreach ($navdb as $k => $v) {
            if (!empty($type) && $type != $v['type']) {
                $navdb2[] = [];
            }
            $navdb2[] = $v;
            $type = $v['type'];
        }

        $arr = ['navdb' => $navdb2, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*------------------------------------------------------ */
    //-- 排序相关
    /*------------------------------------------------------ */
    private function sort_nav($a, $b)
    {
        return $a['vieworder'] > $b['vieworder'] ? 1 : -1;
    }

    /*------------------------------------------------------ */
    //-- 获得系统列表
    /*------------------------------------------------------ */
    private function get_sysnav()
    {
        $sysmain = [
            [$GLOBALS['_LANG']['view_cart'], 'flow.php'],
            [$GLOBALS['_LANG']['pick_out'], 'pick_out.php'],
            [$GLOBALS['_LANG']['group_buy_goods'], 'group_buy.php'],
            [$GLOBALS['_LANG']['snatch'], 'snatch.php'],
            [$GLOBALS['_LANG']['tag_cloud'], 'tag_cloud.php'],
            [$GLOBALS['_LANG']['user_center'], 'user.php'],
            [$GLOBALS['_LANG']['wholesale'], 'wholesale.php'],
            [$GLOBALS['_LANG']['activity'], 'activity.php'],
            [$GLOBALS['_LANG']['myship'], 'myship.php'],
            [$GLOBALS['_LANG']['message_board'], 'message.php'],
            [$GLOBALS['_LANG']['quotation'], 'quotation.php'],
        ];

        $sysmain[] = ['-', '-'];

        $catlist = array_merge(cat_list(0, 0, false), ['-'], article_cat_list(0, 0, false));
        foreach ($catlist as $key => $val) {
            $val['view_name'] = $val['cat_name'];
            for ($i = 0; $i < $val['level']; $i++) {
                $val['view_name'] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $val['view_name'];
            }
            $val['url'] = str_replace('&amp;', '&', $val['url']);
            $val['url'] = str_replace('&', '&amp;', $val['url']);
            $sysmain[] = [$val['cat_name'], $val['url'], $val['view_name']];
        }
        return $sysmain;
    }

    /*------------------------------------------------------ */
    //-- 列表项修改
    /*------------------------------------------------------ */
    private function nav_update($id, $args)
    {
        if (empty($args) || empty($id)) {
            return false;
        }

        return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('nav'), $args, 'update', "id='$id'");
    }

    /*------------------------------------------------------ */
    //-- 根据URI对导航栏项目进行分析，确定其为商品分类还是文章分类
    /*------------------------------------------------------ */
    private function analyse_uri($uri)
    {
        $uri = strtolower(str_replace('&amp;', '&', $uri));
        $arr = explode('-', $uri);
        switch ($arr[0]) {
            case 'category':
                return ['type' => 'c', 'id' => $arr[1]];
                break;
            case 'article_cat':
                return ['type' => 'a', 'id' => $arr[1]];
                break;
            default:

                break;
        }

        list($fn, $pm) = explode('?', $uri);

        if (strpos($uri, '&') === false) {
            $arr = [$pm];
        } else {
            $arr = explode('&', $pm);
        }
        switch ($fn) {
            case 'category.php':
                //商品分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
                    if ($key == 'id') {
                        return ['type' => 'c', 'id' => $val];
                    }
                }
                break;
            case 'article_cat.php':
                //文章分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
                    if ($key == 'id') {
                        return ['type' => 'a', 'id' => $val];
                    }
                }
                break;
            default:
                //未知
                return false;
                break;
        }
    }

    /*------------------------------------------------------ */
    //-- 是否显示
    /*------------------------------------------------------ */
    private function is_show_in_nav($type, $id)
    {
        if ($type == 'c') {
            $tablename = $GLOBALS['ecs']->table('category');
        } else {
            $tablename = $GLOBALS['ecs']->table('article_cat');
        }
        return $GLOBALS['db']->getOne("SELECT show_in_nav FROM $tablename WHERE cat_id = '$id'");
    }

    /*------------------------------------------------------ */
    //-- 设置是否显示
    /*------------------------------------------------------ */
    private function set_show_in_nav($type, $id, $val)
    {
        if ($type == 'c') {
            $tablename = $GLOBALS['ecs']->table('category');
        } else {
            $tablename = $GLOBALS['ecs']->table('article_cat');
        }
        $GLOBALS['db']->query("UPDATE $tablename SET show_in_nav = '$val' WHERE cat_id = '$id'");
        clear_cache_files();
    }
}
