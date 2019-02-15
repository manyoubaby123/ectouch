<?php

namespace app\repositories;

use App\Models\User;

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
    public function __construct()
    {
        $this->user = new User();
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
