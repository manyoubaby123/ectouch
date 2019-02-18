<?php

namespace app\repositories;

use app\models\User;

/**
 * Class UserRepository
 * @package app\repositories
 */
class UserRepository
{
    /**
     * @var User
     */
    protected $user;

    /**
     * UserRepository constructor.
     */
    public function __construct()
    {
        $this->user = new User();
    }

    /**
     * @param $id
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserById($id)
    {
        return $this->user->where(['user_id' => $id])->find();
    }
}
