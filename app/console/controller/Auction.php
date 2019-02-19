<?php

namespace app\console\controller;

use app\libraries\Exchange;

class Auction extends Init
{
    public function index()
    {
        load_helper('goods');

        $exc = new Exchange($GLOBALS['ecs']->table('goods_activity'), $GLOBALS['db'], 'act_id', 'act_name');

        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('auction');

            /* 模板赋值 */
            $this->assign('full_page', 1);
            $this->assign('ur_here', $GLOBALS['_LANG']['auction_list']);
            $this->assign('action_link', ['href' => 'auction.php?act=add', 'text' => $GLOBALS['_LANG']['add_auction']]);

            $list = $this->auction_list();

            $this->assign('auction_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->fetch('auction_list');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->auction_list();

            $this->assign('auction_list', $list['item']);
            $this->assign('filter', $list['filter']);
            $this->assign('record_count', $list['record_count']);
            $this->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $GLOBALS['smarty']->display('auction_list'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('auction');

            $id = intval($_GET['id']);
            $auction = auction_info($id);
            if (empty($auction)) {
                return make_json_error($GLOBALS['_LANG']['auction_not_exist']);
            }
            if ($auction['bid_user_count'] > 0) {
                return make_json_error($GLOBALS['_LANG']['auction_cannot_remove']);
            }
            $name = $auction['act_name'];
            $exc->drop($id);

            /* 记日志 */
            admin_log($name, 'remove', 'auction');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'auction.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
                admin_priv('auction');

                $ids = $_POST['checkboxes'];

                if (isset($_POST['drop'])) {
                    /* 查询哪些拍卖活动已经有人出价 */
                    $sql = "SELECT DISTINCT act_id FROM " . $GLOBALS['ecs']->table('auction_log') .
                        " WHERE act_id " . db_create_in($ids);
                    $ids = array_diff($ids, $GLOBALS['db']->getCol($sql));
                    if (!empty($ids)) {
                        /* 删除记录 */
                        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_activity') .
                            " WHERE act_id " . db_create_in($ids) .
                            " AND act_type = '" . GAT_AUCTION . "'";
                        $GLOBALS['db']->query($sql);

                        /* 记日志 */
                        admin_log('', 'batch_remove', 'auction');

                        /* 清除缓存 */
                        clear_cache_files();
                    }
                    $links[] = ['text' => $GLOBALS['_LANG']['back_auction_list'], 'href' => 'auction.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 查看出价记录
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'view_log') {
            /* 检查权限 */
            admin_priv('auction');

            /* 参数 */
            if (empty($_GET['id'])) {
                return sys_msg('invalid param');
            }
            $id = intval($_GET['id']);
            $auction = auction_info($id);
            if (empty($auction)) {
                return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
            }
            $this->assign('auction', auction_info($id));

            /* 出价记录 */
            $this->assign('auction_log', auction_log($id));

            /* 模板赋值 */
            $this->assign('ur_here', $GLOBALS['_LANG']['auction_log']);
            $this->assign('action_link', ['href' => 'auction.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['auction_list']]);

            return $this->fetch('auction_log');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('auction');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->assign('form_action', $is_add ? 'insert' : 'update');

            /* 初始化、取得拍卖活动信息 */
            if ($is_add) {
                $auction = [
                    'act_id' => 0,
                    'act_name' => '',
                    'act_desc' => '',
                    'goods_id' => 0,
                    'product_id' => 0,
                    'goods_name' => $GLOBALS['_LANG']['pls_search_goods'],
                    'start_time' => date('Y-m-d', time() + 86400),
                    'end_time' => date('Y-m-d', time() + 4 * 86400),
                    'deposit' => 0,
                    'start_price' => 0,
                    'end_price' => 0,
                    'amplitude' => 0
                ];
            } else {
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }
                $id = intval($_GET['id']);
                $auction = auction_info($id, true);
                if (empty($auction)) {
                    return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
                }
                $auction['status'] = $GLOBALS['_LANG']['auction_status'][$auction['status_no']];
                $this->assign('bid_user_count', sprintf($GLOBALS['_LANG']['bid_user_count'], $auction['bid_user_count']));
            }
            $this->assign('auction', $auction);

            /* 赋值时间控件的语言 */
            $this->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            /* 商品货品表 */
            $this->assign('good_products_select', get_good_products_select($auction['goods_id']));

            /* 显示模板 */
            if ($is_add) {
                $this->assign('ur_here', $GLOBALS['_LANG']['add_auction']);
            } else {
                $this->assign('ur_here', $GLOBALS['_LANG']['edit_auction']);
            }
            $this->assign('action_link', $this->list_link($is_add));

            return $this->fetch('auction_info');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('auction');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';

            /* 检查是否选择了商品 */
            $goods_id = intval($_POST['goods_id']);
            if ($goods_id <= 0) {
                return sys_msg($GLOBALS['_LANG']['pls_select_goods']);
            }
            $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$goods_id'";
            $row = $GLOBALS['db']->getRow($sql);
            if (empty($row)) {
                return sys_msg($GLOBALS['_LANG']['goods_not_exist']);
            }
            $goods_name = $row['goods_name'];

            /* 提交值 */
            $auction = [
                'act_id' => intval($_POST['id']),
                'act_name' => empty($_POST['act_name']) ? $goods_name : sub_str($_POST['act_name'], 255, false),
                'act_desc' => $_POST['act_desc'],
                'act_type' => GAT_AUCTION,
                'goods_id' => $goods_id,
                'product_id' => empty($_POST['product_id']) ? 0 : $_POST['product_id'],
                'goods_name' => $goods_name,
                'start_time' => local_strtotime($_POST['start_time']),
                'end_time' => local_strtotime($_POST['end_time']),
                'ext_info' => serialize([
                    'deposit' => round(floatval($_POST['deposit']), 2),
                    'start_price' => round(floatval($_POST['start_price']), 2),
                    'end_price' => empty($_POST['no_top']) ? round(floatval($_POST['end_price']), 2) : 0,
                    'amplitude' => round(floatval($_POST['amplitude']), 2),
                    'no_top' => !empty($_POST['no_top']) ? intval($_POST['no_top']) : 0
                ])
            ];

            /* 保存数据 */
            if ($is_add) {
                $auction['is_finished'] = 0;
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_activity'), $auction, 'INSERT');
                $auction['act_id'] = $GLOBALS['db']->insert_id();
            } else {
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_activity'), $auction, 'UPDATE', "act_id = '$auction[act_id]'");
            }

            /* 记日志 */
            if ($is_add) {
                admin_log($auction['act_name'], 'add', 'auction');
            } else {
                admin_log($auction['act_name'], 'edit', 'auction');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            if ($is_add) {
                $links = [
                    ['href' => 'auction.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_auction']],
                    ['href' => 'auction.php?act=list', 'text' => $GLOBALS['_LANG']['back_auction_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['add_auction_ok'], 0, $links);
            } else {
                $links = [
                    ['href' => 'auction.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_auction_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_auction_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 处理冻结资金
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'settle_money') {
            /* 检查权限 */
            admin_priv('auction');

            /* 检查参数 */
            if (empty($_POST['id'])) {
                return sys_msg('invalid param');
            }
            $id = intval($_POST['id']);
            $auction = auction_info($id);
            if (empty($auction)) {
                return sys_msg($GLOBALS['_LANG']['auction_not_exist']);
            }
            if ($auction['status_no'] != FINISHED) {
                return sys_msg($GLOBALS['_LANG']['invalid_status']);
            }
            if ($auction['deposit'] <= 0) {
                return sys_msg($GLOBALS['_LANG']['no_deposit']);
            }

            /* 处理保证金 */
            $exc->edit("is_finished = 2", $id); // 修改状态
            if (isset($_POST['unfreeze'])) {
                /* 解冻 */
                log_account_change(
                    $auction['last_bid']['bid_user'],
                    $auction['deposit'],
                    (-1) * $auction['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['unfreeze_auction_deposit'], $auction['act_name'])
                );
            } else {
                /* 扣除 */
                log_account_change(
                    $auction['last_bid']['bid_user'],
                    0,
                    (-1) * $auction['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['deduct_auction_deposit'], $auction['act_name'])
                );
            }

            /* 记日志 */
            admin_log($auction['act_name'], 'edit', 'auction');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            return sys_msg($GLOBALS['_LANG']['settle_deposit_ok']);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'search_goods') {
            return check_authz_json('auction');

            $filter = json_decode($_GET['JSON']);
            $arr['goods'] = get_goods_list($filter);

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

    /*
     * 取得拍卖活动列表
     * @return   array
     */
    private function auction_list()
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
                $where .= " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            if ($filter['is_going']) {
                $now = gmtime();
                $where .= " AND is_finished = 0 AND start_time <= '$now' AND end_time >= '$now' ";
            }

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type = '" . GAT_AUCTION . "' $where";
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT * " .
                "FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE act_type = '" . GAT_AUCTION . "' $where " .
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
            $ext_info = unserialize($row['ext_info']);
            $arr = array_merge($row, $ext_info);

            $arr['start_time'] = local_date('Y-m-d H:i', $arr['start_time']);
            $arr['end_time'] = local_date('Y-m-d H:i', $arr['end_time']);

            $list[] = $arr;
        }
        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 列表链接
     * @param   bool $is_add 是否添加（插入）
     * @param   string $text 文字
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $text = '')
    {
        $href = 'auction.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }
        if ($text == '') {
            $text = $GLOBALS['_LANG']['auction_list'];
        }

        return ['href' => $href, 'text' => $text];
    }
}
