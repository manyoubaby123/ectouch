<?php

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

Route::namespace('Shop')->group(function () {
    Route::any('/', 'IndexController@actionIndex');

    Route::any('activity.php', 'ActivityController@actionIndex');

    Route::any('affiche.php', 'AfficheController@actionIndex');

    Route::any('affiliate.php', 'AffiliateController@actionIndex');

    Route::any('api.php', 'ApiController@actionIndex');

    Route::any('article-{id}{s?}.html', 'ArticleController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('article.php', 'ArticleController@actionIndex');

    Route::any('article_cat-{id}-{page}-{sort}-{order}{s?}.html', 'ArticleCatController@actionIndex')
        ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

    Route::any('article_cat-{id}-{page}-{keywords}{s?}.html', 'ArticleCatController@actionIndex')
        ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'keywords' => '.+', 's' => '.*']);

    Route::any('article_cat-{id}-{page}{s?}.html', 'ArticleCatController@actionIndex')
        ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

    Route::any('article_cat-{id}{s?}.html', 'ArticleCatController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('article_cat.php', 'ArticleCatController@actionIndex');

    Route::any('auction-{id}.html', 'auctionController@actionIndex')
        // ->bind('act', 'view')
        ->where(['id' => '[0-9]+']);

    Route::any('auction.php', 'AuctionController@actionIndex');

    Route::any('brand-{id}-c{cat}-{page}-{sort}-{order}.html', 'BrandController@actionIndex')
        ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);

    Route::any('brand-{id}-c{cat}-{page}{s?}.html', 'BrandController@actionIndex')
        ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

    Route::any('brand-{id}-c{cat}{s?}.html', 'BrandController@actionIndex')
        ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 's' => '.*']);

    Route::any('brand-{id}{s?}.html', 'BrandController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('brand.php', 'BrandController@actionIndex');

    Route::any('captcha.php', 'CaptchaController@actionIndex');

    Route::any('catalog.php', 'CatalogController@actionIndex');

    Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

    Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 's' => '.*']);

    Route::any('category-{id}-b{brand}-{page}-{sort}-{order}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

    Route::any('category-{id}-b{brand}-{page}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

    Route::any('category-{id}-b{brand}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 'brand' => '[0-9]+', 's' => '.*']);

    Route::any('category-{id}{s?}.html', 'CategoryController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('category.php', 'CategoryController@actionIndex');

    Route::any('certi.php', 'CertiController@actionIndex');

    Route::any('comment.php', 'CommentController@actionIndex');

    Route::any('compare.php', 'CompareController@actionIndex');

    Route::any('cycle_image.php', 'CycleImageController@actionIndex');

    Route::any('exchange-id{id}{s?}.html', 'ExchangeController@actionIndex')
        // ->bind('act', 'view')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('exchange-{cat_id}-min{integral_min}-max{integral_max}-{page}-{sort}-{order}{s?}.html', 'ExchangeController@actionIndex')
        ->where(['cat_id' => '[0-9]+', 'integral_min' => '[0-9]+', 'integral_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

    Route::any('exchange-{cat_id}-{page}-{sort}-{order}{s?}.html', 'ExchangeController@actionIndex')
        ->where(['cat_id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 's' => '.*']);

    Route::any('exchange-{cat_id}-{page}{s?}.html', 'ExchangeController@actionIndex')
        ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 's' => '.*']);

    Route::any('exchange-{cat_id}{s?}.html', 'ExchangeController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('exchange.php', 'ExchangeController@actionIndex');

    Route::any('feed-c{cat}.xml', 'FeedController@actionIndex')
        ->where(['cat' => '[0-9]+']);

    Route::any('feed-b{brand}.xml', 'FeedController@actionIndex')
        ->where(['brand' => '[0-9]+']);

    Route::any('feed-type{type}.xml', 'FeedController@actionIndex')
        ->where(['type' => '[^-]+']);

    Route::any('feed.{ext}', 'FeedController@actionIndex')
        ->where(['ext' => 'xml|php']);

    Route::any('flow.php', 'FlowController@actionIndex');

    Route::any('gallery.php', 'GalleryController@actionIndex');

    Route::any('goods-{id}{s?}.html', 'GoodsController@actionIndex')
        ->where(['id' => '[0-9]+', 's' => '.*']);

    Route::any('goods.php', 'GoodsController@actionIndex');

    Route::any('goods_script.php', 'GoodsScriptController@actionIndex');

    Route::any('group_buy-{id}.html', 'GroupBuyController@actionIndex')
        // ->bind('act', 'view')
        ->where(['id' => '[0-9]+']);

    Route::any('group_buy.php', 'GroupBuyController@actionIndex');

    Route::any('message.php', 'MessageController@actionIndex');

    Route::any('myship.php', 'MyshipController@actionIndex');

    Route::any('package.php', 'PackageController@actionIndex');

    Route::any('pick_out.php', 'PickOutController@actionIndex');

    Route::any('pm.php', 'PmController@actionIndex');

    Route::any('quotation.php', 'QuotationController@actionIndex');

    Route::any('receive.php', 'ReceiveController@actionIndex');

    Route::any('region.php', 'RegionController@actionIndex');

    Route::any('respond.php', 'RespondController@actionIndex');

    Route::any('tag-{keywords}.html', 'SearchController@actionIndex')
        ->where(['keywords' => '.*']);

    Route::any('search.php', 'SearchController@actionIndex');

    Route::any('sitemaps.php', 'SitemapsController@actionIndex');

    Route::any('snatch-{id}.html', 'SnatchController@actionIndex')
        ->where(['id' => '[0-9]+']);

    Route::any('snatch.php', 'SnatchController@actionIndex');

    Route::any('tag_cloud.php', 'TagCloudController@actionIndex');

    Route::any('topic.php', 'TopicController@actionIndex');

    Route::any('user.php', 'UserController@actionIndex');

    Route::any('vote.php', 'VoteController@actionIndex');

    Route::any('wholesale.php', 'WholesaleController@actionIndex');
});
