<?php

namespace DpdRo\Shipping\Controller\Adminhtml\Addresses;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Module\Dir;

class Imported extends Action
{
    // Parameters
    protected $customAjax;
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $fileUploader;
    protected $messageManager;
    protected $filesystem;
    protected $directory;

    // Constructor
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Registry $coreRegistry,
        JsonFactory $resultJsonFactory,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
        UploaderFactory $fileUploader,
        RedirectFactory $resultRedirectFactory,
        Dir $directory
    ) {
        $obj = ObjectManager::getInstance();
        $this->customAjax            = $obj->create('\DpdRo\Shipping\Model\Ajax');
        $this->resultPageFactory     = $pageFactory;
        $this->coreRegistry          = $coreRegistry;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->messageManager        = $messageManager;
        $this->filesystem            = $filesystem;
        $this->fileUploader          = $fileUploader;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->mediaDirectory        = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->directory             = $directory;
        parent::__construct($context);
    }

    // Response
    public function execute()
    {
        $this->_importFile();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('dpd/addresses/index');
        return $resultRedirect;
    }

    // Prepare file
    public function _prepareAddresses($data)
    {
        $addressess = array();
        if ($data && !empty($data)) {
            foreach ($data as $address) {
                // if (
                //     array_key_exists('id', $address) &&
                //     array_key_exists('countryId', $address) &&
                //     array_key_exists('type', $address) &&
                //     array_key_exists('typeEn', $address) &&
                //     array_key_exists('name', $address) &&
                //     array_key_exists('nameEn', $address) &&
                //     array_key_exists('municipality', $address) &&
                //     array_key_exists('municipalityEn', $address) &&
                //     array_key_exists('region', $address) &&
                //     array_key_exists('regionEn', $address) &&
                //     array_key_exists('postCode', $address) &&
                //     array_key_exists('x', $address) &&
                //     array_key_exists('y', $address)
                // ) {
                $fileAddressValues = array(
                    $address['id'],
                    $address['countryId'],
                    $address['type'],
                    $address['typeEn'],
                    $address['name'],
                    $address['nameEn'],
                    $address['municipality'],
                    $address['municipalityEn'],
                    $address['region'],
                    $address['regionEn'],
                    $address['postCode'],
                    $address['x'],
                    $address['y'],
                );
                $addressValues = array();
                foreach ($fileAddressValues as $fileAddressValue) {
                    array_push($addressValues, "'" . $fileAddressValue . "'");
                }
                $addressData = implode(',', $addressValues);
                if (!empty($addressData)) {
                    array_push($addressess, '(' . $addressData . ')');
                }
                // }
            }
        }
        return $addressess;
    }

    // Insert in db
    public function _insertInDB($data)
    {
        $addresses = $this->_prepareAddresses($data);
        if ($addresses && !empty($addresses)) {
            $this->_addAddress($addresses);
        }
    }

    // Import file
    public function _importFile()
    {
        $modulePath = $this->directory->getDir('DpdRo_Shipping', Dir::MODULE_ETC_DIR);
        $baseFiles = scandir($modulePath . '/import');
        if ($baseFiles && !empty($baseFiles)) {
            foreach ($baseFiles as $file) {
                if ($file != '.' && $file != '..') {
                    $fileInfo = pathinfo($file);
                    $fileName = $fileInfo['filename'];
                    $filePath = $modulePath . '/import/' . $file;
                    $this->_deleteAddresses($fileName);
                    if (($fileHandle = fopen($filePath, "r")) !== FALSE) {
                        $collectorDate = [];
                        $maxLines = 100;
                        $counter = 1;
                        $fileHeader = fgetcsv($fileHandle);
                        while (($fileRow = fgetcsv($fileHandle)) !== FALSE) {
                            if ($counter % $maxLines == 0) {
                                $this->_insertInDB($collectorDate);
                                $collectorDate = [];
                            }
                            $collectorDate[] = array_combine($fileHeader, $fileRow);
                            $counter++;
                        }
                        $this->_insertInDB($collectorDate);
                        fclose($fileHandle);
                    }
                }
            }
        }
    }

    // Add addresses
    public function _addAddress($data)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_addresses');
        $addresses = implode(',' . "\n", $data);
        $query = "
            INSERT INTO 
                `{$tableName}` 
                (
                    id,
                    countryID,
                    type,
                    typeEn,
                    name,
                    nameEn,
                    municipality,
                    municipalityEn,
                    region,
                    regionEn,
                    postCode,
                    latitude,
                    longitude
                ) 
            VALUES 
                {$addresses}
        ";
        $this->customAjax->DB_Query($query);
    }

    // Delete addresses
    public function _deleteAddresses($countryID)
    {
        $tableName = $this->customAjax->DB_GetTable('dpdro_addresses');
        $query = "
            DELETE 
            FROM 
                `{$tableName}`
            WHERE
                `countryID` = {$countryID}
        ";
        $response = $this->customAjax->DB_Query($query);
        return $response;
    }
}
