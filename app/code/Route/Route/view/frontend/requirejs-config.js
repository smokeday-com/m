/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            "route_magento_widget": 'Route_Route/js/view/widget/widget'
        }
    },
    config: {
        mixins: {
            'Aheadworks_OneStepCheckout/js/view/shipping-method': {
                'Route_Route/js/view/shipping-method': true
            },
        }
    },
    paths: {
        'route-protection-widget': 'https://protection-widget.route.com/route-protection-widget',
    },

};
