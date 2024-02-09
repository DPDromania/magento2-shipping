<?php

namespace DpdRo\Shipping\Model\Total\Invoice;

class CashOnDeliveryFee extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * @inheritdoc
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        parent::collect($invoice);

        $codFee = $invoice->getOrder()->getExtensionAttributes()->getCashOnDeliveryFee();
        $baseCodFee = $invoice->getOrder()->getExtensionAttributes()->getBaseCashOnDeliveryFee();

        $invoice->setData(\DpdRo\Shipping\Model\Total\CashOnDeliveryFee::TOTAL_CODE, $codFee);
        $invoice->setData(\DpdRo\Shipping\Model\Total\CashOnDeliveryFee::BASE_TOTAL_CODE, $baseCodFee);

        if (round($codFee, 2) != 0)
        {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $codFee);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseCodFee);
        }

        return $this;
    }
}
