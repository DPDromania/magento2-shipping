<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;

class Validation extends Action
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
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'search') {
                $response = $this->_search($parameters['parameters']);
            } else if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'skip') {
                if (isset($parameters['parameters']['orderID']) && !empty($parameters['parameters']['orderID']) && $parameters['parameters']['orderID'] != '') {
                    $response = $this->_skip($parameters['parameters']['orderID']);
                }
            } else if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'normalize') {
                if (isset($parameters['parameters']['orderID']) && !empty($parameters['parameters']['orderID']) && $parameters['parameters']['orderID'] != '') {
                    $response = $this->_normalize($parameters['parameters']['orderID']);
                }
            } else if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'validated') {
                $response = $this->_validate($parameters['parameters']);
            }
        }
        return $resultJson->setData($response);
    }

    // Validate
    public function _validate($data)
    {
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        if ($data['streetID'] == '0' || $data['streetID'] == '') {
            $response['message'] = __('Address is not valid.');
        } else {
            $parameters = [
                'api' => 'validation/address',
                'method' => 'POST',
                'data' => [
                    'address' => [
                        'countryId' => $data['country'],
                        'siteName'  => $this->customAjax->ReplaceDiacritics($data['city']),
                        'streetId'  => $data['streetID'],
                        'streetNo'  => 0,
                    ]
                ]
            ];
            if (isset($data['postcode']) &&  !empty($data['postcode'])) {
                $parameters['data']['address']['postCode'] = $data['postcode'];
            }
            if (isset($data['number']) &&  !empty($data['number'])) {
                $parameters['data']['address']['streetNo'] = (int) $data['number'];
            }
            $request = $this->customAjax->ApiRequest($parameters);
            if (isset($request) && $request != '') {
                if (isset($request['valid']) && $request['valid']) {
                    if ($request['valid']) {
                        $response = $this->_update($data);
                    } else {
                        $response['message'] = __('Address is not valid.');
                    }
                } else {
                    $response['message'] = $request['error']['message'];
                }
            }
        }
        return $response;
    }

    // Update
    public function _update($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_address');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $orderID = (int) $data['orderID'];
        $checkAddress = $this->customAjax->DPD_GetAddressNormalizedByOrderID($orderID);
        if ($checkAddress && !empty($checkAddress)) {
            $address           = $checkAddress['addressStreetName'];
            $addressStreetID   = $data['streetID'];
            $addressStreetType = $data['streetType'];
            $addressStreetName = $data['streetName'];
            $addressNumber     = $data['number'];
            $addressBlock      = $data['block'];
            $addressApartment  = $data['apartment'];
            $addressScale      = $data['scale'];
            $addressFloor      = $data['floor'];
            $addressFull       = array();
            if ($addressStreetName && !empty($addressStreetName)) {
                array_push($addressFull, $addressStreetName);
            }
            if ($addressNumber && !empty($addressNumber)) {
                array_push($addressFull, $addressNumber);
                if ($addressBlock && !empty($addressBlock)) {
                    array_push($addressFull, $addressBlock);
                    if ($addressApartment && !empty($addressApartment)) {
                        array_push($addressFull, $addressApartment);
                    }
                }
            }
            if ($addressFull && !empty($addressFull)) {
                $address = (string) implode(', ', $addressFull);
            }
            $query = "
                UPDATE 
                    `{$tableName}` 
				SET 
					`address`           = '{$address}', 
					`addressStreetID`   = '{$addressStreetID}', 
					`addressStreetType` = '{$addressStreetType}', 
					`addressStreetName` = '{$addressStreetName}', 
					`addressNumber`     = '{$addressNumber}', 
					`addressBlock`      = '{$addressBlock}',
					`addressApartment`  = '{$addressApartment}',
					`addressScale`      = '{$addressScale}',
					`addressFloor`      = '{$addressFloor}',
					`status`            = 'validated'
				WHERE 
					`orderID` = '{$orderID}'
			";
            $this->customAjax->DB_Query($query);
            $response['error'] = false;
            $response['message'] = sprintf('Successfully! Address <b>%s</b> has been validated.', $address);
        }
        return $response;
    }

    // Search
    public function _search($data)
    {
        $parameters = [
            'api'    => 'location/street',
            'method' => 'POST',
            'data'   => [
                'countryId' => $data['countryID'],
                'siteId'    => $data['cityID'],
                'name'      => $this->customAjax->ReplaceDiacritics($data['search']),
            ]
        ];
        $response = array(
            array(
                'id'       => 1,
                'text'     => $this->customAjax->ReplaceDiacritics($data['search']),
                'actualId' => 0,
                'siteId'   => 1,
                'type'     => '',
                'typeEn'   => '',
                'name'     => $this->customAjax->ReplaceDiacritics($data['search']),
                'nameEn'   => $this->customAjax->ReplaceDiacritics($data['search'])
            )
        );
        $request = $this->customAjax->ApiRequest($parameters);
        if (isset($request) && $request != '') {
            if (isset($request['streets']) && !empty($request['streets'])) {
                foreach ($request['streets'] as $key => $street) {
                    $request['streets'][$key]['text'] = $street['type'] . ' ' . $street['name'];
                }
                $response = $request['streets'];
            }
        }
        return $response;
    }

    // Skip
    public function _skip($orderID)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_address');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            UPDATE 
                `{$tableName}`
            SET 
                `status` = 'skip'
			WHERE 
                `orderID` = '{$orderID}'
        ";
        $this->customAjax->DB_Query($query);
        $response['error'] = false;
        $response['message'] = __('Successfully change address status.');
        return $response;
    }

    // Normalize
    public function _normalize($orderID)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_address');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            UPDATE 
                `{$tableName}`
            SET 
                `status` = 'normalize'
			WHERE 
                `orderID` = '{$orderID}'
        ";
        $this->customAjax->DB_Query($query);
        $response['error'] = false;
        $response['message'] = __('Successfully change address status.');
        return $response;
    }
}
