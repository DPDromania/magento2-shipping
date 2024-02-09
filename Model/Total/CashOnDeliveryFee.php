<?php

declare(strict_types=1);

namespace DpdRo\Shipping\Model\Total;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;

class CashOnDeliveryFee extends AbstractTotal
{
    const CONFIG_PATH_FEE_AMOUNT = 'payment/cashondelivery/fee';

    const TOTAL_CODE = 'cash_on_delivery_fee';
    const BASE_TOTAL_CODE = 'base_cash_on_delivery_fee';

    const LABEL = 'DPD RO Plata ramburs';
    const BASE_LABEL = 'Base DPD RO Plata ramburs';

    /**
     * @var float
     */
    private $fee;
    private $baseCurrency;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $currencyCode = $scopeConfig->getValue("currency/options/base", ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->baseCurrency =  $currencyFactory->create()->load($currencyCode);
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface
        $shippingAssignment,
        Total $total
    ): CashOnDeliveryFee {
        parent::collect($quote, $shippingAssignment, $total);

        if (count($shippingAssignment->getItems()) == 0) {
            return $this;
        }

        $baseCashOnDeliveryFee = $this->getFee($quote);
        $currency = $quote->getStore()->getCurrentCurrency();
        $cashOnDeliveryFee = $this->baseCurrency->convert($baseCashOnDeliveryFee, $currency);

        $total->setData(static::TOTAL_CODE, $cashOnDeliveryFee);
        $total->setData(static::BASE_TOTAL_CODE, $baseCashOnDeliveryFee);

        $total->setTotalAmount(static::TOTAL_CODE, $cashOnDeliveryFee);
        $total->setBaseTotalAmount(static::TOTAL_CODE, $baseCashOnDeliveryFee);

        return $this;
    }

    public function fetch(Quote $quote, Total $total): array
    {
        $base_value = $this->getFee($quote);
        if ($base_value) {
            $currency = $quote->getStore()->getCurrentCurrency();
            $value = $this->baseCurrency->convert($base_value, $currency);
        } else {
            $value = null;
        }
        return [
            'code' => static::TOTAL_CODE,
            'title' => static::LABEL,
            'base_value' => $base_value,
            'value' => $value
        ];
    }

    public function getLabel(): Phrase
    {
        return __(static::LABEL);
    }

    private function getFee(Quote $quote): float
    {
        if ($quote->getPayment()->getMethod() !== 'dpdro_payment') {
            return (float)null;
        }
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Shipping\Model\Ajax');
        $quoteAddress = $quote->getShippingAddress();
        $quoteAddressCountryID = $quoteAddress->getCountryId();
        $paymentData = $apiRequest->DPD_GetPaymentTaxByCountryID($quoteAddressCountryID);
        $quoteShippingMethod = $quoteAddress->getShippingMethod();
        $quoteShippingMethod = str_replace('dpdro_shipping_dpd_', '', $quoteShippingMethod);
        $quoteShippingService = $apiRequest->DPD_GetShippingTaxByQuote($quote, $quoteShippingMethod);
        $quoteShippingServiceWithTax = $apiRequest->DPD_GetShippingTaxByQuote($quote, $quoteShippingMethod, true);
        $serviceTax = 0;
        if (
            is_array($quoteShippingService) && array_key_exists('calculations', $quoteShippingService) && !empty($quoteShippingService['calculations']) &&
            is_array($quoteShippingServiceWithTax) && array_key_exists('calculations', $quoteShippingServiceWithTax) && !empty($quoteShippingServiceWithTax['calculations'])
        ) {
            if (isset($quoteShippingService['calculations'][0]) && isset($quoteShippingServiceWithTax['calculations'][0])) {
                if (isset($quoteShippingService['calculations'][0]['price']) && isset($quoteShippingServiceWithTax['calculations'][0]['price'])) {
                    $serviceCurrency = $quoteShippingService['calculations'][0]['price']['currencyLocal'];
                    $serviceTotal = (float) $quoteShippingService['calculations'][0]['price']['total'];
                    $serviceTotalConverted = (float) $apiRequest->Magento_CurrencyConvert($serviceTotal, $serviceCurrency);
                    $serviceTotalWithTax = (float) $quoteShippingServiceWithTax['calculations'][0]['price']['total'];
                    $serviceTotalWithTaxConverted = (float) $apiRequest->Magento_CurrencyConvert($serviceTotalWithTax, $serviceCurrency);
                    $serviceTax = $serviceTotalWithTaxConverted - $serviceTotalConverted;
                }
            }
        }
        $paymentFee = 0;
        if ($paymentData && !empty($paymentData) && is_array($paymentData)) {
            $paymentFeeTax = $paymentData[0]['tax'];
            $paymentFeeVAT = $paymentData[0]['vat'];
            $paymentFee = (float) $paymentFeeTax + ((float) $paymentFeeTax * (float) $paymentFeeVAT / 100);
        }
        $paymentFeeTotal = (float) $paymentFee + (float) $serviceTax;
        return (float) $paymentFeeTotal;
    }
}
