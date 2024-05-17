define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (
    Component,
    rendererList
) {
    'use strict';

    rendererList.push({
        type: 'picpay_checkout_cc',
        component: 'PicPay_Checkout/js/view/payment/method-renderer/cc'
    });

    return Component.extend({});
});
