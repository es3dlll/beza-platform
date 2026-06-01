# دليل تكامل المتجر الإلكتروني مع بوابة دفع Beza

> دليل عملي خطوة بخطوة لربط أي متجر إلكتروني (ووردبريس، ماجنتو، شوبيفاي) مع بوابة دفع Beza.
> يغطي الإعداد، التكامل البرمجي، والاختبار والتفعيل.
> راجع [`specs/MERCHANT-API-SPEC.md`](../specs/MERCHANT-API-SPEC.md) لمواصفات API الكاملة
> و [`tasks/05-merchants/M3-payment-gateway.md`](../tasks/05-merchants/M3-payment-gateway.md) لتفاصيل المهمة.

---

## المرحلة الأولى: الإعداد

### 1.1 إنشاء حساب تاجر

| الخطوة | الوصف |
|--------|-------|
| 1 | سجل كتاجر عبر `POST /api/v1/merchant/auth/register` |
| 2 | انتظر موافقة المشرف (حالة الحساب تصبح `approved`) |
| 3 | سجل الدخول عبر `POST /api/v1/merchant/auth/login` |
| 4 | احفظ Bearer Token للاستخدام في الطلبات اللاحقة |

### 1.2 الحصول على مفاتيح API

| نوع المفتاح | الاستخدام | مدة الصلاحية |
|-------------|-----------|-------------|
| Public Key | تضمين في واجهة الدفع (آمن للكشف) | غير محدد |
| Secret Key | توقيع الطلبات من الخادم (سري) | غير محدد |
| Webhook Secret | التحقق من توقيع webhooks الواردة | غير محدد |

### 1.3 إعداد Webhook URL

لتلقي إشعارات فورية بحالات الدفع، سجل مسار webhook في متجرك:

```
POST https://store.example.com/webhooks/beza-payment
```

تأكد من أن الخادم يستمع لهذا المسار ويعيد `200 OK` خلال 5 ثوانٍ.

---

## المرحلة الثانية: التكامل البرمجي

### 2.1 تدفق الدفع الكامل

```
عميل يختار منتج ← سلة التسوق ← يختار "الدفع عبر Beza"
        │
        ▼
متجر → POST /api/v1/merchant/payment/create
        │ ← payment_url
        ▼
توجيه العميل إلى payment_url
        │
        ▼
العميل يسجل دخول في Beza ← يؤكد الدفع
        │
        ▼
Beza تعالج الدفع ← تخصم من المحفظة ← تضيف للتاجر
        │
        ├── Webhook → POST store.example.com/webhooks/beza-payment
        │              │ ← تأكيد الدفع
        │              ▼
        │          تحديث حالة الطلب
        │
        └── Redirect → redirect_url?success=true&ref=BZA-...
                        │
                        ▼
                    عرض صفحة "تم الدفع بنجاح"
```

### 2.2 إنشاء رابط الدفع (PHP - مثال)

```php
<?php
// merchant-payment.php - مثال لإنشاء رابط دفع من خادم المتجر

function createPaymentLink($amount, $currency, $orderId, $customerEmail) {
    $merchantToken = getenv('BEZA_MERCHANT_TOKEN');
    $apiUrl = 'https://api.beza.com/api/v1/merchant/payment/create';
    
    $payload = [
        'amount' => $amount,
        'currency' => $currency,
        'order_id' => $orderId,
        'customer_email' => $customerEmail,
        'redirect_url' => 'https://store.example.com/order/' . $orderId . '/success',
        'webhook_url' => 'https://store.example.com/webhooks/beza-payment',
        'description' => 'طلب رقم ' . $orderId,
        'expires_in' => 30,
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $merchantToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['data']['payment_url']; // رابط الدفع
    }
    
    throw new Exception('فشل إنشاء رابط الدفع: ' . $response);
}

// الاستخدام:
$paymentUrl = createPaymentLink(1500.00, 'USD', 'ORD-20260531-0042', 'customer@example.com');
header('Location: ' . $paymentUrl);
exit;
```

### 2.3 معالجة Webhook (PHP - مثال)

```php
<?php
// webhook.php - استقبال إشعارات الدفع من Beza

$webhookSecret = getenv('BEZA_WEBHOOK_SECRET');
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_BEZA_SIGNATURE'] ?? '';

// التحقق من التوقيع
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    exit('توقيع غير صالح');
}

$event = json_decode($payload, true);

switch ($event['type']) {
    case 'payment.completed':
        $reference = $event['data']['reference'];
        $orderId = $event['data']['order_id'];
        $amount = $event['data']['amount'];
        // تحديث حالة الطلب في قاعدة بيانات المتجر
        updateOrderStatus($orderId, 'paid', $reference);
        // إرسال إيصال للعميل
        sendReceipt($event['data']['customer_email'], $reference, $amount);
        break;
        
    case 'payment.failed':
        $orderId = $event['data']['order_id'];
        updateOrderStatus($orderId, 'payment_failed', null);
        break;
        
    case 'payment.refunded':
        $orderId = $event['data']['order_id'];
        updateOrderStatus($orderId, 'refunded', null);
        break;
}

http_response_code(200);
echo 'OK';
```

### 2.4 البيانات المطلوبة للتكامل

| البيان | الوصف | مكان التخزين |
|--------|-------|-------------|
| Merchant Token | توكن المصادقة (صلاحية 24 ساعة) | env / secret manager |
| Public Key | مفتاح عام للتضمين في الواجهة | env |
| Secret Key | مفتاح سري لتوقيع الطلبات | env / secret manager |
| Webhook Secret | مفتاح التحقق من webhooks | env / secret manager |
| API Base URL | `https://api.beza.com/api/v1` | env |
| Payment Page URL | `https://pay.beza.com` | env |

---

## المرحلة الثالثة: الاختبار والتفعيل

### 3.1 بيئة الاختبار (Sandbox)

| البيان | القيمة |
|--------|--------|
| API Base URL (Sandbox) | `https://sandbox.api.beza.com/api/v1` |
| Payment Page (Sandbox) | `https://sandbox.pay.beza.com` |
| مبالغ الاختبار | استخدم `1.00` USD لاختبار النجاح، `0.01` USD لاختبار الفشل |

### 3.2 قائمة التحقق قبل الإطلاق

| الرقم | البند | تم |
|-------|------|----|
| 1 | إنشاء حساب تاجر والموافقة عليه | ⬜ |
| 2 | توليد مفاتيح API (Public + Secret) | ⬜ |
| 3 | إعداد Webhook URL في المتجر | ⬜ |
| 4 | اختبار إنشاء رابط دفع في Sandbox | ⬜ |
| 5 | اختبار دفع ناجح ← Webhook ← تحديث الطلب | ⬜ |
| 6 | اختبار دفع فاشل (رصيد غير كاف) | ⬜ |
| 7 | اختبار انتهاء صلاحية رابط الدفع | ⬜ |
| 8 | اختبار إعادة التوجيه بعد الدفع | ⬜ |
| 9 | التحقق من التوقيع في Webhook | ⬜ |
| 10 | التبديل إلى البيئة الإنتاجية | ⬜ |

### 3.3 سيناريو الدفع الكامل — من السلة إلى التأكيد

```
1. المتصفح: العميل في صفحة الدفع ← يختار "الدفع عبر Beza"
2. الخادم: POST /api/v1/merchant/payment/create
3. الاستجابة: payment_url = https://pay.beza.com/pay/BZA-REF-123
4. المتصفح: إعادة توجيه إلى payment_url (302 Redirect)
5. Beza: صفحة الدفع ← العميل يسجل دخوله
6. Beza: يعرض تفاصيل الطلب والمبلغ
7. Beza: العميل يؤكد الدفع ← معالجة
8. Beza: Webhook → POST store.example.com/webhooks/beza-payment
9. المتجر: تحديث حالة الطلب إلى "مدفوع"
10. Beza: إعادة توجيه إلى redirect_url?success=true&ref=BZA-REF-123
11. المتصفح: صفحة "تم الدفع بنجاح" ← رابط تتبع الطلب
```

### 3.4 قائمة رموز الخطأ الشائعة

| الرمز | السبب | الحل |
|-------|-------|------|
| `INSUFFICIENT_BALANCE` | رصيد المشتري غير كاف | إعلام المشتري بشحن المحفظة |
| `PAYMENT_EXPIRED` | انتهت صلاحية رابط الدفع | إنشاء رابط جديد |
| `DUPLICATE_ORDER` | order_id مكرر | استخدام order_id فريد لكل طلب |
| `MERCHANT_SUSPENDED` | حساب التاجر معلق | التواصل مع الدعم |
| `INVALID_SIGNATURE` | توقيع webhook غير صحيح | التحقق من Webhook Secret |
| `RATE_LIMIT_EXCEEDED` | تجاوز حد الطلبات | إضافة تأخير بين الطلبات |

---

## روابط ذات صلة

- [مواصفات API الكاملة (مصادقة، مفاتيح، دفع، QR، تسوية)](../specs/MERCHANT-API-SPEC.md)
- [مهمة بوابة الدفع M3 — تفاصيل التنفيذ](../tasks/05-merchants/M3-payment-gateway.md)
- [مهمة منتجات التاجر M2](../tasks/05-merchants/M2-merchant-products.md)
- [مهمة طلبات التاجر M4](../tasks/05-merchants/M4-merchant-orders.md)
- [مهمة تسوية التاجر M6](../tasks/05-merchants/M6-merchant-settlement.md)
