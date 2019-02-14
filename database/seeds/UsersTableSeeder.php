<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $users = $this->table('users');

        $users->truncate();

        $users->insert(array(
            'user_id' => 1,
            'user_name' => 'test',
        ))->save();
    }
}
