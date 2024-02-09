<?php

namespace DpdRo\Shipping\Controller\Api;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Session\SessionManagerInterface;

class Session extends Action
{
    // Parameters
    protected $customAjax;
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $coreSession;

    // Constructor
    public function __construct(Context $context, PageFactory $pageFactory, JsonFactory $resultJsonFactory, SessionManagerInterface $coreSession)
    {
        $obj = ObjectManager::getInstance();
        $this->customAjax = $obj->create('\DpdRo\Admin\Model\Ajax');
        $this->resultPageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->coreSession = $coreSession;
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
        if (isset($parameters['action']) && $parameters['action'] == 'set' && isset($parameters['parameters']) && !empty($parameters['parameters'])) {
            $this->setSession($parameters['parameters'], $parameters['type']);
            $response = [
                'error'   => false,
                'message' => __('Success set session!'),
                'response' => $this->getSession($parameters['type'])
            ];
        } else if (isset($parameters['action']) && $parameters['action'] == 'unset') {
            $this->unsetSession($parameters['type']);
            $response = [
                'error'   => false,
                'message' => __('Success unset session!')
            ];
        } else if (isset($parameters['action']) && $parameters['action'] == 'complete') {
            $this->orderComplete();
        }
        return $resultJson->setData($response);
    }

    // Set session
    public function setSession($data, $type = false)
    {
        if ($type) {
            $this->coreSession->start();
            if ($type == 'address') {
                $this->coreSession->setDpdRoAddress($data);
            } else if ($type == 'confirmation') {
                $this->coreSession->setDpdRoConfirmation($data);
            } else if ($type == 'tax') {
                $this->coreSession->setDpdRoTax($data);
            }
        }
    }

    // Get session
    public function getSession($type)
    {
        if ($type) {
            $this->coreSession->start();
            if ($type == 'address') {
                return $this->coreSession->getDpdRoAddress();
            } else if ($type == 'confirmation') {
                return $this->coreSession->getDpdRoConfirmation();
            } else if ($type == 'tax') {
                return $this->coreSession->getDpdRoTax();
            }
        }
        return false;
    }

    // Unset session
    public function unsetSession($type)
    {
        if ($type) {
            $this->coreSession->start();
            if ($type == 'address') {
                return $this->coreSession->unsDpdRoAddress();
            } else if ($type == 'confirmation') {
                return $this->coreSession->unsDpdRoConfirmation();
            } else if ($type == 'tax') {
                return $this->coreSession->unsDpdRoTax();
            }
        }
        return;
    }

}
