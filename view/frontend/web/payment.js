define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default'
    ],
    function(quote, component) {

        'use strict';

        var addressList = [];
        var shippingAddress = quote.shippingAddress();
        if (shippingAddress) {
            if (shippingAddress['countryId'] != '') {
                if (shippingAddress['countryId'] == 'BG') {
                    addressList.push('Bulgaria');
                } else if (shippingAddress['countryId'] == 'GR') {
                    addressList.push('Greece');
                } else if (shippingAddress['countryId'] == 'HU') {
                    addressList.push('Hungary');
                } else if (shippingAddress['countryId'] == 'PL') {
                    addressList.push('Poland');
                } else if (shippingAddress['countryId'] == 'RO') {
                    addressList.push('Romania');
                }
            }
            if (shippingAddress['region']) {
                addressList.push(shippingAddress['region']);
            }
            if (shippingAddress['city']) {
                addressList.push(shippingAddress['city']);
            }
            if (shippingAddress['street']) {
                var shippingAddressStreet = shippingAddress['street'];
                var shippingAddressStreetString = '';
                if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                    shippingAddressStreet.forEach(function(item, index) {
                        if (shippingAddressStreetString == '') {
                            shippingAddressStreetString += item;
                        } else {
                            shippingAddressStreetString += ' '.item;
                        }
                    });
                }
                addressList.push(shippingAddressStreetString);
            }
            if (shippingAddress['postcode']) {
                addressList.push(shippingAddress['postcode']);
            }
        }

        return component.extend({
            defaults: {
                template: 'DpdRo_Shipping/payment'
            },
            address: addressList.join(', ')
        });
    }
);