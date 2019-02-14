<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleCatTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('article_cat')->delete();
        
        DB::table('article_cat')->insert(array(

            array(
                'cat_id' => 1,
                'cat_name' => '系统分类',
                'cat_type' => 2,
                'keywords' => '',
                'cat_desc' => '系统保留分类',
                'sort_order' => 50,
                'show_in_nav' => 0,
                'parent_id' => 0,
            ),

            array(
                'cat_id' => 2,
                'cat_name' => '网店信息',
                'cat_type' => 3,
                'keywords' => '',
                'cat_desc' => '网店信息分类',
                'sort_order' => 50,
                'show_in_nav' => 0,
                'parent_id' => 1,
            ),

            array(
                'cat_id' => 3,
                'cat_name' => '网店帮助分类',
                'cat_type' => 4,
                'keywords' => '',
                'cat_desc' => '网店帮助分类',
                'sort_order' => 50,
                'show_in_nav' => 0,
                'parent_id' => 1,
            ),
        ));
    }
}
