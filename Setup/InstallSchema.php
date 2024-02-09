<?php
/**
 * @category    DpdRo
 * @package     DpdRo_Shipping
 * @copyright   Copyright (c) DPD Ro (https://www.dpd.com/ro/ro/)
 */
namespace DpdRo\Shipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Create table 'dpdro_settings'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_settings'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('name',                  Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('value',                 Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Settings Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_addresses'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_addresses'))
            ->addColumn('id',                    Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('countryID',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('type',                  Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('typeEn',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('name',                  Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('nameEn',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('municipality',          Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('municipalityEn',        Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('region',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('regionEn',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('postCode',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('latitude',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('longitude',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->setComment("DPD RO Addresses Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_order_tax_rates'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_order_tax_rates'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('serviceID',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('basedOn',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('applyOver',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('taxRate',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('calculationType',       Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('status',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Order Tax Rates Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_order_address'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_order_address'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('orderID',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('address',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressCityID',         Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressCityName',       Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressStreetID',       Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressStreetType',     Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressStreetName',     Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressNumber',         Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressBlock',          Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('addressApartment',      Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('officeID',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('officeName',            Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('method',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('status',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Order Address Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_order_settings'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_order_settings'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('orderID',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('shippingTax',           Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('shippingTaxRate',       Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('courierService',        Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('declaredValue',         Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('includeShipping',       Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Order Tax Rates Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_order_shipment'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_order_shipment'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('orderID',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('shipmentID',            Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('shipmentData',          Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('parcels',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('price',                 Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('voucher',               Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('pickup',                Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('deadline',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Order Shipment Table");
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'dpdro_order_courier'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('dpdro_order_courier'))
            ->addColumn('id',                    Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Comment')
            ->addColumn('ordersIDS',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('requestIDS',            Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('applyOver',             Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('pickupTo',              Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => ''], 'Comment')
            ->addColumn('created',               Table::TYPE_DATETIME, null, ['nullable' => false], 'Comment')
            ->setComment("DPD RO Order Courier Table");
        $setup->getConnection()->createTable($table);
        
    }
}
