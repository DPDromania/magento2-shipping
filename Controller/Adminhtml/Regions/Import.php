<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Regions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;

class Import extends Action
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
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'import') {
                if (isset($parameters['parameters']['country']) && !empty($parameters['parameters']['country'])) {
                    $regions = $this->customAjax->DPD_GetAddressesData();
                    if ($regions && !empty($regions)) {
                        if (isset($regions[$parameters['parameters']['country']]) && !empty($regions[$parameters['parameters']['country']])) {
                            $country = 'RO';
                            if ($parameters['parameters']['country'] == '100') {
                                $country = 'BG';
                            }
                            $regionsData = array();
                            $regionsList = $regions[$parameters['parameters']['country']];
                            foreach ($regionsList as $key => $region) {
                                $regionData = "'" . $country . "','" . $key . "','" . $key . "'";
                                array_push($regionsData, '(' . $regionData . ')');
                            }
                            if (!empty($regionsData)) {
                                $this->_addRegions($regionsData);
                            }
                            $response['error'] = false;
                            $response['message'] = __('Successfully import regions!');
                        }
                    }
                }
            }
        }
        return $resultJson->setData($response);
    }

    // Add Regions
    public function _addRegions($regions)
    {
        $tableName = $this->customAjax->DB_GetTable('directory_country_region');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $regions = implode(',' . "\n", $regions);
        $query = "
            INSERT INTO 
                `{$tableName}` 
                (
                    country_id,
                    code,
                    default_name
                ) 
            VALUES 
                {$regions}
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully add new regions!');
        return $response;
    }
}
