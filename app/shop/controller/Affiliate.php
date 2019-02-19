<?php

namespace app\shop\controller;

class Affiliate extends Init
{
    public function index()
    {
        $display_mode = empty($_GET['display_mode']) ? 'javascript' : $_GET['display_mode'];

        if ($display_mode == 'javascript') {
            $charset_array = ['UTF8', 'GBK', 'gbk', 'utf8', 'GB2312', 'gb2312'];
            if (!in_array($charset, $charset_array)) {
                $charset = 'UTF8';
            }
            header('content-type: application/x-javascript; charset=' . ($charset == 'UTF8' ? 'utf-8' : $charset));
        }

        $goodsid = intval($_GET['gid']);
        $userid = intval($_GET['u']);
        $type = intval($_GET['type']);

        $tpl = ROOT_PATH . DATA_DIR . '/affiliate.html';

        $goods_url = $GLOBALS['ecs']->url() . "goods.php?u=$userid&id=";
        $goods = get_goods_info($goodsid);
        $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $GLOBALS['ecs']->url() . $goods['goods_thumb'] : $goods['goods_thumb'];
        $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $GLOBALS['ecs']->url() . $goods['goods_img'] : $goods['goods_img'];
        $goods['shop_price'] = price_format($goods['shop_price']);

        /*if ($charset != 'UTF8')
        {
            $goods['goods_name']  = ecs_iconv('UTF8', $charset, htmlentities($goods['goods_name'], ENT_QUOTES, 'UTF-8'));
            $goods['shop_price'] = ecs_iconv('UTF8', $charset, $goods['shop_price']);
        }*/

        $this->assign('goods', $goods);
        $this->assign('userid', $userid);
        $this->assign('type', $type);

        $this->assign('url', $GLOBALS['ecs']->url());
        $this->assign('goods_url', $goods_url);

        $output = $GLOBALS['smarty']->display($tpl);
        $output = str_replace("\r", '', $output);
        $output = str_replace("\n", '', $output);

        if ($display_mode == 'javascript') {
            echo "document.write('$output');";
        } elseif ($display_mode == 'iframe') {
            echo $output;
        }
    }
}
