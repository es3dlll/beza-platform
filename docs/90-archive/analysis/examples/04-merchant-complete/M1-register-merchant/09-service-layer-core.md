# 09 - طبقة الخدمة الأساسية (Service Layer - Core)

```php
<?php
namespace App\Services\Merchant;
use App\Events\MerchantRegistered;
use App\Exceptions\MerchantAlreadyExistsException;
use App\Exceptions\DocumentUploadFailedException;
use App\Models\Merchant;
use App\Models\MerchantDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantRegistrationService
{
    public function register(
        User   $user, string $businessName, string $businessType,
        string $commercialRegistration, string $taxId,
        string $ownerPhone, string $ownerName,
        array  $bankAccountInfo, array $documents = [],
    ): array {
        $existing = Merchant::where('user_id', $user->id)->first();
        if ($existing) throw new MerchantAlreadyExistsException();

        $merchant = DB::transaction(function () use (
            $user, $businessName, $businessType,
            $commercialRegistration, $taxId,
            $ownerPhone, $ownerName, $bankAccountInfo, $documents
        ) {
            $m = Merchant::create([
                'user_id' => $user->id, 'business_name' => $businessName,
                'business_type' => $businessType,
                'commercial_registration' => $commercialRegistration,
                'tax_id' => $taxId, 'owner_phone' => $ownerPhone,
                'owner_name' => $ownerName,
                'bank_account_info' => $bankAccountInfo,
                'status' => 'pending', 'fee_percentage' => 2.00,
            ]);
            foreach ($documents as $doc) { $this->uploadDocument($m, $doc); }
            return $m;
        }, attempts: 3);

        MerchantRegistered::dispatch($merchant, $user);
        $user->update(['is_merchant' => true]);
        return ['merchant' => $merchant];
    }

    private function uploadDocument(Merchant $merchant, array $docData): void
    {
        $file = $docData['file'];
        $path = $file->store("merchants/{$merchant->id}/documents", 'public');
        MerchantDocument::create([
            'merchant_id' => $merchant->id, 'type' => $docData['type'],
            'file_path' => $path, 'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(), 'is_verified' => false,
        ]);
    }
}
```
