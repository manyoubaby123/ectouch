<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Exchange;

class SnatchController extends InitController
{
    public function actionIndex()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("goods_activity"), $GLOBALS['db'], 'act_id', 'act_name');

        /*------------------------------------------------------ */
        //-- 添加活动
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            /* 初始化信息 */
            $start_time = local_date('Y-m-d H:i');
            $end_time = local_date('Y-m-d H:i', strtotime('+1 week'));
            $snatch = ['start_price' => '1.00', 'end_price' => '800.00', 'max_price' => '0', 'cost_points' => '1', 'start_time' => $start_time, 'end_time' => $end_time, 'option' => '<option value="0">' . $GLOBALS['_LANG']['make_option'] . '</option>'];

            $GLOBALS['smarty']->assign('snatch', $snatch);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['snatch_add']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list']);
            $GLOBALS['smarty']->assign('cat_list', cat_list());
            $GLOBALS['smarty']->assign('brand_list', get_brand_list());
            $GLOBALS['smarty']->assign('form_action', 'insert');

            return $GLOBALS['smarty']->display('snatch_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            /* 检查商品是否存在 */
            $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$_POST[goods_id]'";
            $_POST['goods_name'] = $GLOBALS['db']->getOne($sql);
            if (empty($_POST['goods_name'])) {
                return sys_msg($GLOBALS['_LANG']['no_goods'], 1);
            }

            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='" . $_POST['snatch_name'] . "'";
            if ($GLOBALS['db']->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $_POST['snatch_name']), 1);
            }

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['start_price'])) {
                $_POST['start_price'] = 0;
            }
            if (empty($_POST['end_price'])) {
                $_POST['end_price'] = 0;
            }
            if (empty($_POST['max_price'])) {
                $_POST['max_price'] = 0;
            }
            if (empty($_POST['cost_points'])) {
                $_POST['cost_points'] = 0;
            }
            if (isset($_POST['product_id']) && empty($_POST['product_id'])) {
                $_POST['product_id'] = 0;
            }

            $info = ['start_price' => $_POST['start_price'], 'end_price' => $_POST['end_price'], 'max_price' => $_POST['max_price'], 'cost_points' => $_POST['cost_points']];

            /* 插入数据 */
            $record = ['act_name' => $_POST['snatch_name'], 'act_desc' => $_POST['desc'],
                'act_type' => GAT_SNATCH, 'goods_id' => $_POST['goods_id'], 'goods_name' => $_POST['goods_name'],
                'start_time' => $_POST['start_time'], 'end_time' => $_POST['end_time'],
                'product_id' => $_POST['product_id'],
                'is_finished' => 0, 'ext_info' => serialize($info)];

            $GLOBALS['db']->AutoExecute($GLOBALS['ecs']->table('goods_activity'), $record, 'INSERT');

            admin_log($_POST['snatch_name'], 'add', 'snatch');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list'];
            $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'snatch.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 活动列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['02_snatch_list']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['snatch_add'], 'href' => 'snatch.php?act=add']);

            $snatchs = $this->get_snatchlist();

            $GLOBALS['smarty']->assign('snatch_list', $snatchs['snatchs']);
            $GLOBALS['smarty']->assign('filter', $snatchs['filter']);
            $GLOBALS['smarty']->assign('record_count', $snatchs['record_count']);
            $GLOBALS['smarty']->assign('page_count', $snatchs['page_count']);

            $sort_flag = sort_flag($snatchs['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            $GLOBALS['smarty']->assign('full_page', 1);

            return $GLOBALS['smarty']->display('snatch_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询、翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $snatchs = $this->get_snatchlist();

            $GLOBALS['smarty']->assign('snatch_list', $snatchs['snatchs']);
            $GLOBALS['smarty']->assign('filter', $snatchs['filter']);
            $GLOBALS['smarty']->assign('record_count', $snatchs['record_count']);
            $GLOBALS['smarty']->assign('page_count', $snatchs['page_count']);

            $sort_flag = sort_flag($snatchs['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('snatch_list.htm'),
                '',
                ['filter' => $snatchs['filter'], 'page_count' => $snatchs['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑活动名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_snatch_name') {
            return check_authz_json('snatch_manage');

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='$val' AND act_id <> '$id'";
            if ($GLOBALS['db']->getOne($sql)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $val));
            }

            $exc->edit("act_name='$val'", $id);
            return make_json_result(stripslashes($val));
        }

        /*------------------------------------------------------ */
        //-- 删除指定的活动
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('attr_manage');

            $id = intval($_GET['id']);

            $exc->drop($id);

            $url = 'snatch.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑活动
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            $snatch = $this->get_snatch_info($_REQUEST['id']);

            $snatch['option'] = '<option value="' . $snatch['goods_id'] . '">' . $snatch['goods_name'] . '</option>';
            $GLOBALS['smarty']->assign('snatch', $snatch);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['snatch_edit']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list&' . list_link_postfix()]);
            $GLOBALS['smarty']->assign('form_action', 'update');

            /* 商品货品表 */
            $GLOBALS['smarty']->assign('good_products_select', get_good_products_select($snatch['goods_id']));

            return $GLOBALS['smarty']->display('snatch_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            /* 将时间转换成整数 */
            $_POST['start_time'] = local_strtotime($_POST['start_time']);
            $_POST['end_time'] = local_strtotime($_POST['end_time']);

            /* 处理提交数据 */
            if (empty($_POST['snatch_name'])) {
                $_POST['snatch_name'] = '';
            }
            if (empty($_POST['goods_id'])) {
                $_POST['goods_id'] = 0;
            } else {
                $_POST['goods_name'] = $GLOBALS['db']->getOne("SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id= '$_POST[goods_id]'");
            }
            if (empty($_POST['start_price'])) {
                $_POST['start_price'] = 0;
            }
            if (empty($_POST['end_price'])) {
                $_POST['end_price'] = 0;
            }
            if (empty($_POST['max_price'])) {
                $_POST['max_price'] = 0;
            }
            if (empty($_POST['cost_points'])) {
                $_POST['cost_points'] = 0;
            }
            if (isset($_POST['product_id']) && empty($_POST['product_id'])) {
                $_POST['product_id'] = 0;
            }

            /* 检查活动重名 */
            $sql = "SELECT COUNT(*) " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type='" . GAT_SNATCH . "' AND act_name='" . $_POST['snatch_name'] . "' AND act_id <> '" . $_POST['id'] . "'";
            if ($GLOBALS['db']->getOne($sql)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['snatch_name_exist'], $_POST['snatch_name']), 1);
            }

            $info = ['start_price' => $_POST['start_price'], 'end_price' => $_POST['end_price'], 'max_price' => $_POST['max_price'], 'cost_points' => $_POST['cost_points']];

            /* 更新数据 */
            $record = ['act_name' => $_POST['snatch_name'], 'goods_id' => $_POST['goods_id'],
                'goods_name' => $_POST['goods_name'], 'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'], 'act_desc' => $_POST['desc'],
                'product_id' => $_POST['product_id'],
                'ext_info' => serialize($info)];
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_activity'), $record, 'UPDATE', "act_id = '" . $_POST['id'] . "' AND act_type = " . GAT_SNATCH);

            admin_log($_POST['snatch_name'], 'edit', 'snatch');
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'snatch.php?act=list&' . list_link_postfix()];
            return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 查看活动详情
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'view') {
            /* 权限判断 */
            admin_priv('snatch_manage');

            $id = empty($_REQUEST['snatch_id']) ? 0 : intval($_REQUEST['snatch_id']);

            $bid_list = $this->get_snatch_detail();

            $GLOBALS['smarty']->assign('bid_list', $bid_list['bid']);
            $GLOBALS['smarty']->assign('filter', $bid_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $bid_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $bid_list['page_count']);

            $sort_flag = sort_flag($bid_list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);
            /* 赋值 */
            $GLOBALS['smarty']->assign('info', $this->get_snatch_info($id));
            $GLOBALS['smarty']->assign('full_page', 1);
            $GLOBALS['smarty']->assign('result', get_snatch_result($id));
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['view_detail']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['02_snatch_list'], 'href' => 'snatch.php?act=list']);
            return $GLOBALS['smarty']->display('snatch_view.htm');
        }

        /*------------------------------------------------------ */
        //-- 排序、翻页活动详情
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query_bid') {
            $bid_list = $this->get_snatch_detail();

            $GLOBALS['smarty']->assign('bid_list', $bid_list['bid']);
            $GLOBALS['smarty']->assign('filter', $bid_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $bid_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $bid_list['page_count']);

            $sort_flag = sort_flag($bid_list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('snatch_view.htm'),
                '',
                ['filter' => $bid_list['filter'], 'page_count' => $bid_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search_goods') {
            $filters = json_decode($_GET['JSON']);

            $arr['goods'] = get_goods_list($filters);

            if (!empty($arr['goods'][0]['goods_id'])) {
                $arr['products'] = get_good_products($arr['goods'][0]['goods_id']);
            }

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 搜索货品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search_products') {
            $filters = json_decode($_GET['JSON']);

            if (!empty($filters->goods_id)) {
                $arr['products'] = get_good_products($filters->goods_id);
            }

            return make_json_result($arr);
        }
    }

    /**
     * 获取活动列表
     *
     * @access  public
     *
     * @return void
     */
    private function get_snatchlist()
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
                " WHERE act_type =" . GAT_SNATCH . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 获活动数据 */
            $sql = "SELECT act_id, act_name AS snatch_name, goods_name, start_time, end_time, is_finished, ext_info, product_id " .
                " FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type = " . GAT_SNATCH . $where .
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

        $arr = ['snatchs' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获取指定id snatch 的信息
     *
     * @access  public
     * @param   int $id snatch_id
     *
     * @return array       array(snatch_id, snatch_name, goods_id,start_time, end_time, min_price, integral)
     */
    private function get_snatch_info($id)
    {
        $sql = "SELECT act_id, act_name AS snatch_name, goods_id, product_id, goods_name, start_time, end_time, act_desc, ext_info" .
            " FROM " . $GLOBALS['ecs']->table('goods_activity') .
            " WHERE act_id='$id' AND act_type = " . GAT_SNATCH;

        $snatch = $GLOBALS['db']->getRow($sql);

        /* 将时间转成可阅读格式 */
        $snatch['start_time'] = local_date('Y-m-d H:i', $snatch['start_time']);
        $snatch['end_time'] = local_date('Y-m-d H:i', $snatch['end_time']);
        $row = unserialize($snatch['ext_info']);
        unset($snatch['ext_info']);
        if ($row) {
            foreach ($row as $key => $val) {
                $snatch[$key] = $val;
            }
        }

        return $snatch;
    }

    /**
     * 返回活动详细列表
     *
     * @access  public
     *
     * @return array
     */
    private function get_snatch_detail()
    {
        $filter['snatch_id'] = empty($_REQUEST['snatch_id']) ? 0 : intval($_REQUEST['snatch_id']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bid_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = empty($filter['snatch_id']) ? '' : " WHERE snatch_id='$filter[snatch_id]'";

        /* 获得记录总数以及总页数 */
        $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('snatch_log') . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得活动数据 */
        $sql = "SELECT s.log_id, u.user_name, s.bid_price, s.bid_time " .
            " FROM " . $GLOBALS['ecs']->table('snatch_log') . " AS s " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON s.user_id = u.user_id  " . $where .
            " ORDER by " . $filter['sort_by'] . " " . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ", " . $filter['page_size'];
        $row = $GLOBALS['db']->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['bid_time'] = date($GLOBALS['_CFG']['time_format'], $val['bid_time']);
        }

        $arr = ['bid' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
