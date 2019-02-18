<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Exchange;
use App\Libraries\Iconv;

class PackController extends InitController
{
    public function actionIndex()
    {
        $image = new Iconv($GLOBALS['_CFG']['bgcolor']);

        $exc = new Exchange($GLOBALS['ecs']->table("pack"), $GLOBALS['db'], 'pack_id', 'pack_name');

        /*------------------------------------------------------ */
        //-- 包装列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['06_pack_list']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['pack_add'], 'href' => 'pack.php?act=add']);
            $GLOBALS['smarty']->assign('full_page', 1);

            $packs_list = $this->packs_list();

            $GLOBALS['smarty']->assign('packs_list', $packs_list['packs_list']);
            $GLOBALS['smarty']->assign('filter', $packs_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $packs_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $packs_list['page_count']);

            return $GLOBALS['smarty']->display('pack_list.htm');
        }
        /*------------------------------------------------------ */
        //-- ajax 列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $packs_list = $this->packs_list();
            $GLOBALS['smarty']->assign('packs_list', $packs_list['packs_list']);
            $GLOBALS['smarty']->assign('filter', $packs_list['filter']);
            $GLOBALS['smarty']->assign('record_count', $packs_list['record_count']);
            $GLOBALS['smarty']->assign('page_count', $packs_list['page_count']);

            $sort_flag = sort_flag($packs_list['filter']);
            $GLOBALS['smarty']->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($GLOBALS['smarty']->fetch('pack_list.htm'), '', ['filter' => $packs_list['filter'], 'page_count' => $packs_list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 添加新包装
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('pack');

            $pack['pack_fee'] = 0;
            $pack['free_money'] = 0;

            $GLOBALS['smarty']->assign('pack', $pack);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['pack_add']);
            $GLOBALS['smarty']->assign('form_action', 'insert');
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['06_pack_list'], 'href' => 'pack.php?act=list']);

            return $GLOBALS['smarty']->display('pack_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('pack');

            /*检查包装名是否重复*/
            $is_only = $exc->is_only('pack_name', $_POST['pack_name']);

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['packname_exist'], stripslashes($_POST['pack_name'])), 1);
            }

            /* 处理图片 */
            if (!empty($_FILES['pack_img'])) {
                $upload_img = $image->upload_image($_FILES['pack_img'], "packimg", $_POST['old_packimg']);
                if ($upload_img == false) {
                    return sys_msg($image->error_msg);
                }
                $img_name = basename($upload_img);
            } else {
                $img_name = '';
            }

            /*插入数据*/
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('pack') . "(pack_name, pack_fee, free_money, pack_desc, pack_img)
            VALUES ('$_POST[pack_name]', '$_POST[pack_fee]', '$_POST[free_money]', '$_POST[pack_desc]', '$img_name')";
            $GLOBALS['db']->query($sql);

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'pack.php?act=list';
            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'pack.php?act=add';
            return sys_msg($_POST['pack_name'] . $GLOBALS['_LANG']['packadd_succed'], 0, $link);
            admin_log($_POST['pack_name'], 'add', 'pack');
        }

        /*------------------------------------------------------ */
        //-- 编辑包装
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('pack');

            $sql = "SELECT pack_id, pack_name, pack_fee, free_money, pack_desc, pack_img FROM " . $GLOBALS['ecs']->table('pack') . " WHERE pack_id='$_REQUEST[id]'";
            $pack = $GLOBALS['db']->getRow($sql);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['pack_edit']);
            $GLOBALS['smarty']->assign('action_link', ['text' => $GLOBALS['_LANG']['06_pack_list'], 'href' => 'pack.php?act=list&' . list_link_postfix()]);
            $GLOBALS['smarty']->assign('pack', $pack);
            $GLOBALS['smarty']->assign('form_action', 'update');
            return $GLOBALS['smarty']->display('pack_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('pack');
            if ($_POST['pack_name'] != $_POST['old_packname']) {
                /*检查品牌名是否相同*/
                $is_only = $exc->is_only('pack_name', $_POST['pack_name'], $_POST['id']);

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['packname_exist'], stripslashes($_POST['pack_name'])), 1);
                }
            }

            $param = "pack_name = '$_POST[pack_name]', pack_fee = '$_POST[pack_fee]', free_money= '$_POST[free_money]', pack_desc = '$_POST[pack_desc]' ";
            /* 处理图片 */
            if (!empty($_FILES['pack_img']['name'])) {
                $upload_img = $image->upload_image($_FILES['pack_img'], "packimg", $_POST['old_packimg']);
                if ($upload_img == false) {
                    return sys_msg($image->error_msg);
                }
                $img_name = basename($upload_img);
            } else {
                $img_name = '';
            }

            if (!empty($img_name)) {
                $param .= " ,pack_img = '$img_name' ";
            }

            if ($exc->edit($param, $_POST['id'])) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'pack.php?act=list&' . list_link_postfix();
                $note = sprintf($GLOBALS['_LANG']['packedit_succed'], $_POST['pack_name']);
                return sys_msg($note, 0, $link);
                admin_log($_POST['pack_name'], 'edit', 'pack');
            } else {
                return $GLOBALS['db']->error();
            }
        }

        /* 删除卡片图片 */
        if ($_REQUEST['act'] == 'drop_pack_img') {
            /* 权限判断 */
            admin_priv('pack');
            $pack_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            /* 取得logo名称 */
            $sql = "SELECT pack_img FROM " . $GLOBALS['ecs']->table('pack') . " WHERE pack_id = '$pack_id'";
            $img_name = $GLOBALS['db']->getOne($sql);

            if (!empty($img_name)) {
                @unlink(ROOT_PATH . DATA_DIR . '/packimg/' . $img_name);
                $sql = "UPDATE " . $GLOBALS['ecs']->table('pack') . " SET pack_img = '' WHERE pack_id = '$pack_id'";
                $GLOBALS['db']->query($sql);
            }
            $link = [['text' => $GLOBALS['_LANG']['pack_edit_lnk'], 'href' => 'pack.php?act=edit&id=' . $pack_id], ['text' => $GLOBALS['_LANG']['pack_list_lnk'], 'href' => 'pack.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_pack_img_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑包装名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_name') {
            return check_authz_json('pack');

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 取得该属性所属商品类型id */
            $pack_name = $exc->get_name($id);

            if (!$exc->is_only('pack_name', $val, $id)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['packname_exist'], $pack_name));
            } else {
                $exc->edit("pack_name='$val'", $id);

                admin_log($val, 'edit', 'pack');
                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑包装费用
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_pack_fee') {
            return check_authz_json('pack');

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 取得该属性所属商品类型id */
            $pack_name = $exc->get_name($id);

            $exc->edit("pack_fee='$val'", $id);
            admin_log(addslashes($pack_name), 'edit', 'pack');
            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 编辑免费额度
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_free_money') {
            return check_authz_json('pack');

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 取得该属性所属商品类型id */
            $pack_name = $exc->get_name($id);

            $exc->edit("free_money='$val'", $id);
            admin_log(addslashes($pack_name), 'edit', 'pack');
            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 删除包装
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('pack');

            $id = intval($_GET['id']);
            $name = $exc->get_name($id);
            $img = $exc->get_name($id, 'pack_img');

            if ($exc->drop($id)) {
                /* 删除图片 */
                if (!empty($img)) {
                    @unlink('../' . DATA_DIR . '/packimg/' . $img);
                }
                admin_log(addslashes($name), 'remove', 'pack');

                $url = 'pack.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

                return ecs_header("Location: $url\n");
            } else {
                return make_json_error($GLOBALS['_LANG']['packremove_falure']);
                return false;
            }
        }
    }

    private function packs_list()
    {
        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'pack_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('pack');
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT pack_id, pack_name, pack_img, pack_fee, free_money, pack_desc" .
                " FROM " . $GLOBALS['ecs']->table('pack') .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $packs_list = $GLOBALS['db']->getAll($sql);

        $arr = ['packs_list' => $packs_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
