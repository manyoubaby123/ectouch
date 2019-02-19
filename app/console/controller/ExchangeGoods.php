<?php

namespace app\console\controller;

use app\libraries\Exchange;

class ExchangeGoods extends Init
{
    public function index()
    {

        /*初始化数据交换对象 */
        $exc = new Exchange($GLOBALS['ecs']->table("exchange_goods"), $GLOBALS['db'], 'goods_id', 'exchange_integral');

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /* 取得过滤条件 */
            $filter = [];
            $this->assign('ur_here', $GLOBALS['_LANG']['15_exchange_goods_list']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add']);
            $this->assign('full_page', 1);
            $this->assign('filter', $filter);

            $goods_list = $this->get_exchange_goodslist();

            $this->assign('goods_list', $goods_list['arr']);
            $this->assign('filter', $goods_list['filter']);
            $this->assign('record_count', $goods_list['record_count']);
            $this->assign('page_count', $goods_list['page_count']);

            $sort_flag = sort_flag($goods_list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return $this->fetch('exchange_goods_list');
        }

        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            return check_authz_json('exchange_goods');

            $goods_list = $this->get_exchange_goodslist();

            $this->assign('goods_list', $goods_list['arr']);
            $this->assign('filter', $goods_list['filter']);
            $this->assign('record_count', $goods_list['record_count']);
            $this->assign('page_count', $goods_list['page_count']);

            $sort_flag = sort_flag($goods_list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->display('exchange_goods_list'),
                '',
                ['filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /*初始化*/
            $goods = [];
            $goods['is_exchange'] = 1;
            $goods['is_hot'] = 0;
            $goods['option'] = '<option value="0">' . $GLOBALS['_LANG']['make_option'] . '</option>';

            $this->assign('goods', $goods);
            $this->assign('ur_here', $GLOBALS['_LANG']['exchange_goods_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list']);
            $this->assign('form_action', 'insert');

            return $this->fetch('exchange_goods_info');
        }

        /*------------------------------------------------------ */
        //-- 添加商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /*检查是否重复*/
            $is_only = $exc->is_only('goods_id', $_POST['goods_id'], 0, " goods_id ='$_POST[goods_id]'");

            if (!$is_only) {
                return sys_msg($GLOBALS['_LANG']['goods_exist'], 1);
            }

            /*插入数据*/
            $add_time = gmtime();
            if (empty($_POST['goods_id'])) {
                $_POST['goods_id'] = 0;
            }
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('exchange_goods') . "(goods_id, exchange_integral, is_exchange, is_hot) " .
                "VALUES ('$_POST[goods_id]', '$_POST[exchange_integral]', '$_POST[is_exchange]', '$_POST[is_hot]')";
            $GLOBALS['db']->query($sql);

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'exchange_goods.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'exchange_goods.php?act=list';

            admin_log($_POST['goods_id'], 'add', 'exchange_goods');

            clear_cache_files(); // 清除相关的缓存文件

            return sys_msg($GLOBALS['_LANG']['articleadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            /* 取商品数据 */
            $sql = "SELECT eg.goods_id, eg.exchange_integral,eg.is_exchange, eg.is_hot, g.goods_name " .
                " FROM " . $GLOBALS['ecs']->table('exchange_goods') . " AS eg " .
                "  LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON g.goods_id = eg.goods_id " .
                " WHERE eg.goods_id='$_REQUEST[id]'";
            $goods = $GLOBALS['db']->getRow($sql);
            $goods['option'] = '<option value="' . $goods['goods_id'] . '">' . $goods['goods_name'] . '</option>';

            $this->assign('goods', $goods);
            $this->assign('ur_here', $GLOBALS['_LANG']['exchange_goods_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix()]);
            $this->assign('form_action', 'update');

            return $this->fetch('exchange_goods_info');
        }

        /*------------------------------------------------------ */
        //-- 编辑
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('exchange_goods');

            if (empty($_POST['goods_id'])) {
                $_POST['goods_id'] = 0;
            }

            if ($exc->edit("exchange_integral='$_POST[exchange_integral]', is_exchange='$_POST[is_exchange]', is_hot='$_POST[is_hot]' ", $_POST['goods_id'])) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'exchange_goods.php?act=list&' . list_link_postfix();

                admin_log($_POST['goods_id'], 'edit', 'exchange_goods');

                clear_cache_files();
                return sys_msg($GLOBALS['_LANG']['articleedit_succeed'], 0, $link);
            } else {
                return $GLOBALS['db']->error();
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑使用积分值
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_exchange_integral') {
            return check_authz_json('exchange_goods');

            $id = intval($_POST['id']);
            $exchange_integral = floatval($_POST['val']);

            /* 检查文章标题是否重复 */
            if ($exchange_integral < 0 || $exchange_integral == 0 && $_POST['val'] != "$goods_price") {
                return make_json_error($GLOBALS['_LANG']['exchange_integral_invalid']);
            } else {
                if ($exc->edit("exchange_integral = '$exchange_integral'", $id)) {
                    clear_cache_files();
                    admin_log($id, 'edit', 'exchange_goods');
                    return make_json_result(stripslashes($exchange_integral));
                } else {
                    return make_json_error($GLOBALS['db']->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否兑换
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'toggle_exchange') {
            return check_authz_json('exchange_goods');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_exchange = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换是否兑换
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'toggle_hot') {
            return check_authz_json('exchange_goods');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("is_hot = '$val'", $id);
            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 批量删除商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch_remove') {
            admin_priv('exchange_goods');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_select_goods'], 1);
            }

            $count = 0;
            foreach ($_POST['checkboxes'] as $key => $id) {
                if ($exc->drop($id)) {
                    admin_log($id, 'remove', 'exchange_goods');
                    $count++;
                }
            }

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'exchange_goods.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_succeed'], $count), 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 删除商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('exchange_goods');

            $id = intval($_GET['id']);
            if ($exc->drop($id)) {
                admin_log($id, 'remove', 'article');
                clear_cache_files();
            }

            $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search_goods') {
            $filters = json_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            return make_json_result($arr);
        }
    }

    /* 获得商品列表 */
    private function get_exchange_goodslist()
    {
        $result = get_filter();
        if ($result === false) {
            $filter = [];
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'eg.goods_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = '';
            if (!empty($filter['keyword'])) {
                $where = " AND g.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }

            /* 文章总数 */
            $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = eg.goods_id ' .
                'WHERE 1 ' . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 获取文章数据 */
            $sql = 'SELECT eg.* , g.goods_name ' .
                'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = eg.goods_id ' .
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
            $arr[] = $rows;
        }
        return ['arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
