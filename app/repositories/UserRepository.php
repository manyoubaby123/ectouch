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
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getUserById($id)
    {
        return $this->user->where(['user_id' => $id])->find();
    }
}
