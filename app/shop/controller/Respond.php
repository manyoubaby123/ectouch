<?php

namespace app\shop\controller;

class Respond extends Init
{
    public function index()
    {
        load_helper(['payment', 'order']);
        /* 支付方式代码 */
        $pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';

        /* 参数是否为空 */
        if (empty($pay_code)) {
            $msg = $GLOBALS['_LANG']['pay_not_exist'];
        } else {
            /* 检查code里面有没有问号 */
            if (strpos($pay_code, '?') !== false) {
                $arr1 = explode('?', $pay_code);
                $arr2 = explode('=', $arr1[1]);

                $_REQUEST['code'] = $arr1[0];
                $_REQUEST[$arr2[0]] = $arr2[1];
                $_GET['code'] = $arr1[0];
                $_GET[$arr2[0]] = $arr2[1];
                $pay_code = $arr1[0];
            }

            /* 判断是否启用 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
            if ($GLOBALS['db']->getOne($sql) == 0) {
                $msg = $GLOBALS['_LANG']['pay_disabled'];
            } else {
                $plugin = '\\App\\Plugins\\Payment\\' . parse_name($pay_code, true);

                /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
                if (class_exists($plugin)) {
                    /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
                    $payment = new $plugin();
                    $msg = (@$payment->respond()) ? $GLOBALS['_LANG']['pay_success'] : $GLOBALS['_LANG']['pay_fail'];
                } else {
                    $msg = $GLOBALS['_LANG']['pay_not_exist'];
                }
            }
        }

        $this->shopService->assign_template();
        $position = assign_ur_here();
        $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置
        $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
        $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置
        $GLOBALS['smarty']->assign('helps', get_shop_help());      // 网店帮助

        $GLOBALS['smarty']->assign('message', $msg);
        $GLOBALS['smarty']->assign('shop_url', $GLOBALS['ecs']->url());

        return $GLOBALS['smarty']->display('respond.dwt');
    }
}
