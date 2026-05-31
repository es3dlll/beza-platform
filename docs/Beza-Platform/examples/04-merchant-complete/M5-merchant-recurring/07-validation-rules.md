# 07 - قواعد التحقق

```php
<?php
namespace App\Http\Requests\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
{
    public function rules(): array {
        return [
            'customer_phone' => ['required', 'string', Rule::exists('users', 'phone')->where('status', 'active')],
            'amount'         => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'currency'       => ['required', Rule::in(['SYP', 'USD'])],
            'interval'       => ['required', Rule::in(['monthly', 'yearly'])],
            'description'    => ['nullable', 'string', 'max:500'],
            'max_cycles'     => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }
}
```
