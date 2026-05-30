<?php

return [
    'name' => 'الولاء والمكافآت',
    'description' => 'النقاط، المستويات، الكاش باك، والمكافآت',
    'tiers' => [
        'bronze' => 'برونزي',
        'silver' => 'فضي',
        'gold' => 'ذهبي',
        'platinum' => 'بلاتيني',
    ],
    'txn_types' => [
        'earned' => 'مكتسب',
        'redeemed' => 'مستبدل',
        'expired' => 'منتهي',
        'adjusted' => 'معدّل',
    ],
    'errors' => [
        'insufficient_points' => 'نقاط غير كافية: المطلوب :required، المتوفر :available',
        'points_expired' => 'انتهت صلاحية النقاط',
        'reward_not_found' => 'المكافأة غير موجودة: :id',
    ],
];
