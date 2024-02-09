<?php

namespace DpdRo\Shipping\Plugin\Order;

use DpdRo\Shipping\Model\Order\CashOnDeliveryFeeExtensionManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class SaveCashOnDeliveryFee
{
    /**
     * @var CashOnDeliveryFeeExtensionManagement
     */
    private $extensionManagement;

    public function __construct(CashOnDeliveryFeeExtensionManagement $extensionManagement)
    {
        $this->extensionManagement = $extensionManagement;
    }

    public function beforeSave(OrderRepositoryInterface $subject, Order $order): array
    {
        return [$this->extensionManagement->setDataFromExtension($order)];
    }
}
