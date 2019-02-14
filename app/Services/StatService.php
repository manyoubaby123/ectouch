<?php

namespace App\Services;

/**
 * Class StatService
 * @package App\Services
 */
class StatService
{

/**
 * 统计访问信息
 *
 * @access  public
 * @return  void
 */
    public function visit_stats()
    {
        if (isset($GLOBALS['_CFG']['visit_stats']) && $GLOBALS['_CFG']['visit_stats'] == 'off') {
            return;
        }
        $time = gmtime();
        /* 检查客户端是否存在访问统计的cookie */
        $visit_times = (!empty($_COOKIE['ECS']['visit_times'])) ? intval($_COOKIE['ECS']['visit_times']) + 1 : 1;
        setcookie('ECS[visit_times]', $visit_times, $time + 86400 * 365, '/');

        $browser = get_user_browser();
        $os = get_os();
        $ip = real_ip();
        $area = ecs_geoip($ip);

        /* 语言 */
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $pos = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ';');
            $lang = addslashes(($pos !== false) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, $pos) : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $lang = '';
        }

        /* 来源 */
        if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9) {
            $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
            if ($pos !== false) {
                $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
                $path = substr($_SERVER['HTTP_REFERER'], $pos);

                /* 来源关键字 */
                if (!empty($domain) && !empty($path)) {
                    save_searchengine_keyword($domain, $path);
                }
            } else {
                $domain = $path = '';
            }
        } else {
            $domain = $path = '';
        }

        $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('stats') . ' ( ' .
        'ip_address, visit_times, browser, system, language, area, ' .
        'referer_domain, referer_path, access_url, access_time' .
        ') VALUES (' .
        "'$ip', '$visit_times', '$browser', '$os', '$lang', '$area', " .
        "'" . htmlspecialchars(addslashes($domain)) . "', '" . htmlspecialchars(addslashes($path)) . "', '" . htmlspecialchars(addslashes(PHP_SELF)) . "', '" . $time . "')";
        $GLOBALS['db']->query($sql);
    }
}
