define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'PicPay_Checkout/checkout/summary/interest'
            },
            totals: quote.getTotals(),
            isDisplayed: function() {
                console.log(this.totals);
                return this.isFullMode();
            },

            getRawValue: function () {
                var price = 0;
                if (this.totals() && totals.getSegment('picpay_interest')) {
                    price = totals.getSegment('picpay_interest').value;
                }
                return price;
            },

            getValue: function() {
                var price = this.getRawValue();
                return this.getFormattedPrice(price);
            },
        });
    }
);
