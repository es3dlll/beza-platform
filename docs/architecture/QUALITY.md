# Quality & Testing - الجودة والاختبارات

## أعراف الاختبارات

- **PestPHP** للإطار الأساسي (مع PHPUnit كخيار)
- أسماء دوال وصفية بالإنجليزية: `test_user_can_transfer_money_if_balance_sufficient`
- Arrange → Act → Assert
- `RefreshDatabase` لكل اختبار تكامل
- `Event::fake()` للتحقق من إطلاق الأحداث
- SQLite `:memory:` لقاعدة بيانات الاختبار

## أنواع الاختبارات

| النوع | الغرض | نسبة التغطية |
|-------|-------|-------------|
| **Unit** | دوال Service/Action فردية | 90%+ |
| **Feature (Integration)** | تفاعل بين وحدتين عبر الأحداث | 85%+ |
| **Feature (Complete)** | رحلة مستخدم كاملة | 80%+ |
| **Compliance** | قواعد AML/KYC | 100% |

## أدوات الجودة

| الأداة | الغرض | الأمر |
|--------|-------|-------|
| **Pest** | إطار الاختبارات | `php artisan test` |
| **PHP-CS-Fixer (ECS)** | تنسيق الكود | `composer format` |
| **PHPStan** | التحليل السكوني | `composer analyze` |
| **OpenAPI Diff** | فحص انجراف التوثيق | `php artisan openapi:generate` |

## عتبات الجودة

- تغطية 90%+ للوحدات الجديدة
- 100% نجاح في CI
- 3 تأكيدات كحد أدنى لكل اختبار ميزة
- 5 دقائق كحد أقصى لتنفيذ مجموعة الاختبارات
