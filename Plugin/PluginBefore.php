<?php

namespace DpdRo\Shipping\Plugin;

use Magento\Framework\App\ObjectManager;

class PluginBefore
{
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {

        $this->_request = $context->getRequest();

        if ($this->_request->getFullActionName() == 'sales_order_view') {
            $obj = ObjectManager::getInstance();
            $orderId =  $this->_request->getParam('order_id');
            $store = $obj->create('\Magento\Store\Model\StoreManagerInterface');
            $customAjax = $obj->create('\DpdRo\Shipping\Model\Ajax');
            $shipmentData = $customAjax->DPD_GetShipmentByOrderID($orderId);
            if ($shipmentData && isset($shipmentData['shipmentID']) && !empty($shipmentData['shipmentID'])) {
                $buttonList->add(
                    'dpdro_button',
                    [
                        'label' => __('DPD RO Tracking'),
                        'onclick' => 'window.open("https://tracking.dpd.ro/?shipmentNumber=' . $shipmentData['shipmentID'] . '")',
                        'class' => 'reset'
                    ],
                    -1
                );
                $printLabel = $store->getStore()->getUrl('dpd/printing/index') . '?print=labels&orderID=' . $orderId;
                $buttonList->add(
                    'dpdro_button_print',
                    [
                        'label' => __('DPD RO Print Label'),
                        'onclick' => 'window.open("' . $printLabel . '")',
                        'class' => 'reset'
                    ],
                    -1
                );
            }
        }
    }
}
