<?php

namespace DpdRo\Shipping\Controller\Adminhtml\TaxRates;

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
                if (isset($parameters['parameters']['taxID']) && !empty($parameters['parameters']['taxID']) && $parameters['parameters']['taxID'] != 'false') {
                    $response = $this->_updateTaxRateById($parameters['parameters']);
                } else {
                    $response = $this->_addTaxRate($parameters['parameters']);
                }
            } else {
                if (isset($parameters['parameters']['taxID']) && !empty($parameters['parameters']['taxID']) && $parameters['parameters']['taxID'] != 'false') {
                    $response = $this->_deleteTaxRateById($parameters['parameters']['taxID']);
                }
            }
        }
        return $resultJson->setData($response);
    }

    // Add tax rate
    public function _addTaxRate($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_tax_rates');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $serviceID       = (string) $data['serviceID'];
        $applyOver       = (string) $data['applyOver'];
        $basedOn         = (string) $data['basedOn'];
        $taxRate         = (string) $data['taxRate'];
        $calculationType = (string) $data['calculationType'];
        $status          = (string) $data['status'];
        $checkQuery = "
			SELECT * 
            FROM 
                `{$tableName}` 
			WHERE 
			(
				`serviceID` = {$serviceID} AND 
				(
					`applyOver` = {$applyOver} OR 
					`basedOn` != {$basedOn} 
				) AND
				`status` = 1
			)
		";
        $check = $this->customAjax->DB_Fetch($checkQuery);
        if ($check && !empty($check)) {
            $response['message'] = __('There is already a tax rate with this conditions.');
        } else {
            $query = "
                INSERT INTO 
                    `{$tableName}` 
				SET 
					`serviceID`       = {$serviceID}, 
					`applyOver`       = {$applyOver}, 
					`basedOn`         = {$basedOn}, 
					`taxRate`         = {$taxRate}, 
					`calculationType` = {$calculationType}, 
					`status`          = {$status},
					`created`         = NOW()
            ";
            $response['query']   = $this->customAjax->DB_Query($query);
            $response['error']   = false;
            $response['message'] = __('Successfully add new tax rate!');
        }
        return $response;
    }

    // Update tax rate by ID
    public function _updateTaxRateById($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_tax_rates');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $taxID           = (string) $data['taxID'];
        $serviceID       = (string) $data['serviceID'];
        $applyOver       = (string) $data['applyOver'];
        $basedOn         = (string) $data['basedOn'];
        $taxRate         = (string) $data['taxRate'];
        $calculationType = (string) $data['calculationType'];
        $status          = (string) $data['status'];
        $checkQuery = "
			SELECT * 
            FROM 
                `{$tableName}` 
			WHERE 
			(
				`id` != {$taxID} AND
				`serviceID` = {$serviceID} AND 
				(
					`applyOver` = {$applyOver} OR 
					`basedOn` != {$basedOn} 
				) AND
				`status` = 1
			)
		";
        $check = $this->customAjax->DB_Fetch($checkQuery);
        if ($check && !empty($check)) {
            $response['message'] = __('There is already a tax rate with this conditions.');
        } else {
            $query = "
                UPDATE 
                    `{$tableName}` 
				SET 
					`serviceID`       = {$serviceID}, 
					`applyOver`       = {$applyOver}, 
					`basedOn`         = {$basedOn}, 
					`taxRate`         = {$taxRate}, 
					`calculationType` = {$calculationType}, 
					`status`          = {$status},
					`created`         = NOW()
				WHERE 
					`id` = {$taxID}
            ";
            $response['query']   = $this->customAjax->DB_Query($query);
            $response['error'] = false;
            $response['message'] = __('Successfully updated!');
        }
        return $response;
    }

    // Delete tax rate by ID
    public function _deleteTaxRateById($taxID)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_order_tax_rates');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
			WHERE 
				`id` = {$taxID}
        ";
        $this->customAjax->DB_Query($query);
        $checkQuery = "
			SELECT * 
            FROM 
                `{$tableName}` 
			WHERE 
				`id` = {$taxID}
		";
        $check = $this->customAjax->DB_Fetch($checkQuery);
        if ($check && !empty($check)) {
            $response['message'] = __('Something went wrong. Try again in a few minutes.');
        } else {
            $response['error'] = false;
            $response['message'] = __('Successfully delete tax rate.');
        }
        return $response;
    }
}
