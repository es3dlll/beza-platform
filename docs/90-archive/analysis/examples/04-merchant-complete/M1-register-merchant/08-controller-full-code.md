# 08 - كود المتحكم الكامل (Controller Full Code)

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\RegisterMerchantRequest;
use App\Http\Resources\MerchantResource;
use App\Services\Merchant\MerchantRegistrationService;
use Illuminate\Http\JsonResponse;

class MerchantRegisterController extends Controller
{
    public function __construct(
        private readonly MerchantRegistrationService $registrationService
    ) {}

    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->registrationService->register(
            user: $user,
            businessName: $request->input('business_name'),
            businessType: $request->input('business_type'),
            commercialRegistration: $request->input('commercial_registration'),
            taxId: $request->input('tax_id'),
            ownerPhone: $request->input('owner_phone'),
            ownerName: $request->input('owner_name'),
            bankAccountInfo: $request->input('bank_account_info'),
            documents: $request->file('documents', []),
        );
        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب التسجيل بنجاح، في انتظار المراجعة',
            'data'    => ['merchant' => new MerchantResource($result['merchant']), 'status' => 'pending'],
        ], 201);
    }
}
```

## المسار (Route)
```php
Route::middleware('auth:api')->group(function () {
    Route::post('/merchant/register', [MerchantRegisterController::class, 'register']);
    Route::get('/merchant/status/{id}', [MerchantRegisterController::class, 'status']);
});
```
