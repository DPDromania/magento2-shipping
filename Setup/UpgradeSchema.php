<?php
/**
 * @category    DpdRo
 * @package     DpdRo_Shipping
 * @copyright   Copyright (c) DPD Ro (https://www.dpd.com/ro/ro/)
 */
namespace DpdRo\Shipping\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $connection = $setup->getConnection();
            $connection->addColumn(
                $setup->getTable('dpdro_order_address'),
                'addressScale',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'Comment'
                ]
            );
            $connection->addColumn(
                $setup->getTable('dpdro_order_address'),
                'addressFloor',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'Comment'
                ]
            );
        }
    }
}
