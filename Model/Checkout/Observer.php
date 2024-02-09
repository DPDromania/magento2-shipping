<?php

namespace DpdRo\Shipping\Model\Checkout;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class Observer implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Admin\Model\Ajax');
        $apiSession = $obj->create('\DpdRo\Shipping\Controller\Api\Session');
        $apiServices = $apiRequest->Settings('services');
        $order = $observer->getEvent()->getOrder();
        $orderShippingMethodCode = str_replace('dpdro_shipping_dpd_', '', $order->getShippingMethod());
        if (in_array($orderShippingMethodCode, $apiServices)) {

            // DPD ADD SETTINGS
            $shippingTax = false;
            $shippingTaxRate = false;
            $sessionTax = $apiSession->getSession('tax');
            if (isset($sessionTax) && !empty($sessionTax)) {
                $shippingTax = $sessionTax['tax_' . $orderShippingMethodCode];
                $shippingTaxRate = $sessionTax['tax_rate_' . $orderShippingMethodCode];
            }
            $dataSettings = [
                'orderID'         => $order->getId(),
                'shippingTax'     => $shippingTax,
                'shippingTaxRate' => $shippingTaxRate,
            ];
            $apiRequest->DPD_AddSettings($dataSettings);

            // DPD ADD ADDRESS
            $address = implode(', ', $order->getShippingAddress()->getStreet());
            $addressCityID = '';
            $addressCityName = '';
            $method = 'address';
            $officeID = '';
            $sessionAddress = $apiSession->getSession('address');
            if (isset($sessionAddress) && !empty($sessionAddress)) {
                $addressCityID = $sessionAddress['cityID'];
                $addressCityName = $sessionAddress['cityName'];
            }
            $sessionConfirmation = $apiSession->getSession('confirmation');
            if (isset($sessionConfirmation) && !empty($sessionConfirmation)) {
                $method = $sessionConfirmation['method'];
                $officeID = $sessionConfirmation['pickup'];
            }
            $dataAddress = [
                'orderID'         => $order->getId(),
                'address'         => $address,
                'addressCityID'   => $addressCityID,
                'addressCityName' => $addressCityName,
                'method'          => $method,
                'officeID'        => $officeID
            ];
            $apiRequest->DPD_AddAddress($dataAddress);

            // UNSET SESSIONS
            $apiSession->unsetSession('tax');
            $apiSession->unsetSession('address');
            $apiSession->unsetSession('confirmation');
        }
    }
}
