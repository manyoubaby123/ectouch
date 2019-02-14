<?php

return [
    'v1' => [
        // Other
        'ecapi.article.list' => 'article/index',

        'ecapi.article.show' => 'article/show',

        'ecapi.notice.show' => 'v2/notice/show',

        'ecapi.order.notify.<code:\S+>' => 'v2/order/notify',

        'ecapi.product.intro.<id:\d+>' => 'v2/goods/intro',

        'ecapi.product.share.<id:\d+>' => 'v2/goods/share',

        'ecapi.auth.web' => 'v2/user/web-oauth',

        'ecapi.auth.web.callback/<vendor:\d+>' => 'v2/user/web-callback',

        // Guest
        'ecapi.access.dns' => 'v2/access/dns',

        'ecapi.access.batch' => 'v2/access/batch',

        'ecapi.category.list' => 'v2/goods/category',

        'ecapi.category.all.list' => 'v2/goods/all-category',

        'ecapi.product.list' => 'v2/goods/index',

        'ecapi.search.product.list' => 'v2/goods/search',

        'ecapi.review.product.list' => 'v2/goods/review',

        'ecapi.review.product.subtotal' => 'v2/goods/subtotal',

        'ecapi.recommend.product.list' => 'v2/goods/recommend-list',

        'ecapi.product.accessory.list' => 'v2/goods/accessory-list',

        'ecapi.product.get' => 'v2/goods/info',

        'ecapi.auth.weixin.mplogin' => 'v2/user/weixin-mini-program-login',

        'ecapi.auth.signin' => 'v2/user/signin',

        'ecapi.auth.social' => 'v2/user/auth',

        'ecapi.auth.default.signup' => 'v2/user/signup-by-email',

        'ecapi.auth.mobile.signup' => 'v2/user/signup-by-mobile',

        'ecapi.user.profile.fields' => 'v2/user/fields',

        'ecapi.auth.mobile.verify' => 'v2/user/verify-mobile',

        'ecapi.auth.mobile.send' => 'v2/user/send-code',

        'ecapi.auth.mobile.reset' => 'v2/user/reset-password-by-mobile',

        'ecapi.auth.default.reset' => 'v2/user/reset-password-by-email',

        'ecapi.cardpage.get' => 'v2/card-page/view',

        'ecapi.cardpage.preview' => 'v2/card-page/preview',

        'ecapi.config.get' => 'v2/config/index',

        'ecapi.brand.list' => 'v2/brand/index',

        'ecapi.search.keyword.list' => 'v2/search/index',

        'ecapi.region.list' => 'v2/region/index',

        'ecapi.invoice.type.list' => 'v2/invoice/type',

        'ecapi.invoice.content.list' => 'v2/invoice/content',

        'ecapi.invoice.status.get' => 'v2/invoice/status',

        'ecapi.notice.list' => 'v2/notice/index',

        'ecapi.banner.list' => 'v2/banner/index',

        'ecapi.version.check' => 'v2/version/check',

        'ecapi.recommend.brand.list' => 'v2/brand/recommend',

        'ecapi.message.system.list' => 'v2/message/system',

        'ecapi.message.count' => 'v2/message/unread',

        'ecapi.site.get' => 'v2/site/index',

        'ecapi.splash.list' => 'v2/splash/index',

        'ecapi.splash.preview' => 'v2/splash/view',

        'ecapi.theme.list' => 'v2/theme/index',

        'ecapi.theme.preview' => 'v2/theme/view',

        'ecapi.search.category.list' => 'v2/goods/category-search',

        'ecapi.order.reason.list' => 'v2/order/reason-list',

        'ecapi.search.shop.list' => 'v2/shop/search',

        'ecapi.recommend.shop.list' => 'v2/shop/recommand',

        'ecapi.shop.list' => 'v2/shop/index',

        'ecapi.shop.get' => 'v2/shop/info',

        'ecapi.areacode.list' => 'v2/area-code/index',

        // Authorization
        'ecapi.user.profile.get' => 'v2/user/profile',

        'ecapi.user.profile.update' => 'v2/user/update-profile',

        'ecapi.user.password.update' => 'v2/user/update-password',

        'ecapi.order.list' => 'v2/order/index',

        'ecapi.order.get' => 'v2/order/view',

        'ecapi.order.confirm' => 'v2/order/confirm',

        'ecapi.order.cancel' => 'v2/order/cancel',

        'ecapi.order.price' => 'v2/order/price',

        'ecapi.product.like' => 'v2/goods/set-like',

        'ecapi.product.unlike' => 'v2/goods/set-unlike',

        'ecapi.product.liked.list' => 'v2/goods/liked-list',

        'ecapi.order.review' => 'v2/order/review',

        'ecapi.order.subtotal' => 'v2/order/subtotal',

        'ecapi.payment.types.list' => 'v2/order/payment-list',

        'ecapi.payment.pay' => 'v2/order/pay',

        'ecapi.shipping.vendor.list' => 'v2/shipping/index',

        'ecapi.shipping.status.get' => 'v2/shipping/info',

        'ecapi.shipping.select.shipping' => 'v2/cart/select-shipping',

        'ecapi.consignee.list' => 'v2/consignee/index',

        'ecapi.consignee.update' => 'v2/consignee/modify',

        'ecapi.consignee.add' => 'v2/consignee/add',

        'ecapi.consignee.delete' => 'v2/consignee/remove',

        'ecapi.consignee.setDefault' => 'v2/consignee/set-default',

        'ecapi.score.get' => 'v2/score/view',

        'ecapi.score.history.list' => 'v2/score/history',

        'ecapi.cashgift.list' => 'v2/cash-gift/index',

        'ecapi.cashgift.available' => 'v2/cash-gift/available',

        'ecapi.push.update' => 'v2/message/update-deviceId',

        'ecapi.cart.add' => 'v2/cart/add',

        'ecapi.cart.clear' => 'v2/cart/clear',

        'ecapi.cart.delete' => 'v2/cart/delete',

        'ecapi.cart.get' => 'v2/cart/index',

        'ecapi.cart.update' => 'v2/cart/update',

        'ecapi.cart.checkout' => 'v2/cart/checkout',

        'ecapi.cart.promos' => 'v2/cart/promos',

        'ecapi.product.purchase' => 'v2/goods/purchase',

        'ecapi.product.validate' => 'v2/goods/check-product',

        'ecapi.message.order.list' => 'v2/message/order',

        'ecapi.shop.watch' => 'v2/shop/watch',

        'ecapi.shop.unwatch' => 'v2/shop/unwatch',

        'ecapi.shop.watching.list' => 'v2/shop/watching-list',

        'ecapi.coupon.list' => 'v2/coupon/index',

        'ecapi.coupon.available' => 'v2/coupon/available',

        'ecapi.cart.flow' => 'v2/cart/flow',

        'ecapi.goods.property.total' => 'v2/goods/property-total',

    ],
];
