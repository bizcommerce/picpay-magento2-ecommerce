/**
 * PicPay
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the PicPay license that is
 * available through the world-wide-web at this URL:
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 * @copyright   Copyright (c) PicPay
 *
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PicPay_Checkout/payment/form/pix'
            },

            getCode: function() {
                return 'picpay_checkout_pix';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            hasInstructions: function () {
                return (window.checkoutConfig.payment.picpay_checkout_pix.checkout_instructions.length > 0);
            },

            getInstructions: function () {
                return window.checkoutConfig.payment.picpay_checkout_pix.checkout_instructions;
            }
        });
    }
);
