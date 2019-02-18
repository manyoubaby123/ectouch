<?php

namespace app\web\controller;

class Article extends Init
{
    public function index()
    {
        $_REQUEST['id'] = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $article_id = $_REQUEST['id'];
        if (isset($_REQUEST['cat_id']) && $_REQUEST['cat_id'] < 0) {
            $article_id = $GLOBALS['db']->getOne("SELECT article_id FROM " . $GLOBALS['ecs']->table('article') . " WHERE cat_id = '" . intval($_REQUEST['cat_id']) . "' ");
        }

        $cache_id = sprintf('%X', crc32($_REQUEST['id'] . '-' . $GLOBALS['_CFG']['lang']));

        if (!$GLOBALS['smarty']->is_cached('article.dwt', $cache_id)) {
            /* 文章详情 */
            $article = $this->get_article_info($article_id);

            if (empty($article)) {
                return ecs_header("Location: ./\n");
            }

            if (!empty($article['link']) && $article['link'] != 'http://' && $article['link'] != 'https://') {
                return ecs_header("location:$article[link]\n");
            }

            $GLOBALS['smarty']->assign('article_categories', article_categories_tree($article_id)); //文章分类树
            $GLOBALS['smarty']->assign('categories', get_categories_tree());  // 分类树
            $GLOBALS['smarty']->assign('helps', get_shop_help()); // 网店帮助
            $GLOBALS['smarty']->assign('top_goods', get_top10());    // 销售排行
            $GLOBALS['smarty']->assign('best_goods', get_recommend_goods('best'));       // 推荐商品
            $GLOBALS['smarty']->assign('new_goods', get_recommend_goods('new'));        // 最新商品
            $GLOBALS['smarty']->assign('hot_goods', get_recommend_goods('hot'));        // 热点文章
            $GLOBALS['smarty']->assign('promotion_goods', get_promote_goods());    // 特价商品
            $GLOBALS['smarty']->assign('related_goods', $this->article_related_goods($_REQUEST['id']));  // 特价商品
            $GLOBALS['smarty']->assign('id', $article_id);
            $GLOBALS['smarty']->assign('username', session('user_name'));
            $GLOBALS['smarty']->assign('email', session('email'));
            $GLOBALS['smarty']->assign('type', '1');
            $GLOBALS['smarty']->assign('promotion_info', get_promotion_info());

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $GLOBALS['smarty']->assign('enabled_captcha', 1);
                $GLOBALS['smarty']->assign('rand', mt_rand());
            }

            $GLOBALS['smarty']->assign('article', $article);
            $GLOBALS['smarty']->assign('keywords', htmlspecialchars($article['keywords']));
            $GLOBALS['smarty']->assign('description', htmlspecialchars($article['description']));

            $catlist = [];
            foreach (get_article_parent_cats($article['cat_id']) as $k => $v) {
                $catlist[] = $v['cat_id'];
            }

            assign_template('a', $catlist);

            $position = assign_ur_here($article['cat_id'], $article['title']);
            $GLOBALS['smarty']->assign('page_title', $position['title']);    // 页面标题
            $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 当前位置
            $GLOBALS['smarty']->assign('comment_type', 1);

            /* 相关商品 */
            $sql = "SELECT a.goods_id, g.goods_name " .
                "FROM " . $GLOBALS['ecs']->table('goods_article') . " AS a, " . $GLOBALS['ecs']->table('goods') . " AS g " .
                "WHERE a.goods_id = g.goods_id " .
                "AND a.article_id = '$_REQUEST[id]' ";
            $GLOBALS['smarty']->assign('goods_list', $GLOBALS['db']->getAll($sql));

            /* 上一篇下一篇文章 */
            $next_article = $GLOBALS['db']->getRow("SELECT article_id, title FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id > $article_id AND cat_id=$article[cat_id] AND is_open=1 LIMIT 1");
            if (!empty($next_article)) {
                $next_article['url'] = build_uri('article', ['aid' => $next_article['article_id']], $next_article['title']);
                $GLOBALS['smarty']->assign('next_article', $next_article);
            }

            $prev_aid = $GLOBALS['db']->getOne("SELECT max(article_id) FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id < $article_id AND cat_id=$article[cat_id] AND is_open=1");
            if (!empty($prev_aid)) {
                $prev_article = $GLOBALS['db']->getRow("SELECT article_id, title FROM " . $GLOBALS['ecs']->table('article') . " WHERE article_id = $prev_aid");
                $prev_article['url'] = build_uri('article', ['aid' => $prev_article['article_id']], $prev_article['title']);
                $GLOBALS['smarty']->assign('prev_article', $prev_article);
            }

            assign_dynamic('article');
        }
        if (isset($article) && $article['cat_id'] > 2) {
            return $GLOBALS['smarty']->display('article.dwt', $cache_id);
        } else {
            return $GLOBALS['smarty']->display('article_pro.dwt', $cache_id);
        }
    }

    /**
     * 获得指定的文章的详细信息
     *
     * @access  private
     * @param   integer $article_id
     * @return  array
     */
    private function get_article_info($article_id)
    {
        /* 获得文章的信息 */
        $sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank " .
            "FROM " . $GLOBALS['ecs']->table('article') . " AS a " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('comment') . " AS r ON r.id_value = a.article_id AND comment_type = 1 " .
            "WHERE a.is_open = 1 AND a.article_id = '$article_id' GROUP BY a.article_id";
        $row = $GLOBALS['db']->getRow($sql);

        if ($row !== false) {
            $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
            $row['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示

            /* 作者信息如果为空，则用网站名称替换 */
            if (empty($row['author']) || $row['author'] == '_SHOPHELP') {
                $row['author'] = $GLOBALS['_CFG']['shop_name'];
            }
        }

        return $row;
    }

    /**
     * 获得文章关联的商品
     *
     * @access  public
     * @param   integer $id
     * @return  array
     */
    private function article_related_goods($id)
    {
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '". session('discount') ."') AS shop_price, " .
            'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' ga ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '". session('user_rank') ."' " .
            "WHERE ga.article_id = '$id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
        $res = $GLOBALS['db']->query($sql);

        $arr = [];
        foreach ($res as $row) {
            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            $arr[$row['goods_id']]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            if ($row['promote_price'] > 0) {
                $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
            } else {
                $arr[$row['goods_id']]['promote_price'] = 0;
            }
        }

        return $arr;
    }
}
