# 13 - معالجة الاستثناءات (Exception Handling)

```php
<?php
namespace App\Exceptions\Card;
use Exception;

class CardFrozenException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'البطاقة مجمدة حالياً ولا يمكن تنفيذ العملية'
        ], 423);
    }
}

class InvalidPinFormatException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'رمـز PIN غير صالح - يجب أن يتكون من 4 أرقام فقط'
        ], 422);
    }
}

class CardNotActiveException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'البطاقة غير نشطة ولا يمكن إكمال العملية'
        ], 422);
    }
}

class InvalidStatusTransitionException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'انتقال الحالة غير مسموح به'
        ], 422);
    }
}

class CardLimitUpdateException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تحديث الحد لأن البطاقة مسروقة أو مغلقة'
        ], 422);
    }
}

class PinAlreadyUsedException extends Exception {
    public function render(): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن استخدام آخر 3 رموز PIN مستخدمة سابقاً'
        ], 422);
    }
}
```

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 423 | CardFrozenException | البطاقة مجمدة حالياً |
| 422 | InvalidPinFormatException | رمز PIN غير صالح |
| 422 | CardNotActiveException | البطاقة غير نشطة |
| 422 | InvalidStatusTransitionException | انتقال الحالة غير مسموح |
| 422 | CardLimitUpdateException | لا يمكن تحديث الحد |
| 422 | PinAlreadyUsedException | PIN مكرر من آخر 3 استخدامات |
