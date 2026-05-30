<?php

declare(strict_types=1);

return [
    'name' => 'إدارة البطاقات',
    'description' => 'البطاقات الافتراضية/المدفوعة مسبقاً/المدينة، ضوابط الإنفاق، تفويض المعاملات',
    'card_types' => [
        'virtual' => 'بطاقة افتراضية',
        'prepaid' => 'بطاقة مدفوعة مسبقاً',
        'debit' => 'بطاقة مدينة',
    ],
    'card_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشطة',
        'suspended' => 'موقوفة',
        'blocked' => 'محظورة',
        'cancelled' => 'ملغاة',
        'expired' => 'منتهية الصلاحية',
    ],
    'txn_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'declined' => 'مرفوضة',
        'refunded' => 'مسترجعة',
        'settled' => 'تمت التسوية',
    ],
    'errors' => [
        'not_found' => 'البطاقة غير موجودة: :id',
        'suspended' => 'البطاقة موقوفة أو محظورة',
        'expired' => 'انتهت صلاحية البطاقة',
        'limit_exceeded' => 'تم تجاوز حد البطاقة: :type',
        'merchant_blocked' => 'فئة التاجر محظورة',
    ],
];
