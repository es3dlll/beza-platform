<?php

declare(strict_types=1);

return [
    'name' => 'صرف الرواتب',
    'description' => 'تسجيل أصحاب العمل، دفعات الرواتب، رفع CSV، صرف الرواتب',
    'status' => [
        'pending' => 'بانتظار الموافقة',
        'active' => 'نشط',
        'suspended' => 'موقوف',
        'terminated' => 'منتهي',
    ],
    'batch_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'تمت الموافقة',
        'processing' => 'قيد المعالجة',
        'completed' => 'مكتمل',
        'partially_failed' => 'فشل جزئي',
        'failed' => 'فشل',
        'cancelled' => 'ملغي',
    ],
    'disbursement_status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد المعالجة',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
    ],
    'errors' => [
        'employer_not_found' => 'صاحب العمل غير موجود: :id',
        'employer_suspended' => 'حساب صاحب العمل موقوف',
        'batch_not_found' => 'دفعة الرواتب غير موجودة: :id',
        'insufficient_balance' => 'رصيد الرواتب غير كافٍ: المطلوب :required، المتبقي :remaining',
        'validation_error' => 'فشل التحقق من صحة الرواتب: :reason',
        'csv_error' => 'خطأ في تحليل CSV: :reason',
        'process_error' => 'خطأ في المعالجة: :reason',
    ],
];
