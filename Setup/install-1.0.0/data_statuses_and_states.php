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
            'status'    => 'payer_pending',
            'label'     => 'Payer new'
        ],
        [
            'status'    => 'payer_acknowledged',
            'label'     => 'Payer pending'
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
            'status'     => 'payer_pending',
            'state'      => 'new',
            'is_default' => 1,
        ],
        [
            'status'     => 'payer_acknowledged',
            'state'      => 'new',
            'is_default' => 0,
        ],
    ]
);
