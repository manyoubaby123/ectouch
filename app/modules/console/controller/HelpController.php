<?php

namespace App\Http\Console\Controller;

class HelpController extends InitController
{
    public function index()
    {
        $get_keyword = trim($_GET['al']); // 获取关键字
        header("location:http://www.ectouch.cn/do.php?k=" . $get_keyword . "&v=" . $GLOBALS['_CFG']['ecs_version'] . "&l=" . $GLOBALS['_CFG']['lang'] . "&c=" . EC_CHARSET);
    }
}
