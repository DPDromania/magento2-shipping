<?php

namespace DpdRo\Shipping\Plugin\Order;

use DpdRo\Shipping\Model\Order\CashOnDeliveryFeeExtensionManagement;
use Magento\Sales\Model\Order;

class LoadCashOnDeliveryFee
{
    /**
     * @var CashOnDeliveryFeeExtensionManagement
     */
    private $extensionManagement;

    public function __construct(CashOnDeliveryFeeExtensionManagement $extensionManagement)
    {
        $this->extensionManagement = $extensionManagement;
    }

    public function afterLoad(Order $subject, Order $returnedOrder): Order
    {
        return $this->extensionManagement->setExtensionFromData($returnedOrder);
    }
}
