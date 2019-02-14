<?php

namespace App\Services;

/**
 * Class TagService
 * @package App\Services
 */
class TagService
{

/**
 * 获得指定用户、商品的所有标记
 *
 * @access  public
 * @param   integer $goods_id
 * @param   integer $user_id
 * @return  array
 */
    public function get_tags($goods_id = 0, $user_id = 0)
    {
        $where = '';
        if ($goods_id > 0) {
            $where .= " goods_id = '$goods_id'";
        }

        if ($user_id > 0) {
            if ($goods_id > 0) {
                $where .= " AND";
            }
            $where .= " user_id = '$user_id'";
        }

        if ($where > '') {
            $where = ' WHERE' . $where;
        }

        $sql = 'SELECT tag_id, user_id, tag_words, COUNT(tag_id) AS tag_count' .
        ' FROM ' . $GLOBALS['ecs']->table('tag') .
        "$where GROUP BY tag_words";
        $arr = $GLOBALS['db']->getAll($sql);

        return $arr;
    }

    /**
     *  获取用户的tags
     *
     * @access  public
     * @param   int $user_id 用户ID
     *
     * @return array        $arr            tags列表
     */
    public function get_user_tags($user_id = 0)
    {
        if (empty($user_id)) {
            $GLOBALS['error_no'] = 1;

            return false;
        }

        $tags = get_tags(0, $user_id);

        if (!empty($tags)) {
            color_tag($tags);
        }

        return $tags;
    }

    /**
     *  验证性的删除某个tag
     *
     * @access  public
     * @param   int $tag_words tag的ID
     * @param   int $user_id 用户的ID
     *
     * @return  boolen      bool
     */
    public function delete_tag($tag_words, $user_id)
    {
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('tag') .
        " WHERE tag_words = '$tag_words' AND user_id = '$user_id'";

        return $GLOBALS['db']->query($sql);
    }

    /**
     * 添加商品标签
     *
     * @access  public
     * @param   integer $id
     * @param   string $tag
     * @return  void
     */
    public function add_tag($id, $tag)
    {
        if (empty($tag)) {
            return;
        }

        $arr = explode(',', $tag);

        foreach ($arr as $val) {
            /* 检查是否重复 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table("tag") .
            " WHERE user_id = '" . session('user_id') . "' AND goods_id = '$id' AND tag_words = '$val'";

            if ($GLOBALS['db']->getOne($sql) == 0) {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table("tag") . " (user_id, goods_id, tag_words) " .
                "VALUES ('" . session('user_id') . "', '$id', '$val')";
                $GLOBALS['db']->query($sql);
            }
        }
    }

    /**
     * 标签着色
     *
     * @access   public
     * @param    array
     * @author   Xuan Yan
     *
     * @return   none
     */
    public function color_tag(&$tags)
    {
        $tagmark = [
        ['color' => '#666666', 'size' => '0.8em', 'ifbold' => 1],
        ['color' => '#333333', 'size' => '0.9em', 'ifbold' => 0],
        ['color' => '#006699', 'size' => '1.0em', 'ifbold' => 1],
        ['color' => '#CC9900', 'size' => '1.1em', 'ifbold' => 0],
        ['color' => '#666633', 'size' => '1.2em', 'ifbold' => 1],
        ['color' => '#993300', 'size' => '1.3em', 'ifbold' => 0],
        ['color' => '#669933', 'size' => '1.4em', 'ifbold' => 1],
        ['color' => '#3366FF', 'size' => '1.5em', 'ifbold' => 0],
        ['color' => '#197B30', 'size' => '1.6em', 'ifbold' => 1],
    ];

        $maxlevel = count($tagmark);
        $tcount = $scount = [];

        foreach ($tags as $val) {
            $tcount[] = $val['tag_count']; // 获得tag个数数组
        }
        $tcount = array_unique($tcount); // 去除相同个数的tag

        sort($tcount); // 从小到大排序

        $tempcount = count($tcount); // 真正的tag级数
        $per = $maxlevel >= $tempcount ? 1 : $maxlevel / ($tempcount - 1);

        foreach ($tcount as $key => $val) {
            $lvl = floor($per * $key);
            $scount[$val] = $lvl; // 计算不同个数的tag相对应的着色数组key
        }

        $rewrite = intval($GLOBALS['_CFG']['rewrite']) > 0;

        /* 遍历所有标签，根据引用次数设定字体大小 */
        foreach ($tags as $key => $val) {
            $lvl = $scount[$val['tag_count']]; // 着色数组key

            $tags[$key]['color'] = $tagmark[$lvl]['color'];
            $tags[$key]['size'] = $tagmark[$lvl]['size'];
            $tags[$key]['bold'] = $tagmark[$lvl]['ifbold'];
            if ($rewrite) {
                if (strtolower(EC_CHARSET) !== 'utf-8') {
                    $tags[$key]['url'] = 'tag-' . urlencode(urlencode($val['tag_words'])) . '.html';
                } else {
                    $tags[$key]['url'] = 'tag-' . urlencode($val['tag_words']) . '.html';
                }
            } else {
                $tags[$key]['url'] = 'search.php?keywords=' . urlencode($val['tag_words']);
            }
        }
        shuffle($tags);
    }
}
