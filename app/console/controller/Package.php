<?php

namespace app\console\controller;

use app\libraries\Exchange;

class Package extends Init
{
    public function index()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("goods_activity"), $GLOBALS['db'], 'act_id', 'act_name');

        /*------------------------------------------------------ */
        //-- 添加活动
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('package_manage');

            /* 组合商品 */
            $group_goods_list = [];
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('package_goods') .
                " WHERE package_id = 0 AND admin_id = '". session('admin_id') ."'";

            $GLOBALS['db']->query($sql);

            /* 初始化信息 */
            $start_time = local_date('Y-m-d H:i');
            $end_time = local_date('Y-m-d H:i', strtotime('+1 month'));
            $package = ['package_price' => '', 'start_time' => $start_time, 'end_time' => $end_time];

            $this->assign('package', $package);
            $this->assign('ur_here', $GLOBALS['_LANG']['package_add']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list']);
            $this->assign('cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());
            $this->assign('form_action', 'insert');

            return $this->fetch('package_info');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('package_manage');

            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "'";
            if ($GLOBALS['db']->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            $info = ['package_price' => $_POST['package_price']];

            /* 插入数据 */
            $record = ['act_name' => $_POST['package_name'], 'act_desc' => $_POST['desc'],
                'act_type' => GAT_PACKAGE, 'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'], 'is_finished' => 0, 'ext_info' => serialize($info)];

            $GLOBALS['db']->AutoExecute($GLOBALS['ecs']->table('goods_activity'), $record, 'INSERT');

            /* 礼包编号 */
            $package_id = $GLOBALS['db']->insert_id();

            $this->handle_packagep_goods($package_id);

            admin_log($_POST['package_name'], 'add', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list'];
            $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'package.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑活动
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('package_manage');

            $package = get_package_info($_REQUEST['id']);
            $package_goods_list = get_package_goods($_REQUEST['id']); // 礼包商品

            $this->assign('package', $package);
            $this->assign('ur_here', $GLOBALS['_LANG']['package_edit']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['14_package_list'], 'href' => 'package.php?act=list&' . list_link_postfix()]);
            $this->assign('cat_list', cat_list());
            $this->assign('brand_list', get_brand_list());
            $this->assign('form_action', 'update');
            $this->assign('package_goods_list', $package_goods_list);

            return $this->fetch('package_info');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('package_manage');

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['package_price'])) {
                $_POST['package_price'] = 0;
            }

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "' AND act_id <> '" . $_POST['id'] . "'";
            if ($GLOBALS['db']->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['package_exist'], $_POST['package_name']), 1);
            }

            $info = ['package_price' => $_POST['package_price']];

            /* 更新数据 */
            $record = ['act_name' => $_POST['package_name'], 'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'act_desc' => $_POST['desc'], 'ext_info' => serialize($info)];
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_activity'), $record, 'UPDATE', "act_id = '" . $_POST['id'] . "' AND act_type = " . GAT_PACKAGE);

            admin_log($_POST['package_name'], 'edit', 'package');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'package.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除指定的活动
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('package_manage');

            $id = intval($_GET['id']);

            $exc->drop($id);

            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('package_goods') .
                " WHERE package_id='$id'";
            $GLOBALS['db']->query($sql);

            $url = 'package.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->assign('ur_here', $GLOBALS['_LANG']['14_package_list']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['package_add'], 'href' => 'package.php?act=add']);

            $packages = $this->get_packagelist();

            $this->assign('package_list', $packages['packages']);
            $this->assign('filter', $packages['filter']);
            $this->assign('record_count', $packages['record_count']);
            $this->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            $this->assign('full_page', 1);

            return $this->fetch('package_list');
        }

        /*------------------------------------------------------ */
        //-- 查询、翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $packages = $this->get_packagelist();

            $this->assign('package_list', $packages['packages']);
            $this->assign('filter', $packages['filter']);
            $this->assign('record_count', $packages['record_count']);
            $this->assign('page_count', $packages['page_count']);

            $sort_flag = sort_flag($packages['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->display('package_list'),
                '',
                ['filter' => $packages['filter'], 'page_count' => $packages['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑活动名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_package_name') {
            return check_authz_json('package_manage');

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='$val' AND act_id <> '$id'";
            if ($GLOBALS['db']->getOne($sql)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['package_exist'], $val));
            }

            $exc->edit("act_name='$val'", $id);
            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search_goods') {
            $filters = json_decode($_GET['JSON']);

            $arr = get_goods_list($filters);

            $opt = [];
            foreach ($arr as $key => $val) {
                $opt[$key] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => $val['shop_price']];

                $opt[$key]['products'] = get_good_products($val['goods_id']);
            }

            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品，仅返回名称及ID
        /*------------------------------------------------------ */

        //if ($_REQUEST['act'] == 'get_goods_list')
        //{

//
//    $filters = json_decode($_GET['JSON']);
//
//    $arr = get_goods_list($filters);
//
//    $opt = array();
//    foreach ($arr AS $key => $val)
//    {
//        $opt[$key] = array('value' => $val['goods_id'],
//                        'text' => $val['goods_name'],
//                        'data' => $val['shop_price']);
//
//        $opt[$key]['products'] = get_good_products($val['goods_id']);
//    }
//
//    return make_json_result($opt);
        //}

        /*------------------------------------------------------ */
        //-- 增加一个商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add_package_goods') {
            return check_authz_json('package_manage');

            $fittings = json_decode($_GET['add_ids']);
            $arguments = json_decode($_GET['JSON']);
            $package_id = $arguments[0];
            $number = $arguments[1];

            foreach ($fittings as $val) {
                $val_array = explode('_', $val);
                if (!isset($val_array[1]) || $val_array[1] <= 0) {
                    $val_array[1] = 0;
                }

                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('package_goods') . " (package_id, goods_id, product_id, goods_number, admin_id) " .
                    "VALUES ('$package_id', '" . $val_array[0] . "', '" . $val_array[1] . "', '$number', '". session('admin_id') ."')";
                $GLOBALS['db']->query($sql, 'SILENT');
            }

            $arr = get_package_goods($package_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['g_p'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt);
        }

        /*------------------------------------------------------ */
        //-- 删除一个商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'drop_package_goods') {
            return check_authz_json('package_manage');

            $fittings = json_decode($_GET['drop_ids']);
            $arguments = json_decode($_GET['JSON']);
            $package_id = $arguments[0];

            $goods = [];
            $g_p = [];
            foreach ($fittings as $val) {
                $val_array = explode('_', $val);
                if (isset($val_array[1]) && $val_array[1] > 0) {
                    $g_p['product_id'][] = $val_array[1];
                    $g_p['goods_id'][] = $val_array[0];
                } else {
                    $goods[] = $val_array[0];
                }
            }

            if (!empty($goods)) {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('package_goods') .
                    " WHERE package_id='$package_id' AND " . db_create_in($goods, 'goods_id');
                if ($package_id == 0) {
                    $sql .= " AND admin_id = '". session('admin_id') ."'";
                }
                $GLOBALS['db']->query($sql);
            }

            if (!empty($g_p)) {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('package_goods') .
                    " WHERE package_id='$package_id' AND " . db_create_in($g_p['goods_id'], 'goods_id') . " AND " . db_create_in($g_p['product_id'], 'product_id');
                if ($package_id == 0) {
                    $sql .= " AND admin_id = '". session('admin_id') ."'";
                }
                $GLOBALS['db']->query($sql);
            }

            $arr = get_package_goods($package_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            clear_cache_files();
            return make_json_result($opt);
        }
    }

    /**
     * 获取活动列表
     *
     * @access  public
     *
     * @return void
     */
    private function get_packagelist()
    {
        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = (!empty($filter['keywords'])) ? " AND act_name like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type =" . GAT_PACKAGE . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 获活动数据 */
            $sql = "SELECT act_id, act_name AS package_name, start_time, end_time, is_finished, ext_info " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type = " . GAT_PACKAGE . $where .
                " ORDER by $filter[sort_by] $filter[sort_order] LIMIT " . $filter['start'] . ", " . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['start_time']);
            $row[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['end_time']);
            $info = unserialize($row[$key]['ext_info']);
            unset($row[$key]['ext_info']);
            if ($info) {
                foreach ($info as $info_key => $info_val) {
                    $row[$key][$info_key] = $info_val;
                }
            }
        }

        $arr = ['packages' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 保存某礼包的商品
     * @param   int $package_id
     * @return  void
     */
    private function handle_packagep_goods($package_id)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('package_goods') . " SET " .
            " package_id = '$package_id' " .
            " WHERE package_id = '0'" .
            " AND admin_id = '". session('admin_id') ."'";
        $GLOBALS['db']->query($sql);
    }
}
