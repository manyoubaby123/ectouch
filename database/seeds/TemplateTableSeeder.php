<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemplateTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('template')->delete();

        DB::table('template')->insert(array(

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/vote_list.lbi',
                'sort_order' => 8,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/email_list.lbi',
                'sort_order' => 9,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/order_query.lbi',
                'sort_order' => 6,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/cart.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/promotion_info.lbi',
                'sort_order' => 3,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/auction.lbi',
                'sort_order' => 4,
                'id' => 0,
                'number' => 3,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/group_buy.lbi',
                'sort_order' => 5,
                'id' => 0,
                'number' => 3,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '',
                'library' => '/library/recommend_promotion.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 4,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '右边主区域',
                'library' => '/library/recommend_hot.lbi',
                'sort_order' => 2,
                'id' => 0,
                'number' => 10,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '右边主区域',
                'library' => '/library/recommend_new.lbi',
                'sort_order' => 1,
                'id' => 0,
                'number' => 10,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '右边主区域',
                'library' => '/library/recommend_best.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 10,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/invoice_query.lbi',
                'sort_order' => 7,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/top10.lbi',
                'sort_order' => 2,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '左边区域',
                'library' => '/library/category_tree.lbi',
                'sort_order' => 1,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'index',
                'region' => '',
                'library' => '/library/brands.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 11,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '左边区域',
                'library' => '/library/category_tree.lbi',
                'sort_order' => 1,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '右边区域',
                'library' => '/library/recommend_best.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 5,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '右边区域',
                'library' => '/library/goods_list.lbi',
                'sort_order' => 1,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '右边区域',
                'library' => '/library/pages.lbi',
                'sort_order' => 2,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '左边区域',
                'library' => '/library/cart.lbi',
                'sort_order' => 0,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '左边区域',
                'library' => '/library/price_grade.lbi',
                'sort_order' => 3,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),

            array(
                'filename' => 'category',
                'region' => '左边区域',
                'library' => '/library/filter_attr.lbi',
                'sort_order' => 2,
                'id' => 0,
                'number' => 0,
                'type' => 0,
                'theme' => 'default',
                'remarks' => '',
            ),
        ));
    }
}
