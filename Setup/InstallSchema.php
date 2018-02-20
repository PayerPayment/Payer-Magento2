<?php
/**
 * Schema install
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 *
 * @package Payer\Checkout\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    protected $installer;

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            include('install-1.0.0/payment_handling_fee.php');
        }

        $installer->endSetup();
    }
}
