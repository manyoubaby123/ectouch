<?php

use think\facade\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('activity.php', 'Activity/index');

Route::any('affiche.php', 'Affiche/index');

Route::any('affiliate.php', 'Affiliate/index');

Route::any('api.php', 'Api/index');

Route::any('article-<id><s?>.html', 'Article/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('article.php', 'Article/index');

Route::any('article_cat-<id>-<page>-<sort>-<order><s?>.html', 'ArticleCat/index')
    ->pattern(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

Route::any('article_cat-<id>-<page>-<keywords><s?>.html', 'ArticleCat/index')
    ->pattern(['id' => '[0-9]+', 'page' => '[0-9]+', 'keywords' => '.+', 's' => '.*']);

Route::any('article_cat-<id>-<page><s?>.html', 'ArticleCat/index')
    ->pattern(['id' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

Route::any('article_cat-<id><s?>.html', 'ArticleCat/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('article_cat.php', 'ArticleCat/index');

Route::any('auction-<id>.html', 'auction/index')
// ->bind('act', 'view')
    ->pattern(['id' => '[0-9]+']);

Route::any('auction.php', 'Auction/index');

Route::any('brand-<id>-c<cat>-<page>-<sort>-<order>.html', 'Brand/index')
    ->pattern(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);

Route::any('brand-<id>-c<cat>-<page><s?>.html', 'Brand/index')
    ->pattern(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

Route::any('brand-<id>-c<cat><s?>.html', 'Brand/index')
    ->pattern(['id' => '[0-9]+', 'cat' => '[0-9]+', 's' => '.*']);

Route::any('brand-<id><s?>.html', 'Brand/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('brand.php', 'Brand/index');

Route::any('captcha.php', 'Captcha/index');

Route::any('catalog.php', 'Catalog/index');

Route::any('category-<id>-b<brand>-min<price_min>-max<price_max>-attr<filter_attr>-<page>-<sort>-<order><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

Route::any('category-<id>-b<brand>-min<price_min>-max<price_max>-attr<filter_attr><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 's' => '.*']);

Route::any('category-<id>-b<brand>-<page>-<sort>-<order><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

Route::any('category-<id>-b<brand>-<page><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

Route::any('category-<id>-b<brand><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 'brand' => '[0-9]+', 's' => '.*']);

Route::any('category-<id><s?>.html', 'Category/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('category.php', 'Category/index');

Route::any('certi.php', 'Certi/index');

Route::any('comment.php', 'Comment/index');

Route::any('compare.php', 'Compare/index');

Route::any('cycle_image.php', 'CycleImage/index');

Route::any('exchange-id<id><s?>.html', 'Exchange/index')
// ->bind('act', 'view')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('exchange-<cat_id>-min<integral_min>-max<integral_max>-<page>-<sort>-<order><s?>.html', 'Exchange/index')
    ->pattern(['cat_id' => '[0-9]+', 'integral_min' => '[0-9]+', 'integral_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

Route::any('exchange-<cat_id>-<page>-<sort>-<order><s?>.html', 'Exchange/index')
    ->pattern(['cat_id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

Route::any('exchange-<cat_id>-<page><s?>.html', 'Exchange/index')
    ->pattern(['id' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

Route::any('exchange-<cat_id><s?>.html', 'Exchange/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('exchange.php', 'Exchange/index');

Route::any('feed-c<cat>.xml', 'Feed/index')
    ->pattern(['cat' => '[0-9]+']);

Route::any('feed-b<brand>.xml', 'Feed/index')
    ->pattern(['brand' => '[0-9]+']);

Route::any('feed-type<type>.xml', 'Feed/index')
    ->pattern(['type' => '[^-]+']);

Route::any('feed.<ext>', 'Feed/index')
    ->pattern(['ext' => 'xml|php']);

Route::any('flow.php', 'Flow/index');

Route::any('gallery.php', 'Gallery/index');

Route::any('goods-<id><s?>.html', 'Goods/index')
    ->pattern(['id' => '[0-9]+', 's' => '.*']);

Route::any('goods.php', 'Goods/index');

Route::any('goods_script.php', 'GoodsScript/index');

Route::any('group_buy-<id>.html', 'GroupBuy/index')
// ->bind('act', 'view')
    ->pattern(['id' => '[0-9]+']);

Route::any('group_buy.php', 'GroupBuy/index');

Route::any('message.php', 'Message/index');

Route::any('myship.php', 'Myship/index');

Route::any('package.php', 'Package/index');

Route::any('pick_out.php', 'PickOut/index');

Route::any('pm.php', 'Pm/index');

Route::any('quotation.php', 'Quotation/index');

Route::any('receive.php', 'Receive/index');

Route::any('region.php', 'Region/index');

Route::any('respond.php', 'Respond/index');

Route::any('tag-<keywords>.html', 'Search/index')
    ->pattern(['keywords' => '.*']);

Route::any('search.php', 'Search/index');

Route::any('sitemaps.php', 'Sitemaps/index');

Route::any('snatch-<id>.html', 'Snatch/index')
    ->pattern(['id' => '[0-9]+']);

Route::any('snatch.php', 'Snatch/index');

Route::any('tag_cloud.php', 'TagCloud/index');

Route::any('topic.php', 'Topic/index');

Route::any('user.php', 'User/index');

Route::any('vote.php', 'Vote/index');

Route::any('wholesale.php', 'Wholesale/index');
