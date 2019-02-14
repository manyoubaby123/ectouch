<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminActionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_action')->delete();

        DB::table('admin_action')->insert(array(

            array(
                'action_id' => 1,
                'parent_id' => 0,
                'action_code' => 'goods',
                'relevance' => '',
            ),

            array(
                'action_id' => 2,
                'parent_id' => 0,
                'action_code' => 'cms_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 3,
                'parent_id' => 0,
                'action_code' => 'users_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 4,
                'parent_id' => 0,
                'action_code' => 'priv_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 5,
                'parent_id' => 0,
                'action_code' => 'sys_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 6,
                'parent_id' => 0,
                'action_code' => 'order_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 7,
                'parent_id' => 0,
                'action_code' => 'promotion',
                'relevance' => '',
            ),

            array(
                'action_id' => 8,
                'parent_id' => 0,
                'action_code' => 'email',
                'relevance' => '',
            ),

            array(
                'action_id' => 9,
                'parent_id' => 0,
                'action_code' => 'templates_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 10,
                'parent_id' => 0,
                'action_code' => 'db_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 11,
                'parent_id' => 0,
                'action_code' => 'sms_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 21,
                'parent_id' => 1,
                'action_code' => 'goods_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 22,
                'parent_id' => 1,
                'action_code' => 'remove_back',
                'relevance' => '',
            ),

            array(
                'action_id' => 23,
                'parent_id' => 1,
                'action_code' => 'cat_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 24,
                'parent_id' => 1,
                'action_code' => 'cat_drop',
                'relevance' => 'cat_manage',
            ),

            array(
                'action_id' => 25,
                'parent_id' => 1,
                'action_code' => 'attr_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 26,
                'parent_id' => 1,
                'action_code' => 'brand_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 27,
                'parent_id' => 1,
                'action_code' => 'comment_priv',
                'relevance' => '',
            ),

            array(
                'action_id' => 30,
                'parent_id' => 2,
                'action_code' => 'article_cat',
                'relevance' => '',
            ),

            array(
                'action_id' => 31,
                'parent_id' => 2,
                'action_code' => 'article_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 32,
                'parent_id' => 2,
                'action_code' => 'shopinfo_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 33,
                'parent_id' => 2,
                'action_code' => 'shophelp_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 34,
                'parent_id' => 2,
                'action_code' => 'vote_priv',
                'relevance' => '',
            ),

            array(
                'action_id' => 35,
                'parent_id' => 7,
                'action_code' => 'topic_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 38,
                'parent_id' => 3,
                'action_code' => 'integrate_users',
                'relevance' => '',
            ),

            array(
                'action_id' => 39,
                'parent_id' => 3,
                'action_code' => 'sync_users',
                'relevance' => 'integrate_users',
            ),

            array(
                'action_id' => 40,
                'parent_id' => 3,
                'action_code' => 'users_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 41,
                'parent_id' => 3,
                'action_code' => 'users_drop',
                'relevance' => 'users_manage',
            ),

            array(
                'action_id' => 42,
                'parent_id' => 3,
                'action_code' => 'user_rank',
                'relevance' => '',
            ),

            array(
                'action_id' => 43,
                'parent_id' => 4,
                'action_code' => 'admin_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 44,
                'parent_id' => 4,
                'action_code' => 'admin_drop',
                'relevance' => 'admin_manage',
            ),

            array(
                'action_id' => 45,
                'parent_id' => 4,
                'action_code' => 'allot_priv',
                'relevance' => 'admin_manage',
            ),

            array(
                'action_id' => 46,
                'parent_id' => 4,
                'action_code' => 'logs_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 47,
                'parent_id' => 4,
                'action_code' => 'logs_drop',
                'relevance' => 'logs_manage',
            ),

            array(
                'action_id' => 48,
                'parent_id' => 5,
                'action_code' => 'shop_config',
                'relevance' => '',
            ),

            array(
                'action_id' => 49,
                'parent_id' => 5,
                'action_code' => 'ship_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 50,
                'parent_id' => 5,
                'action_code' => 'payment',
                'relevance' => '',
            ),

            array(
                'action_id' => 51,
                'parent_id' => 5,
                'action_code' => 'shiparea_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 52,
                'parent_id' => 5,
                'action_code' => 'area_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 53,
                'parent_id' => 6,
                'action_code' => 'order_os_edit',
                'relevance' => '',
            ),

            array(
                'action_id' => 54,
                'parent_id' => 6,
                'action_code' => 'order_ps_edit',
                'relevance' => 'order_os_edit',
            ),

            array(
                'action_id' => 55,
                'parent_id' => 6,
                'action_code' => 'order_ss_edit',
                'relevance' => 'order_os_edit',
            ),

            array(
                'action_id' => 56,
                'parent_id' => 6,
                'action_code' => 'order_edit',
                'relevance' => 'order_os_edit',
            ),

            array(
                'action_id' => 57,
                'parent_id' => 6,
                'action_code' => 'order_view',
                'relevance' => '',
            ),

            array(
                'action_id' => 58,
                'parent_id' => 6,
                'action_code' => 'order_view_finished',
                'relevance' => '',
            ),

            array(
                'action_id' => 59,
                'parent_id' => 6,
                'action_code' => 'repay_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 60,
                'parent_id' => 6,
                'action_code' => 'booking',
                'relevance' => '',
            ),

            array(
                'action_id' => 61,
                'parent_id' => 6,
                'action_code' => 'sale_order_stats',
                'relevance' => '',
            ),

            array(
                'action_id' => 62,
                'parent_id' => 6,
                'action_code' => 'client_flow_stats',
                'relevance' => '',
            ),

            array(
                'action_id' => 70,
                'parent_id' => 1,
                'action_code' => 'goods_type',
                'relevance' => '',
            ),

            array(
                'action_id' => 73,
                'parent_id' => 3,
                'action_code' => 'feedback_priv',
                'relevance' => '',
            ),

            array(
                'action_id' => 74,
                'parent_id' => 4,
                'action_code' => 'template_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 75,
                'parent_id' => 5,
                'action_code' => 'friendlink',
                'relevance' => '',
            ),

            array(
                'action_id' => 76,
                'parent_id' => 5,
                'action_code' => 'db_backup',
                'relevance' => '',
            ),

            array(
                'action_id' => 77,
                'parent_id' => 5,
                'action_code' => 'db_renew',
                'relevance' => 'db_backup',
            ),

            array(
                'action_id' => 78,
                'parent_id' => 7,
                'action_code' => 'snatch_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 79,
                'parent_id' => 7,
                'action_code' => 'bonus_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 80,
                'parent_id' => 7,
                'action_code' => 'gift_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 81,
                'parent_id' => 7,
                'action_code' => 'card_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 82,
                'parent_id' => 7,
                'action_code' => 'pack',
                'relevance' => '',
            ),

            array(
                'action_id' => 83,
                'parent_id' => 7,
                'action_code' => 'ad_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 84,
                'parent_id' => 1,
                'action_code' => 'tag_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 85,
                'parent_id' => 3,
                'action_code' => 'surplus_manage',
                'relevance' => 'account_manage',
            ),

            array(
                'action_id' => 86,
                'parent_id' => 4,
                'action_code' => 'agency_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 87,
                'parent_id' => 3,
                'action_code' => 'account_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 88,
                'parent_id' => 5,
                'action_code' => 'flash_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 89,
                'parent_id' => 5,
                'action_code' => 'navigator',
                'relevance' => '',
            ),

            array(
                'action_id' => 90,
                'parent_id' => 7,
                'action_code' => 'auction',
                'relevance' => '',
            ),

            array(
                'action_id' => 91,
                'parent_id' => 7,
                'action_code' => 'group_by',
                'relevance' => '',
            ),

            array(
                'action_id' => 92,
                'parent_id' => 7,
                'action_code' => 'favourable',
                'relevance' => '',
            ),

            array(
                'action_id' => 93,
                'parent_id' => 7,
                'action_code' => 'whole_sale',
                'relevance' => '',
            ),

            array(
                'action_id' => 94,
                'parent_id' => 1,
                'action_code' => 'goods_auto',
                'relevance' => '',
            ),

            array(
                'action_id' => 95,
                'parent_id' => 2,
                'action_code' => 'article_auto',
                'relevance' => '',
            ),

            array(
                'action_id' => 96,
                'parent_id' => 5,
                'action_code' => 'cron',
                'relevance' => '',
            ),

            array(
                'action_id' => 97,
                'parent_id' => 5,
                'action_code' => 'affiliate',
                'relevance' => '',
            ),

            array(
                'action_id' => 98,
                'parent_id' => 5,
                'action_code' => 'affiliate_ck',
                'relevance' => '',
            ),

            array(
                'action_id' => 99,
                'parent_id' => 8,
                'action_code' => 'attention_list',
                'relevance' => '',
            ),

            array(
                'action_id' => 100,
                'parent_id' => 8,
                'action_code' => 'email_list',
                'relevance' => '',
            ),

            array(
                'action_id' => 101,
                'parent_id' => 8,
                'action_code' => 'magazine_list',
                'relevance' => '',
            ),

            array(
                'action_id' => 102,
                'parent_id' => 8,
                'action_code' => 'view_sendlist',
                'relevance' => '',
            ),

            array(
                'action_id' => 103,
                'parent_id' => 1,
                'action_code' => 'virualcard',
                'relevance' => '',
            ),

            array(
                'action_id' => 104,
                'parent_id' => 7,
                'action_code' => 'package_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 105,
                'parent_id' => 1,
                'action_code' => 'picture_batch',
                'relevance' => '',
            ),

            array(
                'action_id' => 106,
                'parent_id' => 1,
                'action_code' => 'goods_export',
                'relevance' => '',
            ),

            array(
                'action_id' => 107,
                'parent_id' => 1,
                'action_code' => 'goods_batch',
                'relevance' => '',
            ),

            array(
                'action_id' => 108,
                'parent_id' => 1,
                'action_code' => 'gen_goods_script',
                'relevance' => '',
            ),

            array(
                'action_id' => 109,
                'parent_id' => 5,
                'action_code' => 'sitemap',
                'relevance' => '',
            ),

            array(
                'action_id' => 110,
                'parent_id' => 5,
                'action_code' => 'file_priv',
                'relevance' => '',
            ),

            array(
                'action_id' => 111,
                'parent_id' => 5,
                'action_code' => 'file_check',
                'relevance' => '',
            ),

            array(
                'action_id' => 112,
                'parent_id' => 9,
                'action_code' => 'template_select',
                'relevance' => '',
            ),

            array(
                'action_id' => 113,
                'parent_id' => 9,
                'action_code' => 'template_setup',
                'relevance' => '',
            ),

            array(
                'action_id' => 114,
                'parent_id' => 9,
                'action_code' => 'library_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 115,
                'parent_id' => 9,
                'action_code' => 'lang_edit',
                'relevance' => '',
            ),

            array(
                'action_id' => 116,
                'parent_id' => 9,
                'action_code' => 'backup_setting',
                'relevance' => '',
            ),

            array(
                'action_id' => 117,
                'parent_id' => 9,
                'action_code' => 'mail_template',
                'relevance' => '',
            ),

            array(
                'action_id' => 118,
                'parent_id' => 10,
                'action_code' => 'db_backup',
                'relevance' => '',
            ),

            array(
                'action_id' => 119,
                'parent_id' => 10,
                'action_code' => 'db_renew',
                'relevance' => '',
            ),

            array(
                'action_id' => 120,
                'parent_id' => 10,
                'action_code' => 'db_optimize',
                'relevance' => '',
            ),

            array(
                'action_id' => 121,
                'parent_id' => 10,
                'action_code' => 'sql_query',
                'relevance' => '',
            ),

            array(
                'action_id' => 122,
                'parent_id' => 10,
                'action_code' => 'convert',
                'relevance' => '',
            ),

            array(
                'action_id' => 124,
                'parent_id' => 11,
                'action_code' => 'sms_send',
                'relevance' => '',
            ),

            array(
                'action_id' => 128,
                'parent_id' => 7,
                'action_code' => 'exchange_goods',
                'relevance' => '',
            ),

            array(
                'action_id' => 129,
                'parent_id' => 6,
                'action_code' => 'delivery_view',
                'relevance' => '',
            ),

            array(
                'action_id' => 130,
                'parent_id' => 6,
                'action_code' => 'back_view',
                'relevance' => '',
            ),

            array(
                'action_id' => 131,
                'parent_id' => 5,
                'action_code' => 'reg_fields',
                'relevance' => '',
            ),

            array(
                'action_id' => 132,
                'parent_id' => 5,
                'action_code' => 'shop_authorized',
                'relevance' => '',
            ),

            array(
                'action_id' => 133,
                'parent_id' => 5,
                'action_code' => 'webcollect_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 134,
                'parent_id' => 4,
                'action_code' => 'suppliers_manage',
                'relevance' => '',
            ),

            array(
                'action_id' => 135,
                'parent_id' => 4,
                'action_code' => 'role_manage',
                'relevance' => '',
            ),
        ));
    }
}
