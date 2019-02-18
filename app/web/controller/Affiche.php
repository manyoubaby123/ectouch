<?php

namespace app\web\controller;

class Affiche extends Init
{
    public function index()
    {
        /* 没有指定广告的id及跳转地址 */
        if (empty($_GET['ad_id'])) {
            return ecs_header("Location: index.php\n");
        } else {
            $ad_id = intval($_GET['ad_id']);
        }

        /* act 操作项的初始化*/
        $_GET['act'] = !empty($_GET['act']) ? trim($_GET['act']) : '';

        if ($_GET['act'] == 'js') {
            /* 编码转换 */
            if (empty($_GET['charset'])) {
                $_GET['charset'] = 'UTF8';
            }

            header('Content-type: application/x-javascript; charset=' . ($_GET['charset'] == 'UTF8' ? 'utf-8' : $_GET['charset']));

            $url = $GLOBALS['ecs']->url();
            $str = "";

            /* 取得广告的信息 */
            $sql = 'SELECT ad.ad_id, ad.ad_name, ad.ad_link, ad.ad_code ' .
                'FROM ' . $GLOBALS['ecs']->table('ad') . ' AS ad ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON ad.position_id = p.position_id ' .
                "WHERE ad.ad_id = '$ad_id' and " . gmtime() . " >= ad.start_time and " . gmtime() . "<= ad.end_time";

            $ad_info = $GLOBALS['db']->getRow($sql);

            if (!empty($ad_info)) {
                /* 转换编码 */
                if ($_GET['charset'] != 'UTF8') {
                    $ad_info['ad_name'] = ecs_iconv('UTF8', $_GET['charset'], $ad_info['ad_name']);
                    $ad_info['ad_code'] = ecs_iconv('UTF8', $_GET['charset'], $ad_info['ad_code']);
                }

                /* 初始化广告的类型和来源 */
                $_GET['type'] = !empty($_GET['type']) ? intval($_GET['type']) : 0;
                $_GET['from'] = !empty($_GET['from']) ? urlencode($_GET['from']) : '';

                $str = '';
                switch ($_GET['type']) {
                    case '0':
                        /* 图片广告 */
                        $src = (strpos($ad_info['ad_code'], 'http://') === false && strpos($ad_info['ad_code'], 'https://') === false) ? $url . DATA_DIR . "/afficheimg/$ad_info[ad_code]" : $ad_info['ad_code'];
                        $str = '<a href="' . $url . 'affiche.php?ad_id=' . $ad_info['ad_id'] . '&from=' . $_GET['from'] . '&uri=' . urlencode($ad_info['ad_link']) . '" target="_blank">' .
                            '<img src="' . $src . '" border="0" alt="' . $ad_info['ad_name'] . '" /></a>';
                        break;

                    case '1':
                        /* Falsh广告 */
                        $src = (strpos($ad_info['ad_code'], 'http://') === false && strpos($ad_info['ad_code'], 'https://') === false) ? $url . DATA_DIR . '/afficheimg/' . $ad_info['ad_code'] : $ad_info['ad_code'];
                        $str = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"> <param name="movie" value="' . $src . '"><param name="quality" value="high"><embed src="' . $src . '" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed></object>';
                        break;

                    case '2':
                        /* 代码广告 */
                        $str = $ad_info['ad_code'];
                        break;

                    case 3:
                        /* 文字广告 */
                        $str = '<a href="' . $url . 'affiche.php?ad_id=' . $ad_info['ad_id'] . '&from=' . $_GET['from'] . '&uri=' . urlencode($ad_info['ad_link']) . '" target="_blank">' . nl2br(htmlspecialchars(addslashes($ad_info['ad_code']))) . '</a>';
                        break;
                }
            }
            echo "document.writeln('$str');";
        } else {
            /* 获取投放站点的名称 */

            $site_name = !empty($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($GLOBALS['_LANG']['self_site']);

            /* 商品的ID */
            $goods_id = !empty($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;

            /* 存入SESSION中,购物后一起存到订单数据表里 */
            session(['from_ad' => $ad_id]);
            session(['referer' => stripslashes($site_name)]);

            /* 如果是商品的站外JS */
            if ($ad_id == '-1') {
                $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('adsense') . " WHERE from_ad = '-1' AND referer = '" . $site_name . "'";
                if ($GLOBALS['db']->getOne($sql) > 0) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('adsense') . " SET clicks = clicks + 1 WHERE from_ad = '-1' AND referer = '" . $site_name . "'";
                } else {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('adsense') . "(from_ad, referer, clicks) VALUES ('-1', '" . $site_name . "', '1')";
                }
                $GLOBALS['db']->query($sql);
                //$GLOBALS['db']->autoReplace($GLOBALS['ecs']->table('adsense'), array('from_ad' => -1, 'referer' => $site_name, 'clicks' => 1), array('clicks' => 1));
                $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = $goods_id";
                $res = $GLOBALS['db']->query($sql);

                $row = $GLOBALS['db']->fetchRow($res);

                $uri = build_uri('goods', ['gid' => $goods_id], $row['goods_name']);

                return ecs_header("Location: $uri\n");
            } else {
                /* 更新站内广告的点击次数 */
                $GLOBALS['db']->query('UPDATE ' . $GLOBALS['ecs']->table('ad') . " SET click_count = click_count + 1 WHERE ad_id = '$ad_id'");

                $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('adsense') . " WHERE from_ad = '" . $ad_id . "' AND referer = '" . $site_name . "'";
                if ($GLOBALS['db']->getOne($sql) > 0) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('adsense') . " SET clicks = clicks + 1 WHERE from_ad = '" . $ad_id . "' AND referer = '" . $site_name . "'";
                } else {
                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('adsense') . "(from_ad, referer, clicks) VALUES ('" . $ad_id . "', '" . $site_name . "', '1')";
                }
                $GLOBALS['db']->query($sql);

                $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('ad') . " WHERE ad_id = '$ad_id'";
                $ad_info = $GLOBALS['db']->getRow($sql);
                /* 跳转到广告的链接页面 */
                if (!empty($ad_info['ad_link'])) {
                    $uri = (strpos($ad_info['ad_link'], 'http://') === false && strpos($ad_info['ad_link'], 'https://') === false) ? $GLOBALS['ecs']->http() . urldecode($ad_info['ad_link']) : urldecode($ad_info['ad_link']);
                } else {
                    $uri = $GLOBALS['ecs']->url();
                }

                return ecs_header("Location: $uri\n");
            }
        }
    }
}
