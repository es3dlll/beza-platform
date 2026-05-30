<?php

declare(strict_types=1);

return [
    'name' => 'محرك الفواتير',
    'description' => 'دفع فواتير الخدمات والاتصالات والحكومة',
    'categories' => [
        'telecom' => 'اتصالات',
        'utility' => 'خدمات',
        'government' => 'حكومي',
        'installment' => 'تقسيط',
    ],
    'status' => [
        'pending_inquiry' => 'بانتظار الاستعلام',
        'inquired' => 'تم الاستعلام',
        'pending_payment' => 'بانتظار الدفع',
        'paid' => 'مدفوع',
        'failed' => 'فشل',
        'refunded' => 'مسترجع',
    ],
    'errors' => [
        'already_paid' => 'الفاتورة مدفوعة مسبقاً: :id',
        'not_found' => 'المزوّد أو الحساب غير موجود: :account',
        'payment_failed' => 'رفض مزوّد الخدمة الدفع: :reason',
        'inquiry_failed' => 'فشل الاستعلام عن الفاتورة: :reason',
        'invalid_amount' => 'عدم تطابق مبلغ الفاتورة: المتوقع :expected، المستلم :actual',
        'account_format_invalid' => 'تنسيق رقم الحساب غير صالح: :account (التنسيق :format)',
        'retry_exceeded' => 'عدد محاولات إعادة المحاولة تجاوز الحد للحساب: :account. انتظر 30 دقيقة.',
    ],
];
