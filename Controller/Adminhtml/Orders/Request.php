<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;

class Request extends Action
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
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'courier') {
                $response = $this->_requestCourierAction($parameters['parameters']);
            } elseif (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'pickup') {
                if (isset($parameters['parameters']['orderID']) && !empty($parameters['parameters']['orderID']) && $parameters['parameters']['orderID'] != '') {
                    $response = $this->_orderCompleteById($parameters['parameters']['orderID']);
                }
            }
        }
        return $resultJson->setData($response);
    }

    // Request courier
    public function _requestCourierAction($data)
    {
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $parameters = [
            'api' => 'pickup',
            'method' => 'POST',
            'data' => [
                'explicitShipmentIdList' => json_decode($data['ordersIDS']),
                'visitEndTime'           => '19:00',
                'autoAdjustPickupDate'   => true
            ]
        ];
        $requestCourier = $this->customAjax->ApiRequest($parameters);
        if (!is_array($requestCourier) || empty($requestCourier) || array_key_exists('error', $requestCourier)) {
            $response['message'] = $requestCourier['error']['message'];
        } else {
            $data = [
                'ordersIDS'   => json_encode($data['ordersIDS']),
                'requestIDS'  => $requestCourier['orders'][0]['id'],
                'applyOver'   => $requestCourier['orders'][0]['pickupPeriodFrom'],
                'pickupTo'    => $requestCourier['orders'][0]['pickupPeriodTo']
            ];
            $response = $this->_createCourier($data);
        }
        return $response;
    }
    public function _createCourier($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_courier');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $ordersIDS    = (string) $data['ordersIDS'];
        $requestIDS   = (string) $data['requestIDS'];
        $applyOver    = (string) $data['applyOver'];
        $pickupTo     = (string) $data['pickupTo'];
        $query = "
            INSERT INTO 
                `{$tableName}` 
            SET 
                `ordersIDS`      = '{$ordersIDS}', 
                `requestIDS`     = '{$requestIDS}', 
                `applyOver`      = '{$applyOver}', 
                `pickupTo`       = '{$pickupTo}',
                `created`        = NOW()
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully requested courier!');
        return $response;
    }

    // Complete order by id
    public function _orderCompleteById($orderID)
    {
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $obj = ObjectManager::getInstance();
        $order = $obj->create('\Magento\Sales\Model\Order')->load($orderID);
        $orderState = Order::STATE_COMPLETE;
        $order->setState($orderState)->setStatus(Order::STATE_COMPLETE);
        $order->save();
        $response['error'] = false;
        $response['message'] = __('Successfully complete order.');
        return $response;
    }
}
