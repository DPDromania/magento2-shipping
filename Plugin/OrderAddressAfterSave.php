<?php

namespace DpdRo\Shipping\Plugin;

use Magento\Sales\Model\Order\AddressRepository;
use Magento\Framework\App\ObjectManager;

class OrderAddressAfterSave
{
    public function afterSave(AddressRepository $subject, $result)
    {
        $data = $result->getData();
        if (isset($data['address_type']) && !empty($data['address_type']) && $data['address_type'] == 'shipping') {
            $obj = ObjectManager::getInstance();
            $apiRequest = $obj->create('\DpdRo\Shipping\Model\Ajax');
            $orderID = $data['parent_id'];
            $shipmentData = $apiRequest->DPD_GetShipmentByOrderID($orderID);
            if ($shipmentData && !empty($shipmentData)) {
                // Do nothing
            } else {
                $orderAddressUpdate = [
                    'orderID'  => $orderID,
                    'address'  => $data['street'],
                    'cityID'   => '',
                    'cityName' => $data['city'],
                ];
                $checkCityId = $apiRequest->GetCityByName($data['country_id'], $data['region'], $data['city']);
                if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
                    $orderAddressUpdate['cityID'] = (int) $checkCityId['id'];
                    $orderAddressUpdate['cityName'] = (string) $checkCityId['name'];
                }
                $this->_update($orderAddressUpdate);
            }
        }
    }

    // Update
    public function _update($data)
    {
        $obj = ObjectManager::getInstance();
        $apiRequest = $obj->create('\DpdRo\Shipping\Model\Ajax');
        $tableName = $apiRequest->DB_GetTable('dpdro_order_address');
        $orderID = (int) $data['orderID'];
        $checkAddress = $apiRequest->DPD_GetAddressNormalizedByOrderID($orderID);
        if ($checkAddress && !empty($checkAddress)) {
            $address           = $data['address'];
            $addressCityID     = $data['cityID'];
            $addressCityName   = $data['cityName'];
            $addressStreetID   = '';
            $addressStreetType = '';
            $addressStreetName = '';
            $query = "
                UPDATE 
                    `{$tableName}` 
				SET 
					`address`           = '{$address}', 
					`addressCityID`     = '{$addressCityID}', 
					`addressCityName`   = '{$addressCityName}', 
					`addressStreetID`   = '{$addressStreetID}', 
					`addressStreetType` = '{$addressStreetType}', 
					`addressStreetName` = '{$addressStreetName}',
					`status`            = ''
				WHERE 
					`orderID` = '{$orderID}'
			";
            $apiRequest->DB_Query($query);
        }
    }
}
