<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Regions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    // Parameters
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $resultJsonFactory;

    // Constructor
    public function __construct(Context $context, PageFactory $pageFactory, Registry $coreRegistry, JsonFactory $resultJsonFactory)
    {
        $this->resultPageFactory = $pageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    // Render
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
