# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## استثناءات خاصة بالإلغاء

```php
<?php
// app/Exceptions/DealNotCancellableException.php

namespace App\Exceptions;

use Exception;

class DealNotCancellableException extends Exception
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("لا يمكن إلغاء الصفقة بحالتها: {$currentStatus}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن إلغاء هذه الصفقة',
            'errors'  => [
                'deal' => ['الصفقة غير قابلة للإلغاء في حالتها الحالية'],
            ],
        ], 422);
    }
}
```

## معالجة في Handler

```php
// app/Exceptions/Handler.php

// استثناءات الصفقات ترث render() — معالجة تلقائية
```
