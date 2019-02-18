<?php

namespace app\services;

/**
 * Class ArticleService
 * @package app\services
 */
class ArticleService
{
    /**
     * 获得文章分类下的文章列表
     * @param $cat_id
     * @param int $page
     * @param int $size
     * @param string $requirement
     * @return array
     */
    public function get_cat_articles($cat_id, $page = 1, $size = 20, $requirement = '')
    {
        //取出所有非0的文章
        if ($cat_id == '-1') {
            $cat_str = 'cat_id > 0';
        } else {
            $cat_str = get_article_children($cat_id);
        }
        //增加搜索条件，如果有搜索内容就进行搜索
        if ($requirement != '') {
            $sql = 'SELECT article_id, title, author, add_time, file_url, open_type' .
                ' FROM ' . $GLOBALS['ecs']->table('article') .
                ' WHERE is_open = 1 AND title like \'%' . $requirement . '%\' ' .
                ' ORDER BY article_type DESC, article_id DESC';
        } else {
            $sql = 'SELECT article_id, title, author, add_time, file_url, open_type' .
                ' FROM ' . $GLOBALS['ecs']->table('article') .
                ' WHERE is_open = 1 AND ' . $cat_str .
                ' ORDER BY article_type DESC, article_id DESC';
        }

        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $article_id = $row['article_id'];

                $arr[$article_id]['id'] = $article_id;
                $arr[$article_id]['title'] = $row['title'];
                $arr[$article_id]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ? sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
                $arr[$article_id]['author'] = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
                $arr[$article_id]['url'] = $row['open_type'] != 1 ? build_uri('article', ['aid' => $article_id], $row['title']) : trim($row['file_url']);
                $arr[$article_id]['add_time'] = date($GLOBALS['_CFG']['date_format'], $row['add_time']);
            }
        }

        return $arr;
    }

    /**
     * 获得指定分类下的文章总数
     * @param $cat_id
     * @param string $requirement
     * @return mixed
     */
    public function get_article_count($cat_id, $requirement = '')
    {
        if ($requirement != '') {
            $count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('article') . ' WHERE ' . get_article_children($cat_id) . ' AND  title like \'%' . $requirement . '%\'  AND is_open = 1');
        } else {
            $count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('article') . " WHERE " . get_article_children($cat_id) . " AND is_open = 1");
        }
        return $count;
    }

    /**
     * 分配文章列表给smarty
     *
     * @access  public
     * @param   integer $id 文章分类的编号
     * @param   integer $num 文章数量
     * @return  array
     */
    public function assign_articles($id, $num)
    {
        $sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('article_cat') . " WHERE cat_id = '" . $id . "'";

        $cat['id'] = $id;
        $cat['name'] = $GLOBALS['db']->getOne($sql);
        $cat['url'] = build_uri('article_cat', ['acid' => $id], $cat['name']);

        $articles['cat'] = $cat;
        $articles['arr'] = get_cat_articles($id, 1, $num);

        return $articles;
    }

    /**
     * 分配帮助信息
     *
     * @access  public
     * @return  array
     */
    public function get_shop_help()
    {
        $sql = 'SELECT c.cat_id, c.cat_name, c.sort_order, a.article_id, a.title, a.file_url, a.open_type ' .
            'FROM ' . $GLOBALS['ecs']->table('article') . ' AS a ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('article_cat') . ' AS c ' .
            'ON a.cat_id = c.cat_id WHERE c.cat_type = 5 AND a.is_open = 1 ' .
            'ORDER BY c.sort_order ASC, a.article_id';
        $res = $GLOBALS['db']->getAll($sql);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$row['cat_id']]['cat_id'] = build_uri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
            $arr[$row['cat_id']]['cat_name'] = $row['cat_name'];
            $arr[$row['cat_id']]['article'][$key]['article_id'] = $row['article_id'];
            $arr[$row['cat_id']]['article'][$key]['title'] = $row['title'];
            $arr[$row['cat_id']]['article'][$key]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
                sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
            $arr[$row['cat_id']]['article'][$key]['url'] = $row['open_type'] != 1 ?
                build_uri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);
        }

        return $arr;
    }

    /**
     * 获得指定分类同级的所有分类以及该分类下的子分类
     *
     * @access  public
     * @param   integer $cat_id 分类编号
     * @return  array
     */
    public function article_categories_tree($cat_id = 0)
    {
        if ($cat_id > 0) {
            $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('article_cat') . " WHERE cat_id = '$cat_id'";
            $parent_id = $GLOBALS['db']->getOne($sql);
        } else {
            $parent_id = 0;
        }

        /*
         判断当前分类中全是是否是底级分类，
         如果是取出底级分类上级分类，
         如果不是取当前分类及其下的子分类
        */
        $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('article_cat') . " WHERE parent_id = '$parent_id'";
        if ($GLOBALS['db']->getOne($sql)) {
            /* 获取当前分类及其子分类 */
            $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, ' .
                'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order ' .
                'FROM ' . $GLOBALS['ecs']->table('article_cat') . ' AS a ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('article_cat') . ' AS b ON b.parent_id = a.cat_id ' .
                "WHERE a.parent_id = '$parent_id' AND a.cat_type=1 ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";
        } else {
            /* 获取当前分类及其父分类 */
            $sql = 'SELECT a.cat_id, a.cat_name, b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order ' .
                'FROM ' . $GLOBALS['ecs']->table('article_cat') . ' AS a ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('article_cat') . ' AS b ON b.parent_id = a.cat_id ' .
                "WHERE b.parent_id = '$parent_id' AND b.cat_type = 1 ORDER BY sort_order ASC";
        }
        $res = $GLOBALS['db']->getAll($sql);

        $cat_arr = [];
        foreach ($res as $row) {
            $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
            $cat_arr[$row['cat_id']]['url'] = build_uri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);

            if ($row['child_id'] != null) {
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id'] = $row['child_id'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url'] = build_uri('article_cat', ['acid' => $row['child_id']], $row['child_name']);
            }
        }

        return $cat_arr;
    }

    /**
     * 获得指定文章分类的所有上级分类
     *
     * @access  public
     * @param   integer $cat 分类编号
     * @return  array
     */
    public function get_article_parent_cats($cat)
    {
        if ($cat == 0) {
            return [];
        }

        $arr = $GLOBALS['db']->getAll('SELECT cat_id, cat_name, parent_id FROM ' . $GLOBALS['ecs']->table('article_cat'));

        if (empty($arr)) {
            return [];
        }

        $index = 0;
        $cats = [];

        while (1) {
            foreach ($arr as $row) {
                if ($cat == $row['cat_id']) {
                    $cat = $row['parent_id'];

                    $cats[$index]['cat_id'] = $row['cat_id'];
                    $cats[$index]['cat_name'] = $row['cat_name'];

                    $index++;
                    break;
                }
            }

            if ($index == 0 || $cat == 0) {
                break;
            }
        }

        return $cats;
    }

    /**
     * 取得文章列表：用于商品关联文章
     * @param   object $filters 过滤条件
     */
    public function get_article_list($filter)
    {
        /* 创建数据容器对象 */
        $ol = new OptionList();

        /* 取得过滤条件 */
        $where = ' WHERE a.cat_id = c.cat_id AND c.cat_type = 1 ';
        $where .= isset($filter->title) ? " AND a.title LIKE '%" . mysql_like_quote($filter->title) . "%'" : '';

        /* 取得数据 */
        $sql = 'SELECT a.article_id, a.title ' .
            'FROM ' . $GLOBALS['ecs']->table('article') . ' AS a, ' . $GLOBALS['ecs']->table('article_cat') . ' AS c ' . $where;
        $res = $GLOBALS['db']->query($sql);

        foreach ($res as $row) {
            $ol->add_option($row['article_id'], $row['title']);
        }

        /* 生成列表 */
        $ol->build_select();
    }

    /**
     * 获得指定分类下的子分类的数组
     *
     * @access  public
     * @param   int $cat_id 分类的ID
     * @param   int $selected 当前选中分类的ID
     * @param   boolean $re_type 返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int $level 限定返回的级数。为0时返回所有级数
     * @return  mix
     */
    public function article_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
    {
        static $res = null;

        if ($res === null) {
            $data = read_static_cache('art_cat_pid_releate');
            if ($data === false) {
                $sql = "SELECT c.*, COUNT(s.cat_id) AS has_children, COUNT(a.article_id) AS aricle_num " .
                    ' FROM ' . $GLOBALS['ecs']->table('article_cat') . " AS c" .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('article_cat') . " AS s ON s.parent_id=c.cat_id" .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('article') . " AS a ON a.cat_id=c.cat_id" .
                    " GROUP BY c.cat_id " .
                    " ORDER BY parent_id, sort_order ASC";
                $res = $GLOBALS['db']->getAll($sql);
                write_static_cache('art_cat_pid_releate', $res);
            } else {
                $res = $data;
            }
        }

        if (empty($res) == true) {
            return $re_type ? '' : [];
        }

        $options = article_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

        /* 截取到指定的缩减级别 */
        if ($level > 0) {
            if ($cat_id == 0) {
                $end_level = $level;
            } else {
                $first_item = reset($options); // 获取第一个元素
                $end_level = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options as $key => $val) {
                if ($val['level'] >= $end_level) {
                    unset($options[$key]);
                }
            }
        }

        $pre_key = 0;
        foreach ($options as $key => $value) {
            $options[$key]['has_children'] = 1;
            if ($pre_key > 0) {
                if ($options[$pre_key]['cat_id'] == $options[$key]['parent_id']) {
                    $options[$pre_key]['has_children'] = 1;
                }
            }
            $pre_key = $key;
        }

        if ($re_type == true) {
            $select = '';
            foreach ($options as $var) {
                $select .= '<option value="' . $var['cat_id'] . '" ';
                $select .= ' cat_type="' . $var['cat_type'] . '" ';
                $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
            }

            return $select;
        } else {
            foreach ($options as $key => $value) {
                $options[$key]['url'] = build_uri('article_cat', ['acid' => $value['cat_id']], $value['cat_name']);
            }
            return $options;
        }
    }

    /**
     * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
     *
     * @access  private
     * @param   int $cat_id 上级分类ID
     * @param   array $arr 含有所有分类的数组
     * @param   int $level 级别
     * @return  void
     */
    public function article_cat_options($spec_cat_id, $arr)
    {
        static $cat_options = [];

        if (isset($cat_options[$spec_cat_id])) {
            return $cat_options[$spec_cat_id];
        }

        if (!isset($cat_options[0])) {
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = [];
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = [$cat_id];
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = [];
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            $cat_options[0] = $options;
        } else {
            $options = $cat_options[0];
        }

        if (!$spec_cat_id) {
            return $options;
        } else {
            if (empty($options[$spec_cat_id])) {
                return [];
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options as $key => $value) {
                if ($key != $spec_cat_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }

            $spec_cat_id_array = [];
            foreach ($options as $key => $value) {
                if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }
}
