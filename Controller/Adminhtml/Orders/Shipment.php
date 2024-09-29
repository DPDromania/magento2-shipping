<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;

class Shipment extends Action
{
    // Parameters
    protected $customAjax;
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $resultJsonFactory;

    // Constructor
    public function __construct(Context $context, PageFactory $pageFactory, Registry $coreRegistry, JsonFactory $resultJsonFactory)
    {
        $obj = ObjectManager::getInstance();
        $this->customAjax = $obj->create('\DpdRo\Shipping\Model\Ajax');
        $this->resultPageFactory = $pageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    // Response
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $parameters = $this->getRequest()->getParams();
        if (isset($parameters['parameters']) && !empty($parameters['parameters'])) {
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'create') {
                $response = $this->_actionAddShipment($parameters['parameters']);
            }
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'delete') {
                if (isset($parameters['parameters']['orderID']) && !empty($parameters['parameters']['orderID']) && $parameters['parameters']['orderID'] != '') {
                    $response = $this->_deleteShipmentById($parameters['parameters']['orderID']);
                }
            }
        }
        return $resultJson->setData($response);
    }

    // Add shipment
    public function _actionAddShipment($data)
    {
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $obj = ObjectManager::getInstance();
        $settings = $this->customAjax->DPD_GetSettingsByOrderID($data['orderID']);
        $apiSettings = $this->customAjax->Settings();
        if (!$settings || empty($settings)) {
            $settings = [
                'shippingTax'     => '',
                'shippingTaxRate' => '',
                'courierService'  => $apiSettings['payerCourier'],
                'includeShipping' => $apiSettings['senderPayerIncludeShipping'],
                'declaredValue'   => $apiSettings['senderPayerInsurance'],
            ];
        }
        $parameters = [
            'api'    => 'shipment',
            'method' => 'POST',
            'data'   => [
                'clientSystemId' => '221220090300',
                'service' => [
                    'autoAdjustPickupDate' => true,
                    'serviceId' => ''
                ],
                'content'      => [
                    'package'  => "BOX",
                    'contents' => "DPD",
                    'parcels'  => array()
                ],
                'payment' => [
                    'courierServicePayer' => $settings['courierService']
                ],
                'recipient'    => [],
                'shipmentNote' => $data['shipmentNotes'],
                'ref1'         => __('Magento 2 ID: ', 'wc-dpd') . $data['orderID'],
                'ref2'         => $data['shipmentRef2'],
            ]
        ];
        if ((!empty($apiSettings['officeLocations']) && $apiSettings['officeLocations'] !== '0') || (!empty($apiSettings['clientContracts']) && $apiSettings['clientContracts'] !== '0')) {
            if (!empty($apiSettings['officeLocations']) && $apiSettings['officeLocations'] !== '0') {
                $parameters['data']['sender'] = [
                    'dropoffOfficeId' => (int) $apiSettings['officeLocations']
                ];
            } else if (!empty($apiSettings['clientContracts']) && $apiSettings['clientContracts'] !== '0') {
                $parameters['data']['sender'] = [
                    'clientId' => $apiSettings['clientContracts'],
                ];
            }
        }
        if ($settings['courierService'] === 'THIRD_PARTY') {
            $parameters['data']['payment']['thirdPartyClientId'] = $apiSettings['payerCourierThirdParty'];
        }
        if (json_decode($data['shipmentProducts'])) {
            $parcelsCount = [];
            foreach (json_decode($data['shipmentProducts']) as $product) {
                if (is_array($parcelsCount) && !empty($parcelsCount) && array_key_exists($product->parcel, $parcelsCount)) {
                    $parcelsCountWeight = floatval($parcelsCount[$product->parcel]);
                } else {
                    $parcelsCountWeight = 0;
                }
                $parcelsCount[$product->parcel] = $parcelsCountWeight + floatval($product->weight);
            }
            foreach ($parcelsCount as $key => $parcel) {
                $productParcel = [
                    'seqNo' => $key,
                    'weight' => floatval($parcel)
                ];
                array_push($parameters['data']['content']['parcels'], $productParcel);
            }
        }
        $orderData = $obj->create('Magento\Sales\Model\Order')->load($data['orderID']);
        $shippingMethodCode = str_replace('dpdro_shipping_dpd_', '', $orderData->getShippingMethod());
        $parameters['data']['service']['serviceId'] = (int) $shippingMethodCode;
        if ($data['shipmentSwap'] && $data['shipmentSwap'] !== 'false') {
            $parameters['data']['service']['additionalServices']['returns']['swap'] = [
                'serviceId' => $shippingMethodCode,
                'parcelsCount' => count($parameters['data']['content']['parcels'])
            ];
        }
        if ($data['shipmentRod'] && $data['shipmentRod'] !== 'false') {
            $parameters['data']['service']['additionalServices']['returns']['rod'] = [
                'enabled' => true
            ];
        }
        if ($data['shipmentVoucher'] && $data['shipmentVoucher'] !== 'false') {
            $parameters['data']['service']['additionalServices']['returns']['returnVoucher'] = [
                'serviceId' => (int) $shippingMethodCode,
                'payer' =>  $data['shipmentVoucherSender']
            ];
        }
        $orderTotal = $orderData->getGrandTotal();
        $shippingPrice = $orderData->getShippingAmount();
        $serviceCodTax = $this->customAjax->DPD_GetShippingTax(
            $shippingMethodCode, 
            (!in_array($shippingMethodCode, ['2303'])) ? true : false, 
            $data['orderID']
        );

        if (isset($serviceCodTax['calculations'][0]['error'])) {
            $response['message'] = $serviceCodTax['calculations'][0]['error']['message'];
            return $response;
        }

        $serviceCodTaxPrice = $serviceCodTax['calculations'][0]['price']['total'];
        $serviceCodTaxCurrency = $serviceCodTax['calculations'][0]['price']['currencyLocal'];
        $serviceCodTaxPriceConverted = (float) $this->customAjax->Magento_CurrencyConvert($serviceCodTaxPrice, $serviceCodTaxCurrency);
        $codPaymentDeclaredValue = 0;
        $paymentMethodCode = $orderData->getPayment()->getMethodInstance()->getCode();
        $orderCountryID = $orderData->getShippingAddress()->getCountryId();
        
        if ($paymentMethodCode && $paymentMethodCode == 'dpdro_payment') {
            $amount = $orderData->getGrandTotal();

            if ($settings['courierService'] === 'RECIPIENT') {
                $amount = $orderData->getSubtotal();
            }

            $parameters['data']['service']['additionalServices']['cod'] = [
                'currencyCode' => $orderData->getOrderCurrencyCode(),
                'processingType' => 'CASH',
                'amount' => $amount
            ];

        } else {
            $parameters['data']['payment']['courierServicePayer'] = 'SENDER';
        }

        if ($settings['declaredValue'] === 'yes') {
            $parameters['data']['service']['additionalServices']['declaredValue']['amount'] = (float) $codPaymentDeclaredValue;
        }

        $clientLastName = ($orderData->getShippingAddress()->getLastname() && $orderData->getShippingAddress()->getLastname() !== '') ? ' - ' . $orderData->getShippingAddress()->getLastname() : '';
        if ($data['shipmentPrivate'] == 'true') {
            $parameters['data']['recipient'] = [
                'phone1' => [
                    'number' => (string) $orderData->getShippingAddress()->getTelephone()
                ],
                'email' => (string) $orderData->getShippingAddress()->getEmail(),
                'clientName' => $data['shipmentPrivatePerson'],
                'contactName' => $orderData->getShippingAddress()->getFirstname() . $clientLastName,
                'privatePerson' => false
            ];
        } else {
            $parameters['data']['recipient'] = [
                'phone1' => [
                    'number' => (string) $orderData->getShippingAddress()->getTelephone()
                ],
                'email' => (string) $orderData->getShippingAddress()->getEmail(),
                'clientName' => $orderData->getShippingAddress()->getFirstname() . $clientLastName,
                'privatePerson' => true
            ];
        }

        $apiAddress = $this->customAjax->DPD_GetAddressNormalizedByOrderID($data['orderID']);

        if ($apiAddress && !empty($apiAddress)) {
            if ($apiAddress['method'] && $apiAddress['method'] === 'pickup') {
                $parameters['data']['recipient']['pickupOfficeId'] = (int) $apiAddress['officeID'];
            } else {
                $countryData = $this->customAjax->GetCountryByID($orderCountryID);
                if ($countryData) {
                    $parameters['data']['recipient']['address']['countryId'] = $countryData['id'];
                    if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
                        if ($orderData->getShippingAddress()->getData('postcode') && !empty($orderData->getShippingAddress()->getData('postcode'))) {
                            $parameters['data']['recipient']['address']['postCode'] = trim($orderData->getShippingAddress()->getData('postcode'));
                        }
                    }
                }
                if (isset($apiAddress['addressCityID']) &&  !empty($apiAddress['addressCityID'])) {
                    $parameters['data']['recipient']['address']['siteId'] = $apiAddress['addressCityID'];
                } else {
                    if (isset($apiAddress['addressCityName']) &&  !empty($apiAddress['addressCityName'])) {
                        $parameters['data']['recipient']['address']['siteName'] = $this->customAjax->ReplaceDiacritics($apiAddress['addressCityName']);
                    }
                }
                if ($apiAddress['status'] && !empty($apiAddress['status']) && $apiAddress['status'] == 'skip') {
                    $parameters['data']['recipient']['address']['addressLine1'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('street'));
                    $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('street'));
                    if (strlen($parameters['data']['recipient']['address']['addressLine1']) > 50) {
                        $parameters['data']['recipient']['address']['addressLine1'] = substr($parameters['data']['recipient']['address']['addressLine1'], 0, 50);
                    }
                    if (strlen($parameters['data']['recipient']['address']['streetName']) > 50) {
                        $parameters['data']['recipient']['address']['streetName'] = substr($parameters['data']['recipient']['address']['streetName'], 0, 50);
                    }
                    $parameters['data']['recipient']['address']['streetNo'] = (int) strpbrk($orderData->getShippingAddress()->getData('street'), '0123456789');
                    if (empty($parameters['data']['recipient']['address']['streetNo'])) {
                        $parameters['data']['recipient']['address']['streetNo'] = 0;
                    }
                } else {
                    if (isset($data['shipmentValidation']) && !empty($data['shipmentValidation'])) {
                        $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('street'));
                        if (isset($data['shipmentValidation']['streetName']) &&  !empty($data['shipmentValidation']['streetName'])) {
                            $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics($data['shipmentValidation']['streetName']);
                        }
                        if (strlen($parameters['data']['recipient']['address']['streetName']) > 50) {
                            $parameters['data']['recipient']['address']['streetName'] = substr($parameters['data']['recipient']['address']['streetName'], 0, 50);
                        }
                        if (isset($data['shipmentValidation']['streetType']) &&  !empty($data['shipmentValidation']['streetType'])) {
                            $parameters['data']['recipient']['address']['streetType'] = $data['shipmentValidation']['streetType'];
                        }
                        $parameters['data']['recipient']['address']['streetNo'] = 0;
                        if (isset($data['shipmentValidation']['number']) &&  !empty($data['shipmentValidation']['number'])) {
                            $parameters['data']['recipient']['address']['streetNo'] = (int) $data['shipmentValidation']['number'];
                        }
                    } else {
                        if (isset($apiAddress['addressStreetID']) &&  !empty($apiAddress['addressStreetID'])) {
                            $parameters['data']['recipient']['address']['streetId'] = $apiAddress['addressStreetID'];
                        } else {
                            if (isset($apiAddress['addressStreetType']) &&  !empty($apiAddress['addressStreetType'])) {
                                $parameters['data']['recipient']['address']['streetType'] = $apiAddress['addressStreetType'];
                            }
                        }
                        $parameters['data']['recipient']['address']['streetNo'] = 0;
                        if (isset($apiAddress['addressNumber']) &&  !empty($apiAddress['addressNumber'])) {
                            $parameters['data']['recipient']['address']['streetNo'] = (int) $apiAddress['addressNumber'];
                        }
                    }
                    if (isset($apiAddress['addressBlock']) &&  !empty($apiAddress['addressBlock'])) {
                        $parameters['data']['recipient']['address']['blockNo'] = $apiAddress['addressBlock'];
                    }
                    if (isset($apiAddress['addressApartment']) &&  !empty($apiAddress['addressApartment'])) {
                        $parameters['data']['recipient']['address']['apartmentNo'] = $apiAddress['addressApartment'];
                    }
                    if (isset($apiAddress['addressScale']) &&  !empty($apiAddress['addressScale'])) {
                        $parameters['data']['recipient']['address']['entranceNo'] = $apiAddress['addressScale'];
                    }
                    if (isset($apiAddress['addressFloor']) &&  !empty($apiAddress['addressFloor'])) {
                        $parameters['data']['recipient']['address']['floorNo'] = $apiAddress['addressFloor'];
                    }
                }
            }
        } else {
            $countryData = $this->customAjax->GetCountryByID($orderCountryID);
            if ($countryData) {
                $parameters['data']['recipient']['address']['countryId'] = $countryData['id'];
            }
            if ($orderData->getShippingAddress()->getData('city')) {
                $parameters['data']['recipient']['address']['siteName'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('city'));
            }
            $shipping_address = explode(',', $orderData->getShippingAddress()->getData('street'));
            $parameters['data']['recipient']['address']['addressLine1'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('street'));
            $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics($orderData->getShippingAddress()->getData('street'));
            $parameters['data']['recipient']['address']['streetNo'] = (int) strpbrk($orderData->getShippingAddress()->getData('street'), '0123456789');
            if (empty($parameters['data']['recipient']['address']['streetNo'])) {
                $parameters['data']['recipient']['address']['streetNo'] = 0;
            }
            if ($shipping_address &&  count($shipping_address) > 0) {
                foreach ($shipping_address as $address) {
                    if (strpos($address, 'str.') !== false) {
                        $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics(str_replace('str.', '', $address));
                    }
                    $streetsType = $this->customAjax->GetStreetsType($orderCountryID);
                    if (!empty($streetsType)) {
                        foreach ($streetsType as $type) {
                            if (strpos($address, $type) !== false) {
                                $parameters['data']['recipient']['address']['streetName'] = $this->customAjax->ReplaceDiacritics(str_replace($type, '', $address));
                                $parameters['data']['recipient']['address']['streetType'] = $type;
                            }
                        }
                    }
                    if (strpos(strtolower($address), 'nr.') !== false) {
                        $parameters['data']['recipient']['address']['streetNo'] = (int) trim(str_replace('nr.', '', $address));
                    }
                }
            }
            if (strlen($parameters['data']['recipient']['address']['streetName']) > 50) {
                $parameters['data']['recipient']['address']['streetName'] = substr($parameters['data']['recipient']['address']['streetName'], 0, 50);
            }
            if ($orderData->getShippingAddress()->getData('postcode') && !empty($orderData->getShippingAddress()->getData('postcode'))) {
                $parameters['data']['recipient']['address']['postCode'] = trim($orderData->getShippingAddress()->getData('postcode'));
            }
        }
        
        $requestAddShipment = $this->customAjax->ApiRequest($parameters);
        if (!is_array($requestAddShipment) || empty($requestAddShipment) || array_key_exists('error', $requestAddShipment)) {
            $response['error'] = true;
            $response['msg'] = $requestAddShipment['error'];
            $response['dpd_shipment_error'] = $requestAddShipment['error']['message'];
            $response['dpd_shipment_context'] = $requestAddShipment['error']['context'];
        } else {
            $shipmentData = [
                'shipment_swap'        => $data['shipmentSwap'],
                'shipment_rod'         => $data['shipmentRod'],
                'shipment_has_voucher' => $data['shipmentVoucher'],
            ];
            $deadline = (isset($requestAddShipment['deliveryDeadline']) && !empty($requestAddShipment['deliveryDeadline'])) ? $requestAddShipment['deliveryDeadline'] : '0000-00-00 00:00:00';
            $orderShipmentData = [
                'orderID'       => $data['orderID'],
                'shipmentID'    => $requestAddShipment['id'],
                'shipmentData'  => json_encode($shipmentData),
                'parcels'       => json_encode($requestAddShipment['parcels']),
                'price'         => json_encode($requestAddShipment['price']),
                'voucher'       => $data['shipmentVoucherSender'],
                'pickup'        => $requestAddShipment['pickupDate'],
                'deadline'      => $deadline
            ];
            $orderShipmentID = $this->_addShipment($orderShipmentData);
            if ($orderShipmentID && !empty($orderShipmentID)) {
                $response['error'] = false;
            } else {
                $response['error'] = true;
            }
        }
        return $response;
    }
    public function _addShipment($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_shipment');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $orderID       = (string) $data['orderID'];
        $shipmentID    = (string) $data['shipmentID'];
        $shipmentData  = (string) $data['shipmentData'];
        $parcels       = (string) $data['parcels'];
        $price         = (string) $data['price'];
        $voucher       = (string) $data['voucher'];
        $pickup        = (string) $data['pickup'];
        $deadline      = (string) $data['deadline'];
        $query = "
            INSERT INTO 
                `{$tableName}` 
            SET 
                `orderID`        = '{$orderID}', 
                `shipmentID`     = '{$shipmentID}', 
                `shipmentData`   = '{$shipmentData}', 
                `parcels`        = '{$parcels}',
                `price`          = '{$price}',
                `voucher`        = '{$voucher}',
                `pickup`         = '{$pickup}',
                `deadline`       = '{$deadline}',
                `created`        = NOW()
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully add shipment!');
        return $response;
    }

    // Delete shipment by ID
    public function _deleteShipmentById($orderID)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_shipment');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
			WHERE 
                `orderID` = '{$orderID}'
        ";
        $this->customAjax->DB_Query($query);
        $checkQuery = "
			SELECT * 
            FROM 
                `{$tableName}` 
			WHERE 
                `orderID` = '{$orderID}'
		";
        $check = $this->customAjax->DB_Fetch($checkQuery);
        if ($check && !empty($check)) {
            $response['message'] = __('Something went wrong. Try again in a few minutes.');
        } else {
            $response['error'] = false;
            $response['message'] = __('Successfully delete shipment.');
        }
        return $response;
    }
}