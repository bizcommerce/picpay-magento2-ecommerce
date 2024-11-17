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
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer'
    ],
    function ($, ko, Component, customer) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PicPay_Checkout/payment/form/pix',
            },

            taxvat: ko.observable(),

            getCode: function() {
                return 'picpay_checkout_pix';
            },

            validate: function () {
                const $form = $('#' + 'form_' + this.getCode());
                return ($form.validation() && $form.validation('isValid'));
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'taxvat': this.taxvat()
                    }
                };
            },

            isLoggedIn: function () {
                return customer.isLoggedIn();
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
