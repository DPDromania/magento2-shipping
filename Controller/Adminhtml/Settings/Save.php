<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Settings;

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
            $this->_deleteSettings();
            foreach ($parameters['parameters'] as $name => $value) {
                $this->_addSettingsByName($name, $value);
            }
            $response['error'] = false;
            $response['message'] = __('Successfully saved settings!');
        }
        return $resultJson->setData($response);
    }

    // Add Settings by Name
    public function _addSettingsByName($name, $value)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_settings');
        $query = "
            INSERT INTO 
                `{$tableName}` 
			SET 
				`name`    = '{$name}', 
				`value`   = '{$value}', 
				`created` = NOW()
        ";
        $response = $this->customAjax->DB_Query($query);
        return $response;
    }

    // Update Settings by Name
    public function _updateSettingsByName($name, $value)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_settings');
        $query = "
            UPDATE 
                `{$tableName}` 
			SET 
				`name` = '{$name}', 
				`value` = '{$value}' 
			WHERE 
				`name` = '{$value}'
        ";
        $response = $this->customAjax->DB_Query($query);
        return $response;
    }

    // Delete Settings
    public function _deleteSettings()
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_settings');
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
        ";
        $response = $this->customAjax->DB_Query($query);
        return $response;
    }
}
