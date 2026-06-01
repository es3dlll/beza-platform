# 13 - معالجة الاستثناءات (Exception Handling)

```php
<?php
namespace App\Exceptions\Card;
use Exception;

class CardIssuanceFailedException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'فشل إصدار البطاقة، حاول مرة أخرى'
        ], 422);
    }
}

class InvalidCardTypeException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'نوع البطاقة غير صالح، يُسمح فقط بـ virtual أو physical'
        ], 422);
    }
}

class CardLimitExceededException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'تم تجاوز حد البطاقة المسموح به'
        ], 422);
    }
}

class CardAlreadyExistsException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'لديك بالفعل بطاقة نشطة من هذا النوع والعملة'
        ], 422);
    }
}

class CardNotFoundException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'البطاقة غير موجودة'
        ], 404);
    }
}
```

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | CardIssuanceFailedException | فشل إصدار البطاقة، حاول مرة أخرى |
| 422 | InvalidCardTypeException | نوع البطاقة غير صالح |
| 422 | CardLimitExceededException | تم تجاوز حد البطاقة المسموح به |
| 422 | CardAlreadyExistsException | لديك بالفعل بطاقة نشطة من هذا النوع والعملة |
| 404 | CardNotFoundException | البطاقة غير موجودة |
