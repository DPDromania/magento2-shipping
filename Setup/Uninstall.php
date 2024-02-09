<?php
/**
 * @category    DpdRo
 * @package     DpdRo_Shipping
 * @copyright   Copyright (c) DPD Ro (https://www.dpd.com/ro/ro/)
 */
namespace DpdRo\Shipping\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Drop tables
         */
        $setup->getConnection()->dropTable($setup->getTable('dpdro_settings'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_addresses'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_order_tax_rates'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_order_address'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_order_settings'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_order_shipment'));
        $setup->getConnection()->dropTable($setup->getTable('dpdro_order_courier'));
    }
}
