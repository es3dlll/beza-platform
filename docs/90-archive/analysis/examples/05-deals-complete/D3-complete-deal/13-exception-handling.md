# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## استثناءات خاصة بإتمام الصفقة

```php
<?php
// app/Exceptions/DealNotCompletableException.php

namespace App\Exceptions;

use Exception;

class DealNotCompletableException extends Exception
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("لا يمكن إتمام الصفقة بحالتها الحالية: {$currentStatus}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن إتمام هذه الصفقة',
            'errors'  => [
                'deal' => ['الصفقة غير قابلة للإتمام في حالتها الحالية'],
            ],
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/NoActiveInvestorsException.php

class NoActiveInvestorsException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد مستثمرون نشطون في هذه الصفقة',
        ], 422);
    }
}
```
