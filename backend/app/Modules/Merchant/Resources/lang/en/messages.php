<?php

return [
    'name' => 'Merchant QR',
    'description' => 'Merchant registration, QR payments, and settlement',
    'status' => [
        'pending' => 'Pending Approval',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'terminated' => 'Terminated',
    ],
    'payment_status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
    ],
    'errors' => [
        'not_found' => 'Merchant not found: :id',
        'suspended' => 'Merchant account is suspended: :id',
        'qr_expired' => 'Dynamic QR code has expired',
        'payment_above_max' => 'Payment amount :amount exceeds maximum :max',
        'refund_expired' => 'Refund window has expired (7 days)',
    ],
];
