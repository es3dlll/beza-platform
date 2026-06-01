# 07 - قواعد التحقق (Validation Rules)

## إرسال إشعار

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:100|exists:notification_templates,type',
            'channels' => 'sometimes|array',
            'channels.*' => 'in:fcm,sms,email',
            'data' => 'sometimes|array',
            'priority' => 'sometimes|integer|between:0,5',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود',
            'type.required' => 'نوع الإشعار مطلوب',
            'type.exists' => 'نوع الإشعار غير مدعوم',
            'channels.*.in' => 'القناة غير مدعومة',
        ];
    }
}
```

## تحديث إشعار كمقروء

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('notification');
        return $notification->notifiable_id === $this->user()->id
            && $notification->notifiable_type === get_class($this->user());
    }

    public function rules(): array
    {
        return [];
    }
}
```
