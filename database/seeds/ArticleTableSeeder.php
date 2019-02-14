<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('article')->delete();

        DB::table('article')->insert(array(

            array(
                'article_id' => 1,
                'cat_id' => 2,
                'title' => '免责条款',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),

            array(
                'article_id' => 2,
                'cat_id' => 2,
                'title' => '隐私保护',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),

            array(
                'article_id' => 3,
                'cat_id' => 2,
                'title' => '咨询热点',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),

            array(
                'article_id' => 4,
                'cat_id' => 2,
                'title' => '联系我们',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),

            array(
                'article_id' => 5,
                'cat_id' => 2,
                'title' => '公司简介',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),

            array(
                'article_id' => 6,
                'cat_id' => -1,
                'title' => '用户协议',
                'content' => '',
                'author' => '',
                'author_email' => '',
                'keywords' => '',
                'article_type' => 0,
                'is_open' => 1,
                'add_time' => 1542102809,
                'file_url' => '',
                'open_type' => 0,
                'link' => '',
                'description' => null,
            ),
        ));
    }
}
