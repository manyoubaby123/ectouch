<?php

namespace app\libraries;

use Yii;

class Mysql
{
    public function query($sql)
    {
        $m = strtolower(substr(ltrim(trim($sql), '('), 0, 6));
        if ($m == 'select' || substr($m, 0, 4) == 'desc' || substr($m, 0, 4) == 'show') {
            $res = Yii::$app->db->createCommand($sql)->queryAll();
        } else {
            $res = Yii::$app->db->createCommand($sql)->execute();
        }

        return $res;
    }

    public function error()
    {
        return '';
    }

    public function errno()
    {
        return '';
    }

    public function insert_id()
    {
        return Yii::$app->db->getLastInsertID();
    }

    public function version()
    {
        return $this->getOne("select version() as ver");
    }

    /**
     * @param $sql
     * @param $num
     * @param int $start
     * @return array|bool|int
     */
    public function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    /**
     * @param $sql
     * @param bool $limited
     * @return bool|mixed
     */
    public function getOne($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if (empty($res)) {
            return false;
        }

        return reset($res[0]);
    }

    /**
     * @param $sql
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getOneCached($sql)
    {
        $cache_id = md5($sql);

        $res = cache($cache_id);

        if (is_null($res)) {
            $res = $this->getOne($sql);
            cache($cache_id, $res, Carbon::now()->addWeekdays(1));
        }

        return $res;
    }

    /**
     * @param $sql
     * @return array|bool|int
     */
    public function getAll($sql)
    {
        $res = $this->query($sql);
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }

    /**
     * @param $sql
     * @return array|bool|\Illuminate\Cache\CacheManager|int|mixed
     * @throws \Exception
     */
    public function getAllCached($sql)
    {
        $cache_id = md5($sql);

        $res = cache($cache_id);

        if (is_null($res)) {
            $res = $this->getAll($sql);
            cache($cache_id, $res, Carbon::now()->addWeekdays(1));
        }

        return $res;
    }

    /**
     * @param $sql
     * @param bool $limited
     * @return bool|mixed
     */
    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if (empty($res)) {
            return false;
        }

        return $res[0];
    }

    /**
     * @param $sql
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getRowCached($sql)
    {
        $cache_id = md5($sql);

        $res = cache($cache_id);

        if (is_null($res)) {
            $res = $this->getRow($sql);
            cache($cache_id, $res, Carbon::now()->addWeekdays(1));
        }

        return $res;
    }

    /**
     * @param $sql
     * @return array|bool
     */
    public function getCol($sql)
    {
        $res = $this->query($sql);
        if (empty($res)) {
            return [];
        }

        $arr = [];
        foreach ($res as $row) {
            $arr[] = reset($row);
        }

        return $arr;
    }

    /**
     * @param $sql
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getColCached($sql)
    {
        $cache_id = md5($sql);

        $res = cache($cache_id);

        if (is_null($res)) {
            $res = $this->getCol($sql);
            cache($cache_id, $res, Carbon::now()->addWeekdays(1));
        }

        return $res;
    }

    /**
     * @param $table
     * @param $field_values
     * @param string $mode
     * @param string $where
     * @return array|bool|int
     */
    public function autoExecute($table, $field_values, $mode = 'INSERT', $where = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT') {
            $fields = $values = [];
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = [];
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql) {
            return $this->query($sql);
        } else {
            return false;
        }
    }

    /**
     * @param $table
     * @param $field_values
     * @param $update_values
     * @return array|bool|int
     */
    public function autoReplace($table, $field_values, $update_values)
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = [];
        foreach ($field_descs as $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = [];
        foreach ($field_names as $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = [];
        foreach ($update_values as $key => $value) {
            if (array_key_exists($key, $field_values) == true) {
                if (is_int($value) || is_float($value)) {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                } else {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys)) {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                if (!empty($sets)) {
                    $sql .= 'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                }
            }
        }

        if ($sql) {
            return $this->query($sql);
        } else {
            return false;
        }
    }
}
