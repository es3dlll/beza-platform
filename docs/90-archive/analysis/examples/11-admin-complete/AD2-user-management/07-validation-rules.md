# 07 - قواعد التحقق (Validation Rules)

## UserFilterRequest (للبحث والفلترة)

```php
<?php
// app/Http/Requests/Admin/UserFilterRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'search'     => ['nullable', 'string', 'max:100'],
            'status'     => ['nullable', Rule::in(['active', 'suspended', 'blocked', 'pending'])],
            'kyc_status' => ['nullable', Rule::in(['not_submitted', 'pending', 'verified', 'rejected'])],
            'role'       => ['nullable', Rule::in(['all', 'merchant', 'agent', 'user'])],
            'per_page'   => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by'    => ['nullable', Rule::in(['created_at', 'name', 'status', 'last_login_at'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'search.max'      => 'حد البحث 100 حرف كحد أقصى',
            'status.in'       => 'حالة غير صحيحة',
            'kyc_status.in'   => 'حالة KYC غير صحيحة',
            'per_page.min'    => 'أقل عدد عناصر هو 10',
            'per_page.max'    => 'أكبر عدد عناصر هو 100',
        ];
    }
}
```

## UserUpdateRequest (لتعديل بيانات المستخدم)

```php
<?php
// app/Http/Requests/Admin/UserUpdateRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name'  => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', Rule::unique('users')->ignore($userId)],
        ];
    }
}
```

## التحقق الإضافي في Service Layer

```php
// 1. لا يمكن حذف الذات
if ($adminId === $targetUserId) {
    throw new CannotDeleteSelfException();
}

// 2. لا يمكن تعليق/حظر مشرف
$targetUser = User::findOrFail($targetUserId);
if ($targetUser->is_admin) {
    throw new CannotSuspendAdminException();
}

// 3. لا يمكن تعليق مستخدم معلق أصلاً
if ($targetUser->isSuspended() && $action === 'suspend') {
    throw new UserAlreadySuspendedException();
}
```
