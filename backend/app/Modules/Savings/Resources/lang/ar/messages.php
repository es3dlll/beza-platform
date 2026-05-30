<?php

return [
    'name' => 'محرك الادخار',
    'description' => 'الادخار القائم على الأهداف، التحويل التلقائي، توزيع الأرباح',
    'goal_status' => [
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],
    'transaction_types' => [
        'contribution' => 'إيداع',
        'withdrawal' => 'سحب',
        'profit' => 'ربح',
        'penalty' => 'غرامة السحب المبكر',
        'auto_sweep' => 'تحويل تلقائي',
    ],
    'errors' => [
        'goal_not_found' => 'هدف الادخار غير موجود: :id',
        'goal_completed' => 'هدف الادخار مكتمل بالفعل',
        'insufficient_balance' => 'رصيد الادخار غير كافٍ',
        'invalid_amount' => 'مبلغ المساهمة غير صالح: الحد الأدنى 1,000 ل.س',
        'early_withdrawal_penalty' => 'تنطبق غرامة السحب المبكر بقيمة :penalty ل.س',
    ],
];
