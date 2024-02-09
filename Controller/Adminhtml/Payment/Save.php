<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Payment;

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
            $this->_deletePaymentTax();
            foreach ($parameters['parameters'] as $tax) {
                $this->_savePaymentTax($tax);
            }
            $response['error']   = false;
            $response['message'] = __('Successfully payment tax!');
        }
        return $resultJson->setData($response);
    }

    // Add payment tax
    public function _savePaymentTax($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_payment');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $countryID       = (string) $data['country'];
        $tax             = (string) $data['tax'];
        $vat             = (string) $data['vat'];
        $status          = (string) $data['status'];
        $query = "
            INSERT INTO 
                `{$tableName}` 
            SET 
                `countryID`       = '{$countryID}', 
                `tax`             = '{$tax}', 
                `vat`             = '{$vat}', 
                `status`          = '{$status}',
                `created`         = NOW()
        ";
        $response['query']   = $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully payment tax!');
        return $response;
    }

    // Delete payment tax
    public function _deletePaymentTax()
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_payment');
        $response = [
            'error' => true,
            'message' => __('Oops, An error occured, please try again later!'),
        ];
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
        ";
        $this->customAjax->DB_Query($query);
        $response['error']   = false;
        $response['message'] = __('Successfully delete payment tax!');
        return $response;
    }
}
