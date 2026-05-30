<?php

declare(strict_types=1);

return [
    'name' => 'QR التجار',
    'description' => 'تسجيل التجار ومدفوعات QR والتسوية',
    'status' => [
        'pending' => 'بانتظار الموافقة',
        'active' => 'نشط',
        'suspended' => 'موقوف',
        'terminated' => 'منتهي',
    ],
    'payment_status' => [
        'pending' => 'قيد الانتظار',
        'paid' => 'مدفوع',
        'refunded' => 'مسترجع',
        'failed' => 'فشل',
    ],
    'errors' => [
        'not_found' => 'التاجر غير موجود: :id',
        'suspended' => 'حساب التاجر موقوف: :id',
        'qr_expired' => 'انتهت صلاحية رمز QR الديناميكي',
        'payment_above_max' => 'مبلغ الدفع :amount يتجاوز الحد الأقصى :max',
        'refund_expired' => 'انتهت فترة الاسترجاع (7 أيام)',
    ],
];
