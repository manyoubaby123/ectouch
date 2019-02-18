<?php

use think\facade\Route;

/**
 * 设置自定义后台入口路由
 */

Route::any('/', function () {
    return redirect()->route('dashboard');
});
Route::any('index.php', 'Index/index')->name('dashboard');
Route::any('account_log.php', 'AccountLog/index');
Route::any('ad_position.php', 'AdPosition/index');
Route::any('admin_logs.php', 'AdminLogs/index');
Route::any('ads.php', 'Ads/index');
Route::any('adsense.php', 'Adsense/index');
Route::any('affiliate_ck.php', 'AffiliateCk/index');
Route::any('affiliate.php', 'Affiliate/index');
Route::any('agency.php', 'Agency/index');
Route::any('area_manage.php', 'AreaManage/index');
Route::any('article_auto.php', 'ArticleAuto/index');
Route::any('article.php', 'Article/index');
Route::any('articlecat.php', 'Articlecat/index');
Route::any('attention_list.php', 'AttentionList/index');
Route::any('attribute.php', 'Attribute/index');
Route::any('auction.php', 'Auction/index');
Route::any('bonus.php', 'Bonus/index');
Route::any('brand.php', 'Brand/index');
Route::any('captcha.php', 'Captcha/index');
Route::any('captcha_manage.php', 'CaptchaManage/index');
Route::any('card.php', 'Card/index');
Route::any('category.php', 'Category/index');
Route::any('check_file_priv.php', 'CheckFilePriv/index');
Route::any('cloud.php', 'Cloud/index');
Route::any('comment_manage.php', 'CommentManage/index');
Route::any('convert.php', 'Convert/index');
Route::any('cron.php', 'Cron/index');
Route::any('database.php', 'Database/index');
Route::any('edit_languages.php', 'EditLanguages/index');
Route::any('email_list.php', 'EmailList/index');
Route::any('exchange_goods.php', 'ExchangeGoods/index');
Route::any('favourable.php', 'Favourable/index');
Route::any('filecheck.php', 'Filecheck/index');
Route::any('flashplay.php', 'Flashplay/index');
Route::any('flow_stats.php', 'FlowStats/index');
Route::any('friend_link.php', 'FriendLink/index');
Route::any('gen_goods_script.php', 'GenGoodsScript/index');
Route::any('get_password.php', 'GetPassword/index');
Route::any('goods_auto.php', 'GoodsAuto/index');
Route::any('goods_batch.php', 'GoodsBatch/index');
Route::any('goods_booking.php', 'GoodsBooking/index');
Route::any('goods.php', 'Goods/index');
Route::any('goods_export.php', 'GoodsExport/index');
Route::any('goods_type.php', 'GoodsType/index');
Route::any('group_buy.php', 'GroupBuy/index');
Route::any('guest_stats.php', 'GuestStats/index');
Route::any('help.php', 'Help/index');
Route::any('integrate.php', 'Integrate/index');
Route::any('license.php', 'License/index');
Route::any('magazine_list.php', 'MagazineList/index');
Route::any('mail_template.php', 'MailTemplate/index');
Route::any('message.php', 'Message/index');
Route::any('navigator.php', 'Navigator/index');
Route::any('order.php', 'Order/index');
Route::any('order_stats.php', 'OrderStats/index');
Route::any('pack.php', 'Pack/index');
Route::any('package.php', 'Package/index');
Route::any('patch_num.php', 'PatchNum/index');
Route::any('payment.php', 'Payment/index');
Route::any('picture_batch.php', 'PictureBatch/index');
Route::any('privilege.php', 'Privilege/index');
Route::any('receive.php', 'Receive/index');
Route::any('reg_fields.php', 'RegFields/index');
Route::any('role.php', 'Role/index');
Route::any('sale_general.php', 'SaleGeneral/index');
Route::any('sale_list.php', 'SaleList/index');
Route::any('sale_order.php', 'SaleOrder/index');
Route::any('search_log.php', 'SearchLog/index');
Route::any('searchengine_stats.php', 'SearchengineStats/index');
Route::any('send.php', 'Send/index');
Route::any('shipping_area.php', 'ShippingArea/index');
Route::any('shipping.php', 'Shipping/index');
Route::any('shop_config.php', 'ShopConfig/index');
Route::any('shophelp.php', 'Shophelp/index');
Route::any('shopinfo.php', 'Shopinfo/index');
Route::any('sitemap.php', 'Sitemap/index');
Route::any('sms.php', 'Sms/index');
Route::any('snatch.php', 'Snatch/index');
Route::any('sql.php', 'Sql/index');
Route::any('suppliers.php', 'Suppliers/index');
Route::any('suppliers_goods.php', 'SuppliersGoods/index');
Route::any('tag_manage.php', 'TagManage/index');
Route::any('template.php', 'Template/index');
Route::any('topic.php', 'Topic/index');
Route::any('user_account.php', 'UserAccount/index');
Route::any('user_account_manage.php', 'UserAccountManage/index');
Route::any('user_msg.php', 'UserMsg/index');
Route::any('user_rank.php', 'UserRank/index');
Route::any('users.php', 'Users/index');
Route::any('users_order.php', 'UsersOrder/index');
Route::any('view_sendlist.php', 'ViewSendlist/index');
Route::any('virtual_card.php', 'VirtualCard/index');
Route::any('visit_sold.php', 'VisitSold/index');
Route::any('vote.php', 'Vote/index');
Route::any('wholesale.php', 'Wholesale/index');
