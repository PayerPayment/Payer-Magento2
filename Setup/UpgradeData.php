<?php
namespace Payer\Checkout\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;


/**
 * Class UpgradeData.
 *
 * @package Payer\Checkout\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    protected $installer;

    /**
     * Upgrades DB schema for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $installer       = $setup;
        $installer->startSetup();
        $this->installer = $installer;

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            include('upgrade-1.0.1/data-status-state.php');
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            include('upgrade-1.0.2/data-status-state.php');
        }

        $installer->endSetup();
    }
}
