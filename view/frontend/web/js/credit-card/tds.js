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

define([
    'underscore',
    'ko',
    'jquery',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList'
 ], function (
    _,
    ko,
    $,
    $t,
    urlBuilder,
    modal,
    fullScreenLoader,
    customerData,
    messageList
) {
    'use strict';

    return class Tds {
        runTds(cardData, placeOrderCallback) {
            let self = this;
            cardData.additional_data.browser_data = this.getBrowserData();

            $.ajax({
                url: urlBuilder.build('picpay_checkout/tds/enrollment'),
                global: true,
                data: JSON.stringify(cardData),
                contentType: 'application/json',
                type: 'POST',
                async: true
            }).done(function (data) {
                if (data['cardholderAuthenticationStatus'] == 'Challenged' && data['accessToken']) {
                    self.setTdsIframe(data);
                    $('#picpay-tds-step-up-form').submit();
                    $('#picpay-tds-modal').modal('openModal');
                    fullScreenLoader.stopLoader();
                    self.checkChallengeStatus(placeOrderCallback);
                    return;
                }

                if (data['cardholderAuthenticationStatus'] == 'Approved') {
                    self.placeOrder(placeOrderCallback, true);
                    return;
                }

                if (data['cardholderAuthenticationStatus'] == 'Rejected' && self.canPlaceNotAuthorizedOrder()) {
                    self.placeOrder(placeOrderCallback, false);
                    return;
                }

                $('#picpay-tds-modal').modal('closeModal');
                self.displayErrorMessage($t('We were unable to authenticate your transaction, please try again.'));
                fullScreenLoader.stopLoader();
            });
        }

        initTdsModal() {
            let modalOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: false,
                modalClass: 'picpay-tds-modal',
                buttons: []
            };

            modal(modalOptions, $('#picpay-tds-modal'));
        }

        setTdsIframe(data) {
            let iframe = $('#picpay-tds-step-up-iframe');
            let modal = $('.picpay-tds-modal .modal-inner-wrap');

            iframe.css('height', data['heightChallenge']);
            modal.css('height', 'fit-content');

            iframe.css('width', data['widthChallenge']);
            modal.css('width', 'fit-content');

            let form = $('#picpay-tds-step-up-form');
            form.attr('action', data['stepUpUrl']);

            let input = $('#picpay-tds-access-code');
            input.val(data['accessToken']);
        }

        getBrowserData() {
            return {
                httpBrowserJavaEnabled: navigator.javaEnabled(),
                httpBrowserJavaScriptEnabled: true,
                httpBrowserColorDepth: screen.colorDepth,
                httpBrowserScreenHeight: screen.height,
                httpBrowserScreenWidth: screen.width,
                httpBrowserTimeDifference: new Date().getTimezoneOffset(),
                httpBrowserLanguage: navigator.language,
                userAgentBrowserValue: navigator.userAgent
            }
        }

        checkChallengeStatus(placeOrderCallback) {
            let self = this;
            let challengeInterval = setInterval(function() {
                $.ajax({
                    url: urlBuilder.build('picpay_checkout/tds/challenge'),
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.error) {
                            messageList.addSuccessMessage({'message': $t(response.error)});
                            return;
                        }

                        if (response.challenge_status == 'Approved') {
                            clearInterval(challengeInterval);
                            self.placeOrder(placeOrderCallback, true);
                            return;
                        }

                        if (response.challenge_status == 'Rejected' && self.canPlaceNotAuthorizedOrder()) {
                            clearInterval(challengeInterval);
                            self.placeOrder(placeOrderCallback, false);
                            return;
                        }

                        if (response.challenge_status == 'Rejected') {
                            $('#picpay-tds-modal').modal('closeModal');
                            self.displayErrorMessage($t('We were unable to authenticate your transaction, please try again.'));
                            clearInterval(challengeInterval);
                        }
                    },
                    error: function () {
                        clearInterval(challengeInterval);
                        $('#picpay-tds-modal').modal('closeModal');
                        console.error('An error occurred while checking the order status.');
                    }
                });
            }, 2000);
        }

        placeOrder(placeOrderCallback, withTds) {
            if (typeof placeOrderCallback === 'function') {
                placeOrderCallback(withTds);
            }
            $('#picpay-tds-modal').modal('closeModal');
        }

        canPlaceNotAuthorizedOrder() {
            return window.checkoutConfig.payment['picpay_checkout_cc'].place_not_authenticated_order;
        }

        displayErrorMessage(message) {
            messageList.addErrorMessage({'message': $t(message)});
            customerData.set('messages', {
                messages: [
                    { text: message, type: 'error' }
                ]
            });
        }
    }
});
