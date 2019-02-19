<?php

namespace app\services;

/**
 * Class ShopService
 * @package app\services
 */
class ShopService
{

    /**
     * 取得自定义导航栏列表
     * @param   string $type 位置，如top、bottom、middle
     * @return  array         列表
     */
    public function get_navigator($ctype = '', $catlist = [])
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('nav') . '
            WHERE ifshow = \'1\' ORDER BY type, vieworder';
        $res = $GLOBALS['db']->query($sql);

        $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);

        if (intval($GLOBALS['_CFG']['rewrite'])) {
            if (strpos($cur_url, '-')) {
                preg_match('/([a-z]*)-([0-9]*)/', $cur_url, $matches);
                $cur_url = $matches[1] . '.php?id=' . $matches[2];
            }
        } else {
            $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);
        }

        $noindex = false;
        $active = 0;
        $navlist = [
            'top' => [],
            'middle' => [],
            'bottom' => []
        ];
        foreach ($res as $row) {
            $navlist[$row['type']][] = [
                'name' => $row['name'],
                'opennew' => $row['opennew'],
                'url' => $row['url'],
                'ctype' => $row['ctype'],
                'cid' => $row['cid'],
            ];
        }

        /*遍历自定义是否存在currentPage*/
        foreach ($navlist['middle'] as $k => $v) {
            $condition = empty($ctype) ? (strpos($cur_url, $v['url']) === 0) : (strpos($cur_url, $v['url']) === 0 && strlen($cur_url) == strlen($v['url']));
            if ($condition) {
                $navlist['middle'][$k]['active'] = 1;
                $noindex = true;
                $active += 1;
            }
        }

        if (!empty($ctype) && $active < 1) {
            foreach ($catlist as $key => $val) {
                foreach ($navlist['middle'] as $k => $v) {
                    if (!empty($v['ctype']) && $v['ctype'] == $ctype && $v['cid'] == $val && $active < 1) {
                        $navlist['middle'][$k]['active'] = 1;
                        $noindex = true;
                        $active += 1;
                    }
                }
            }
        }

        if ($noindex == false) {
            $navlist['config']['index'] = 1;
        }

        return $navlist;
    }

    /**
     * 显示一个提示信息
     *
     * @access  public
     * @param   string $content
     * @param   string $link
     * @param   string $href
     * @param   string $type 信息类型：warning, error, info
     * @param   string $auto_redirect 是否自动跳转
     * @return  void
     */
    public function show_message($content, $links = '', $hrefs = '', $type = 'info', $auto_redirect = true)
    {
        $this->shopService->assign_template();

        $msg['content'] = $content;
        if (is_array($links) && is_array($hrefs)) {
            if (!empty($links) && count($links) == count($hrefs)) {
                foreach ($links as $key => $val) {
                    $msg['url_info'][$val] = $hrefs[$key];
                }
                $msg['back_url'] = $hrefs['0'];
            }
        } else {
            $link = empty($links) ? $GLOBALS['_LANG']['back_up_page'] : $links;
            $href = empty($hrefs) ? 'javascript:history.back()' : $hrefs;
            $msg['url_info'][$link] = $href;
            $msg['back_url'] = $href;
        }

        $msg['type'] = $type;
        $position = assign_ur_here(0, $GLOBALS['_LANG']['sys_msg']);
        $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置

        if (is_null($GLOBALS['smarty']->get_template_vars('helps'))) {
            $GLOBALS['smarty']->assign('helps', get_shop_help()); // 网店帮助
        }

        $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);
        $GLOBALS['smarty']->assign('message', $msg);
        return $GLOBALS['smarty']->fetch('message.dwt');
    }
}
