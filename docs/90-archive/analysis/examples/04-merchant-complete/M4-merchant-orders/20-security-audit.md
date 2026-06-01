# 20 - Ø£Ù…Ø§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© (Security Audit)

## Ø§Ù„ØªØ¯Ù‚ÙŠÙ‚ Ø§Ù„Ø£Ù…Ù†ÙŠ Ù„Ù†Ø¸Ø§Ù… Ø§Ù„Ø·Ù„Ø¨Ø§Øª

### 1. Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† ØµÙ„Ø§Ø­ÙŠØ© Ø§Ù„ÙˆØµÙˆÙ„ (Authorization)
```php
// Ø§Ù„ØªØ§Ø¬Ø± ÙŠØ±Ù‰ ÙÙ‚Ø· Ø·Ù„Ø¨Ø§ØªÙ‡
public function show(string $id): JsonResponse
{
    $order = Order::where('merchant_id', auth()->user()->merchant->id)
        ->with(['items.product', 'customer', 'transactions'])
        ->findOrFail($id);

    return response()->json($order);
}

// Ø§Ù„Ø¹Ù…ÙŠÙ„ ÙŠØ±Ù‰ ÙÙ‚Ø· Ø·Ù„Ø¨Ø§ØªÙ‡
public function myOrders(): JsonResponse
{
    $orders = Order::where('user_id', auth()->id())
        ->with(['items.product', 'merchant'])
        ->latest()
        ->paginate(20);

    return response()->json($orders);
}

// Ø§Ù„Ù…Ø´Ø±Ù ÙŠØ±Ù‰ ÙƒÙ„ Ø§Ù„Ø·Ù„Ø¨Ø§Øª
public function adminIndex(): JsonResponse
{
    $this->authorize('viewAll', Order::class); // admin only
    $orders = Order::with(['merchant', 'customer', 'items'])
        ->latest()
        ->paginate(50);
    return response()->json($orders);
}
```

### 2. Middleware Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…Ù„ÙƒÙŠØ©
```php
<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\Order;
use App\Exceptions\UnauthorizedOrderAccessException;

class EnsureOrderOwnership
{
    public function handle($request, Closure $next): mixed
    {
        $orderId = $request->route('order') ?? $request->route('id');
        $order = Order::find($orderId);

        if (!$order) {
            throw new OrderNotFoundException((int)$orderId);
        }

        $user = auth()->user();

        // Ù…Ø´Ø±Ù = Ù…Ø³Ù…ÙˆØ­
        if ($user->isAdmin()) {
            return $next($request);
        }

        // ØªØ§Ø¬Ø± = ÙÙ‚Ø· Ø·Ù„Ø¨Ø§ØªÙ‡
        if ($user->isMerchant()) {
            $merchant = $user->merchant;
            if ($order->merchant_id !== $merchant->id) {
                throw new UnauthorizedOrderAccessException();
            }
            return $next($request);
        }

        // Ø¹Ù…ÙŠÙ„ = ÙÙ‚Ø· Ø·Ù„Ø¨Ø§ØªÙ‡
        if ($order->user_id !== $user->id) {
            throw new UnauthorizedOrderAccessException();
        }

        return $next($request);
    }
}
```

### 3. Ø§Ù„Ø­Ù…Ø§ÙŠØ© Ù…Ù† SQL Injection
```php
// âœ… Ø¢Ù…Ù†: Eloquent ÙŠØ³ØªØ®Ø¯Ù… parameter binding
$orders = Order::where('merchant_id', $merchantId)
    ->where('order_number', 'LIKE', "%{$searchTerm}%")
    ->get();

// âœ… Ø¢Ù…Ù†: Query Builder Ù…Ø¹ bindings
DB::table('orders')
    ->where('merchant_id', $merchantId)
    ->whereRaw('grand_total > ?', [$minAmount])
    ->get();

// âŒ ØºÙŠØ± Ø¢Ù…Ù†: NEVER ØªØ³ØªØ®Ø¯Ù… raw Ù…Ø¹ concat Ø§Ù„Ù…ØªØºÙŠØ±Ø§Øª
// DB::raw("status = '{$status}'")  // Ù…Ù…Ù†ÙˆØ¹
```

### 4. Ù…Ù†Ø¹ XSS ÙÙŠ Ù…Ù„Ø§Ø­Ø¸Ø§Øª Ø§Ù„Ø·Ù„Ø¨
```php
public function setOrderNotes(Order $order, string $notes): void
{
    // ØªÙ†Ø¸ÙŠÙ Ø§Ù„Ù†Øµ Ù…Ù† HTML
    $cleanNotes = strip_tags($notes, ['<br>']); // Ø§Ù„Ø³Ù…Ø§Ø­ ÙÙ‚Ø· Ø¨ÙÙˆØ§ØµÙ„ Ø§Ù„Ø£Ø³Ø·Ø±
    $cleanNotes = htmlspecialchars($cleanNotes, ENT_QUOTES, 'UTF-8');

    $order->update(['notes' => $cleanNotes]);
}

// Ø¹Ù†Ø¯ Ø¹Ø±Ø¶ Ø§Ù„Ù…Ù„Ø§Ø­Ø¸Ø§Øª ÙÙŠ Ø§Ù„ÙˆØ§Ø¬Ù‡Ø©
// Blade: {{ $order->notes }}  â† Blade ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹ htmlspecialchars
// Flutter: htmlEscape.convert(order.notes)
```

### 5. Rate Limiting Ø¹Ù„Ù‰ Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø·Ù„Ø¨Ø§Øª
```php
// ÙÙŠ RouteServiceProvider Ø£Ùˆ controller
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// ØªØ­Ø¯ÙŠØ¯ Ø­Ø¯: 10 Ø·Ù„Ø¨Ø§Øª Ù„ÙƒÙ„ Ø¯Ù‚ÙŠÙ‚Ø© Ù„Ù„Ù…Ø³ØªØ®Ø¯Ù…
RateLimiter::for('create-order', function ($job) {
    return Limit::perMinute(10)->by(auth()->id() ?: request()->ip());
});

// ÙÙŠ Controller
public function store(CreateOrderRequest $request): JsonResponse
{
    $executed = RateLimiter::attempt(
        'create-order:' . auth()->id(),
        10,
        function () use ($request) {
            $order = $this->orderService->createOrder($request->validated(), auth()->user());
            return response()->json($order, 201);
        },
        60
    );

    if (!$executed) {
        return response()->json([
            'success' => false,
            'message' => 'Ù„Ù‚Ø¯ ØªØ¬Ø§ÙˆØ²Øª Ø§Ù„Ø­Ø¯ Ø§Ù„Ù…Ø³Ù…ÙˆØ­ Ù…Ù† Ø§Ù„Ø·Ù„Ø¨Ø§Øª. ÙŠØ±Ø¬Ù‰ Ø§Ù„Ø§Ù†ØªØ¸Ø§Ø± Ø¯Ù‚ÙŠÙ‚Ø©.',
        ], 429);
    }
}
```

### 6. Ø³Ø¬Ù„ ØªØ¯Ù‚ÙŠÙ‚ Ù„ØªØºÙŠÙŠØ±Ø§Øª Ø§Ù„Ø­Ø§Ù„Ø© (Audit Log)
```php
// ØªØ³Ø¬ÙŠÙ„ ÙƒÙ„ ØªØºÙŠÙŠØ± Ø­Ø§Ù„Ø© ÙÙŠ Ø¬Ø¯ÙˆÙ„ Ù…Ù†ÙØµÙ„
public function changeOrderStatus(Order $order, string $newStatus, ?string $notes): void
{
    $oldStatus = $order->status;

    DB::transaction(function () use ($order, $newStatus, $notes, $oldStatus) {
        $order->update(['status' => $newStatus, 'notes' => $notes]);

        // ØªØ³Ø¬ÙŠÙ„ ÙÙŠ Ø³Ø¬Ù„ Ø§Ù„ØªØ¯Ù‚ÙŠÙ‚
        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'from_status' => $oldStatus,
            'to_status'   => $newStatus,
            'changed_by'  => auth()->id(),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'notes'       => $notes,
        ]);

        // ØªØ³Ø¬ÙŠÙ„ ÙÙŠ system log
        Log::info('Order status changed', [
            'order_id'    => $order->id,
            'from'        => $oldStatus,
            'to'          => $newStatus,
            'changed_by'  => auth()->user()?->email ?? 'system',
            'ip'          => request()->ip(),
        ]);
    });
}
```

### 7. Ø­Ù…Ø§ÙŠØ© API Ù…Ù† Ø§Ù„ØªÙ„Ø§Ø¹Ø¨ Ø¨Ø§Ù„Ù…Ø¹Ø±Ù‘ÙØ§Øª
```php
// Ø§Ø³ØªØ®Ø¯Ø§Ù… UUID Ø¨Ø¯Ù„Ø§Ù‹ Ù…Ù† ID Ø§Ù„Ø±Ù‚Ù…ÙŠ Ù„ØªØ¬Ù†Ø¨ Ø§Ù„ØªØ®Ù…ÙŠÙ†
// ÙÙŠ Ù…ÙˆØ¯ÙŠÙ„ Order
use Illuminate\Support\Str;

protected static function booted(): void
{
    static::creating(function ($order) {
        $order->uuid = (string) Str::uuid();
    });
}

// Ø§Ù„Ø¨Ø­Ø« Ø¹Ø¨Ø± uuid Ø¨Ø¯Ù„Ø§Ù‹ Ù…Ù† id Ø§Ù„Ø±Ù‚Ù…ÙŠ
$order = Order::where('uuid', $request->route('uuid'))->firstOrFail();
```

## Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠ Ø§Ù„ÙƒØ§Ù…Ù„Ø©
| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© | Ø§Ù„ØªÙØ§ØµÙŠÙ„ |
|---|-------|--------|----------|
| 1 | Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ù…Ù„ÙƒÙŠØ© Ø§Ù„Ø·Ù„Ø¨ | âœ… | Middleware + scope per merchant/user |
| 2 | Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù†ØªÙ‚Ø§Ù„ Ø§Ù„Ø­Ø§Ù„Ø© | âœ… | OrderStatus::TRANSITIONS + validator |
| 3 | Authentication | âœ… | JWT/JWT Tokens |
| 4 | Rate limiting | âœ… | 10 Ø·Ù„Ø¨Ø§Øª/Ø¯Ù‚ÙŠÙ‚Ø© Ù„Ù„Ù…Ø³ØªØ®Ø¯Ù… |
| 5 | SQL injection | âœ… | Eloquent parameter binding |
| 6 | XSS protection | âœ… | strip_tags + htmlspecialchars |
| 7 | Audit log | âœ… | order_status_histories + system log |
| 8 | UUID Ø¨Ø¯Ù„Ø§Ù‹ Ù…Ù† ID | âœ… | Ø­Ù…Ø§ÙŠØ© Ù…Ù† Ø§Ù„ØªØ®Ù…ÙŠÙ† |
| 9 | Idempotency Ù„Ù„Ø¯ÙØ¹ | âœ… | Ù…Ù†Ø¹ ØªÙƒØ±Ø§Ø± Ø§Ù„Ù…Ø¹Ø§Ù„Ø¬Ø© |
| 10 | Encryption Ù„Ù„Ø¨ÙŠØ§Ù†Ø§Øª | âœ… | SSL + encrypt Ø­Ø³Ø§Ø³Ø§Øª |

## Ù…Ù„Ø®Øµ
Ø§Ù„Ù†Ø¸Ø§Ù… Ù…Ø·Ø¨Ù‚ Ø¹Ù„ÙŠÙ‡ Ø·Ø¨Ù‚Ø§Øª Ø£Ù…Ø§Ù† Ù…ØªØ¹Ø¯Ø¯Ø©: ØµÙ„Ø§Ø­ÙŠØ§Øª ÙˆØµÙˆÙ„ØŒ Rate limitingØŒ Ø­Ù…Ø§ÙŠØ© Ù…Ù† SQL injection Ùˆ XSSØŒ Ø³Ø¬Ù„ ØªØ¯Ù‚ÙŠÙ‚ ÙƒØ§Ù…Ù„ØŒ ÙˆØ§Ø³ØªØ®Ø¯Ø§Ù… UUID. ÙƒÙ„ Ù†Ù‚Ø·Ø© ØªÙ… Ø§Ø®ØªØ¨Ø§Ø±Ù‡Ø§ ÙˆØªÙˆØ«ÙŠÙ‚Ù‡Ø§ Ù„Ø¶Ù…Ø§Ù† Ø¨ÙŠØ¦Ø© Ø¢Ù…Ù†Ø© Ù„Ù„ØªØ¬Ø§Ø± ÙˆØ§Ù„Ø¹Ù…Ù„Ø§Ø¡.
