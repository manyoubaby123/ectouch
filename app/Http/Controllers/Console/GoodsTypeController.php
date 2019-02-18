<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Exchange;

class GoodsTypeController extends InitController
{
    public function actionIndex()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("goods_type"), $GLOBALS['db'], 'cat_id', 'cat_name');

        /*------------------------------------------------------ */
        //-- 管理界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'manage') {
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['08_goods_type']);
            $GLOBALS['smarty']->assign('full_page', 1);

            $good_type_list = $this->get_goodstype();
            $good_in_type = '';

            $GLOBALS['smarty']->assign('goods_type_arr', $good_type_list['type']);
            $GLOBALS['smarty']->assign('filter', $good_type_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $good_type_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $good_type_list['page_count']);

            $query = $GLOBALS['db']->query("SELECT a.cat_id FROM " . $GLOBALS['ecs']->table('attribute') . " AS a RIGHT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS g ON g.attr_id = a.attr_id GROUP BY a.cat_id");
            foreach ($query as $row) {
                $good_in_type[$row['cat_id']] = 1;
            }
            $GLOBALS['smarty']->assign('good_in_type', $good_in_type);

            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['new_goods_type'], 'href' => 'goods_type.php?act=add']);

            return $GLOBALS['smarty']->display('goods_type.htm');
        }

        /*------------------------------------------------------ */
        //-- 获得列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $good_type_list = $this->get_goodstype();

            $GLOBALS['smarty']->assign('goods_type_arr', $good_type_list['type']);
            $GLOBALS['smarty']->assign('filter', $good_type_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $good_type_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $good_type_list['page_count']);

            return make_json_result(
                $GLOBALS['smarty']->fetch('goods_type.htm'),
                '',
                ['filter' => $good_type_list['filter'], 'page_count' => $good_type_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 修改商品类型名称
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit_type_name') {
            return check_authz_json('goods_type');

            $type_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type_name = !empty($_POST['val']) ? json_str_iconv(trim($_POST['val'])) : '';

            /* 检查名称是否重复 */
            $is_only = $exc->is_only('cat_name', $type_name, $type_id);

            if ($is_only) {
                $exc->edit("cat_name='$type_name'", $type_id);

                admin_log($type_name, 'edit', 'goods_type');

                return make_json_result(stripslashes($type_name));
            } else {
                return make_json_error($GLOBALS['_LANG']['repeat_type_name']);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换启用状态
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_enabled') {
            return check_authz_json('goods_type');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $exc->edit("enabled='$val'", $id);

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加商品类型
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add') {
            admin_priv('goods_type');

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['new_goods_type']);
            $GLOBALS['smarty']->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list']]);
            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('form_act', 'insert');
            $GLOBALS['smarty']->assign('goods_type', ['enabled' => 1]);

            return $GLOBALS['smarty']->display('goods_type_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            //$goods_type['cat_name']   = trim_right(sub_str($_POST['cat_name'], 60));
            //$goods_type['attr_group'] = trim_right(sub_str($_POST['attr_group'], 255));
            $goods_type['cat_name'] = sub_str($_POST['cat_name'], 60);
            $goods_type['attr_group'] = sub_str($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);

            if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_type'), $goods_type) !== false) {
                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['add_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['add_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品类型
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit') {
            $goods_type = $this->get_goodstype_info(intval($_GET['cat_id']));

            if (empty($goods_type)) {
                return sys_msg($GLOBALS['_LANG']['cannot_found_goodstype'], 1);
            }

            admin_priv('goods_type');

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['edit_goods_type']);
            $GLOBALS['smarty']->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list']]);
            $GLOBALS['smarty']->assign('action', 'add');
            $GLOBALS['smarty']->assign('form_act', 'update');
            $GLOBALS['smarty']->assign('goods_type', $goods_type);

            return $GLOBALS['smarty']->display('goods_type_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            $goods_type['cat_name'] = sub_str($_POST['cat_name'], 60);
            $goods_type['attr_group'] = sub_str($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);
            $cat_id = intval($_POST['cat_id']);
            $old_groups = get_attr_groups($cat_id);

            if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_type'), $goods_type, 'UPDATE', "cat_id='$cat_id'") !== false) {
                /* 对比原来的分组 */
                $new_groups = explode("\n", str_replace("\r", '', $goods_type['attr_group']));  // 新的分组

                foreach ($old_groups as $key => $val) {
                    $found = array_search($val, $new_groups);

                    if ($found === null || $found === false) {
                        /* 老的分组没有在新的分组中找到 */
                        $this->update_attribute_group($cat_id, $key, 0);
                    } else {
                        /* 老的分组出现在新的分组中了 */
                        if ($key != $found) {
                            $this->update_attribute_group($cat_id, $key, $found); // 但是分组的key变了,需要更新属性的分组
                        }
                    }
                }

                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除商品类型
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('goods_type');

            $id = intval($_GET['id']);

            $name = $exc->get_name($id);

            if ($exc->drop($id)) {
                admin_log(addslashes($name), 'remove', 'goods_type');

                /* 清除该类型下的所有属性 */
                $sql = "SELECT attr_id FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE cat_id = '$id'";
                $arr = $GLOBALS['db']->getCol($sql);

                $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE attr_id " . db_create_in($arr));
                $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE attr_id " . db_create_in($arr));

                $url = 'goods_type.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

                return ecs_header("Location: $url\n");
            } else {
                return make_json_error($GLOBALS['_LANG']['remove_failed']);
            }
        }
    }

    /**
     * 获得所有商品类型
     *
     * @access  public
     * @return  array
     */
    private function get_goodstype()
    {
        $result = get_filter();
        if ($result === false) {
            /* 分页大小 */
            $filter = [];

            /* 记录总数以及页数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_type');
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            $filter = page_and_size($filter);

            /* 查询记录 */
            $sql = "SELECT t.*, COUNT(a.cat_id) AS attr_count " .
                "FROM " . $GLOBALS['ecs']->table('goods_type') . " AS t " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a ON a.cat_id=t.cat_id " .
                "GROUP BY t.cat_id " .
                'LIMIT ' . $filter['start'] . ',' . $filter['page_size'];
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $all = $GLOBALS['db']->getAll($sql);

        foreach ($all as $key => $val) {
            $all[$key]['attr_group'] = strtr($val['attr_group'], ["\r" => '', "\n" => ", "]);
        }

        return ['type' => $all, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得指定的商品类型的详情
     *
     * @param   integer $cat_id 分类ID
     *
     * @return  array
     */
    private function get_goodstype_info($cat_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('goods_type') . " WHERE cat_id='$cat_id'";

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 更新属性的分组
     *
     * @param   integer $cat_id 商品类型ID
     * @param   integer $old_group
     * @param   integer $new_group
     *
     * @return  void
     */
    private function update_attribute_group($cat_id, $old_group, $new_group)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('attribute') .
            " SET attr_group='$new_group' WHERE cat_id='$cat_id' AND attr_group='$old_group'";
        $GLOBALS['db']->query($sql);
    }
}
