<?php

namespace App\Http\Controllers\Console;

class SearchLog extends Init
{
    public function index()
    {
        $_REQUEST['act'] = trim($_REQUEST['act']);

        admin_priv('search_log');
        if ($_REQUEST['act'] == 'list') {
            $logdb = $this->get_search_log();
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['search_log']);
            $GLOBALS['smarty']->assign('full_page', 1);
            $GLOBALS['smarty']->assign('logdb', $logdb['logdb']);
            $GLOBALS['smarty']->assign('filter', $logdb['filter']);
            $GLOBALS['smarty']->assign('record_count', $logdb['record_count']);
            $GLOBALS['smarty']->assign('page_count', $logdb['page_count']);
            $GLOBALS['smarty']->assign('start_date', local_date('Y-m-d'));
            $GLOBALS['smarty']->assign('end_date', local_date('Y-m-d'));

            return $GLOBALS['smarty']->display('search_log_list.htm');
        }
        if ($_REQUEST['act'] == 'query') {
            $logdb = $this->get_search_log();
            $GLOBALS['smarty']->assign('full_page', 0);
            $GLOBALS['smarty']->assign('logdb', $logdb['logdb']);
            $GLOBALS['smarty']->assign('filter', $logdb['filter']);
            $GLOBALS['smarty']->assign('record_count', $logdb['record_count']);
            $GLOBALS['smarty']->assign('page_count', $logdb['page_count']);
            $GLOBALS['smarty']->assign('start_date', local_date('Y-m-d'));
            $GLOBALS['smarty']->assign('end_date', local_date('Y-m-d'));
            return make_json_result(
                $GLOBALS['smarty']->fetch('search_log_list.htm'),
                '',
                ['filter' => $logdb['filter'], 'page_count' => $logdb['page_count']]
            );
        }
    }

    private function get_search_log()
    {
        $where = '';
        if (isset($_REQUEST['start_dateYear']) && isset($_REQUEST['end_dateYear'])) {
            $start_date = $_POST['start_dateYear'] . '-' . $_POST['start_dateMonth'] . '-' . $_POST['start_dateDay'];
            $end_date = $_POST['end_dateYear'] . '-' . $_POST['end_dateMonth'] . '-' . $_POST['end_dateDay'];
            $where .= " AND date <= '$end_date' AND date >= '$start_date'";
            $filter['start_dateYear'] = $_REQUEST['start_dateYear'];
            $filter['start_dateMonth'] = $_REQUEST['start_dateMonth'];
            $filter['start_dateDay'] = $_REQUEST['start_dateDay'];

            $filter['end_dateYear'] = $_REQUEST['end_dateYear'];
            $filter['end_dateMonth'] = $_REQUEST['end_dateMonth'];
            $filter['end_dateDay'] = $_REQUEST['end_dateDay'];
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('keywords') . " WHERE  searchengine='ectouch' $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $logdb = [];
        $filter = page_and_size($filter);
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('keywords') .
            " WHERE  searchengine='ectouch' $where" .
            " ORDER BY date DESC, count DESC" .
            "  LIMIT $filter[start],$filter[page_size]";
        $query = $GLOBALS['db']->query($sql);

        foreach ($query as $rt) {
            $logdb[] = $rt;
        }
        $arr = ['logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
