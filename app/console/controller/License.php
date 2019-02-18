<?php

namespace App\Http\Controllers\Console;

class License extends Init
{
    public function index()
    {

        /*------------------------------------------------------ */
        //-- 证书编辑页
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_edit') {
            /* 检查权限 */
            admin_priv('shop_authorized');

            load_helper('license');

            $license = get_shop_license();

            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['license_here']);
            $GLOBALS['smarty']->assign('is_download', '0');
            if ($license['certificate_id'] != '' && $license['token'] != '') {
                $GLOBALS['smarty']->assign('is_download', '1');
            }

            $GLOBALS['smarty']->assign('certificate_id', $license['certificate_id']);
            $GLOBALS['smarty']->assign('token', $license['token']);

            return $GLOBALS['smarty']->display('license.htm');
        }

        /*------------------------------------------------------ */
        //-- 证书下载
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'download') {
            /* 检查权限 */
            admin_priv('shop_authorized');

            load_helper('license');

            $license = get_shop_license();

            if ($license['certificate_id'] == '' || $license['token'] == '') {
                $links[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'license.php?act=list_edit'];
                return sys_msg($GLOBALS['_LANG']['no_license_down'], 0, $links);
            }
            /* 文件下载 */
            return ecs_header("Content-Type:text/plain");
            return ecs_header("Accept-Ranges:bytes");
            return ecs_header("Content-Disposition: attachment; filename=CERTIFICATE.CER");
            echo $license['certificate_id'] . '|' . $license['token'];
        }

        /*------------------------------------------------------ */
        //-- 证书上传
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'upload') {
            /* 检查权限 */
            admin_priv('shop_authorized');

            /* 接收上传文件 */
            /* 取出证书内容 */
            $license_arr = [];
            if (isset($_FILES['license']['error']) && $_FILES['license']['error'] == 0 && preg_match('/CER$/i', $_FILES['license']['name'])) {
                if (file_exists($_FILES['license']['tmp_name']) && is_readable($_FILES['license']['tmp_name'])) {
                    if ($license_f = fopen($_FILES['license']['tmp_name'], 'r')) {
                        $license_content = '';
                        while (!feof($license_f)) {
                            $license_content .= fgets($license_f, 4096);
                        }
                        $license_content = trim($license_content);
                        $license_content = addslashes_deep($license_content);
                        $license_arr = explode('|', $license_content);
                    }
                }
            }

            /* 恢复证书 */
            if (count($license_arr) != 2 || $license_arr[0] == '' || $license_arr[1] == '') {
                $links[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'license.php?act=list_edit'];
                return sys_msg($GLOBALS['_LANG']['fail_license'], 1, $links);
            } else {
                load_helper('main');
                load_helper('license');

                // 证书登录
                $login_result = license_login();
                if ($login_result['flag'] != 'login_succ') {
                    $links[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'license.php?act=list_edit'];
                    return sys_msg($GLOBALS['_LANG']['fail_license_login'], 1, $links);
                }

                $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . "
                SET value = '" . $license_arr[0] . "'
                WHERE code = 'certificate_id'";
                $GLOBALS['db']->query($sql);
                $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . "
                SET value = '" . $license_arr[1] . "'
                WHERE code = 'token'";
                $GLOBALS['db']->query($sql);

                $links[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'license.php?act=list_edit'];
                return sys_msg($GLOBALS['_LANG']['recover_license'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 证书删除
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'del') {
            /* 检查权限 */
            admin_priv('shop_authorized');

            $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . "
            SET value = ''
            WHERE code IN('certificate_id', 'token')";
            $GLOBALS['db']->query($sql);

            $links[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'license.php?act=list_edit'];
            return sys_msg($GLOBALS['_LANG']['delete_license'], 0, $links);
        }
    }
}
