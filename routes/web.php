<?php

return [
    'activity.php' => 'activity/index',
    'affiche.php' => 'affiche/index',
    'affiliate.php' => 'affiliate/index',
    'api.php' => 'api/index',

    'article-<id:\d+><s:.*>.html' => 'article/index',
    'article.php' => 'article/index',

    'article_cat-<id:\d+>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+><s:.*>.html' => 'article-cat/index',
    'article_cat-<id:\d+>-<page:\d+>-<keywords:.+><s:.*>.html' => 'article-cat/index',
    'article_cat-<id:\d+>-<page:\d+><s:.*>.html' => 'article-cat/index',
    'article_cat-<id:\d+><s:.*>.html' => 'article-cat/index',
    'article_cat.php' => 'article-cat/index',

    [
        'pattern' => 'auction-<id:\d+>.html',
        'route' => 'auction/index',
        'defaults' => ['act' => 'view'],
    ],

    'auction.php' => 'auction/index',

    'brand-<id:\d+>-c<cat:\d+>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+>.html' => 'brand/index',
    'brand-<id:\d+>-c<cat:\d+>-<page:\d+><s:.*>.html' => 'brand/index',
    'brand-<id:\d+>-c<cat:\d+><s:.*>.html' => 'brand/index',
    'brand-<id:\d+><s:.*>.html' => 'brand/index',
    'brand.php' => 'brand/index',

    'captcha.php' => 'captcha/index',
    'catalog.php' => 'catalog/index',

    'category-<id:\d+>-b<brand:\d+>-min<price_min:\d+>-max<price_max:\d+>-attr<filter_attr:[^-]*>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+><s:.*>.html' => 'category/index',
    'category-<id:\d+>-b<brand:\d+>-min<price_min:\d+>-max<price_max:\d+>-attr<filter_attr:[^-]*><s:.*>.html' => 'category/index',
    'category-<id:\d+>-b<brand:\d+>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+><s:.*>.html' => 'category/index',
    'category-<id:\d+>-b<brand:\d+>-<page:\d+><s:.*>.html' => 'category/index',
    'category-<id:\d+>-b<brand:\d+><s:.*>.html' => 'category/index',
    'category-<id:\d+><s:.*>.html' => 'category/index',
    'category.php' => 'category/index',

    'certi.php' => 'certi/index',
    'comment.php' => 'comment/index',
    'compare.php' => 'compare/index',
    'crowd_funding.php' => 'crowd-funding/index',
    'cycle_image.php' => 'cycle-image/index',
    'discover.php' => 'discover/index',

    [
        'pattern' => 'exchange-id<id:\d+><s:.*>.html',
        'route' => 'exchange/index',
        'defaults' => ['act' => 'view'],
    ],

    'exchange-<cat_id:\d+>-min<integral_min:\d+>-max<integral_max:\d+>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+><s:.*>.html' => 'exchange/index',
    'exchange-<cat_id:\d+>-<page:\d+>-<sort:.+>-<order:[a-zA-Z]+><s:.*>.html' => 'exchange/index',
    'exchange-<cat_id:\d+>-<page:\d+><s:.*>.html' => 'exchange/index',
    'exchange-<cat_id:\d+><s:.*>.html' => 'exchange/index',
    'exchange.php' => 'exchange/index',

    'feed-c<cat:\d+>.xml' => 'feed/index',
    'feed-b<brand:\d+>.xml' => 'feed/index',
    'feed-type<type:[^-]+>.xml' => 'feed/index',
    'feed.<xml|php>' => 'feed/index',

    'flow.php' => 'flow/index',
    'gallery.php' => 'gallery/index',

    'goods-<id:\d+><s:.*>.html' => 'goods/index',
    'goods.php' => 'goods/index',

    'goods_script.php' => 'goods-script/index',

    [
        'pattern' => 'group_buy-<id:\d+>.html',
        'route' => 'group-buy/index',
        'defaults' => ['act' => 'view'],
    ],
    'group_buy.php' => 'group-buy/index',
    'groupon.php' => 'groupon/index',

    'message.php' => 'message/index',
    'myship.php' => 'myship/index',
    'oauth.php' => 'oauth/index',
    'package.php' => 'package/index',
    'pick_out.php' => 'pick-out/index',
    'pm.php' => 'pm/index',
    'quotation.php' => 'quotation/index',
    'receive.php' => 'receive/index',
    'region.php' => 'region/index',
    'respond.php' => 'respond/index',

    'sales.php' => 'sales/index',

    'tag-<keywords:.*>.html' => 'search/index',
    'search.php' => 'search/index',

    'sitemaps.php' => 'sitemaps/index',

    'snatch-<id:\d+>.html' => 'snatch/index',
    'snatch.php' => 'snatch/index',

    'tag_cloud.php' => 'tag-cloud/index',
    'topic.php' => 'topic/index',
    'user.php' => 'user/index',
    'vote.php' => 'vote/index',
    'wechat.php' => 'wechat/index',
    'wholesale.php' => 'wholesale/index',
];
