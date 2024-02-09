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

class Import extends Action
{
    // Parameters
    protected $customAjax;
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $fileUploader;
    protected $messageManager;
    protected $filesystem;

    // Constructor
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Registry $coreRegistry,
        JsonFactory $resultJsonFactory,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
        UploaderFactory $fileUploader,
        RedirectFactory $resultRedirectFactory
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
        parent::__construct($context);
    }

    // Response
    public function execute()
    {
        $uploadFile = $this->_uploadFile();
        $parameters = $this->getRequest()->getParams();
        $data = [
            'file' => $uploadFile,
            'country' => $parameters['country'],
        ];
        $this->_importFile($data);
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('dpd/addresses/index');
        return $resultRedirect;
    }

    // Upload file
    public function _uploadFile()
    {
        $directory = 'dpdro_import/';
        $parameters = 'file';
        try {
            $file = $this->getRequest()->getFiles($parameters);
            $fileName = ($file && array_key_exists('name', $file)) ? $file['name'] : null;

            if ($file && $fileName) {
                $target = $this->mediaDirectory->getAbsolutePath($directory);
                $uploader = $this->fileUploader->create(['fileId' => $parameters]);
                $uploader->setAllowedExtensions(['csv']);
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(true);
                $result = $uploader->save($target);
                if ($result['file']) {
                    $this->messageManager->addSuccess(__('File has been successfully uploaded and imported.'));
                }
                return $target . $uploader->getUploadedFileName();
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return false;
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
    public function _importFile($parameters)
    {
        $countryID = $parameters['country'];
        $this->_deleteAddresses($countryID);
        $filePath = $parameters['file'];
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
