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
            template: 'PicPay_Checkout/payment/info/pix',
            qr_code: '',
            qr_code_base64: '',
            expiration_time: 300,
            order_status: ''
        },

        initialize: function () {
            this._super();
            this.expiration_time = ko.observable(this.expiration_time);
            this.hasExpirationTime = ko.observable(false)
            if (this.expiration_time()) {
                this.hasExpirationTime(true);
                this.startCountdown();
                this.checkPaymentStatus();
            }

            this.buttonText = ko.observable($t('Copy code'));
            this.formattedTime = ko.computed(this.formatTime.bind(this));
            this.paymentReceived = ko.observable(this.order_status == 'PAID');
            this.orderCanceled = ko.observable(this.order_status == 'CANCELED' || this.order_status == 'EXPIRED');

            return this;
        },

        formatTime: function() {
            let seconds = this.expiration_time();
            let minutes = Math.floor(seconds / 60);
            let remainingSeconds = seconds % 60;
            return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
        },

        startCountdown: function() {
            let self = this;
            let timer = setInterval(function() {
                let currentTime = self.expiration_time();
                if (currentTime > 0) {
                    self.expiration_time(currentTime - 1);
                }
            }, 1000);
        },

        copyPastePixCode: function (data, event) {
            let self = this;
            navigator.clipboard.writeText(self.qr_code).then(function() {
                self.buttonText($t('Copied!'));
            }).catch(function(error) {
                console.error('Could not copy text: ', error);
            });
        },

        checkPaymentStatus: function () {
            let source = new EventSource(
                urlBuilder.build('picpay_checkout/payment/status?' + 'order_id=' + this.expiration_time())
            );
            let self = this;
            source.onmessage = function(event) {
                let details = JSON.parse(event.data);
                if (details.is_paid) {
                    self.paymentReceived(true);
                    window.href.location = details.redirect;
                }
            };
        }
    });
});
