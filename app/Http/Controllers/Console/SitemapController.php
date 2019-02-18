<?php

namespace App\Http\Controllers\Console;

use App\Libraries\GoogleSitemap;
use App\Libraries\GoogleSitemapItem;

class SitemapController extends InitController
{
    public function actionIndex()
    {
        /* 检查权限 */
        admin_priv('sitemap');

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            /*------------------------------------------------------ */
            //-- 设置更新频率
            /*------------------------------------------------------ */

            $config = unserialize($GLOBALS['_CFG']['sitemap']);
            $GLOBALS['smarty']->assign('config', $config);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['sitemap']);
            $GLOBALS['smarty']->assign('arr_changefreq', [1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1]);
            return $GLOBALS['smarty']->display('sitemap.htm');
        } else {
            /*------------------------------------------------------ */
            //-- 生成站点地图
            /*------------------------------------------------------ */

            $domain = $GLOBALS['ecs']->url();
            $today = local_date('Y-m-d');

            $sm = new GoogleSitemap();
            $smi = new GoogleSitemapItem($domain, $today, $_POST['homepage_changefreq'], $_POST['homepage_priority']);
            $sm->add_item($smi);

            $config = [
                'homepage_changefreq' => $_POST['homepage_changefreq'],
                'homepage_priority' => $_POST['homepage_priority'],
                'category_changefreq' => $_POST['category_changefreq'],
                'category_priority' => $_POST['category_priority'],
                'content_changefreq' => $_POST['content_changefreq'],
                'content_priority' => $_POST['content_priority'],
            ];
            $config = serialize($config);

            $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('shop_config') . " SET VALUE='$config' WHERE code='sitemap'");

            /* 商品分类 */
            $sql = "SELECT cat_id,cat_name FROM " . $GLOBALS['ecs']->table('category') . " ORDER BY parent_id";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $smi = new GoogleSitemapItem(
                    $domain . build_uri('category', ['cid' => $row['cat_id']], $row['cat_name']),
                    $today,
                    $_POST['category_changefreq'],
                    $_POST['category_priority']
                );
                $sm->add_item($smi);
            }

            /* 文章分类 */
            $sql = "SELECT cat_id,cat_name FROM " . $GLOBALS['ecs']->table('article_cat') . " WHERE cat_type=1";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $smi = new GoogleSitemapItem(
                    $domain . build_uri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']),
                    $today,
                    $_POST['category_changefreq'],
                    $_POST['category_priority']
                );
                $sm->add_item($smi);
            }

            /* 商品 */
            $sql = "SELECT goods_id, goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_delete = 0";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $smi = new GoogleSitemapItem(
                    $domain . build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']),
                    $today,
                    $_POST['content_changefreq'],
                    $_POST['content_priority']
                );
                $sm->add_item($smi);
            }

            /* 文章 */
            $sql = "SELECT article_id,title,file_url,open_type FROM " . $GLOBALS['ecs']->table('article') . " WHERE is_open=1";
            $res = $GLOBALS['db']->query($sql);

            foreach ($res as $row) {
                $article_url = $row['open_type'] != 1 ? build_uri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);
                $smi = new GoogleSitemapItem(
                    $domain . $article_url,
                    $today,
                    $_POST['content_changefreq'],
                    $_POST['content_priority']
                );
                $sm->add_item($smi);
            }

            clear_cache_files();    // 清除缓存

            $sm_file = public_path('sitemaps.xml');
            if ($sm->build($sm_file)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['generate_success'], $GLOBALS['ecs']->url() . "sitemaps.xml"));
            } else {
                $sm_file = public_path(DATA_DIR . '/sitemaps.xml');
                if ($sm->build($sm_file)) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['generate_success'], $GLOBALS['ecs']->url() . DATA_DIR . '/sitemaps.xml'));
                } else {
                    return sys_msg(sprintf($GLOBALS['_LANG']['generate_failed']));
                }
            }
        }
    }
}
