<?php

namespace app\console\controller;

use app\libraries\Exchange;

class Favourable extends Init
{
    public function index()
    {
        load_helper('goods');

        $exc = new Exchange($GLOBALS['ecs']->table('favourable_activity'), $GLOBALS['db'], 'act_id', 'act_name');

        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            admin_priv('favourable');

            /* 模板赋值 */
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['favourable_list']);
            $this->assign('action_link', ['href' => 'favourable.php?act=add', 'text' => $GLOBALS['_LANG']['add_favourable']]);

            $list = $this->favourable_list();

            $this->assign('favourable_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $GLOBALS['smarty']->display('favourable_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->favourable_list();

            $this->assign('favourable_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('favourable_list.htm'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('favourable');

            $id = intval($_GET['id']);
            $favourable = favourable_info($id);
            if (empty($favourable)) {
                return make_json_error($GLOBALS['_LANG']['favourable_not_exist']);
            }
            $name = $favourable['act_name'];
            $exc->drop($id);

            /* 记日志 */
            admin_log($name, 'remove', 'favourable');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'favourable.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('favourable');

                $ids = $_POST['checkboxes'];

                if (isset($_POST['drop'])) {
                    /* 删除记录 */
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('favourable_activity') .
                        " WHERE act_id " . db_create_in($ids);
                    $GLOBALS['db']->query($sql);

                    /* 记日志 */
                    admin_log('', 'batch_remove', 'favourable');

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_favourable_list'], 'href' => 'favourable.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok']);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_sort_order') {
            return check_authz_json('favourable');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $sql = "UPDATE " . $GLOBALS['ecs']->table('favourable_activity') .
                " SET sort_order = '$val'" .
                " WHERE act_id = '$id' LIMIT 1";
            $GLOBALS['db']->query($sql);

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('favourable');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->assign('form_action', $is_add ? 'insert' : 'update');

            /* 初始化、取得优惠活动信息 */
            if ($is_add) {
                $favourable = [
                    'act_id' => 0,
                    'act_name' => '',
                    'start_time' => date('Y-m-d', time() + 86400),
                    'end_time' => date('Y-m-d', time() + 4 * 86400),
                    'user_rank' => '',
                    'act_range' => FAR_ALL,
                    'act_range_ext' => '',
                    'min_amount' => 0,
                    'max_amount' => 0,
                    'act_type' => FAT_GOODS,
                    'act_type_ext' => 0,
                    'gift' => []
                ];
            } else {
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }
                $id = intval($_GET['id']);
                $favourable = favourable_info($id);
                if (empty($favourable)) {
                    return sys_msg($GLOBALS['_LANG']['favourable_not_exist']);
                }
            }
            $this->assign('favourable', $favourable);

            /* 取得用户等级 */
            $user_rank_list = [];
            $user_rank_list[] = [
                'rank_id' => 0,
                'rank_name' => $GLOBALS['_LANG']['not_user'],
                'checked' => strpos(',' . $favourable['user_rank'] . ',', ',0,') !== false
            ];
            $sql = "SELECT rank_id, rank_name FROM " . $GLOBALS['ecs']->table('user_rank');
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $row) {
                $row['checked'] = strpos(',' . $favourable['user_rank'] . ',', ',' . $row['rank_id'] . ',') !== false;
                $user_rank_list[] = $row;
            }
            $this->assign('user_rank_list', $user_rank_list);

            /* 取得优惠范围 */
            $act_range_ext = [];
            if ($favourable['act_range'] != FAR_ALL && !empty($favourable['act_range_ext'])) {
                if ($favourable['act_range'] == FAR_CATEGORY) {
                    $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $GLOBALS['ecs']->table('category') .
                        " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
                } elseif ($favourable['act_range'] == FAR_BRAND) {
                    $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $GLOBALS['ecs']->table('brand') .
                        " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
                } else {
                    $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $GLOBALS['ecs']->table('goods') .
                        " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
                }
                $act_range_ext = $GLOBALS['db']->getAll($sql);
            }
            $this->assign('act_range_ext', $act_range_ext);

            /* 赋值时间控件的语言 */
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            /* 显示模板 */
            if ($is_add) {
                $this->assign('ur_here', $GLOBALS['_LANG']['add_favourable']);
            } else {
                $this->assign('ur_here', $GLOBALS['_LANG']['edit_favourable']);
            }
            $href = 'favourable.php?act=list';
            if (!$is_add) {
                $href .= '&' . list_link_postfix();
            }
            $this->assign('action_link', ['href' => $href, 'text' => $GLOBALS['_LANG']['favourable_list']]);

            return $GLOBALS['smarty']->display('favourable_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('favourable');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';

            /* 检查名称是否重复 */
            $act_name = sub_str($_POST['act_name'], 255, false);
            if (!$exc->is_only('act_name', $act_name, intval($_POST['id']))) {
                return sys_msg($GLOBALS['_LANG']['act_name_exists']);
            }

            /* 检查享受优惠的会员等级 */
            if (!isset($_POST['user_rank'])) {
                return sys_msg($GLOBALS['_LANG']['pls_set_user_rank']);
            }

            /* 检查优惠范围扩展信息 */
            if (intval($_POST['act_range']) > 0 && !isset($_POST['act_range_ext'])) {
                return sys_msg($GLOBALS['_LANG']['pls_set_act_range']);
            }

            /* 检查金额上下限 */
            $min_amount = floatval($_POST['min_amount']) >= 0 ? floatval($_POST['min_amount']) : 0;
            $max_amount = floatval($_POST['max_amount']) >= 0 ? floatval($_POST['max_amount']) : 0;
            if ($max_amount > 0 && $min_amount > $max_amount) {
                return sys_msg($GLOBALS['_LANG']['amount_error']);
            }

            /* 取得赠品 */
            $gift = [];
            if (intval($_POST['act_type']) == FAT_GOODS && isset($_POST['gift_id'])) {
                foreach ($_POST['gift_id'] as $key => $id) {
                    $gift[] = ['id' => $id, 'name' => $_POST['gift_name'][$key], 'price' => $_POST['gift_price'][$key]];
                }
            }

            /* 提交值 */
            $favourable = [
                'act_id' => intval($_POST['id']),
                'act_name' => $act_name,
                'start_time' => local_strtotime($_POST['start_time']),
                'end_time' => local_strtotime($_POST['end_time']),
                'user_rank' => isset($_POST['user_rank']) ? join(',', $_POST['user_rank']) : '0',
                'act_range' => intval($_POST['act_range']),
                'act_range_ext' => intval($_POST['act_range']) == 0 ? '' : join(',', $_POST['act_range_ext']),
                'min_amount' => floatval($_POST['min_amount']),
                'max_amount' => floatval($_POST['max_amount']),
                'act_type' => intval($_POST['act_type']),
                'act_type_ext' => floatval($_POST['act_type_ext']),
                'gift' => serialize($gift)
            ];
            if ($favourable['act_type'] == FAT_GOODS) {
                $favourable['act_type_ext'] = round($favourable['act_type_ext']);
            }

            /* 保存数据 */
            if ($is_add) {
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('favourable_activity'), $favourable, 'INSERT');
                $favourable['act_id'] = $GLOBALS['db']->insert_id();
            } else {
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('favourable_activity'), $favourable, 'UPDATE', "act_id = '$favourable[act_id]'");
            }

            /* 记日志 */
            if ($is_add) {
                admin_log($favourable['act_name'], 'add', 'favourable');
            } else {
                admin_log($favourable['act_name'], 'edit', 'favourable');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            if ($is_add) {
                $links = [
                    ['href' => 'favourable.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_favourable']],
                    ['href' => 'favourable.php?act=list', 'text' => $GLOBALS['_LANG']['back_favourable_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['add_favourable_ok'], 0, $links);
            } else {
                $links = [
                    ['href' => 'favourable.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_favourable_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_favourable_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search') {
            /* 检查权限 */
            return check_authz_json('favourable');

            $filter = json_decode($_GET['JSON']);
            $filter->keyword = json_str_iconv($filter->keyword);
            if ($filter->act_range == FAR_ALL) {
                $arr[0] = [
                    'id' => 0,
                    'name' => $GLOBALS['_LANG']['js_languages']['all_need_not_search']
                ];
            } elseif ($filter->act_range == FAR_CATEGORY) {
                $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $GLOBALS['ecs']->table('category') .
                    " WHERE cat_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
                $arr = $GLOBALS['db']->getAll($sql);
            } elseif ($filter->act_range == FAR_BRAND) {
                $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $GLOBALS['ecs']->table('brand') .
                    " WHERE brand_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
                $arr = $GLOBALS['db']->getAll($sql);
            } else {
                $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%'" .
                    " OR goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
                $arr = $GLOBALS['db']->getAll($sql);
            }
            if (empty($arr)) {
                $arr = [0 => [
                    'id' => 0,
                    'name' => $GLOBALS['_LANG']['search_result_empty']
                ]];
            }

            return make_json_result($arr);
        }
    }

    /*
     * 取得优惠活动列表
     * @return   array
     */
    private function favourable_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['is_going'] = empty($_REQUEST['is_going']) ? 0 : 1;
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = "";
            if (!empty($filter['keyword'])) {
                $where .= " AND act_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            if ($filter['is_going']) {
                $now = gmtime();
                $where .= " AND start_time <= '$now' AND end_time >= '$now' ";
            }

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('favourable_activity') .
                " WHERE 1 $where";
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT * " .
                "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
                " WHERE 1 $where " .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ", $filter[page_size]";

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $GLOBALS['db']->query($sql);

        $list = [];
        foreach ($res as $row) {
            $row['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
            $row['end_time'] = local_date('Y-m-d H:i', $row['end_time']);

            $list[] = $row;
        }

        return ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
