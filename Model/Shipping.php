<?php
/**
 * @category    DpdRo
 * @package     DpdRo_Shipping
 * @copyright   Copyright (c) DPD Ro (https://www.dpd.com/ro/ro/)
 */
namespace DpdRo\Shipping\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Framework\App\ObjectManager;

class Shipping extends AbstractCarrier implements CarrierInterface
{

    protected $_code = 'dpdro_shipping';
    protected $_rateResultFactory;
    protected $_rateMethodFactory;

    public function __construct(ScopeConfigInterface $scopeConfig, ErrorFactory $rateErrorFactory, LoggerInterface $logger, ResultFactory $rateResultFactory, MethodFactory $rateMethodFactory, array $data = [])
    {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateResultFactory->create();
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Shipping\Model\Ajax');
        if ($apiRequest->CheckConnection() != 'success') {
            return $result;
        }
        if (!$apiRequest->CheckActive()) {
            return $result;
        }
        $taxesCalculation = $apiRequest->DPD_GetShippingTaxByRequest($request);
        $taxesCalculationByID = array();
        if (is_array($taxesCalculation) && array_key_exists('calculations', $taxesCalculation) && !empty($taxesCalculation['calculations'])) {
            foreach ($taxesCalculation['calculations'] as $dpd_tax) {
                $taxesCalculationByID[$dpd_tax['serviceId']] = $dpd_tax;
            }
        }
        $settings = $apiRequest->Settings();
        $cartData = $apiRequest->Magento_GetCartData();
        if ($settings['services'] && !empty($settings['services'])) {
            foreach ($settings['services'] as $service) {
                if (is_array($taxesCalculationByID) && array_key_exists($service, $taxesCalculationByID) && is_array($taxesCalculationByID[$service]) && array_key_exists('price', $taxesCalculationByID[$service]) && !empty($taxesCalculationByID[$service]['price'])) {
                    $serviceTax = $taxesCalculationByID[$service]['price']['total'];
                    $serviceCurrency = $taxesCalculationByID[$service]['price']['currencyLocal'];
                    $serviceTaxConverted = $apiRequest->Magento_CurrencyConvert($serviceTax, $serviceCurrency);
                    $taxRateData = $apiRequest->DPD_GetTaxRateByServiceId($service, $cartData['products']);
                    $sessionGet = $apiRequest->DPD_GetSessionConfirmation('tax');
                    if (isset($sessionGet) && !empty($sessionGet)) {
                        $sessionGet['tax_' . $service] = $serviceTaxConverted;
                        $sessionGet['tax_rate_' . $service] = 'no';
                    } else {
                        $sessionGet = [
                            'tax_' . $service => $serviceTaxConverted,
                            'tax_rate_' . $service => 'no'
                        ];
                    }
                    if ($settings['payerCourier'] === 'RECIPIENT') {
                        // Recipient pay the tax
                    } else {
                        if ($taxRateData) {
                            if ($taxRateData['calculationType']) {
                                $serviceTaxConverted = (float) $taxRateData['taxRate'];
                            } else {
                                $serviceTaxConverted = (float) $serviceTaxConverted + ($serviceTaxConverted * ($taxRateData['taxRate'] / 100));
                            }
                            $sessionGet['tax_rate_' . $service] = 'yes';
                        }
                    }
                    $sessionGet['tax_' . $service] = $serviceTaxConverted;
                    $apiRequest->DPD_SetSessionConfirmation($sessionGet, 'tax');
                    $result->append($this->_methodTemplate($service, $serviceTaxConverted));
                }
            }
        }
        return $result;
    }

    public function getAllowedMethods()
    {
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Admin\Model\Ajax');
        $apiSettings = $apiRequest->Settings();
        $apiServices = $apiRequest->ListServices();
        $methods = [];
        if ($apiSettings['services'] && !empty($apiSettings['services'])) {
            foreach ($apiSettings['services'] as $apiService) {
                $methods['dpd_' . $apiService] = $apiServices[$apiService];
            }
        }
        return $methods;
    }

    protected function _methodTemplate($serviceID, $price)
    {
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Admin\Model\Ajax');
        $apiServices = $apiRequest->ListServices();
        $price = (float) $price;
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('dpd_' . $serviceID);
        $method->setMethodTitle($apiServices[$serviceID]);
        $method->setPrice($price);
        $method->setCost($price);
        return $method;
    }
}
