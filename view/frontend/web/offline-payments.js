define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(component, rendererList) {

        'use strict';

        rendererList.push({
            type: 'dpdro_payment',
            component: 'DpdRo_Checkou/payment'
        });

        return component.extend({});
    }
);