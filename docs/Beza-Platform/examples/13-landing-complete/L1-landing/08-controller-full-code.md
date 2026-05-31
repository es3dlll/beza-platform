# 08 - Full Code — وحدات التحكم

## ContactController

```php
<?php
// app/Http/Controllers/Api/Landing/ContactController.php

namespace App\Http\Controllers\Api\Landing;

use App\Events\ContactSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService
    ) {}

    /**
     * استقبال رسالة من نموذج الاتصال
     *
     * @POST /api/contact
     */
    public function store(ContactRequest $request): JsonResponse
    {
        $contact = $this->contactService->submitContact(
            name:    $request->input('name'),
            email:   $request->input('email'),
            subject: $request->input('subject'),
            message: $request->input('message'),
            phone:   $request->input('phone'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رسالتك بنجاح، سنتواصل معك قريباً',
            'data'    => [
                'contact_id' => $contact->id,
            ],
        ], 201);
    }
}
```

## SubscribeController

```php
<?php
// app/Http/Controllers/Api/Landing/SubscribeController.php

namespace App\Http\Controllers\Api\Landing;

use App\Events\NewsletterSubscribed;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscriber;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;

class SubscribeController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * الاشتراك في النشرة البريدية
     *
     * @POST /api/newsletter/subscribe
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $subscriber = $this->newsletterService->subscribe(
            email:  $request->input('email'),
            name:   $request->input('name'),
            source: $request->input('source', 'landing'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم الاشتراك في النشرة البريدية بنجاح',
            'data'    => [
                'subscriber_id' => $subscriber->id,
            ],
        ], 201);
    }

    /**
     * إلغاء الاشتراك
     *
     * @POST /api/newsletter/unsubscribe
     */
    public function unsubscribe(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $this->newsletterService->unsubscribe($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الاشتراك بنجاح',
        ]);
    }
}
```

## MerchantInquiryController

```php
<?php
// app/Http/Controllers/Api/Landing/MerchantInquiryController.php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantInquiryRequest;
use App\Models\MerchantInquiry;
use Illuminate\Http\JsonResponse;

class MerchantInquiryController extends Controller
{
    public function store(MerchantInquiryRequest $request): JsonResponse
    {
        $inquiry = MerchantInquiry::create($request->validated());

        // إرسال إشعار لفريق المبيعات
        \App\Events\MerchantInquirySubmitted::dispatch($inquiry);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام طلبك، سنتواصل معك قريباً',
        ], 201);
    }
}
```

## AgentInquiryController

```php
<?php
// app/Http/Controllers/Api/Landing/AgentInquiryController.php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentInquiryRequest;
use App\Models\AgentInquiry;
use Illuminate\Http\JsonResponse;

class AgentInquiryController extends Controller
{
    public function store(AgentInquiryRequest $request): JsonResponse
    {
        $inquiry = AgentInquiry::create($request->validated());

        \App\Events\AgentInquirySubmitted::dispatch($inquiry);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام طلبك للانضمام كموزع معتمد',
        ], 201);
    }
}
```

## المسارات (Routes)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\Landing\AgentInquiryController;
use App\Http\Controllers\Api\Landing\ContactController;
use App\Http\Controllers\Api\Landing\MerchantInquiryController;
use App\Http\Controllers\Api\Landing\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::prefix('landing')->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/newsletter/subscribe', [SubscribeController::class, 'subscribe']);
    Route::post('/newsletter/unsubscribe', [SubscribeController::class, 'unsubscribe']);
    Route::post('/merchant/inquiry', [MerchantInquiryController::class, 'store']);
    Route::post('/agent/inquiry', [AgentInquiryController::class, 'store']);
});
```

الفعلية (قيد التشغيل):

```php
<?php
// routes/api.php (existing)

Route::post('/contact', [ContactController::class, 'store']);
Route::post('/newsletter/subscribe', [SubscribeController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [SubscribeController::class, 'unsubscribe']);
Route::post('/merchant-inquiry', [MerchantInquiryController::class, 'store']);
Route::post('/agent-inquiry', [AgentInquiryController::class, 'store']);
```
