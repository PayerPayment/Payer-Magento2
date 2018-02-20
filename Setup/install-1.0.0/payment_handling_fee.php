<?php
use Magento\Framework\DB\Ddl\Table;

$columnNames = [
    'handling_fee_amount'           => 'Handling fee amount',
    'base_handling_fee_amount'      => 'Base handling fee',
    'handling_fee_tax_amount'       => 'Handling fee tax',
    'base_handling_fee_tax_amount'  => 'Base handling fee tax',
    'handling_fee_incl_tax'         => 'Handling fee incl tax',
    'base_handling_fee_incl_tax'    => 'Base handling fee',
];

$tables = [
    'sales_invoice',
    'sales_creditmemo',
    'sales_order',
    'quote',
    'quote_address'
];

foreach ($tables as $table) {
    foreach ($columnNames as $columnName => $comment) {
        $installer->getConnection()
            ->addColumn(
                $installer->getTable($table),
                $columnName,
                [
                    'type'      => Table::TYPE_DECIMAL,
                    'length'    => '12,4',
                    'unsigned'  => true,
                    'nullable'  => true,
                    'default'   => '0.0000',
                    'primary'   => false,
                    'comment'   => $comment,
                ]
            );
    }
}
