<?php

namespace DpdRo\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class Orderplaceafter implements ObserverInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $obj = ObjectManager::getInstance();
            $apiRequest = $obj->create('\DpdRo\Shipping\Model\Ajax');
            $apiSettings = $apiRequest->Settings();
            $apiServices = $apiSettings['services'];
            $order = $observer->getEvent()->getOrder();
            $orderShippingMethodCode = str_replace('dpdro_shipping_dpd_', '', $order->getShippingMethod());
            if ($_REQUEST && !empty($_REQUEST) && isset($_REQUEST['limit'])) {
                if (in_array($orderShippingMethodCode, $apiServices)) {
                    $dataSettings = [
                        'orderID'         => $order->getId(),
                        'shippingTax'     => false,
                        'shippingTaxRate' => 'no',
                    ];
                    $orderData = $apiRequest->Magento_GetOrderData($order->getId());
                    $shippingAmount = (float) $order->getShippingAmount();
                    $taxRateData = $apiRequest->DPD_GetTaxRateByServiceId($orderShippingMethodCode, $orderData['products']);
                    if ($apiSettings['payerCourier'] === 'RECIPIENT') {
                        // Recipient pay the tax
                    } else {
                        if ($taxRateData) {
                            if ($taxRateData['calculationType']) {
                                $shippingAmount = (float) $taxRateData['taxRate'];
                            } else {
                                $shippingAmount = (float) $shippingAmount + ($shippingAmount * ($taxRateData['taxRate'] / 100));
                            }
                            $dataSettings['shippingTaxRate'] = 'yes';
                        }
                    }
                    $dataSettings['shippingTax'] = (float) $shippingAmount;
                    $apiRequest->DPD_AddSettings($dataSettings);
                    $dataAddress = [
                        'orderID'         => $order->getId(),
                        'address'         => implode(', ', $order->getShippingAddress()->getStreet()),
                        'addressCityID'   => '',
                        'addressCityName' => $order->getShippingAddress()->getCity(),
                        'method'          => 'address',
                        'officeID'        => ''
                    ];
                    $checkDataCountry = $order->getShippingAddress()->getCountryId();
                    $checkDataRegion = $apiRequest->ReplaceDiacritics($order->getShippingAddress()->getRegion());
                    $checkDataCity = $apiRequest->ReplaceDiacritics($order->getShippingAddress()->getCity());
                    $checkCityId = $apiRequest->GetCityByName($checkDataCountry, $checkDataRegion, $checkDataCity);
                    if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
                        $dataAddress['addressCityID'] = (int) $checkCityId['id'];
                    }
                    $apiRequest->DPD_AddAddress($dataAddress);
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
