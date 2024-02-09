<?php

namespace DpdRo\Shipping\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\ObjectManager;

class ConfigProvider implements ConfigProviderInterface
{
    public function getConfig()
    {
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Admin\Model\Ajax');
        $apiSession = $obj->create('\DpdRo\Shipping\Controller\Api\Session');
        $store = $obj->create('\Magento\Store\Model\StoreManagerInterface');
        $obj = ObjectManager::getInstance();
        $paymentTaxName = $obj->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/dpdro_payment/title');
        $config = [];
        $config['dpdro'] = [
            'url'             => $store->getStore()->getUrl('dpdro/api/url'),
            'ajax'            => $store->getStore()->getUrl('dpdro/api/session'),
            'active'          => $apiRequest->CheckActive(),
            'session'         => $apiSession->getSession('confirmation'),
            'connected'       => $apiRequest->CheckConnection(),
            'addresses'       => $apiRequest->DPD_GetAddressesData(true),
            'offices'         => $apiRequest->ListOfficeLocations(),
            'officesGroup'    => $apiRequest->ListOfficeLocationsGroups(),
            'payment'         => $paymentTaxName,
        ];
        return $config;
    }
}
