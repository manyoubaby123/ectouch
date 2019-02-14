<?php

namespace App\Services;

/**
 * Class MessageService
 * @package App\Services
 */
class MessageService
{
    /**
     *  获取指定用户的留言
     *
     * @access  public
     * @param   int $user_id 用户ID
     * @param   int $user_name 用户名
     * @param   int $num 列表最大数量
     * @param   int $start 列表其实位置
     * @return  array   $msg            留言及回复列表
     * @return  string  $order_id       订单ID
     */
    public function get_message_list($user_id, $user_name, $num, $start, $order_id = 0)
    {
        /* 获取留言数据 */
        $msg = [];
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('feedback');
        if ($order_id) {
            $sql .= " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id' ORDER BY msg_time DESC";
        } else {
            $sql .= " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . session('user_name') . "' AND order_id=0 ORDER BY msg_time DESC";
        }

        $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

        foreach ($res as $rows) {
            /* 取得留言的回复 */
            //if (empty($order_id))
            //{
            $reply = [];
            $sql = "SELECT user_name, user_email, msg_time, msg_content" .
                " FROM " . $GLOBALS['ecs']->table('feedback') .
                " WHERE parent_id = '" . $rows['msg_id'] . "'";
            $reply = $GLOBALS['db']->getRow($sql);

            if ($reply) {
                $msg[$rows['msg_id']]['re_user_name'] = $reply['user_name'];
                $msg[$rows['msg_id']]['re_user_email'] = $reply['user_email'];
                $msg[$rows['msg_id']]['re_msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $reply['msg_time']);
                $msg[$rows['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
            }
            //}

            $msg[$rows['msg_id']]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
            $msg[$rows['msg_id']]['msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['msg_time']);
            $msg[$rows['msg_id']]['msg_type'] = $order_id ? $rows['user_name'] : $GLOBALS['_LANG']['type'][$rows['msg_type']];
            $msg[$rows['msg_id']]['msg_title'] = nl2br(htmlspecialchars($rows['msg_title']));
            $msg[$rows['msg_id']]['message_img'] = $rows['message_img'];
            $msg[$rows['msg_id']]['order_id'] = $rows['order_id'];
        }

        return $msg;
    }

    /**
     *  添加留言函数
     *
     * @access  public
     * @param   array $message
     *
     * @return  boolen      $bool
     */
    public function add_message($message)
    {
        $upload_size_limit = $GLOBALS['_CFG']['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $GLOBALS['_CFG']['upload_size_limit'];
        $status = 1 - $GLOBALS['_CFG']['message_check'];

        $last_char = strtolower($upload_size_limit{strlen($upload_size_limit) - 1});

        switch ($last_char) {
            case 'm':
                $upload_size_limit *= 1024 * 1024;
                break;
            case 'k':
                $upload_size_limit *= 1024;
                break;
        }

        if ($message['upload']) {
            if ($_FILES['message_img']['size'] / 1024 > $upload_size_limit) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['upload_file_limit'], $upload_size_limit));
                return false;
            }
            $img_name = upload_file($_FILES['message_img'], 'feedbackimg');

            if ($img_name === false) {
                return false;
            }
        } else {
            $img_name = '';
        }

        if (empty($message['msg_title'])) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['msg_title_empty']);

            return false;
        }

        $message['msg_area'] = isset($message['msg_area']) ? intval($message['msg_area']) : 0;
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('feedback') .
            " (msg_id, parent_id, user_id, user_name, user_email, msg_title, msg_type, msg_status,  msg_content, msg_time, message_img, order_id, msg_area)" .
            " VALUES (NULL, 0, '$message[user_id]', '$message[user_name]', '$message[user_email]', " .
            " '$message[msg_title]', '$message[msg_type]', '$status', '$message[msg_content]', '" . gmtime() . "', '$img_name', '$message[order_id]', '$message[msg_area]')";
        $GLOBALS['db']->query($sql);

        return true;
    }
}
