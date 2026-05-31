# 07 - قواعد التحقق (Validation Rules)

```php
<?php
namespace App\Http\Requests\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMerchantRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:100'],
            'commercial_registration' => ['required', 'string', 'max:100', Rule::unique('merchants')],
            'tax_id' => ['required', 'string', 'max:100', Rule::unique('merchants')],
            'owner_phone' => ['required', 'string', 'regex:/^[0-9+\-\(\)\s]{7,20}$/'],
            'owner_name' => ['required', 'string', 'max:255'],
            'bank_account_info.bank_name' => ['required', 'string'],
            'bank_account_info.account_number' => ['required', 'string'],
            'bank_account_info.iban' => ['required', 'string'],
            'documents' => ['required', 'array', 'min:2'],
            'documents.*.type' => ['required', Rule::in(['registration','commercial','tax','owner_id'])],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:10240'],
        ];
    }
}
```
