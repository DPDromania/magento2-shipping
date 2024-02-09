<?php

namespace DpdRo\Shipping\Model;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class Observer implements ObserverInterface
{
    // Parameters
    protected $customAjax;

    // Constructor
    public function __construct()
    {
        $obj = ObjectManager::getInstance();
        $this->customAjax = $obj->create('\DpdRo\Shipping\Model\Ajax');
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        if ($paymentMethod == 'dpdro_payment') {
            if ($this->customAjax->CheckConnection() != 'success') {
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false);
            }
            if (!$this->customAjax->CheckActive()) {
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false);
            }
            if ($observer->getEvent()->hasQuote()) {
                $countryAllowed = array(
                    'BG',
                    'GR',
                    'HU',
                    'PL',
                    'RO',
                );
                $quoteAddress = $observer->getEvent()->getQuote()->getShippingAddress();
                $quoteShippingMethod = $quoteAddress->getShippingMethod();
                $quoteData = $this->Magento_GetQuoteById($observer->getEvent()->getQuote()->getId());
                $quoteAddressCountry = '';
                if ($quoteData && !empty($quoteData) && isset($quoteData['country_id'])) {
                    $quoteAddressCountry = $quoteData['country_id'];
                }
                $apiService = $this->customAjax->Settings('services');
                $quoteShippingMethod = str_replace('dpdro_shipping_dpd_', '', $quoteShippingMethod);
                if (in_array($quoteShippingMethod, $apiService)) {
                    if (!in_array($quoteAddressCountry, $countryAllowed)) {
                        $checkResult = $observer->getEvent()->getResult();
                        $checkResult->setData('is_available', false);
                    }
                } else {
                    $checkResult = $observer->getEvent()->getResult();
                    $checkResult->setData('is_available', false);
                }
                $paymentData = $this->customAjax->DPD_GetPaymentTaxByCountryID($quoteAddressCountry);
                if (!$paymentData) {
                    $checkResult = $observer->getEvent()->getResult();
                    $checkResult->setData('is_available', false);
                }
            }
        }
    }
    public function Magento_GetQuoteById($id)
    {
        $tableName = $this->customAjax->DB_GetTable('quote_address');
        $quoteId = (int) $id;
        $query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`quote_id` = {$quoteId} AND
				`address_type` = 'shipping'
		";
        $response = $this->customAjax->DB_Fetch($query);
        if ($response && !empty($response)) {
            return $response[0];
        }
        return false;
    }
}
