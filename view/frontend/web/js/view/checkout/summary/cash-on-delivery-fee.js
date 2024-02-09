define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'DpdRo_CashOnDeliveryFee/js/action/set-payment-and-update-totals',
        'mage/translate',
        'knockout'
    ],
    function(Component, quote, priceUtils, totals, setPaymentAndUpdateTotalsAction, translate, ko) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'DpdRo_CashOnDeliveryFee/cash-on-delivery-fee',
                title: translate('DPD RO Plata ramburs'),
                value: ko.observable(0.0),
                shouldDisplay: ko.observable(false)
            },
            initialize: function() {
                this._super();

                quote.paymentMethod.subscribe(function(newPaymentMethod) {
                    setPaymentAndUpdateTotalsAction(newPaymentMethod)
                });

                quote.totals.subscribe((function(newTotals) {
                    this.value(this.getFormattedTotalValue(newTotals));
                    this.shouldDisplay(this.isTotalDisplayed(newTotals));
                }).bind(this));
            },
            isTotalDisplayed: function(totals) {
                return this.getTotalValue(totals) > 0;
            },
            getTotalValue: function(totals) {
                if (typeof totals.total_segments === 'undefined' || !totals.total_segments instanceof Array) {
                    return 0.0;
                }

                return totals.total_segments.reduce(function(cashOnDeliveryTotalValue, currentTotal) {
                    return currentTotal.code === 'cash_on_delivery_fee' ? currentTotal.value : cashOnDeliveryTotalValue
                }, 0.0);
            },
            getFormattedTotalValue: function(totals) {
                return this.getFormattedPrice(this.getTotalValue(totals));
            }
        });
    }
);