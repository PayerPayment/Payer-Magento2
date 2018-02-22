<?php
$statusTable      = $installer->getTable('sales_order_status');
$statusStateTable = $installer->getTable('sales_order_status_state');

$installer->getConnection()->insertArray(
    $statusTable,
    [
        'status',
        'label',
    ],
    [
        [
            'status'    => 'payer_on_hold',
            'label'     => 'Payer on hold'
        ],
    ]
);

$installer->getConnection()->insertArray(
    $statusStateTable,
    [
        'status',
        'state',
        'is_default',
    ],
    [
        [
            'status'     => 'payer_on_hold',
            'state'      => 'new',
            'is_default' => 0,
        ],
    ]
);
