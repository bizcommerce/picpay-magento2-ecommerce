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
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon',
    'Magento_Customer/js/model/customer',
    'Magento_Payment/js/view/payment/cc-form',
    'mage/url',
    'PicPay_Checkout/js/model/credit-card-validation/credit-card-number-validator',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'picpay-cc-form',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/mage',
    'mage/validation',
    'picpay_checkout/validation'
], function (
    _,
    ko,
    $,
    $t,
    setCouponCodeAction,
    cancelCouponCodeAction,
    customer,
    Component,
    urlBuilder,
    cardNumberValidator,
    creditCardData,
    creditCardForm
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PicPay_Checkout/payment/form/cc',
            taxvat: window.checkoutConfig.payment.picpay_checkout_cc.customer_taxvat.replace(/[^0-9]/g, ""),
            creditCardOwner: '',
            creditCardInstallments: '',
            picpayCreditCardNumber: '',
            creditCardType: '',
            showCardData: ko.observable(true),
            installments: ko.observableArray([]),
            hasInstallments: ko.observable(false),
            installmentsUrl: '',
            showInstallmentsWarning: ko.observable(true),
            debounceTimer: null
        },

        /** @inheritdoc */
        initObservable: function () {
            var self = this;
            this._super()
                .observe([
                    'taxvat',
                    'creditCardType',
                    'creditCardExpDate',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'picpayCreditCardNumber',
                    'creditCardType',
                    'creditCardVerificationNumber',
                    'selectedCardType',
                    'creditCardOwner',
                    'creditCardInstallments'
                ]);

            this.creditCardVerificationNumber('');

            setCouponCodeAction.registerSuccessCallback(function () {
                self.updateInstallmentsValues();
            });

            cancelCouponCodeAction.registerSuccessCallback(function () {
                self.updateInstallmentsValues();
            });

            //Set credit card number to credit card data object
            this.picpayCreditCardNumber.subscribe(function (value) {
                let result;

                self.installments.removeAll();
                self.hasInstallments(false);
                self.showInstallmentsWarning(true);
                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }

                result = cardNumberValidator(value);
                if (!result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.picpayCreditCardNumber = value;
                    self.creditCardType(result.card.type);
                }

                self.updateInstallmentsValues();
            });


            return this;
        },

        loadCard: function () {
            let ccName = document.getElementById(this.getCode() + '_cc_owner');
            let ccNumber = document.getElementById(this.getCode() + '_cc_number');
            let ccExpDate = document.getElementById(this.getCode() + '_cc_exp_date');
            let ccCvv = document.getElementById(this.getCode() + '_cc_cid');
            let ccSingle = document.getElementById('picpay-checkout-cc-ccsingle');
            let ccFront = document.getElementById('picpay-checkout-cc-front');
            let ccBack = document.getElementById('picpay-checkout-cc-back');

            creditCardForm(ccName, ccNumber, ccExpDate, ccCvv, ccSingle, ccFront, ccBack);
        },

        getCode: function () {
            return this.item.method;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {

            let ccExpMonth = '';
            let ccExpYear = '';
            let ccExpDate = this.creditCardExpDate();

            if (typeof ccExpDate !== "undefined" && ccExpDate !== null) {
                let ccExpDateFull = ccExpDate.split('/');
                ccExpMonth = ccExpDateFull[0];
                ccExpYear = ccExpDateFull[1];
            }
            return {
                'method': this.item.method,
                'additional_data': {
                    'taxvat': this.taxvat(),
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_type': this.creditCardType(),
                    'cc_exp_month': ccExpMonth,
                    'cc_exp_year': ccExpYear.length === 4 ? ccExpYear : '20' + ccExpYear,
                    'cc_number': this.picpayCreditCardNumber(),
                    'cc_owner': this.creditCardOwner(),
                    'installments': this.creditCardInstallments()
                }
            };
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment[this.getCode()].availableTypes;
        },

        getIcons: function (type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type)
                ? window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        /**
         * Check if payment is active
         *
         * @returns {Boolean}
         */
        isActive: function () {
            return this.getCode() === this.isChecked();
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            const $form = $('#' + 'form_' + this.getCode());
            return ($form.validation() && $form.validation('isValid'));
        },

        /**
         * @returns {boolean|*}
         */
        retrieveInstallmentsUrl: function () {
            try {
                this.installmentsUrl = window.checkoutConfig.payment.ccform.urls[this.getCode()].retrieve_installments;
                return this.installmentsUrl;
            } catch (e) {
                console.log('Installments URL not defined');
            }
            return false;
        },

        isLoggedIn: function () {
            return customer.isLoggedIn();
        },

        updateInstallmentsValues: function () {

            var self = this;
            if (self.picpayCreditCardNumber().length > 6) {
                if (self.debounceTimer !== null) {
                    clearTimeout(self.debounceTimer);
                }

                //I need to change it to a POST with body
                self.debounceTimer = setTimeout(() => {
                    fetch(self.retrieveInstallmentsUrl(), {
                        method: 'POST',
                        cache: 'no-cache',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            form_key: window.checkoutConfig.formKey,
                            cc_type: self.creditCardType()
                        })
                    }).then((response) => {
                        self.installments.removeAll();
                        return response.json();
                    }).then(json => {
                        json.forEach(function (installment) {
                            self.installments.push(installment);
                            self.hasInstallments(true);
                            self.showInstallmentsWarning(false);
                        });
                    });
                }, 500);
            }
        },

        canUseTds: function () {
            return window.checkoutConfig.payment[this.getCode()].use_tds;
        },

        placeNotAuthorizedOrder: function () {
            return window.checkoutConfig.payment[this.getCode()].place_not_authorized_order;
        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            let formData = this.getData();
            formData.additional_data.browser_data = this.getBrowserData();
            if (this.canUseTds()) {
                $.ajax({
                    url: urlBuilder.build('picpay_checkout/tds/enrollment'),
                    global: true,
                    data: JSON.stringify(this.getData()),
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).done(function (data) {
                    alert('success')
                    console.log(data)
                }).fail(function (response) {
                    alert('fail')
                    console.log(response)
                });
            } else {
                return this._super(data, event);
            }


            // @todo Verifica se deve usar 3DS
            // @todo Chama a criação de sessão
            // @todo Verifica se tem challenge, se tiver, exibe para o usuário



            // Add custom logic before the order is placed
            alert('placeOrder')

            // Custom logic: for example, validating specific fields or sending data to an external service
            console.log("Custom placeOrder logic executed");

            // Call the original placeOrder function
            // return this._super(data, event);
        },

        getBrowserData: function () {
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
    });
});
