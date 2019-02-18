<?php

namespace App\Http\Controllers\Console;

use App\Libraries\Mysql;

class Convert extends Init
{
    public function index()
    {
        if ($_REQUEST['act'] == 'main') {
            admin_priv('convert');

            /* 取得插件文件中的转换程序 */
            $modules = read_modules('../includes/modules/convert');
            for ($i = 0; $i < count($modules); $i++) {
                $code = $modules[$i]['code'];

                load_helper($code, 'convert');

                $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
            }
            $GLOBALS['smarty']->assign('module_list', $modules);

            /* 设置默认值 */
            $def_val = [
                'host' => $db_host,
                'db' => '',
                'user' => $db_user,
                'pass' => $db_pass,
                'prefix' => 'sdb_',
                'path' => ''
            ];
            $GLOBALS['smarty']->assign('def_val', $def_val);

            /* 取得字符集数组 */
            $GLOBALS['smarty']->assign('charset_list', get_charset_list());

            /* 显示模板 */
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['convert']);

            return $GLOBALS['smarty']->display('convert_main.htm');
        }

        /*------------------------------------------------------ */
        //-- 转换前检查
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            return check_authz_json('convert');

            /* 取得参数 */
            $config = json_decode($_POST['JSON']);

            /* 测试连接数据库 */
            $sdb = new Mysql($config->host, $config->user, $config->pass, $config->db);

            /* 检查必需的表是否存在 */
            $sprefix = $config->prefix;
            $config->path = rtrim(str_replace('\\', '/', $config->path), '/');  // 把斜线替换为反斜线，去掉结尾的反斜线

            $config->code = '\\App\\Plugins\\Convert\\' . parse_name($config->code, true);
            $convert = new $config->code($sdb, $sprefix, $config->path);
            $required_table_list = $convert->required_tables();

            $sql = "SHOW TABLES";
            $table_list = $sdb->getCol($sql);

            $diff_arr = array_diff($required_table_list, $table_list);
            if ($diff_arr) {
                return make_json_error(sprintf($GLOBALS['_LANG']['table_error'], join(',', $table_list)));
            }

            /* 检查源目录是否存在，是否可读 */
            $dir_list = $convert->required_dirs();
            foreach ($dir_list as $dir) {
                $cur_dir = ($config->path . $dir);
                if (!file_exists($cur_dir) || !is_dir($cur_dir)) {
                    return make_json_error(sprintf($GLOBALS['_LANG']['dir_error'], $cur_dir));
                }

                if (file_mode_info($cur_dir) & 1 != 1) {
                    return make_json_error(sprintf($GLOBALS['_LANG']['dir_not_readable'], $cur_dir));
                }

                $res = $this->check_files_readable($cur_dir);
                if ($res !== true) {
                    return make_json_error(sprintf($GLOBALS['_LANG']['file_not_readable'], $res));
                }
            }

            /* 创建图片目录 */
            $img_dir = ROOT_PATH . IMAGE_DIR . '/' . date('Ym') . '/';
            if (!file_exists($img_dir)) {
                make_dir($img_dir);
            }

            /* 需要检查可写的目录 */
            $to_dir_list = [
                ROOT_PATH . IMAGE_DIR . '/upload/',
                $img_dir,
                ROOT_PATH . DATA_DIR . '/afficheimg/',
                ROOT_PATH . 'cert/'
            ];

            /* 检查目的目录是否存在，是否可写 */
            foreach ($to_dir_list as $to_dir) {
                if (!file_exists($to_dir) || !is_dir($to_dir)) {
                    return make_json_error(sprintf($GLOBALS['_LANG']['dir_error'], $to_dir));
                }

                if (file_mode_info($to_dir) & 4 != 4) {
                    return make_json_error(sprintf($GLOBALS['_LANG']['dir_not_writable'], $to_dir));
                }
            }

            /* 保存配置信息 */
            session(['convert_config' => $config]);

            /* 包含插件语言文件 */
            load_lang($config->code, 'convert');

            /* 取得第一步操作 */
            $step = $convert->next_step('');

            /* 返回 */
            return make_json_result($step, $GLOBALS['_LANG'][$step]);
        }

        /*------------------------------------------------------ */
        //-- 转换操作
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'process') {
            /* 设置执行时间 */
            set_time_limit(0);

            /* 检查权限 */
            return check_authz_json('convert');

            /* 取得参数 */
            $step = json_str_iconv($_POST['step']);

            /* 连接原数据库 */
            $config = session('convert_config');

            $sdb = new Mysql($config->host, $config->user, $config->pass, $config->db);
            $sdb->set_mysql_charset($config->charset);

            /* 创建插件对象 */
            $config->code = '\\App\\Plugins\\Convert\\' . parse_name($config->code, true);
            $convert = new $config->code($sdb, $config->prefix, $config->path, $config->charset);

            /* 包含插件语言文件 */
            load_lang($config->code, 'convert');

            /* 执行步骤 */
            $result = $convert->process($step);
            if ($result !== true) {
                return make_json_error($result);
            }

            /* 取得下一步操作 */
            $step = $convert->next_step($step);

            /* 返回 */
            return make_json_result($step, empty($GLOBALS['_LANG'][$step]) ? '' : $GLOBALS['_LANG'][$step]);
        }
    }

    /**
     * 检查某个目录的文件是否可读（不包括子目录）
     * 前提：$dirname 是目录且存在且可读
     *
     * @param   string $dirname 目录名：以 / 结尾，以 / 分隔
     * @return  mix     如果所有文件可读，返回true；否则，返回第一个不可读的文件名
     */
    private function check_files_readable($dirname)
    {
        /* 遍历文件，检查文件是否可读 */
        if ($dh = opendir($dirname)) {
            while (($file = readdir($dh)) !== false) {
                if (filetype($dirname . $file) == 'file' && strtolower($file) != 'thumbs.db') {
                    if (file_mode_info($dirname . $file) & 1 != 1) {
                        return $dirname . $file;
                    }
                }
            }
            closedir($dh);
        }

        /* 全部可读的返回值 */
        return true;
    }

    /**
     * 把一个目录的文件复制到另一个目录（不包括子目录）
     * 前提：$from_dir 是目录且存在且可读，$to_dir 是目录且存在且可写
     *
     * @param   string $from_dir 源目录
     * @param   string $to_dir 目标目录
     * @param   string $file_prefix 文件名前缀
     * @return  mix     成功返回true，否则返回第一个失败的文件名
     */
    private function copy_files($from_dir, $to_dir, $file_prefix = '')
    {
        /* 遍历并复制文件 */
        if ($dh = opendir($from_dir)) {
            while (($file = readdir($dh)) !== false) {
                if (filetype($from_dir . $file) == 'file' && strtolower($file) != 'thumbs.db') {
                    if (!copy($from_dir . $file, $to_dir . $file_prefix . $file)) {
                        return $from_dir . $file;
                    }
                }
            }
            closedir($dh);
        }

        /* 全部复制成功，返回true */
        return true;
    }

    /**
     * 把一个目录的文件复制到另一个目录（包括子目录）
     * 前提：$from_dir 是目录且存在且可读，$to_dir 是目录且存在且可写
     *
     * @param   string $from_dir 源目录
     * @param   string $to_dir 目标目录
     * @param   string $file_prefix 文件前缀
     * @return  mix     成功返回true，否则返回第一个失败的文件名
     */
    private function copy_dirs($from_dir, $to_dir, $file_prefix = '')
    {
        $result = true;
        if (!is_dir($from_dir)) {
            return "It's not a dir";
        }
        if (!is_dir($to_dir)) {
            if (!mkdir($to_dir, 0700)) {
                return "can't mkdir";
            }
        }
        $handle = opendir($from_dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $src = $from_dir . DIRECTORY_SEPARATOR . $file;
                $dtn = $to_dir . DIRECTORY_SEPARATOR . $file_prefix . $file;
                if (is_dir($src)) {
                    $this->copy_dirs($src, $dtn);
                } else {
                    if (!copy($src, $dtn)) {
                        $result = false;
                        break;
                    }
                }
            }
        }
        closedir($handle);
        return $result;
    }
}
