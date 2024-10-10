define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/translate',
    'mage/url'
], function ($, Component, ko, $t, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PicPay_Checkout/payment/info/wallet',
            qr_code: '',
            qr_code_base64: '',
            order_status: ''
        },

        initialize: function () {
            this._super();
            this.buttonText = ko.observable($t('Copy code'));
            this.paymentReceived = ko.observable(this.order_status == 'PAID');

            return this;
        },

        copyPasteWalletCode: function (data, event) {
            let self = this;
            navigator.clipboard.writeText(self.qr_code).then(function() {
                self.buttonText($t('Copied!'));
            }).catch(function(error) {
                console.error('Could not copy text: ', error);
            });
        }
    });
});
