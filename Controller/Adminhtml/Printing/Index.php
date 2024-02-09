<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Printing;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;

class Index extends Action
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

    // Render
    public function execute()
    {
        $apiSettings = $this->customAjax->Settings();
        $parameters = $this->getRequest()->getParams();
        $response = '';
        if (isset($parameters['print'])) {
            if ($parameters['print'] === 'labels') {
                if (isset($parameters['orderID']) && !empty($parameters['orderID'])) {
                    $response = $this->_printLabels($parameters['orderID']);
                }
            } else if ($parameters['print'] === 'voucher') {
                if (isset($parameters['orderID']) && !empty($parameters['orderID'])) {
                    $response = $this->_printVoucher($parameters['orderID']);
                }
            }
        }
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="shipment_' . $parameters['print'] . '_' . $parameters['orderID'] . '.' . $apiSettings['printFormat'] . '"');
        echo $response;
        die();
    }

    // Print labels
	public function _printLabels($orderID)
	{
		$apiSettings = $this->customAjax->Settings();
		$parameters = [
			'api' => 'print',
			'method' => 'POST',
			'data' => [
				'format'    => $apiSettings['printFormat'],
				'paperSize' => $apiSettings['printPaperSize'],
				'parcels'   => array()
			]
		];
		$shipmentData = $this->customAjax->DPD_GetShipmentByOrderID($orderID);
		$shipmentDataParcels = json_decode($shipmentData['parcels']);
		if ($shipmentDataParcels) {
			foreach ($shipmentDataParcels as $parcel) {
				$parcel_data = [
					'parcel' => [
						'id' => $parcel->id
					]
				];
				array_push($parameters['data']['parcels'], $parcel_data);
			}
		}
		$response = $this->customAjax->ApiRequest($parameters, false);
		return $response;
	}

	// Print voucher
	public function _printVoucher($orderID)
	{
		$shipmentData = $this->customAjax->DPD_GetShipmentByOrderID($orderID);
		$shipmentDataParcels = json_decode($shipmentData['parcels']);
		$parameters = [
			'api'    => 'print/voucher',
			'method' => 'POST',
			'data'   => [
				'shipmentIds' => [$shipmentDataParcels[0]->id],
			]
		];
		return $this->customAjax->ApiRequest($parameters, false);
	}
}
