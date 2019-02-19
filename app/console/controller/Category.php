<?php

namespace app\console\controller;

use app\libraries\Exchange;

class Category extends Init
{
    public function index()
    {
        $exc = new Exchange($GLOBALS['ecs']->table("category"), $GLOBALS['db'], 'cat_id', 'cat_name');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 商品分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 获取分类列表 */
            $cat_list = cat_list(0, 0, false);

            /* 模板赋值 */
            $this->assign('ur_here', $GLOBALS['_LANG']['03_category_list']);
            $this->assign('action_link', ['href' => 'category.php?act=add', 'text' => $GLOBALS['_LANG']['04_category_add']]);
            $this->assign('full_page', 1);

            $this->assign('cat_info', $cat_list);

            /* 列表页面 */

            return $GLOBALS['smarty']->display('category_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $cat_list = cat_list(0, 0, false);
            $this->assign('cat_info', $cat_list);

            return make_json_result($GLOBALS['smarty']->fetch('category_list.htm'));
        }
        /*------------------------------------------------------ */
        //-- 添加商品分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限检查 */
            admin_priv('cat_manage');

            /* 模板赋值 */
            $this->assign('ur_here', $GLOBALS['_LANG']['04_category_add']);
            $this->assign('action_link', ['href' => 'category.php?act=list', 'text' => $GLOBALS['_LANG']['03_category_list']]);

            $this->assign('goods_type_list', goods_type_list(0)); // 取得商品类型
            $this->assign('attr_list', $this->get_attr_list()); // 取得商品属性

            $this->assign('cat_select', cat_list(0, 0, true));
            $this->assign('form_act', 'insert');
            $this->assign('cat_info', ['is_show' => 1]);

            /* 显示页面 */

            return $GLOBALS['smarty']->display('category_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 商品分类添加时的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            /* 权限检查 */
            admin_priv('cat_manage');

            /* 初始化变量 */
            $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['measure_unit'] = !empty($_POST['measure_unit']) ? trim($_POST['measure_unit']) : '';
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
            $cat['style'] = !empty($_POST['style']) ? trim($_POST['style']) : '';
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;
            $cat['grade'] = !empty($_POST['grade']) ? intval($_POST['grade']) : 0;
            $cat['filter_attr'] = !empty($_POST['filter_attr']) ? implode(',', array_unique(array_diff($_POST['filter_attr'], [0]))) : 0;

            $cat['cat_recommend'] = !empty($_POST['cat_recommend']) ? $_POST['cat_recommend'] : [];

            if (cat_exists($cat['cat_name'], $cat['parent_id'])) {
                /* 同级别下不能有重复的分类名称 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
            }

            if ($cat['grade'] > 10 || $cat['grade'] < 0) {
                /* 价格区间数超过范围 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['grade_error'], 0, $link);
            }

            /* 入库的操作 */
            if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('category'), $cat) !== false) {
                $cat_id = $GLOBALS['db']->insert_id();
                if ($cat['show_in_nav'] == 1) {
                    $vieworder = $GLOBALS['db']->getOne("SELECT max(vieworder) FROM " . $GLOBALS['ecs']->table('nav') . " WHERE type = 'middle'");
                    $vieworder += 2;
                    //显示在自定义导航栏中
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('nav') .
                        " (name,ctype,cid,ifshow,vieworder,opennew,url,type)" .
                        " VALUES('" . $cat['cat_name'] . "', 'c', '" . $GLOBALS['db']->insert_id() . "','1','$vieworder','0', '" . build_uri('category', ['cid' => $cat_id], $cat['cat_name']) . "','middle')";
                    $GLOBALS['db']->query($sql);
                }
                $this->insert_cat_recommend($cat['cat_recommend'], $cat_id);

                admin_log($_POST['cat_name'], 'add', 'category');   // 记录管理员操作
                clear_cache_files();    // 清除缓存

                /*添加链接*/
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'category.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'category.php?act=list';

                return sys_msg($GLOBALS['_LANG']['catadd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            admin_priv('cat_manage');   // 权限检查
            $cat_id = intval($_REQUEST['cat_id']);
            $cat_info = $this->get_cat_info($cat_id);  // 查询分类信息数据
            $attr_list = $this->get_attr_list();
            $filter_attr_list = [];

            if ($cat_info['filter_attr']) {
                $filter_attr = explode(",", $cat_info['filter_attr']);  //把多个筛选属性放到数组中

                foreach ($filter_attr as $k => $v) {
                    $attr_cat_id = $GLOBALS['db']->getOne("SELECT cat_id FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE attr_id = '" . intval($v) . "'");
                    $filter_attr_list[$k]['goods_type_list'] = goods_type_list($attr_cat_id);  //取得每个属性的商品类型
                    $filter_attr_list[$k]['filter_attr'] = $v;
                    $attr_option = [];

                    foreach ($attr_list[$attr_cat_id] as $val) {
                        $attr_option[key($val)] = current($val);
                    }

                    $filter_attr_list[$k]['option'] = $attr_option;
                }

                $this->assign('filter_attr_list', $filter_attr_list);
            } else {
                $attr_cat_id = 0;
            }

            /* 模板赋值 */
            $this->assign('attr_list', $attr_list); // 取得商品属性
            $this->assign('attr_cat_id', $attr_cat_id);
            $this->assign('ur_here', $GLOBALS['_LANG']['category_edit']);
            $this->assign('action_link', ['text' => $GLOBALS['_LANG']['03_category_list'], 'href' => 'category.php?act=list']);

            //分类是否存在首页推荐
            $res = $GLOBALS['db']->getAll("SELECT recommend_type FROM " . $GLOBALS['ecs']->table("cat_recommend") . " WHERE cat_id=" . $cat_id);
            if (!empty($res)) {
                $cat_recommend = [];
                foreach ($res as $data) {
                    $cat_recommend[$data['recommend_type']] = 1;
                }
                $this->assign('cat_recommend', $cat_recommend);
            }

            $this->assign('cat_info', $cat_info);
            $this->assign('form_act', 'update');
            $this->assign('cat_select', cat_list(0, $cat_info['parent_id'], true));
            $this->assign('goods_type_list', goods_type_list(0)); // 取得商品类型

            /* 显示页面 */

            return $GLOBALS['smarty']->display('category_info.htm');
        }
        if ($_REQUEST['act'] == 'add_category') {
            $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
            $category = empty($_REQUEST['cat']) ? '' : json_str_iconv(trim($_REQUEST['cat']));

            if (cat_exists($category, $parent_id)) {
                return make_json_error($GLOBALS['_LANG']['catname_exist']);
            } else {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('category') . "(cat_name, parent_id, is_show)" .
                    "VALUES ( '$category', '$parent_id', 1)";

                $GLOBALS['db']->query($sql);
                $category_id = $GLOBALS['db']->insert_id();

                $arr = ["parent_id" => $parent_id, "id" => $category_id, "cat" => $category];

                clear_cache_files();    // 清除缓存

                return make_json_result($arr);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品分类信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限检查 */
            admin_priv('cat_manage');

            /* 初始化变量 */
            $cat_id = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $old_cat_name = $_POST['old_cat_name'];
            $cat['parent_id'] = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
            $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
            $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
            $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
            $cat['measure_unit'] = !empty($_POST['measure_unit']) ? trim($_POST['measure_unit']) : '';
            $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
            $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;
            $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
            $cat['style'] = !empty($_POST['style']) ? trim($_POST['style']) : '';
            $cat['grade'] = !empty($_POST['grade']) ? intval($_POST['grade']) : 0;
            $cat['filter_attr'] = !empty($_POST['filter_attr']) ? implode(',', array_unique(array_diff($_POST['filter_attr'], [0]))) : 0;
            $cat['cat_recommend'] = !empty($_POST['cat_recommend']) ? $_POST['cat_recommend'] : [];

            /* 判断分类名是否重复 */

            if ($cat['cat_name'] != $old_cat_name) {
                if (cat_exists($cat['cat_name'], $cat['parent_id'], $cat_id)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
                }
            }

            /* 判断上级目录是否合法 */
            $children = array_keys(cat_list($cat_id, 0, false));     // 获得当前分类的所有下级分类
            if (in_array($cat['parent_id'], $children)) {
                /* 选定的父类是当前分类或当前分类的下级分类 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']["is_leaf_error"], 0, $link);
            }

            if ($cat['grade'] > 10 || $cat['grade'] < 0) {
                /* 价格区间数超过范围 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['grade_error'], 0, $link);
            }

            $dat = $GLOBALS['db']->getRow("SELECT cat_name, show_in_nav FROM " . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'");

            if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('category'), $cat, 'UPDATE', "cat_id='$cat_id'")) {
                if ($cat['cat_name'] != $dat['cat_name']) {
                    //如果分类名称发生了改变
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('nav') . " SET name = '" . $cat['cat_name'] . "' WHERE ctype = 'c' AND cid = '" . $cat_id . "' AND type = 'middle'";
                    $GLOBALS['db']->query($sql);
                }
                if ($cat['show_in_nav'] != $dat['show_in_nav']) {
                    //是否显示于导航栏发生了变化
                    if ($cat['show_in_nav'] == 1) {
                        //显示
                        $nid = $GLOBALS['db']->getOne("SELECT id FROM " . $GLOBALS['ecs']->table('nav') . " WHERE ctype = 'c' AND cid = '" . $cat_id . "' AND type = 'middle'");
                        if (empty($nid)) {
                            //不存在
                            $vieworder = $GLOBALS['db']->getOne("SELECT max(vieworder) FROM " . $GLOBALS['ecs']->table('nav') . " WHERE type = 'middle'");
                            $vieworder += 2;
                            $uri = build_uri('category', ['cid' => $cat_id], $cat['cat_name']);

                            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('nav') . " (name,ctype,cid,ifshow,vieworder,opennew,url,type) VALUES('" . $cat['cat_name'] . "', 'c', '$cat_id','1','$vieworder','0', '" . $uri . "','middle')";
                        } else {
                            $sql = "UPDATE " . $GLOBALS['ecs']->table('nav') . " SET ifshow = 1 WHERE ctype = 'c' AND cid = '" . $cat_id . "' AND type = 'middle'";
                        }
                        $GLOBALS['db']->query($sql);
                    } else {
                        //去除
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('nav') . " SET ifshow = 0 WHERE ctype = 'c' AND cid = '" . $cat_id . "' AND type = 'middle'");
                    }
                }

                //更新首页推荐
                $this->insert_cat_recommend($cat['cat_recommend'], $cat_id);
                /* 更新分类信息成功 */
                clear_cache_files(); // 清除缓存
                admin_log($_POST['cat_name'], 'edit', 'category'); // 记录管理员操作

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'category.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['catedit_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 批量转移商品分类页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'move') {
            /* 权限检查 */
            admin_priv('cat_drop');

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

            /* 模板赋值 */
            $this->assign('ur_here', $GLOBALS['_LANG']['move_goods']);
            $this->assign('action_link', ['href' => 'category.php?act=list', 'text' => $GLOBALS['_LANG']['03_category_list']]);

            $this->assign('cat_select', cat_list(0, $cat_id, true));
            $this->assign('form_act', 'move_cat');

            /* 显示页面 */

            return $GLOBALS['smarty']->display('category_move.htm');
        }

        /*------------------------------------------------------ */
        //-- 处理批量转移商品分类的处理程序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'move_cat') {
            /* 权限检查 */
            admin_priv('cat_drop');

            $cat_id = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
            $target_cat_id = !empty($_POST['target_cat_id']) ? intval($_POST['target_cat_id']) : 0;

            /* 商品分类不允许为空 */
            if ($cat_id == 0 || $target_cat_id == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'category.php?act=move'];
                return sys_msg($GLOBALS['_LANG']['cat_move_empty'], 0, $link);
            }

            /* 更新商品分类 */
            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET cat_id = '$target_cat_id' " .
                "WHERE cat_id = '$cat_id'";
            if ($GLOBALS['db']->query($sql)) {
                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'category.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['move_cat_success'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_sort_order') {
            return check_authz_json('cat_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->cat_update($id, ['sort_order' => $val])) {
                clear_cache_files(); // 清除缓存
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑数量单位
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_measure_unit') {
            return check_authz_json('cat_manage');

            $id = intval($_POST['id']);
            $val = json_str_iconv($_POST['val']);

            if ($this->cat_update($id, ['measure_unit' => $val])) {
                clear_cache_files(); // 清除缓存
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_grade') {
            return check_authz_json('cat_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($val > 10 || $val < 0) {
                /* 价格区间数超过范围 */
                return make_json_error($GLOBALS['_LANG']['grade_error']);
            }

            if ($this->cat_update($id, ['grade' => $val])) {
                clear_cache_files(); // 清除缓存
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示在导航栏
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_show_in_nav') {
            return check_authz_json('cat_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->cat_update($id, ['show_in_nav' => $val]) != false) {
                if ($val == 1) {
                    //显示
                    $vieworder = $GLOBALS['db']->getOne("SELECT max(vieworder) FROM " . $GLOBALS['ecs']->table('nav') . " WHERE type = 'middle'");
                    $vieworder += 2;
                    $catname = $GLOBALS['db']->getOne("SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$id'");
                    //显示在自定义导航栏中
                    $GLOBALS['_CFG']['rewrite'] = 0;
                    $uri = build_uri('category', ['cid' => $id], $catname);

                    $nid = $GLOBALS['db']->getOne("SELECT id FROM " . $GLOBALS['ecs']->table('nav') . " WHERE ctype = 'c' AND cid = '" . $id . "' AND type = 'middle'");
                    if (empty($nid)) {
                        //不存在
                        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('nav') . " (name,ctype,cid,ifshow,vieworder,opennew,url,type) VALUES('" . $catname . "', 'c', '$id','1','$vieworder','0', '" . $uri . "','middle')";
                    } else {
                        $sql = "UPDATE " . $GLOBALS['ecs']->table('nav') . " SET ifshow = 1 WHERE ctype = 'c' AND cid = '" . $id . "' AND type = 'middle'";
                    }
                    $GLOBALS['db']->query($sql);
                } else {
                    //去除
                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('nav') . "SET ifshow = 0 WHERE ctype = 'c' AND cid = '" . $id . "' AND type = 'middle'");
                }
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_is_show') {
            return check_authz_json('cat_manage');

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->cat_update($id, ['is_show' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['db']->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 删除商品分类
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'remove') {
            return check_authz_json('cat_manage');

            /* 初始化分类ID并取得分类名称 */
            $cat_id = intval($_GET['id']);
            $cat_name = $GLOBALS['db']->getOne('SELECT cat_name FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id='$cat_id'");

            /* 当前分类下是否有子分类 */
            $cat_count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id='$cat_id'");

            /* 当前分类下是否存在商品 */
            $goods_count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . " WHERE cat_id='$cat_id'");

            /* 如果不存在下级子分类和商品，则删除之 */
            if ($cat_count == 0 && $goods_count == 0) {
                /* 删除分类 */
                $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
                if ($GLOBALS['db']->query($sql)) {
                    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('nav') . "WHERE ctype = 'c' AND cid = '" . $cat_id . "' AND type = 'middle'");
                    clear_cache_files();
                    admin_log($cat_name, 'remove', 'category');
                }
            } else {
                return make_json_error($cat_name . ' ' . $GLOBALS['_LANG']['cat_isleaf']);
            }

            $url = 'category.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

            return ecs_header("Location: $url\n");
        }
    }

    /**
     * 获得商品分类的所有信息
     *
     * @param   integer $cat_id 指定的分类ID
     *
     * @return  mix
     */
    private function get_cat_info($cat_id)
    {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('category') . " WHERE cat_id='$cat_id' LIMIT 1";
        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 添加商品分类
     *
     * @param   integer $cat_id
     * @param   array $args
     *
     * @return  mix
     */
    private function cat_update($cat_id, $args)
    {
        if (empty($args) || empty($cat_id)) {
            return false;
        }

        return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('category'), $args, 'update', "cat_id='$cat_id'");
    }

    /**
     * 获取属性列表
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function get_attr_list()
    {
        $sql = "SELECT a.attr_id, a.cat_id, a.attr_name " .
            " FROM " . $GLOBALS['ecs']->table('attribute') . " AS a,  " .
            $GLOBALS['ecs']->table('goods_type') . " AS c " .
            " WHERE  a.cat_id = c.cat_id AND c.enabled = 1 " .
            " ORDER BY a.cat_id , a.sort_order";

        $arr = $GLOBALS['db']->getAll($sql);

        $list = [];

        foreach ($arr as $val) {
            $list[$val['cat_id']][] = [$val['attr_id'] => $val['attr_name']];
        }

        return $list;
    }

    /**
     * 插入首页推荐扩展分类
     *
     * @access  public
     * @param   array $recommend_type 推荐类型
     * @param   integer $cat_id 分类ID
     *
     * @return void
     */
    private function insert_cat_recommend($recommend_type, $cat_id)
    {
        //检查分类是否为首页推荐
        if (!empty($recommend_type)) {
            //取得之前的分类
            $recommend_res = $GLOBALS['db']->getAll("SELECT recommend_type FROM " . $GLOBALS['ecs']->table("cat_recommend") . " WHERE cat_id=" . $cat_id);
            if (empty($recommend_res)) {
                foreach ($recommend_type as $data) {
                    $data = intval($data);
                    $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table("cat_recommend") . "(cat_id, recommend_type) VALUES ('$cat_id', '$data')");
                }
            } else {
                $old_data = [];
                foreach ($recommend_res as $data) {
                    $old_data[] = $data['recommend_type'];
                }
                $delete_array = array_diff($old_data, $recommend_type);
                if (!empty($delete_array)) {
                    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table("cat_recommend") . " WHERE cat_id=$cat_id AND recommend_type " . db_create_in($delete_array));
                }
                $insert_array = array_diff($recommend_type, $old_data);
                if (!empty($insert_array)) {
                    foreach ($insert_array as $data) {
                        $data = intval($data);
                        $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table("cat_recommend") . "(cat_id, recommend_type) VALUES ('$cat_id', '$data')");
                    }
                }
            }
        } else {
            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table("cat_recommend") . " WHERE cat_id=" . $cat_id);
        }
    }
}
