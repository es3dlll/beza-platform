# 07 - قواعد التحقق (Validation Rules)

```php
<?php
namespace App\Http\Requests\Card;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    public function authorize(): bool {
        $card = $this->route('card');
        return $card && $card->user_id === $this->user()->id;
    }
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['change_status', 'change_pin', 'update_limit'])],
            'status' => ['required_if:action,change_status', Rule::in(['active', 'frozen', 'closed', 'lost', 'stolen'])],
            'new_pin' => ['required_if:action,change_pin', 'string', 'digits:4', 'regex:/^[0-9]{4}$/'],
            'daily_limit' => ['required_if:action,update_limit', 'numeric', 'min:1000', 'max:50000000'],
            'monthly_limit' => ['required_if:action,update_limit', 'numeric', 'min:10000', 'max:500000000'],
        ];
    }
    public function messages(): array
    {
        return [
            'action.required' => 'نوع الإجراء مطلوب',
            'action.in' => 'الإجراء غير صالح',
            'status.required_if' => 'الحالة الجديدة مطلوبة',
            'status.in' => 'حالة البطاقة غير صالحة',
            'new_pin.required_if' => 'رمز PIN الجديد مطلوب',
            'new_pin.digits' => 'PIN يجب أن يكون 4 أرقام',
            'new_pin.regex' => 'PIN يجب أن يحتوي أرقاماً فقط',
            'daily_limit.min' => 'الحد اليومي يجب أن يكون 1,000 على الأقل',
            'daily_limit.max' => 'الحد اليومي يجب ألا يتجاوز 50,000,000',
            'monthly_limit.min' => 'الحد الشهري يجب أن يكون 10,000 على الأقل',
            'monthly_limit.max' => 'الحد الشهري يجب ألا يتجاوز 500,000,000',
        ];
    }
}
```

## قواعد انتقال الحالة (Transition Rules)

| من | إلى | مسموح؟ |
|----|-----|--------|
| issued | active | نعم |
| active | frozen | نعم |
| active | closed | نعم |
| active | lost/stolen | نعم |
| frozen | active | نعم |
| frozen | closed | نعم |
| lost/stolen | أي حالة | لا (دائم) |
| closed | أي حالة | لا (دائم) |
