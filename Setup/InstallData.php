<?php

namespace Payer\Checkout\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class InstallData.
 *
 * @package Payer\Checkout\Setup
 */
class InstallData implements
    InstallDataInterface
{
    protected $installer;

    /**
     * install.
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $installer = $setup;
        $installer->startSetup();
        $this->installer = $installer;

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            include('install-1.0.0/data_statuses_and_states.php');
        }

        $installer->endSetup();
    }
}
