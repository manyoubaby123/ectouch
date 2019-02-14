<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegFieldsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('reg_fields')->delete();

        DB::table('reg_fields')->insert(array(

            array(
                'id' => 1,
                'reg_field_name' => 'MSN',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),

            array(
                'id' => 2,
                'reg_field_name' => 'QQ',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),

            array(
                'id' => 3,
                'reg_field_name' => '办公电话',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),

            array(
                'id' => 4,
                'reg_field_name' => '家庭电话',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),

            array(
                'id' => 5,
                'reg_field_name' => '手机',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),

            array(
                'id' => 6,
                'reg_field_name' => '密码找回问题',
                'dis_order' => 0,
                'display' => 1,
                'type' => 1,
                'is_need' => 1,
            ),
        ));
    }
}
