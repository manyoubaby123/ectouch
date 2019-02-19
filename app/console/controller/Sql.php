<?php

namespace app\console\controller;

class Sql extends Init
{
    public function index()
    {
        $_POST['sql'] = !empty($_POST['sql']) ? trim($_POST['sql']) : '';

        if (!$_POST['sql']) {
            $_REQUEST['act'] = 'main';
        }

        /*------------------------------------------------------ */
        //-- 用户帐号列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'main') {
            admin_priv('sql_query');

            $this->assign('type', -1);
            $this->assign('ur_here', $GLOBALS['_LANG']['04_sql_query']);

            return $GLOBALS['smarty']->display('sql.htm');
        }

        if ($_REQUEST['act'] == 'query') {
            admin_priv('sql_query');
            if (!empty($_POST['sql'])) {
                preg_match_all("/(SELECT)/i", $_POST['sql'], $matches);
                if (isset($matches[1]) && count($matches[1]) > 1) {
                    return sys_msg("this sql more than one SELECT ");
                }

                if (preg_match("/(UPDATE|DELETE|TRUNCATE|ALTER|DROP|FLUSH|INSERT|REPLACE|SET|CREATE|CONCAT)/i", $_POST['sql'])) {
                    return sys_msg("this sql May contain UPDATE,DELETE,TRUNCATE,ALTER,DROP,FLUSH,INSERT,REPLACE,SET,CREATE,CONCAT ");
                }
            }

            $this->assign_sql($_POST['sql']);

            $this->assign('ur_here', $GLOBALS['_LANG']['04_sql_query']);

            return $GLOBALS['smarty']->display('sql.htm');
        }
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function assign_sql($sql)
    {
        $sql = stripslashes($sql);
        $this->assign('sql', $sql);

        /* 解析查询项 */
        $sql = str_replace("\r", '', $sql);
        $query_items = explode(";\n", $sql);
        foreach ($query_items as $key => $value) {
            if (empty($value)) {
                unset($query_items[$key]);
            }
        }
        /* 如果是多条语句，拆开来执行 */
        if (count($query_items) > 1) {
            foreach ($query_items as $key => $value) {
                if ($GLOBALS['db']->query($value, 'SILENT')) {
                    $this->assign('type', 1);
                } else {
                    $this->assign('type', 0);
                    $this->assign('error', $GLOBALS['db']->error());
                    return;
                }
            }
            return; //退出函数
        }

        /* 单独一条sql语句处理 */
        if (preg_match("/^(?:UPDATE|DELETE|TRUNCATE|ALTER|DROP|FLUSH|INSERT|REPLACE|SET|CREATE)\\s+/i", $sql)) {
            if ($GLOBALS['db']->query($sql, 'SILENT')) {
                $this->assign('type', 1);
            } else {
                $this->assign('type', 0);
                $this->assign('error', $GLOBALS['db']->error());
            }
        } else {
            $data = $GLOBALS['db']->getAll($sql);
            if ($data === false) {
                $this->assign('type', 0);
                $this->assign('error', $GLOBALS['db']->error());
            } else {
                $result = '';
                if (is_array($data) && isset($data[0]) === true) {
                    $result = "<table> \n <tr>";
                    $keys = array_keys($data[0]);
                    for ($i = 0, $num = count($keys); $i < $num; $i++) {
                        $result .= "<th>" . $keys[$i] . "</th>\n";
                    }
                    $result .= "</tr> \n";
                    foreach ($data as $data1) {
                        $result .= "<tr>\n";
                        foreach ($data1 as $value) {
                            $result .= "<td>" . $value . "</td>";
                        }
                        $result .= "</tr>\n";
                    }
                    $result .= "</table>\n";
                } else {
                    $result = "<center><h3>" . $GLOBALS['_LANG']['no_data'] . "</h3></center>";
                }

                $this->assign('type', 2);
                $this->assign('result', $result);
            }
        }
    }
}
