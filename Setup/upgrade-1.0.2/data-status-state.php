<?php
$statusStateTable = $installer->getTable('sales_order_status_state');

$installer->getConnection()->update(
    $statusStateTable,
    [
        'is_default'        => 0,
        'visible_on_front'  => 1,
    ],
    ['status in (?)' =>  ["payer_pending", "payer_acknowledged", "payer_on_hold"]]
);
