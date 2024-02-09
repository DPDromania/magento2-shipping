<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Regions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;

class Save extends Action
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
            if (isset($parameters['action']) && !empty($parameters['action']) && $parameters['action'] == 'save') {
                if (isset($parameters['parameters']['region']) && !empty($parameters['parameters']['region']) && $parameters['parameters']['region'] != 'false') {
                    $response = $this->_updateRegionById($parameters['parameters']);
                } else {
                    $response = $this->_addRegion($parameters['parameters']);
                }
            } else {
                if (isset($parameters['parameters']['region']) && !empty($parameters['parameters']['region']) && $parameters['parameters']['region'] != 'false') {
                    $response = $this->_deleteRegionById($parameters['parameters']['region']);
                }
            }
        }
        return $resultJson->setData($response);
    }

    // Add Region
    public function _addRegion($data)
    {
        $tableName = $this->customAjax->DB_GetTable('directory_country_region');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $country      = (string) $data['country'];
        $code         = (string) $data['code'];
        $name         = (string) $data['name'];
        $query = "
            INSERT INTO 
                `{$tableName}` 
            SET 
                `country_id`      = '{$country}', 
                `code`            = '{$code}', 
                `default_name`    = '{$name}'
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully add new region!');
        return $response;
    }

    // Update Region by ID
    public function _updateRegionById($data)
    {
        $tableName = $this->customAjax->DB_GetTable('directory_country_region');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $region     = (int) $data['region'];
        $country    = (string) $data['country'];
        $code       = (string) $data['code'];
        $name       = (string) $data['name'];
        $query = "
            UPDATE 
                `{$tableName}` 
            SET 
                `country_id`      = '{$country}', 
                `code`            = '{$code}', 
                `default_name`    = '{$name}'
            WHERE 
                `region_id` = {$region}
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error'] = false;
        $response['message'] = __('Successfully updated!');
        return $response;
    }

    // Delete Region by ID
    public function _deleteRegionById($region)
    {
        $tableName = $this->customAjax->DB_GetTable('directory_country_region');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
			WHERE 
				`region_id` = {$region}
        ";
        $this->customAjax->DB_Query($query);
        $checkQuery = "
			SELECT * 
            FROM 
                `{$tableName}` 
			WHERE 
				`region_id` = {$region}
		";
        $check = $this->customAjax->DB_Fetch($checkQuery);
        if ($check && !empty($check)) {
            $response['message'] = __('Something went wrong. Try again in a few minutes.');
        } else {
            $response['error'] = false;
            $response['message'] = __('Successfully delete region.');
        }
        return $response;
    }
}
