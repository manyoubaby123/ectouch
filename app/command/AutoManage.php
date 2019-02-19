<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoManage extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app:auto-manage')
            ->setDescription('Auto manage');
    }

    protected function execute(Input $input, Output $output)
    {
        $time = gmtime();
        $limit = !empty($cron['auto_manage_count']) ? $cron['auto_manage_count'] : 5;
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('auto_manage') . " WHERE starttime > '0' AND starttime <= '$time' OR endtime > '0' AND endtime <= '$time' LIMIT $limit";
        $autodb = $GLOBALS['db']->getAll($sql);
        foreach ($autodb as $key => $val) {
            $del = $up = false;
            if ($val['type'] == 'goods') {
                $goods = true;
                $where = " WHERE goods_id = '$val[item_id]'";
            } else {
                $goods = false;
                $where = " WHERE article_id = '$val[item_id]'";
            }

            //上下架判断
            if (!empty($val['starttime']) && !empty($val['endtime'])) {
                //上下架时间均设置
                if ($val['starttime'] <= $time && $time < $val['endtime']) {
                    //上架时间 <= 当前时间 < 下架时间
                    $up = true;
                    $del = false;
                } elseif ($val['starttime'] >= $time && $time > $val['endtime']) {
                    //下架时间 <= 当前时间 < 上架时间
                    $up = false;
                    $del = false;
                } elseif ($val['starttime'] == $time && $time == $val['endtime']) {
                    //下架时间 == 当前时间 == 上架时间
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('auto_manage') . "WHERE item_id = '$val[item_id]' AND type = '$val[type]'";
                    $GLOBALS['db']->query($sql);
                    continue;
                } elseif ($val['starttime'] > $val['endtime']) {
                    // 下架时间 < 上架时间 < 当前时间
                    $up = true;
                    $del = true;
                } elseif ($val['starttime'] < $val['endtime']) {
                    // 上架时间 < 下架时间 < 当前时间
                    $up = false;
                    $del = true;
                } else {
                    // 上架时间 = 下架时间 < 当前时间
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('auto_manage') . "WHERE item_id = '$val[item_id]' AND type = '$val[type]'";
                    $GLOBALS['db']->query($sql);

                    continue;
                }
            } elseif (!empty($val['starttime'])) {
                //只设置了上架时间
                $up = true;
                $del = true;
            } else {
                //只设置了下架时间
                $up = false;
                $del = true;
            }

            if ($goods) {
                if ($up) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET is_on_sale = 1 $where";
                } else {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET is_on_sale = 0 $where";
                }
            } else {
                if ($up) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('article') . " SET is_open = 1 $where";
                } else {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('article') . " SET is_open = 0 $where";
                }
            }
            $GLOBALS['db']->query($sql);
            if ($del) {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('auto_manage') . "WHERE item_id = '$val[item_id]' AND type = '$val[type]'";
                $GLOBALS['db']->query($sql);
            } else {
                if ($up) {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('auto_manage') . " SET starttime = 0 WHERE item_id = '$val[item_id]' AND type = '$val[type]'";
                } else {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('auto_manage') . " SET endtime = 0 WHERE item_id = '$val[item_id]' AND type = '$val[type]'";
                }
                $GLOBALS['db']->query($sql);
            }
        }
    }
}
