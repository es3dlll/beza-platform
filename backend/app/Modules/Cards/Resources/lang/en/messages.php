<?php

return [
    'name' => 'Card Management',
    'description' => 'Virtual/prepaid/debit cards, spending controls, transaction authorization',
    'card_types' => [
        'virtual' => 'Virtual Card',
        'prepaid' => 'Prepaid Card',
        'debit' => 'Debit Card',
    ],
    'card_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'blocked' => 'Blocked',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],
    'txn_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'declined' => 'Declined',
        'refunded' => 'Refunded',
        'settled' => 'Settled',
    ],
    'errors' => [
        'not_found' => 'Card not found: :id',
        'suspended' => 'Card is suspended or blocked',
        'expired' => 'Card has expired',
        'limit_exceeded' => 'Card limit exceeded: :type',
        'merchant_blocked' => 'Merchant category blocked',
    ],
];
