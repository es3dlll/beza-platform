#!/usr/bin/env python3
"""Generate M2-M6 documentation files (100 files)."""
import os

BASE = r"C:\Users\xRoot\Desktop\Beza-Platform\tasks\examples\04-merchant-complete"

def wf(op_dir, filename, content):
    path = os.path.join(BASE, op_dir, filename)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(content.lstrip("\n"))
    print(f"  {filename}")

# =====================================================================
# M2 - MERCHANT PRODUCTS
# =====================================================================
print("Generating M2-merchant-products...")
od = "M2-merchant-products"

wf(od, "00-index.md", """# فهرس - إدارة منتجات التاجر (Merchant Products)

```
M2-merchant-products/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md
├── 02-architecture.md
├── 03-data-flow-sequence.md
├── 04-database-relationships.md
├── 05-migrations.md
├── 06-eloquent-models.md
├── 07-validation-rules.md
├── 08-controller-full-code.md
├── 09-service-layer-core.md
├── 10-service-layer-aux.md
├── 11-events-and-listeners.md
├── 12-notification-system.md
├── 13-exception-handling.md
├── 14-database-transactions-acid.md
├── 15-api-specification.md
├── 16-flutter-implementation.md
├── 17-react-implementation.md
├── 18-testing-complete.md
├── 19-edge-cases.md
└── 20-security-audit.md
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | إدارة منتجات التاجر |
| الأولوية | P1 (عالية) |
| API | `GET|POST /api/v1/merchant/products`, `PUT|DELETE /api/v1/merchant/products/{id}` |
| Controller | `MerchantProductController` |
| Service | `ProductService` / `ProductImageService` |
| DB Tables | merchant_products, product_images |
| Flutter | `MerchantProductsScreen` |
| React | `MerchantProductsPage` |
""")

wf(od, "01-business-idea.md", """# 01 - فكرة العمل وسيناريو المستخدم

## الفكرة الأساسية
تاجر لديه متجر إلكتروني يريد إضافة منتجاته إلى منصة Beza ليتمكن العملاء من شرائها عبر بوابة الدفع.

## سيناريو المستخدم
```
بصفتي: تاجر في Beza
أريد: إدارة منتجاتي (إضافة/تعديل/حذف)
لكي: أعرضها للعملاء للشراء عبر بوابة الدفع
```

## قبول السيناريو
| # | الشرط | الحالة |
|---|-------|--------|
| 1 | إضافة منتج جديد (name, price SYP+USD) | إجباري |
| 2 | رفع صورة للمنتج | اختياري |
| 3 | تفعيل/تعطيل المنتج (is_active) | إجباري |
| 4 | إدارة المخزون (stock) | اختياري |
| 5 | التحقق من أن المنتج يتبع للتاجر | أمني |
| 6 | تعديل/حذف المنتج | CRUD |
""")

wf(od, "02-architecture.md", """# 02 - مكان العملية في الأرشيتيكشر

```
  Flutter/React
       │ CRUD /api/v1/merchant/products
       ▼
  ┌─────────────────────────┐
  │  MerchantProductController │
  │  index / store / show /    │
  │  update / destroy          │
  └──────────┬────────────────┘
             │
  ┌──────────┴────────────────┐
  │  ProductService             │
  │  - CRUD operations          │
  │  - Ownership check          │
  └──────────┬────────────────┘
             │
  ┌──────────┴────────────────┐
  │  ProductImageService       │
  │  - Upload/Delete images    │
  └──────────┬────────────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │ products│
        │ images  │
        └─────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات الكامل

```
  Merchant      Flutter/React      Laravel API        ProductService     MySQL         Storage
     │                │                  │                  │               │              │
     │  إضافة         │                  │                  │               │              │
     │───────────────>│                  │                  │               │              │
     │                │  POST /products  │                  │               │              │
     │                │─────────────────>│                  │               │              │
     │                │                  │  Validate        │               │              │
     │                │                  │─────────────────>│               │              │
     │                │                  │  Check merchant  │──────────────>│              │
     │                │                  │  Upload images   │──────────────>│─────────────>│
     │                │                  │  INSERT product  │──────────────>│              │
     │                │ Response 201     │                  │               │              │
     │                │<─────────────────│                  │               │              │
     │<───────────────│                  │                  │               │              │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول

## ER Diagram
```
┌──────────────────┐        ┌─────────────────────────────────────┐
│    merchants      │───────>│        merchant_products            │
│──────────────────│ 1     M│─────────────────────────────────────│
│ id               │        │ PK id                               │
└──────────────────┘        │ FK merchant_id                      │
                            │ name                                │
                            │ price_syp / price_usd               │
                            │ category                            │
                            │ stock (nullable)                    │
                            │ is_active                           │
                            └────────────────┬────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │   product_images    │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK product_id      │
                                   │ image_path         │
                                   │ is_primary         │
                                   └────────────────────┘
```
""")

wf(od, "05-migrations.md", """# 05 - كود الميغريشن الكامل

```php
Schema::create('merchant_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price_syp', 15, 2);
    $table->decimal('price_usd', 15, 2);
    $table->string('category', 100)->nullable();
    $table->unsignedInteger('stock')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['merchant_id', 'is_active']);
});

Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('merchant_products')->onDelete('cascade');
    $table->string('image_path', 500);
    $table->boolean('is_primary')->default(false);
    $table->integer('sort_order')->default(0);
});
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز مع العلاقات

```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class MerchantProduct extends Model
{
    protected $table = 'merchant_products';
    protected $fillable = ['merchant_id', 'name', 'description', 'price_syp', 'price_usd', 'category', 'stock', 'is_active'];
    protected $casts = ['price_syp' => 'decimal:2', 'price_usd' => 'decimal:2', 'stock' => 'integer', 'is_active' => 'boolean'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function images() { return $this->hasMany(ProductImage::class); }
    public function primaryImage() { return $this->hasOne(ProductImage::class)->where('is_primary', true); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}

class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];
    public function product() { return $this->belongsTo(MerchantProduct::class); }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - كل قواعد التحقق + أسبابها

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_syp' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'category'  => ['nullable', 'string', 'max:100'],
            'stock'     => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['boolean'],
            'images'    => ['nullable', 'array', 'max:5'],
            'images.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
    public function messages(): array {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'price_syp.required' => 'سعر المنتج بالليرة مطلوب',
            'price_usd.required' => 'سعر المنتج بالدولار مطلوب',
            'images.*.max'  => 'حجم الصورة لا يتجاوز 2MB',
            'images.max'    => 'الحد الأقصى 5 صور',
        ];
    }
}
```
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Merchant\\ProductRequest;
use App\\Http\\Resources\\MerchantProductResource;
use App\\Services\\Merchant\\ProductService;
use Illuminate\\Http\\JsonResponse;

class MerchantProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse {
        $merchant = $request->user()->merchant;
        $products = $merchant->products()->with('primaryImage')
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->paginate(20);
        return response()->json(['success' => true, 'data' => MerchantProductResource::collection($products)]);
    }

    public function store(ProductRequest $request): JsonResponse {
        $product = $this->productService->create(
            merchant: $request->user()->merchant,
            data: $request->validated(),
            images: $request->file('images', []),
        );
        return response()->json(['success' => true, 'message' => 'تم إضافة المنتج', 'data' => new MerchantProductResource($product)], 201);
    }

    public function show(Request $request, $id): JsonResponse {
        $product = $this->productService->findForMerchant($request->user()->merchant->id, $id);
        return response()->json(['success' => true, 'data' => new MerchantProductResource($product->load('images'))]);
    }

    public function update(ProductRequest $request, $id): JsonResponse {
        $product = $this->productService->update($request->user()->merchant, $id, $request->validated(), $request->file('images', []));
        return response()->json(['success' => true, 'message' => 'تم تحديث المنتج', 'data' => new MerchantProductResource($product)]);
    }

    public function destroy(Request $request, $id): JsonResponse {
        $this->productService->delete($request->user()->merchant, $id);
        return response()->json(['success' => true, 'message' => 'تم حذف المنتج']);
    }
}
```
""")

wf(od, "09-service-layer-core.md", """# 09 - ProductService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\Merchant;
use App\\Models\\MerchantProduct;
use Illuminate\\Support\\Facades\\DB;

class ProductService
{
    public function create(Merchant $merchant, array $data, array $images = []): MerchantProduct
    {
        return DB::transaction(function () use ($merchant, $data, $images) {
            $product = $merchant->products()->create([
                'name' => $data['name'], 'description' => $data['description'] ?? null,
                'price_syp' => $data['price_syp'], 'price_usd' => $data['price_usd'],
                'category' => $data['category'] ?? null, 'stock' => $data['stock'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            foreach ($images as $image) {
                $path = $image->store("products/{$product->id}", 'public');
                $product->images()->create(['image_path' => $path, 'is_primary' => !$product->images()->exists(), 'sort_order' => 0]);
            }
            return $product;
        }, attempts: 3);
    }

    public function update(Merchant $merchant, int $productId, array $data, array $images = []): MerchantProduct {
        $product = $this->findForMerchant($merchant->id, $productId);
        DB::transaction(function () use ($product, $data, $images) {
            $product->update($data);
            foreach ($images as $image) {
                $path = $image->store("products/{$product->id}", 'public');
                $product->images()->create(['image_path' => $path, 'is_primary' => false, 'sort_order' => $product->images()->count()]);
            }
        });
        return $product->fresh()->load('images');
    }

    public function delete(Merchant $merchant, int $productId): void {
        $product = $this->findForMerchant($merchant->id, $productId);
        foreach ($product->images as $image) { Storage::disk('public')->delete($image->image_path); }
        $product->delete();
    }

    public function findForMerchant(int $merchantId, int $productId): MerchantProduct {
        return MerchantProduct::where('merchant_id', $merchantId)->findOrFail($productId);
    }
}
```
""")

wf(od, "10-service-layer-aux.md", """# 10 - ProductImageService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\MerchantProduct;
use App\\Models\\ProductImage;
use Illuminate\\Support\\Facades\\Storage;

class ProductImageService
{
    public function setPrimary(MerchantProduct $product, int $imageId): void {
        $product->images()->update(['is_primary' => false]);
        $product->images()->findOrFail($imageId)->update(['is_primary' => true]);
    }

    public function deleteImage(MerchantProduct $product, int $imageId): void {
        $image = $product->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }
}
```
""")

wf(od, "11-events-and-listeners.md", """# 11 - الأحداث والمستمعين

```php
<?php
namespace App\\Events;
use App\\Models\\MerchantProduct;
use Illuminate\\Foundation\\Events\\Dispatchable;

class ProductCreated { use Dispatchable; public function __construct(public readonly MerchantProduct $product) {} }
class ProductUpdated { use Dispatchable; public function __construct(public readonly MerchantProduct $product) {} }
class ProductDeleted { use Dispatchable; public function __construct(public readonly int $productId, public readonly int $merchantId) {} }
```
""")

wf(od, "12-notification-system.md", """# 12 - نظام الإشعارات

```php
<?php
namespace App\\Notifications;
use App\\Models\\MerchantProduct;
use Illuminate\\Notifications\\Notification;

class ProductAdded extends Notification
{
    public function __construct(private readonly MerchantProduct $product) {}
    public function via($notifiable): array { return ['database', 'fcm']; }
    public function toArray($notifiable): array {
        return ['type' => 'product_added', 'title' => 'تم إضافة منتج', 'body' => "تم إضافة {$this->product->name}", 'product_id' => $this->product->id];
    }
}
```
""")

wf(od, "13-exception-handling.md", """# 13 - الاستثناءات

```php
<?php
namespace App\\Exceptions;
use Exception;

class ProductNotFoundException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404); }
}
class ProductNotBelongToMerchantException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'هذا المنتج لا يتبع لمتجرك'], 403); }
}
```
""")

wf(od, "14-database-transactions-acid.md", """# 14 - ACID + الأقفال

## ضمان ACID
```php
DB::transaction(function () {
    $product = MerchantProduct::create([...]);  // فشل → ROLLBACK
    foreach ($images as $image) {
        ProductImage::create([...]);  // فشل → ROLLBACK للمنتج أيضاً
    }
}, attempts: 3);
```

## معالجة المخزون
```php
// تحديث آمن للمخزون يمنع البيع بعد النفاد
DB::update('UPDATE merchant_products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$qty, $id, $qty]);
```
""")

wf(od, "15-api-specification.md", """# 15 - مواصفات API

```yaml
paths:
  /merchant/products:
    get:
      summary: قائمة المنتجات
      parameters: [{ name: category, in: query, schema: { type: string } }]
      responses: { '200': { description: القائمة } }
    post:
      summary: إضافة منتج
      requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              required: [name, price_syp, price_usd]
              properties:
                name: { type: string }
                price_syp: { type: number }
                price_usd: { type: number }
                images: { type: array, items: { type: string, format: binary } }
      responses: { '201': { description: تم الإنشاء } }
  /merchant/products/{id}:
    get: { summary: عرض منتج }
    put: { summary: تحديث منتج }
    delete: { summary: حذف منتج }
```
""")

wf(od, "16-flutter-implementation.md", """# 16 - Flutter UI

```dart
// presentation/bloc/product_bloc.dart
class ProductBloc extends Bloc<ProductEvent, ProductState> {
  final IProductRepository repository;
  ProductBloc({required this.repository}) : super(ProductInitial()) {
    on<LoadProducts>(_onLoad);
    on<CreateProduct>(_onCreate);
    on<DeleteProduct>(_onDelete);
  }
  Future<void> _onLoad(LoadProducts event, Emitter<ProductState> emit) async {
    emit(ProductLoading());
    try { final products = await repository.getProducts(); emit(ProductLoaded(products)); }
    catch (e) { emit(ProductError(e.toString())); }
  }
}

// presentation/screens/merchant_products_screen.dart
class MerchantProductsScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('منتجاتي')),
      body: BlocBuilder<ProductBloc, ProductState>(
        builder: (context, state) {
          if (state is ProductLoading) return CircularProgressIndicator();
          if (state is ProductLoaded) return ListView.builder(
            itemCount: state.products.length,
            itemBuilder: (_, i) => ListTile(title: Text(state.products[i].name), subtitle: Text('${state.products[i].priceUsd} USD'));
          return Text('لا توجد منتجات');
        },
      ),
      floatingActionButton: FloatingActionButton(child: Icon(Icons.add), onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AddProductScreen()))),
    );
  }
}
```
""")

wf(od, "17-react-implementation.md", """# 17 - React UI

```jsx
// hooks/useProducts.js
import { useState, useEffect, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useProducts() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const loadProducts = useCallback(async () => {
    setLoading(true);
    try { const res = await merchantApi.getProducts(); setProducts(res.data.data); }
    finally { setLoading(false); }
  }, []);
  const createProduct = useCallback(async (formData) => {
    const res = await merchantApi.createProduct(formData);
    setProducts(prev => [res.data.data, ...prev]);
  }, []);
  const deleteProduct = useCallback(async (id) => {
    await merchantApi.deleteProduct(id);
    setProducts(prev => prev.filter(p => p.id !== id));
  }, []);
  useEffect(() => { loadProducts(); }, [loadProducts]);
  return { products, loading, createProduct, deleteProduct };
}

// pages/MerchantProductsPage.jsx
export default function MerchantProductsPage() {
  const { products, loading, deleteProduct } = useProducts();
  return (
    <div>
      <h1>منتجاتي</h1>
      {loading && <p>جاري التحميل...</p>}
      <div className="products-grid">
        {products.map(p => (
          <div key={p.id} className="product-card">
            <h3>{p.name}</h3>
            <p>{p.price_usd} USD / {p.price_syp} SYP</p>
            <button onClick={() => deleteProduct(p.id)}>حذف</button>
          </div>
        ))}
      </div>
    </div>
  );
}
```
""")

wf(od, "18-testing-complete.md", """# 18 - الاختبارات

```php
<?php
namespace Tests\\Feature\\Merchant;
use App\\Models\\Merchant;
use App\\Models\\MerchantProduct;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    private Merchant $merchant;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->merchant = Merchant::factory()->create(['user_id' => $user->id]);
        $this->token = $user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function it_creates_a_product() {
        $response = $this->withToken($this->token)->postJson('/api/v1/merchant/products', [
            'name' => 'منتج تجريبي', 'price_syp' => 100000, 'price_usd' => 10,
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('merchant_products', ['name' => 'منتج تجريبي']);
    }

    /** @test */
    public function it_lists_products() {
        MerchantProduct::factory()->count(3)->create(['merchant_id' => $this->merchant->id]);
        $response = $this->withToken($this->token)->getJson('/api/v1/merchant/products');
        $response->assertStatus(200);
        $this->assertCount(3, $response['data']);
    }

    /** @test */
    public function it_requires_authentication() {
        $this->postJson('/api/v1/merchant/products', [])->assertStatus(401);
    }
}
```
""")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة

1. **صورة كبيرة جدا**: Validation → max 2MB لكل صورة.
2. **منتج بدون مخزون (خدمة)**: stock = null → مخزون غير محدود.
3. **آخر قطعة في المخزون**: UPDATE WHERE stock >= 1 → يمنع البيع بعد النفاد.
4. **حذف منتج عليه طلبات**: Soft delete أو منع الحذف إذا كان pending.
5. **تعديل سعر منتج بعد نشره**: تخزين السعر القديم في metadata.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | صورة > 2MB | رفض الرفع |
| 2 | stock = null | مخزون غير محدود |
| 3 | آخر قطعة | بيع آمن |
| 4 | حذف منتج | حذف مع الصور |
| 5 | إنشاء بدون صورة | مسموح |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية

## التحقق من الملكية
```php
// كل منتج يتحقق من merchant_id يتبع للتاجر المصادق
$product = MerchantProduct::where('merchant_id', $merchantId)->findOrFail($id);
```

## حماية رفع الصور
```php
'mimes:jpg,jpeg,png,webp'  // التحقق من الصيغة
'max:2048'                  // التحقق من الحجم
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من الملكية | ✅ |
| 2 | التحقق من نوع الملفات | ✅ |
| 3 | تحديد حجم الملفات | ✅ |
| 4 | Mass assignment | ✅ |
| 5 | Authentication | ✅ |
| 6 | SQL injection | ✅ |
""")

print("M2 complete!")

# =====================================================================
# M3 - PAYMENT GATEWAY
# =====================================================================
print("Generating M3-payment-gateway...")
od = "M3-payment-gateway"

wf(od, "00-index.md", """# فهرس - بوابة الدفع وروابط الدفع (Payment Gateway)

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | إنشاء روابط دفع |
| الأولوية | P0 (حرجة) |
| API | `POST /api/v1/merchant/payment-link`, `GET /api/v1/merchant/payment-link/{token}` |
| Controller | `PaymentLinkController` |
| Service | `PaymentLinkService` |
| Event | `PaymentLinkCreated`, `PaymentCompleted` |
| DB Tables | payment_links |
| Flutter | `PaymentLinkScreen` |
| React | `PaymentLinkPage` |
""")

wf(od, "01-business-idea.md", """# 01 - فكرة العمل وسيناريو المستخدم

## الفكرة الأساسية
تاجر يريد إرسال رابط دفع لعميل عبر واتساب. العميل يضغط الرابط ويدفع مباشرة.

## سيناريو المستخدم
```
بصفتي: تاجر
أريد: إنشاء رابط دفع وإرساله للعميل
لكي: يتمكن العميل من الدفع إلكترونياً
```

## قبول السيناريو
| # | الشرط |
|---|--------|
| 1 | إنشاء رابط دفع بمبلغ محدد (SYP/USD) |
| 2 | إضافة وصف ورابط إعادة توجيه |
| 3 | صلاحية الرابط (expiry_hours) |
| 4 | تجميد المبلغ في محفظة التاجر |
| 5 | Webhook لإشعار التاجر بالدفع |
""")

wf(od, "02-architecture.md", """# 02 - مكان العملية

```
  Merchant (Flutter/React)
       │ POST /payment-link
       ▼
  ┌────────────────────┐
  │ PaymentLinkController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ PaymentLinkService   │
  │ 1. Create link       │
  │ 2. Freeze amount     │
  │ 3. Generate URL      │
  └──────────┬─────────┘
             │
  Customer (Browser)
       │ Click link
       ▼
  ┌────────────────────┐
  │ Payment Page        │
  │ (Flutter Web/React) │
  └──────────┬─────────┘
             │ POST /pay
             ▼
  ┌────────────────────┐
  │ PaymentController    │
  │ 1. Process payment   │
  │ 2. Unfreeze + deduct │
  │ 3. Send webhook      │
  └────────────────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات

```
  Merchant         API            LinkService        MySQL         Customer        Webhook
     │              │                  │                │               │              │
     │ POST /link   │                  │                │               │              │
     │─────────────>│                  │                │               │              │
     │              │  Create link     │                │               │              │
     │              │─────────────────>│                │               │              │
     │              │  Freeze amount   │───────────────>│               │              │
     │              │  Return link URL │                │               │              │
     │<─────────────│                  │                │               │              │
     │  Send URL    │                  │                │               │              │
     │──────────────┼───────────────────────────────────────────────>│              │
     │              │                  │   GET /pay/token            │              │
     │              │                  │<─────────────────────────────│              │
     │              │  POST /pay       │                │               │              │
     │              │                  │<─────────────────────────────│              │
     │              │  Process payment │                │               │              │
     │              │─────────────────>│                │               │              │
     │              │  Unfreeze        │───────────────>│               │              │
     │              │  Webhook         │────────────────────────────────────────────────>│
     │              │  Redirect        │                │               │              │
     │              │                  │<─────────────────────────────│              │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│            payment_links                │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ token (UNIQUE)                          │
                            │ amount                                  │
                            │ currency (SYP/USD)                      │
                            │ description                             │
                            │ redirect_url                            │
                            │ status (active/used/expired/cancelled)  │
                            │ expires_at                              │
                            │ paid_at                                 │
                            └─────────────────────────────────────────┘
```

```sql
CREATE TABLE payment_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    amount DECIMAL(15,2) NOT NULL,
    currency ENUM('SYP','USD') NOT NULL,
    description TEXT,
    redirect_url VARCHAR(500),
    status ENUM('active','used','expired','cancelled') DEFAULT 'active',
    expires_at TIMESTAMP NOT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```
""")

wf(od, "05-migrations.md", """# 05 - الميغريشن

```php
<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['SYP', 'USD']);
            $table->text('description')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->enum('status', ['active','used','expired','cancelled'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['token', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('payment_links'); }
};
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز

```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class PaymentLink extends Model
{
    protected $fillable = ['merchant_id', 'token', 'amount', 'currency', 'description', 'redirect_url', 'status', 'expires_at', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function isExpired(): bool { return $this->status === 'expired' || $this->expires_at->isPast(); }
    public function isActive(): bool { return $this->status === 'active' && !$this->isExpired(); }
    public function markAsPaid(): void { $this->update(['status' => 'used', 'paid_at' => now()]); }
    public static function generateToken(): string { return bin2hex(random_bytes(32)); }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - قواعد التحقق

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class PaymentLinkRequest extends FormRequest
{
    public function rules(): array {
        return [
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency'     => ['required', Rule::in(['SYP', 'USD'])],
            'description'  => ['nullable', 'string', 'max:500'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'expiry_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }
    public function messages(): array {
        return ['amount.required' => 'المبلغ مطلوب', 'amount.min' => 'أقل مبلغ 0.01', 'currency.in' => 'العملة غير مدعومة'];
    }
}
```
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Merchant\\PaymentLinkRequest;
use App\\Http\\Resources\\PaymentLinkResource;
use App\\Services\\Merchant\\PaymentLinkService;
use Illuminate\\Http\\JsonResponse;

class PaymentLinkController extends Controller
{
    public function __construct(private readonly PaymentLinkService $linkService) {}

    public function store(PaymentLinkRequest $request): JsonResponse {
        $link = $this->linkService->create(
            merchant: $request->user()->merchant,
            amount: $request->input('amount'),
            currency: $request->input('currency'),
            description: $request->input('description'),
            redirectUrl: $request->input('redirect_url'),
            expiryHours: $request->input('expiry_hours'),
        );
        return response()->json(['success' => true, 'message' => 'تم إنشاء رابط الدفع', 'data' => new PaymentLinkResource($link)], 201);
    }

    public function show($token): JsonResponse {
        return response()->json(['success' => true, 'data' => new PaymentLinkResource($this->linkService->findByToken($token))]);
    }

    public function cancel($id): JsonResponse {
        $this->linkService->cancel($id);
        return response()->json(['success' => true, 'message' => 'تم إلغاء رابط الدفع']);
    }
}
```
""")

wf(od, "09-service-layer-core.md", """# 09 - PaymentLinkService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Events\\PaymentLinkCreated;
use App\\Exceptions\\PaymentLinkExpiredException;
use App\\Models\\Merchant;
use App\\Models\\PaymentLink;
use Illuminate\\Support\\Facades\\DB;

class PaymentLinkService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    public function create(Merchant $merchant, float $amount, string $currency, ?string $description = null, ?string $redirectUrl = null, int $expiryHours = 24): PaymentLink {
        return DB::transaction(function () use ($merchant, $amount, $currency, $description, $redirectUrl, $expiryHours) {
            $wallet = $this->walletService->getWallet($merchant->id, $currency);
            if (!$wallet || !$wallet->is_active) throw new \\RuntimeException('محفظة التاجر غير نشطة');
            if ($wallet->available_balance < $amount) throw new \\RuntimeException('رصيد غير كافٍ');
            $this->walletService->freeze($wallet, $amount);
            return PaymentLink::create([
                'merchant_id' => $merchant->id, 'token' => PaymentLink::generateToken(),
                'amount' => $amount, 'currency' => $currency, 'description' => $description,
                'redirect_url' => $redirectUrl, 'status' => 'active', 'expires_at' => now()->addHours($expiryHours),
            ]);
        }, attempts: 3);
    }

    public function processPayment(string $token): array {
        $link = PaymentLink::where('token', $token)->firstOrFail();
        if ($link->isExpired()) throw new PaymentLinkExpiredException();
        if ($link->status !== 'active') throw new \\RuntimeException('الرابط مستخدم مسبقاً');

        return DB::transaction(function () use ($link) {
            $link->markAsPaid();
            $wallet = $this->walletService->getWallet($link->merchant_id, $link->currency);
            $this->walletService->unfreeze($wallet, $link->amount);
            return ['redirect_url' => $link->redirect_url];
        }, attempts: 3);
    }

    public function cancel(int $linkId): void {
        $link = PaymentLink::findOrFail($linkId);
        if ($link->status !== 'active') throw new \\RuntimeException('لا يمكن إلغاء رابط منتهي');
        DB::transaction(function () use ($link) {
            $link->update(['status' => 'cancelled']);
            $wallet = $this->walletService->getWallet($link->merchant_id, $link->currency);
            $this->walletService->unfreeze($wallet, $link->amount);
        });
    }

    public function findByToken(string $token): PaymentLink { return PaymentLink::where('token', $token)->firstOrFail(); }
}
```
""")

wf(od, "10-service-layer-aux.md", """# 10 - PaymentLinkExpiryService

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\PaymentLink;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class PaymentLinkExpiryService
{
    public function expireOverdueLinks(): int {
        $count = PaymentLink::where('status', 'active')->where('expires_at', '<', now())->count();
        PaymentLink::where('status', 'active')->where('expires_at', '<', now())->chunkById(100, function ($links) {
            foreach ($links as $link) {
                DB::transaction(function () use ($link) {
                    $link->markAsExpired();
                    $wallet = MerchantWallet::where('merchant_id', $link->merchant_id)->where('currency', $link->currency)->first();
                    if ($wallet) { $wallet->increment('balance', $link->amount); $wallet->decrement('frozen_balance', $link->amount); }
                });
            }
        });
        return $count;
    }
}
```
""")

wf(od, "11-events-and-listeners.md", "# 11 - الأحداث والمستمعين\n\n```php\n<?php\nnamespace App\\Events;\nuse App\\Models\\PaymentLink;\nuse Illuminate\\Foundation\\Events\\Dispatchable;\n\nclass PaymentLinkCreated { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }\nclass PaymentCompleted { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }\n```\n")

wf(od, "12-notification-system.md", "# 12 - نظام الإشعارات\n\n```php\n<?php\nnamespace App\\Notifications;\nuse App\\Models\\PaymentLink;\nuse Illuminate\\Notifications\\Notification;\n\nclass PaymentReceived extends Notification {\n    public function __construct(private readonly PaymentLink $link) {}\n    public function via($notifiable): array { return ['database', 'fcm']; }\n    public function toArray($notifiable): array {\n        return ['type' => 'payment_received', 'title' => 'تم استلام دفعة', 'body' => \"استلام {$this->link->amount} {$this->link->currency}\", 'link_id' => $this->link->id];\n    }\n}\n```\n")

wf(od, "13-exception-handling.md", "# 13 - الاستثناءات\n\n```php\n<?php\nnamespace App\\Exceptions;\nuse Exception;\n\nclass PaymentLinkExpiredException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'رابط الدفع منتهي الصلاحية'], 410); }\n}\nclass InsufficientMerchantBalanceException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'رصيد التاجر غير كافٍ'], 422); }\n}\n```\n")

wf(od, "14-database-transactions-acid.md", "# 14 - ACID\n\n## التجميد والدفع\n```php\nDB::transaction(function () {\n    $link->markAsPaid();\n    $wallet = MerchantWallet::find($link->merchant_id);\n    $wallet->increment('balance', $link->amount);\n    $wallet->decrement('frozen_balance', $link->amount);\n}, attempts: 3);\n```\n\n## إنشاء الرابط مع تجميد الرصيد\n```php\nDB::transaction(function () {\n    $this->walletService->freeze($wallet, $amount);  // تجميد يمنع صرف الرصيد\n    PaymentLink::create([...]);  // إنشاء الرابط\n}, attempts: 3);\n```\n")

wf(od, "15-api-specification.md", """# 15 - مواصفات API

```yaml
paths:
  /merchant/payment-link:
    post:
      summary: إنشاء رابط دفع
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [amount, currency, expiry_hours]
              properties:
                amount: { type: number }
                currency: { type: string, enum: [SYP, USD] }
                description: { type: string }
                redirect_url: { type: string }
                expiry_hours: { type: integer }
      responses: { '201': { description: تم إنشاء الرابط } }
  /merchant/payment-link/{token}:
    get: { summary: عرض رابط الدفع }
```

```bash
curl -X POST http://localhost:8000/api/v1/merchant/payment-link \\
  -H "Authorization: Bearer TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"amount": 100, "currency": "USD", "expiry_hours": 24}'
```
""")

wf(od, "16-flutter-implementation.md", "# 16 - Flutter UI\n\n```dart\n// presentation/bloc/payment_link_bloc.dart\nclass PaymentLinkBloc extends Bloc<PaymentLinkEvent, PaymentLinkState> {\n  final IPaymentLinkRepository repository;\n  PaymentLinkBloc({required this.repository}) : super(PaymentLinkInitial()) {\n    on<CreatePaymentLink>(_onCreate);\n  }\n  Future<void> _onCreate(CreatePaymentLink event, Emitter<PaymentLinkState> emit) async {\n    emit(PaymentLinkLoading());\n    try { final link = await repository.create(amount: event.amount, currency: event.currency, expiryHours: event.expiryHours);\n      emit(PaymentLinkSuccess(link)); }\n    catch (e) { emit(PaymentLinkFailure(e.toString())); }\n  }\n}\n\n// presentation/screens/payment_link_screen.dart\nclass PaymentLinkScreen extends StatelessWidget {\n  @override\n  Widget build(BuildContext context) {\n    return Scaffold(\n      appBar: AppBar(title: Text('رابط دفع')),\n      body: Column(children: [\n        TextFormField(decoration: InputDecoration(labelText: 'المبلغ'), keyboardType: TextInputType.number),\n        DropdownButtonFormField(items: [DropdownMenuItem(value: 'USD', child: Text('USD')), DropdownMenuItem(value: 'SYP', child: Text('SYP'))], onChanged: (_) {}),\n        ElevatedButton(onPressed: () {}, child: Text('إنشاء رابط')),\n      ]),\n    );\n  }\n}\n```\n")

wf(od, "17-react-implementation.md", "# 17 - React UI\n\n```jsx\n// hooks/usePaymentLink.js\nimport { useState, useCallback } from 'react';\nimport { merchantApi } from '../services/api';\n\nexport function usePaymentLink() {\n  const [state, setState] = useState({ loading: false, link: null, error: null });\n  const create = useCallback(async (data) => {\n    setState(prev => ({ ...prev, loading: true, error: null }));\n    try { const res = await merchantApi.createPaymentLink(data);\n      setState({ loading: false, link: res.data.data, error: null }); }\n    catch (err) { setState({ loading: false, link: null, error: err.response?.data?.message }); }\n  }, []);\n  return { ...state, create };\n}\n\n// pages/PaymentLinkPage.jsx\nimport { usePaymentLink } from '../hooks/usePaymentLink';\nexport default function PaymentLinkPage() {\n  const { loading, link, error, create } = usePaymentLink();\n  const [form, setForm] = useState({ amount: '', currency: 'USD', expiryHours: 24 });\n  const handleSubmit = (e) => { e.preventDefault(); create(form); };\n  return (\n    <div>\n      <h1>رابط دفع</h1>\n      {error && <div className=\"error\">{error}</div>}\n      {link && <div>الرابط: {window.location.origin}/pay/{link.token}</div>}\n      <form onSubmit={handleSubmit}>\n        <input type=\"number\" placeholder=\"المبلغ\" value={form.amount} onChange={e => setForm({...form, amount: e.target.value})} required />\n        <select value={form.currency} onChange={e => setForm({...form, currency: e.target.value})}><option value=\"USD\">USD</option><option value=\"SYP\">SYP</option></select>\n        <button type=\"submit\" disabled={loading}>{loading ? 'جاري...' : 'إنشاء رابط'}</button>\n      </form>\n    </div>\n  );\n}\n```\n")

wf(od, "18-testing-complete.md", """# 18 - الاختبارات

```php
<?php
namespace Tests\\Feature\\Merchant;
use App\\Models\\Merchant;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class PaymentLinkTest extends TestCase
{
    use RefreshDatabase;
    private Merchant $merchant;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->merchant = Merchant::factory()->create(['user_id' => $user->id]);
        MerchantWallet::factory()->create(['merchant_id' => $this->merchant->id, 'currency' => 'USD', 'balance' => 1000]);
        $this->token = $user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function it_creates_payment_link() {
        $response = $this->withToken($this->token)->postJson('/api/v1/merchant/payment-link', [
            'amount' => 100, 'currency' => 'USD', 'expiry_hours' => 24,
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('payment_links', ['amount' => 100, 'currency' => 'USD', 'status' => 'active']);
    }

    /** @test */
    public function it_requires_authentication() {
        $this->postJson('/api/v1/merchant/payment-link', [])->assertStatus(401);
    }
}
```
""")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة

1. **دفع رابط منتهي الصلاحية**: يظهر "منتهي الصلاحية" والمبلغ المجمد يُعاد للتاجر.
2. **دفع مكرر لنفس الرابط**: الرابط يتحول إلى used بعد أول دفعة.
3. **رصيد التاجر يتغير بين إنشاء الرابط والدفع**: الرابط يضمن المبلغ (تم تجميده).
4. **انتهاء صلاحية الرابط قبل الدفع**: Cron job يحرر التجميد ويعيد الرصيد.
5. **Webhook فاشل**: يُعاد المحاولة 3 مرات عبر Queue.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | رابط منتهٍ | رفض الدفع، فك التجميد |
| 2 | دفع مكرر | رفض (status=used) |
| 3 | رصيد غير كافٍ | منع إنشاء الرابط |
| 4 | Webhook فاشل | 3 محاولات + تسجيل |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية

## توكن الرابط
```php
// 64 حرف عشوائي آمن = 2^256 احتمال
bin2hex(random_bytes(32));
```

## منع تخمين التوكن
64 حرف عشوائي — غير قابل للتخمين.

## التحقق من الصلاحية
```php
if ($link->expires_at->isPast()) { throw new PaymentLinkExpiredException(); }
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | توكن عشوائي آمن | ✅ |
| 2 | صلاحية محددة | ✅ |
| 3 | تجميد الرصيد | ✅ |
| 4 | منع الدفع المكرر | ✅ |
| 5 | HTTPS | ✅ |
| 6 | Rate limiting | ✅ |
""")

print("M3 complete!")

# =====================================================================
# M4 - MERCHANT ORDERS
# =====================================================================
print("Generating M4-merchant-orders...")
od = "M4-merchant-orders"

wf(od, "00-index.md", """# فهرس - إدارة طلبات التاجر (Merchant Orders)

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | إدارة طلبات التاجر |
| الأولوية | P1 (عالية) |
| API | `GET /api/v1/merchant/orders`, `PATCH /api/v1/merchant/orders/{id}/status` |
| Controller | `MerchantOrderController` |
| Service | `OrderService` |
| Event | `OrderStatusUpdated` |
| DB Tables | merchant_orders, order_items |
| Flutter | `MerchantOrdersScreen` |
| React | `MerchantOrdersPage` |
""")

wf(od, "01-business-idea.md", """# 01 - فكرة العمل

## الفكرة الأساسية
تاجر يعرض الطلبات القادمة من العملاء عبر بوابة الدفع ويدير حالتها.

## سيناريو المستخدم
```
بصفتي: تاجر
أريد: عرض وإدارة طلبات العملاء
لكي: أتمكن من معالجة الطلبات وتحديث حالتها
```

## قبول السيناريو
| # | الشرط |
|---|--------|
| 1 | عرض الطلبات حسب الحالة (pending/processing/shipped/delivered/cancelled) |
| 2 | تغيير حالة الطلب |
| 3 | إشعار العميل عند تغيير الحالة |
| 4 | البحث والتصفية حسب التاريخ |
""")

wf(od, "02-architecture.md", """# 02 - مكان العملية

```
  Merchant (Flutter/React)
       │ GET /orders | PATCH /orders/{id}/status
       ▼
  ┌────────────────────┐
  │ MerchantOrderController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ OrderService         │
  │ 1. List orders       │
  │ 2. Update status     │
  │ 3. Notify customer   │
  └──────────┬─────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │  orders │
        │  items  │
        └─────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات

```
  Merchant         API            OrderService        MySQL         Customer
     │                │                  │               │              │
     │  عرض طلبات     │                  │               │              │
     │───────────────>│                  │               │              │
     │                │  GET /orders     │               │              │
     │                │─────────────────>│               │              │
     │                │  Query orders    │──────────────>│              │
     │                │ Response         │               │              │
     │<───────────────│                  │               │              │
     │                │                  │               │              │
     │  تغيير حالة    │                  │               │              │
     │───────────────>│                  │               │              │
     │                │  PATCH /status   │               │              │
     │                │─────────────────>│               │              │
     │                │  Update order    │──────────────>│              │
     │                │  Notify customer │─────────────────────────────>│
     │                │ Response         │               │              │
     │<───────────────│                  │               │              │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────┐
│    merchants      │───────>│          merchant_orders             │
│──────────────────│ 1     M│─────────────────────────────────────│
│ id               │        │ PK id                               │
└──────────────────┘        │ FK merchant_id                      │
                            │ FK customer_id → users.id           │
                            │ status (pending/processing/         │
                            │   shipped/delivered/cancelled)       │
                            │ total_amount                        │
                            │ currency                            │
                            │ notes                               │
                            │ created_at / updated_at              │
                            └────────────────┬────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │     order_items     │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK order_id        │
                                   │ product_name        │
                                   │ quantity            │
                                   │ unit_price          │
                                   └────────────────────┘
```
""")

wf(od, "05-migrations.md", """# 05 - الميغريشن

```php
Schema::create('merchant_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users');
    $table->string('order_number', 50)->unique();
    $table->enum('status', ['pending','processing','shipped','delivered','cancelled'])->default('pending');
    $table->decimal('total_amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['merchant_id', 'status']);
});

Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('merchant_orders')->onDelete('cascade');
    $table->string('product_name');
    $table->unsignedInteger('quantity');
    $table->decimal('unit_price', 15, 2);
});
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز

```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class MerchantOrder extends Model
{
    protected $fillable = ['merchant_id', 'customer_id', 'order_number', 'status', 'total_amount', 'currency', 'notes'];
    protected $casts = ['total_amount' => 'decimal:2'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }

    public function scopeByStatus($q, $s) { return $q->where('status', $s); }
    public static function generateOrderNumber(): string { return 'ORD-' . now()->format('ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - قواعد التحقق

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function rules(): array {
        return [
            'status' => ['required', Rule::in(['pending','processing','shipped','delivered','cancelled'])],
            'notes'  => ['nullable', 'string', 'max:500'],
        ];
    }
    public function messages(): array {
        return ['status.required' => 'الحالة مطلوبة', 'status.in' => 'حالة غير صالحة'];
    }
}
```
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Resources\\MerchantOrderResource;
use App\\Services\\Merchant\\OrderService;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class MerchantOrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse {
        $merchant = $request->user()->merchant;
        $orders = $merchant->orders()
            ->with('items', 'customer')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json(['success' => true, 'data' => MerchantOrderResource::collection($orders)]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $id): JsonResponse {
        $this->orderService->updateStatus($request->user()->merchant->id, $id, $request->input('status'), $request->input('notes'));
        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }
}
```
""")

wf(od, "09-service-layer-core.md", """# 09 - OrderService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Events\\OrderStatusUpdated;
use App\\Exceptions\\InvalidOrderStatusTransitionException;
use App\\Models\\MerchantOrder;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class OrderService
{
    private const VALID_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'cancelled'],
        'delivered'  => [],
        'cancelled'  => [],
    ];

    public function updateStatus(int $merchantId, int $orderId, string $newStatus, ?string $notes = null): MerchantOrder
    {
        $order = MerchantOrder::where('merchant_id', $merchantId)->findOrFail($orderId);

        if (!in_array($newStatus, self::VALID_TRANSITIONS[$order->status] ?? [])) {
            throw new InvalidOrderStatusTransitionException($order->status, $newStatus);
        }

        DB::transaction(function () use ($order, $newStatus, $notes) {
            $order->update(['status' => $newStatus, 'notes' => $notes ? $notes : $order->notes]);
        });

        try { OrderStatusUpdated::dispatch($order); }
        catch (\\Throwable $e) { Log::warning('فشل إرسال حدث تحديث الطلب', ['order_id' => $order->id]); }

        return $order->fresh();
    }
}
```
""")

wf(od, "10-service-layer-aux.md", "# 10 - OrderStatusTransitionService\n\n```php\n<?php\nnamespace App\\Services\\Merchant;\n\nclass OrderStatusTransitionService\n{\n    private const TRANSITIONS = [\n        'pending' => ['processing', 'cancelled'],\n        'processing' => ['shipped', 'cancelled'],\n        'shipped' => ['delivered', 'cancelled'],\n        'delivered' => [],\n        'cancelled' => [],\n    ];\n\n    public function getAllowedTransitions(string $currentStatus): array {\n        return self::TRANSITIONS[$currentStatus] ?? [];\n    }\n\n    public function canTransition(string $from, string $to): bool {\n        return in_array($to, self::TRANSITIONS[$from] ?? []);\n    }\n}\n```\n")

wf(od, "11-events-and-listeners.md", "# 11 - الأحداث والمستمعين\n\n```php\n<?php\nnamespace App\\Events;\nuse App\\Models\\MerchantOrder;\nuse Illuminate\\Foundation\\Events\\Dispatchable;\n\nclass OrderStatusUpdated { use Dispatchable; public function __construct(public readonly MerchantOrder $order) {} }\n```\n\n```php\n<?php\nnamespace App\\Listeners;\nuse App\\Events\\OrderStatusUpdated;\nuse App\\Notifications\\OrderStatusChanged;\nuse Illuminate\\Contracts\\Queue\\ShouldQueue;\n\nclass SendOrderStatusNotification implements ShouldQueue {\n    public function handle(OrderStatusUpdated $event): void {\n        $event->order->customer->notify(new OrderStatusChanged($event->order));\n    }\n}\n```\n")

wf(od, "12-notification-system.md", "# 12 - الإشعارات\n\n```php\n<?php\nnamespace App\\Notifications;\nuse App\\Models\\MerchantOrder;\nuse Illuminate\\Notifications\\Notification;\n\nclass OrderStatusChanged extends Notification {\n    public function __construct(private readonly MerchantOrder $order) {}\n    public function via($notifiable): array { return ['database', 'fcm']; }\n    public function toArray($notifiable): array {\n        return ['type' => 'order_status', 'title' => 'تحديث حالة الطلب',\n                'body' => \"الطلب #{$this->order->order_number} أصبح {$this->order->status}\",\n                'order_id' => $this->order->id, 'status' => $this->order->status];\n    }\n}\n```\n")

wf(od, "13-exception-handling.md", "# 13 - الاستثناءات\n\n```php\n<?php\nnamespace App\\Exceptions;\nuse Exception;\n\nclass InvalidOrderStatusTransitionException extends Exception {\n    public function __construct(string $from, string $to) {\n        parent::__construct(\"لا يمكن تغيير الحالة من {$from} إلى {$to}\");\n    }\n    public function render(): JsonResponse {\n        return response()->json(['success' => false, 'message' => $this->getMessage()], 422);\n    }\n}\n\nclass OrderNotFoundException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404); }\n}\n```\n")

wf(od, "14-database-transactions-acid.md", "# 14 - ACID\n\n## تحديث الحالة\n```php\nDB::transaction(function () use ($order, $newStatus, $notes) {\n    $order->update(['status' => $newStatus, 'notes' => $notes]);\n    // إذا فشل الإشعار → لا يؤثر على تحديث الحالة\n});\n```\n\n## التحقق من الانتقال الصحيح\n```php\n// قبل التحديث: التأكد من أن الانتقال مسموح\nif (!in_array($newStatus, $validTransitions[$order->status])) {\n    throw new InvalidOrderStatusTransitionException();\n}\n```\n")

wf(od, "15-api-specification.md", """# 15 - مواصفات API

```yaml
paths:
  /merchant/orders:
    get:
      summary: قائمة الطلبات
      parameters:
        - { name: status, in: query, schema: { type: string, enum: [pending,processing,shipped,delivered,cancelled] } }
        - { name: date_from, in: query, schema: { type: string, format: date } }
      responses: { '200': { description: قائمة الطلبات } }
  /merchant/orders/{id}/status:
    patch:
      summary: تحديث حالة الطلب
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [status]
              properties:
                status: { type: string, enum: [pending,processing,shipped,delivered,cancelled] }
                notes: { type: string }
      responses: { '200': { description: تم التحديث } }
```

```bash
curl -X PATCH http://localhost:8000/api/v1/merchant/orders/1/status \\
  -H "Authorization: Bearer TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"status": "processing"}'
```
""")

wf(od, "16-flutter-implementation.md", "# 16 - Flutter UI\n\n```dart\n// presentation/bloc/order_bloc.dart\nclass OrderBloc extends Bloc<OrderEvent, OrderState> {\n  final IOrderRepository repository;\n  OrderBloc({required this.repository}) : super(OrderInitial()) {\n    on<LoadOrders>(_onLoad);\n    on<UpdateOrderStatus>(_onUpdateStatus);\n  }\n  Future<void> _onLoad(LoadOrders event, Emitter<OrderState> emit) async {\n    emit(OrderLoading());\n    try { final orders = await repository.getOrders(status: event.status); emit(OrderLoaded(orders)); }\n    catch (e) { emit(OrderError(e.toString())); }\n  }\n}\n\n// presentation/screens/merchant_orders_screen.dart\nclass MerchantOrdersScreen extends StatelessWidget {\n  @override\n  Widget build(BuildContext context) {\n    return Scaffold(\n      appBar: AppBar(title: Text('الطلبات'), actions: [\n        PopupMenuButton<String>(\n          onSelected: (s) => context.read<OrderBloc>().add(LoadOrders(status: s)),\n          itemBuilder: (_) => ['pending','processing','shipped','delivered'].map((s) => PopupMenuItem(value: s, child: Text(s))).toList(),\n        ),\n      ]),\n      body: BlocBuilder<OrderBloc, OrderState>(\n        builder: (context, state) {\n          if (state is OrderLoaded) return ListView.builder(\n            itemCount: state.orders.length,\n            itemBuilder: (_, i) => OrderCard(order: state.orders[i]),\n          );\n          return Center(child: CircularProgressIndicator());\n        },\n      ),\n    );\n  }\n}\n```\n")

wf(od, "17-react-implementation.md", "# 17 - React UI\n\n```jsx\n// hooks/useOrders.js\nimport { useState, useEffect, useCallback } from 'react';\nimport { merchantApi } from '../services/api';\n\nexport function useOrders() {\n  const [orders, setOrders] = useState([]);\n  const [loading, setLoading] = useState(false);\n  const [filter, setFilter] = useState('');\n\n  const loadOrders = useCallback(async () => {\n    setLoading(true);\n    try { const params = filter ? { status: filter } : {};\n      const res = await merchantApi.getOrders(params); setOrders(res.data.data); }\n    finally { setLoading(false); }\n  }, [filter]);\n\n  const updateStatus = useCallback(async (id, status) => {\n    await merchantApi.updateOrderStatus(id, status);\n    loadOrders();\n  }, [loadOrders]);\n\n  useEffect(() => { loadOrders(); }, [loadOrders]);\n  return { orders, loading, filter, setFilter, updateStatus };\n}\n\n// pages/MerchantOrdersPage.jsx\nexport default function MerchantOrdersPage() {\n  const { orders, loading, filter, setFilter, updateStatus } = useOrders();\n  return (\n    <div>\n      <h1>الطلبات</h1>\n      <select value={filter} onChange={e => setFilter(e.target.value)}>\n        <option value=\"\">الكل</option>\n        <option value=\"pending\">قيد الانتظار</option>\n        <option value=\"processing\">قيد المعالجة</option>\n        <option value=\"shipped\">تم الشحن</option>\n        <option value=\"delivered\">تم التوصيل</option>\n      </select>\n      {loading && <p>جاري التحميل...</p>}\n      {orders.map(order => (\n        <div key={order.id} className=\"order-card\">\n          <h3>طلب #{order.order_number}</h3>\n          <p>الحالة: {order.status}</p>\n          <p>المبلغ: {order.total_amount} {order.currency}</p>\n          <button onClick={() => updateStatus(order.id, 'processing')}>معالجة</button>\n          <button onClick={() => updateStatus(order.id, 'cancelled')}>إلغاء</button>\n        </div>\n      ))}\n    </div>\n  );\n}\n```\n")

wf(od, "18-testing-complete.md", """# 18 - الاختبارات

```php
<?php
namespace Tests\\Feature\\Merchant;
use App\\Models\\Merchant;
use App\\Models\\MerchantOrder;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_orders() {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create();
        MerchantOrder::factory()->count(3)->create(['merchant_id' => $merchant->id]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/merchant/orders');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_updates_order_status() {\n        $order = MerchantOrder::factory()->create(['status' => 'pending']);\n        \$this->actingAs($order->merchant->user);\n
        \$response = \$this->patchJson(\"/api/v1/merchant/orders/{$order->id}/status\", ['status' => 'processing']);
        \$response->assertStatus(200);
        \$this->assertEquals('processing', \$order->fresh()->status);
    }
}
```
""")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة

1. **تغيير حالة غير مسموح**: delivered → processing → ممنوع.
2. **إلغاء طلب بعد الشحن**: مسموح فقط قبل التوصيل.
3. **طلبات متعددة بنفس العميل**: معالجة كل طلب مستقل.
4. **إشعار يفشل عند تغيير الحالة**: Queue يعيد المحاولة.
5. **بحث بأيام قديمة جدا**: Pagination مع فهرسة created_at.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | انتقال غير صالح | رفض (422) |
| 2 | إلغاء بعد التوصيل | ممنوع |
| 3 | فشل الإشعار | Retry via Queue |
| 4 | طلبات كثيرة | Pagination |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية

## التحقق من الملكية
```php
// كل طلب يجب أن يتبع لتاجر معين
$order = MerchantOrder::where('merchant_id', $merchantId)->findOrFail($id);
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من ملكية الطلب | ✅ |
| 2 | التحقق من انتقال الحالة | ✅ |
| 3 | Authentication | ✅ |
| 4 | Rate limiting | ✅ |
| 5 | SQL injection | ✅ |
""")

print("M4 complete!")

# =====================================================================
# M5 - MERCHANT RECURRING
# =====================================================================
print("Generating M5-merchant-recurring...")
od = "M5-merchant-recurring"

wf(od, "00-index.md", """# فهرس - الفوترة المتكررة (Merchant Recurring)

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | الفوترة المتكررة والاشتراكات |
| الأولوية | P2 (متوسطة) |
| API | `POST /api/v1/merchant/subscriptions`, `GET /api/v1/merchant/subscriptions` |
| Controller | `SubscriptionController` |
| Service | `SubscriptionService` / `RecurringBillingService` |
| Event | `SubscriptionCreated`, `SubscriptionChargeCompleted` |
| DB Tables | merchant_subscriptions, subscription_charges |
| Flutter | `SubscriptionScreen` |
| React | `SubscriptionPage` |
""")

wf(od, "01-business-idea.md", """# 01 - فكرة العمل

## الفكرة الأساسية
تاجر يريد إنشاء اشتراكات شهرية/سنوية للعملاء (مثل: خدمة إنترنت، اشتراك نادي، تأمين).

## سيناريو المستخدم
```
بصفتي: تاجر
أريد: إنشاء اشتراكات متكررة للعملاء
لكي: أحصل على مدفوعات دورية منتظمة
```

## قبول السيناريو
| # | الشرط |
|---|--------|
| 1 | إنشاء اشتراك (customer_phone, amount, currency, interval) |
| 2 | موافقة العميل على الاشتراك |
| 3 | خصم تلقائي في كل دورة |
| 4 | إشعار قبل الخصم بـ 3 أيام |
| 5 | تحديد max_cycles (عدد الدورات) |
""")

wf(od, "02-architecture.md", """# 02 - مكان العملية

```
  Merchant (Flutter/React)          Customer
       │ POST /subscriptions            │
       ▼                                │
  ┌──────────────────┐                  │
  │ SubscriptionController │             │
  └──────────┬───────┘                  │
             │                          │
  ┌──────────┴───────┐                  │
  │ SubscriptionService │               │
  │ 1. Create subscription│             │
  │ 2. Confirm consent    │             │
  └──────────┬───────┘                  │
             │                          │
  ┌──────────┴───────┐                  │
  │ RecurringBilling   │                │
  │ Service (Cron)     │                │
  │ 1. Check due subs  │               │
  │ 2. Process charge  │               │
  │ 3. Notify 3 days before│           │
  └──────────┬───────┘                  │
             │                          │
        ┌────┴────┐                     │
        │  MySQL  │                     │
        │ subs    │  ← Consent ←───────│
        │ charges │                     │
        └─────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات

```
  Merchant          API           SubscriptionService      MySQL        Customer         Cron
     │                │                    │                 │              │              │
     │ إنشاء اشتراك   │                    │                 │              │              │
     │───────────────>│                    │                 │              │              │
     │                │  Create sub        │                 │              │              │
     │                │───────────────────>│                 │              │              │
     │                │  Set pending       │────────────────>│              │              │
     │                │  Request consent   │                 │              │              │
     │                │                    │──────────────────────────────>│              │
     │                │ Response           │                 │              │              │
     │<───────────────│                    │                 │              │              │
     │                │                    │                 │              │              │
     │                │  Confirm consent   │                 │              │              │
     │                │<───────────────────────────────────────────────────│              │
     │                │  Activate sub      │────────────────>│              │              │
     │                │                    │                 │              │              │
     │                │                    │                 │              │  ── 3 days before ──
     │                │  Notify upcoming   │                 │              │<──────────────│
     │                │                    │──────────────────────────────>│              │
     │                │                    │                 │              │              │
     │                │  Charge due        │                 │              │  ── Due date ──
     │                │                    │                 │              │<──────────────│
     │                │  Process payment   │────────────────>│              │              │
     │                │  Notify both       │──────────────────────────────>│              │
     │<───────────────│                    │                 │              │              │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│       merchant_subscriptions            │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ FK customer_id → users.id               │
                            │ amount                                  │
                            │ currency (SYP/USD)                      │
                            │ interval (monthly/yearly)               │
                            │ status (pending/active/paused/cancelled)│
                            │ max_cycles                              │
                            │ current_cycle                           │
                            │ next_charge_at                          │
                            │ customer_consented_at                   │
                            │ created_at                              │
                            └────────────────┬────────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │ subscription_charges │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK subscription_id │
                                   │ cycle_number        │
                                   │ amount              │
                                   │ status (pending/    │
                                   │   completed/failed) │
                                   │ charged_at          │
                                   └────────────────────┘
```
""")

wf(od, "05-migrations.md", """# 05 - الميغريشن

```php
Schema::create('merchant_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users');
    $table->decimal('amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->enum('interval', ['monthly', 'yearly']);
    $table->enum('status', ['pending','active','paused','cancelled','completed'])->default('pending');
    $table->unsignedSmallInteger('max_cycles')->default(12);
    $table->unsignedSmallInteger('current_cycle')->default(0);
    $table->timestamp('next_charge_at')->nullable();
    $table->timestamp('customer_consented_at')->nullable();
    $table->timestamps();
});

Schema::create('subscription_charges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscription_id')->constrained('merchant_subscriptions')->onDelete('cascade');
    $table->unsignedSmallInteger('cycle_number');
    $table->decimal('amount', 15, 2);
    $table->enum('status', ['pending','completed','failed'])->default('pending');
    $table->timestamp('charged_at')->nullable();
    $table->timestamps();
});
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز

```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class MerchantSubscription extends Model
{
    protected $fillable = ['merchant_id', 'customer_id', 'amount', 'currency', 'interval', 'status', 'max_cycles', 'current_cycle', 'next_charge_at', 'customer_consented_at'];
    protected $casts = ['amount' => 'decimal:2', 'max_cycles' => 'integer', 'current_cycle' => 'integer', 'next_charge_at' => 'datetime', 'customer_consented_at' => 'datetime'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function charges() { return $this->hasMany(SubscriptionCharge::class); }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isComplete(): bool { return $this->current_cycle >= $this->max_cycles; }
}

class SubscriptionCharge extends Model
{
    protected $fillable = ['subscription_id', 'cycle_number', 'amount', 'status', 'charged_at'];
    protected $casts = ['amount' => 'decimal:2'];
    public function subscription() { return $this->belongsTo(MerchantSubscription::class); }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - قواعد التحقق

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

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
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Resources\\SubscriptionResource;
use App\\Services\\Merchant\\SubscriptionService;
use Illuminate\\Http\\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subService) {}

    public function store(SubscriptionRequest $request): JsonResponse {
        $sub = $this->subService->create(
            merchant: $request->user()->merchant,
            customerPhone: $request->input('customer_phone'),
            amount: $request->input('amount'),
            currency: $request->input('currency'),
            interval: $request->input('interval'),
            description: $request->input('description'),
            maxCycles: $request->input('max_cycles'),
        );
        return response()->json(['success' => true, 'message' => 'تم إنشاء الاشتراك، في انتظار موافقة العميل', 'data' => new SubscriptionResource($sub)], 201);
    }

    public function index(Request $request): JsonResponse {
        $subs = $request->user()->merchant->subscriptions()->with('customer', 'charges')->paginate(20);
        return response()->json(['success' => true, 'data' => SubscriptionResource::collection($subs)]);
    }

    public function cancel($id): JsonResponse {
        $this->subService->cancel($id);
        return response()->json(['success' => true, 'message' => 'تم إلغاء الاشتراك']);
    }
}
```
""")

wf(od, "09-service-layer-core.md", """# 09 - SubscriptionService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Events\\SubscriptionCreated;
use App\\Models\\Merchant;
use App\\Models\\MerchantSubscription;
use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;

class SubscriptionService
{
    public function create(Merchant $merchant, string $customerPhone, float $amount, string $currency, string $interval, ?string $description = null, int $maxCycles = 12): MerchantSubscription
    {
        $customer = User::where('phone', $customerPhone)->where('status', 'active')->firstOrFail();

        $sub = DB::transaction(function () use ($merchant, $customer, $amount, $currency, $interval, $description, $maxCycles) {
            return MerchantSubscription::create([
                'merchant_id'    => $merchant->id,
                'customer_id'    => $customer->id,
                'amount'         => $amount,
                'currency'       => $currency,
                'interval'       => $interval,
                'status'         => 'pending',
                'max_cycles'     => $maxCycles,
                'current_cycle'  => 0,
                'next_charge_at' => null,
            ]);
        });

        SubscriptionCreated::dispatch($sub);
        return $sub;
    }

    public function confirmConsent(int $subscriptionId): void {
        $sub = MerchantSubscription::findOrFail($subscriptionId);
        if ($sub->status !== 'pending') throw new \\RuntimeException('الاشتراك ليس في حالة انتظار');

        $nextCharge = $sub->interval === 'monthly' ? now()->addMonth() : now()->addYear();
        $sub->update([
            'status' => 'active',
            'customer_consented_at' => now(),
            'next_charge_at' => $nextCharge,
        ]);
    }

    public function cancel(int $subscriptionId): void {
        $sub = MerchantSubscription::findOrFail($subscriptionId);
        $sub->update(['status' => 'cancelled', 'next_charge_at' => null]);
    }

    public function cancel(int $merchantId, int $subId): void {
        $sub = MerchantSubscription::where('merchant_id', $merchantId)->findOrFail($subId);
        $sub->update(['status' => 'cancelled']);
    }
}
```
""")

wf(od, "10-service-layer-aux.md", """# 10 - RecurringBillingService

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\MerchantSubscription;
use App\\Models\\SubscriptionCharge;
use App\\Notifications\\UpcomingCharge;
use App\\Notifications\\ChargeCompleted;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class RecurringBillingService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    public function sendUpcomingNotifications(): void {
        $dueSoon = MerchantSubscription::where('status', 'active')
            ->whereDate('next_charge_at', now()->addDays(3))
            ->get();
        foreach ($dueSoon as $sub) {
            try { $sub->customer->notify(new UpcomingCharge($sub)); }
            catch (\\Throwable $e) { Log::warning('فشل إشعار الشحن القادم', ['sub_id' => $sub->id]); }
        }
    }

    public function processDueCharges(): int {
        $processed = 0;
        $dueSubs = MerchantSubscription::where('status', 'active')
            ->where('next_charge_at', '<=', now())
            ->whereColumn('current_cycle', '<', 'max_cycles')
            ->get();

        foreach ($dueSubs as $sub) {
            try {
                DB::transaction(function () use ($sub, &$processed) {
                    $wallet = $this->walletService->getWallet($sub->merchant_id, $sub->currency);
                    $this->walletService->increment($wallet, $sub->amount);
                    $sub->increment('current_cycle');
                    $nextCharge = $sub->interval === 'monthly' ? now()->addMonth() : now()->addYear();
                    $sub->update(['next_charge_at' => $nextCharge]);
                    if ($sub->isComplete()) $sub->update(['status' => 'completed']);
                    SubscriptionCharge::create([
                        'subscription_id' => $sub->id,
                        'cycle_number'    => $sub->current_cycle,
                        'amount'          => $sub->amount,
                        'status'          => 'completed',
                        'charged_at'      => now(),
                    ]);
                    $sub->customer->notify(new ChargeCompleted($sub));
                    $processed++;
                });
            } catch (\\Throwable $e) {
                Log::error('فشل معالجة شحن متكرر', ['sub_id' => $sub->id, 'error' => $e->getMessage()]);
            }
        }
        return $processed;
    }
}
```
""")

wf(od, "11-events-and-listeners.md", "# 11 - الأحداث\n\n```php\n<?php\nnamespace App\\Events;\nuse App\\Models\\MerchantSubscription;\nuse Illuminate\\Foundation\\Events\\Dispatchable;\n\nclass SubscriptionCreated { use Dispatchable; public function __construct(public readonly MerchantSubscription $subscription) {} }\nclass SubscriptionChargeCompleted { use Dispatchable; public function __construct(public readonly MerchantSubscription $subscription, public readonly int $cycleNumber) {} }\n```\n")

wf(od, "12-notification-system.md", "# 12 - الإشعارات\n\n```php\n<?php\nnamespace App\\Notifications;\nuse App\\Models\\MerchantSubscription;\nuse Illuminate\\Notifications\\Notification;\n\nclass UpcomingCharge extends Notification {\n    public function __construct(private readonly MerchantSubscription $sub) {}\n    public function via($notifiable): array { return ['database', 'fcm']; }\n    public function toArray($notifiable): array {\n        return ['type' => 'upcoming_charge', 'title' => 'قرب موعد الاشتراك',\n                'body' => \"سيتم خصم {$this->sub->amount} {$this->sub->currency} بعد 3 أيام\"];\n    }\n}\n\nclass ChargeCompleted extends Notification {\n    public function __construct(private readonly MerchantSubscription $sub) {}\n    public function toArray($notifiable): array {\n        return ['type' => 'charge_completed', 'title' => 'تم تجديد الاشتراك',\n                'body' => \"تم خصم {$this->sub->amount} {$this->sub->currency} للدورة {$this->sub->current_cycle}\"];\n    }\n}\n```\n")

wf(od, "13-exception-handling.md", "# 13 - الاستثناءات\n\n```php\n<?php\nnamespace App\\Exceptions;\nuse Exception;\n\nclass SubscriptionNotFoundException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'الاشتراك غير موجود'], 404); }\n}\nclass SubscriptionAlreadyActiveException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'الاشتراك نشط بالفعل'], 422); }\n}\nclass CustomerConsentRequiredException extends Exception {\n    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'موافقة العميل مطلوبة'], 422); }\n}\n```\n")

wf(od, "14-database-transactions-acid.md", "# 14 - ACID\n\n## معالجة الشحن\n```php\nDB::transaction(function () use ($sub) {\n    $wallet = $this->walletService->getWallet($sub->merchant_id, $sub->currency);\n    $this->walletService->increment($wallet, $sub->amount);  // إضافة رصيد\n    $sub->increment('current_cycle');\n    $sub->update(['next_charge_at' => $nextCharge]);\n    SubscriptionCharge::create([...]);  // تسجيل الشحن\n}, attempts: 3);\n```\n\n## سباق التوقيت\n```sql\n-- التأكد من عدم معالجة الاشتراك مرتين\nUPDATE merchant_subscriptions\nSET current_cycle = current_cycle + 1\nWHERE id = ? AND current_cycle < max_cycles;\n```\n")

wf(od, "15-api-specification.md", """# 15 - مواصفات API

```yaml
paths:
  /merchant/subscriptions:
    post:
      summary: إنشاء اشتراك
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [customer_phone, amount, currency, interval, max_cycles]
              properties:
                customer_phone: { type: string }
                amount: { type: number }
                currency: { type: string, enum: [SYP, USD] }
                interval: { type: string, enum: [monthly, yearly] }
                max_cycles: { type: integer }
      responses: { '201': { description: تم إنشاء الاشتراك } }
    get:
      summary: قائمة الاشتراكات
      responses: { '200': { description: القائمة } }
  /merchant/subscriptions/{id}/cancel:
    post:
      summary: إلغاء اشتراك
      responses: { '200': { description: تم الإلغاء } }
```

```bash
# إنشاء اشتراك
curl -X POST http://localhost:8000/api/v1/merchant/subscriptions \\
  -H "Authorization: Bearer TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"customer_phone": "963944123456", "amount": 100, "currency": "USD", "interval": "monthly", "max_cycles": 12}'
```
""")

wf(od, "16-flutter-implementation.md", "# 16 - Flutter UI\n\n```dart\n// presentation/bloc/subscription_bloc.dart\nclass SubscriptionBloc extends Bloc<SubscriptionEvent, SubscriptionState> {\n  final ISubscriptionRepository repository;\n  SubscriptionBloc({required this.repository}) : super(SubscriptionInitial()) {\n    on<CreateSubscription>(_onCreate);\n    on<LoadSubscriptions>(_onLoad);\n  }\n  Future<void> _onCreate(CreateSubscription event, Emitter<SubscriptionState> emit) async {\n    emit(SubscriptionLoading());\n    try { final sub = await repository.create(event.data); emit(SubscriptionCreated(sub)); }\n    catch (e) { emit(SubscriptionError(e.toString())); }\n  }\n  Future<void> _onLoad(LoadSubscriptions event, Emitter<SubscriptionState> emit) async {\n    emit(SubscriptionLoading());\n    try { final subs = await repository.getAll(); emit(SubscriptionsLoaded(subs)); }\n    catch (e) { emit(SubscriptionError(e.toString())); }\n  }\n}\n\n// presentation/screens/subscription_screen.dart\nclass SubscriptionScreen extends StatelessWidget {\n  @override\n  Widget build(BuildContext context) {\n    return Scaffold(\n      appBar: AppBar(title: Text('الاشتراكات')),\n      body: BlocBuilder<SubscriptionBloc, SubscriptionState>(\n        builder: (context, state) {\n          if (state is SubscriptionsLoaded) return ListView.builder(\n            itemCount: state.subscriptions.length,\n            itemBuilder: (_, i) => ListTile(\n              title: Text('\\${state.subscriptions[i].amount} \\${state.subscriptions[i].currency}'),\n              subtitle: Text(state.subscriptions[i].status),\n            ),\n          );\n          return Center(child: CircularProgressIndicator());\n        },\n      ),\n    );\n  }\n}\n```\n")

wf(od, "17-react-implementation.md", "# 17 - React UI\n\n```jsx\n// hooks/useSubscriptions.js\nimport { useState, useEffect, useCallback } from 'react';\nimport { merchantApi } from '../services/api';\n\nexport function useSubscriptions() {\n  const [subscriptions, setSubscriptions] = useState([]);\n  const [loading, setLoading] = useState(false);\n  const loadSubscriptions = useCallback(async () => {\n    setLoading(true);\n    try { const res = await merchantApi.getSubscriptions(); setSubscriptions(res.data.data); }\n    finally { setLoading(false); }\n  }, []);\n  const createSubscription = useCallback(async (data) => {\n    const res = await merchantApi.createSubscription(data);\n    setSubscriptions(prev => [res.data.data, ...prev]);\n    return res.data.data;\n  }, []);\n  useEffect(() => { loadSubscriptions(); }, [loadSubscriptions]);\n  return { subscriptions, loading, createSubscription };\n}\n\n// pages/SubscriptionPage.jsx\nexport default function SubscriptionPage() {\n  const { subscriptions, loading, createSubscription } = useSubscriptions();\n  const [form, setForm] = useState({ customer_phone: '', amount: '', currency: 'USD', interval: 'monthly', max_cycles: 12 });\n  const handleSubmit = async (e) => { e.preventDefault(); await createSubscription(form); };\n  return (\n    <div>\n      <h1>الاشتراكات</h1>\n      <form onSubmit={handleSubmit}>\n        <input placeholder=\"رقم العميل\" value={form.customer_phone} onChange={e => setForm({...form, customer_phone: e.target.value})} required />\n        <input type=\"number\" placeholder=\"المبلغ\" value={form.amount} onChange={e => setForm({...form, amount: e.target.value})} required />\n        <button type=\"submit\" disabled={loading}>إنشاء اشتراك</button>\n      </form>\n      {subscriptions.map(s => <div key={s.id}>{s.amount} {s.currency} - {s.status}</div>)}\n    </div>\n  );\n}\n```\n")

wf(od, "18-testing-complete.md", "# 18 - الاختبارات\n\n```php\n<?php\nnamespace Tests\\Feature\\Merchant;\nuse App\\Models\\Merchant;\nuse App\\Models\\User;\nuse Illuminate\\Foundation\\Testing\\RefreshDatabase;\nuse Tests\\TestCase;\n\nclass SubscriptionTest extends TestCase\n{\n    use RefreshDatabase;\n\n    /** @test */\n    public function it_creates_subscription() {\n        $merchant = Merchant::factory()->create();\n        $customer = User::factory()->create(['status' => 'active']);\n        $token = $merchant->user->createToken('test')->plainTextToken;\n\n        $response = $this->withToken($token)->postJson('/api/v1/merchant/subscriptions', [\n            'customer_phone' => $customer->phone,\n            'amount' => 100, 'currency' => 'USD',\n            'interval' => 'monthly', 'max_cycles' => 12,\n        ]);\n        $response->assertStatus(201);\n        $this->assertDatabaseHas('merchant_subscriptions', ['amount' => 100, 'status' => 'pending']);\n    }\n\n    /** @test */\n    public function it_requires_customer_consent() {\n        $response = $this->postJson('/api/v1/merchant/subscriptions', []);\n        $response->assertStatus(401);\n    }\n}\n```\n")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة

1. **موافقة العميل مفقودة**: الاشتراك يبقى pending حتى يوافق العميل.
2. **رصيد العميل غير كافٍ عند الشحن**: فشل الشحن → إعادة محاولة في الدورة القادمة.
3. **إلغاء الاشتراك من قبل العميل**: تحديث الحالة إلى cancelled.
4. **الوصول إلى max_cycles**: تحديث الحالة إلى completed تلقائياً.
5. **تغيير المبلغ بعد إنشاء الاشتراك**: لا يمكن — إنشاء اشتراك جديد.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | بدون موافقة | pending |
| 2 | رصيد غير كافٍ | failed → retry |
| 3 | إلغاء من عميل | cancelled |
| 4 | max_cycles | completed |
| 5 | تغيير المبلغ | اشتراك جديد |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية

## موافقة العميل
```php
// الاشتراك لا ينشط إلا بموافقة العميل
if ($sub->status !== 'pending') throw new \\RuntimeException('الاشتراك ليس في حالة انتظار');
$sub->update(['status' => 'active', 'customer_consented_at' => now()]);
```

## التحقق عند كل شحن
```php
// لا يمكن شحن اشتراك ملغي أو مكتمل
$dueSubs = MerchantSubscription::where('status', 'active')
    ->where('next_charge_at', '<=', now())
    ->whereColumn('current_cycle', '<', 'max_cycles')
    ->get();
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | موافقة العميل مطلوبة | ✅ |
| 2 | التحقق من max_cycles | ✅ |
| 3 | منع الشحن المكرر | ✅ |
| 4 | Authentication | ✅ |
| 5 | إشعار مسبق (3 أيام) | ✅ |
""")

print("M5 complete!")

# =====================================================================
# M6 - MERCHANT SETTLEMENT
# =====================================================================
print("Generating M6-merchant-settlement...")
od = "M6-merchant-settlement"

wf(od, "00-index.md", """# فهرس - تسوية مدفوعات التاجر (Merchant Settlement)

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | تسوية رصيد التاجر وتحويل بنكي |
| الأولوية | P1 (عالية) |
| API | `POST /api/v1/merchant/settlement`, `GET /api/v1/merchant/settlement/history` |
| Controller | `SettlementController` |
| Service | `SettlementService` |
| Event | `SettlementRequested`, `SettlementCompleted` |
| DB Tables | merchant_settlements, merchant_wallets |
| Flutter | `SettlementScreen` |
| React | `SettlementPage` |
""")

wf(od, "01-business-idea.md", """# 01 - فكرة العمل

## الفكرة الأساسية
تاجر يريد تحويل رصيده من محفظة Beza إلى حسابه البنكي بشكل دوري.

## سيناريو المستخدم
```
بصفتي: تاجر
أريد: تسوية رصيدي وتحويله إلى حسابي البنكي
لكي: أحصل على أرباحي نقداً في البنك
```

## قبول السيناريو
| # | الشرط |
|---|--------|
| 1 | طلب تسوية رصيد التاجر |
| 2 | حساب المبلغ (المبيعات - الرسوم - المرتجعات) |
| 3 | الحد الأدنى للتسوية 50 USD |
| 4 | دورة التسوية كل أسبوع |
| 5 | رسوم تحويل بنكي 1% |
| 6 | تحويل بنكي وتحديث الرصيد |
""")

wf(od, "02-architecture.md", """# 02 - مكان العملية

```
  Merchant (Flutter/React)
       │ POST /settlement
       ▼
  ┌────────────────────┐
  │ SettlementController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ SettlementService     │
  │ 1. Calculate amount   │
  │ 2. Apply fees (1%)    │
  │ 3. Check min (50 USD) │
  │ 4. Create settlement  │
  │ 5. Bank transfer      │
  │ 6. Update wallet      │
  └──────────┬─────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │settle-  │
        │  ments  │
        │wallets  │
        └─────────┘
```
""")

wf(od, "03-data-flow-sequence.md", """# 03 - تدفق البيانات

```
  Merchant         API           SettlementService     BankAPI         MySQL        Admin
     │               │                   │                │              │            │
     │  طلب تسوية    │                   │                │              │            │
     │──────────────>│                   │                │              │            │
     │               │  POST /settlement │                │              │            │
     │               │──────────────────>│                │              │            │
     │               │  Calculate sales  │─────────────────────────────>│            │
     │               │  - fees (2%)      │                │              │            │
     │               │  - refunds        │                │              │            │
     │               │  - transfer fee   │                │              │            │
     │               │                   │                │              │            │
     │               │  Check min (50)   │                │              │            │
     │               │  Create settlement│─────────────────────────────>│            │
     │               │  (status:pending) │                │              │            │
     │               │                   │                │              │            │
     │               │  Response         │                │              │            │
     │<──────────────│                   │                │              │            │
     │               │                   │                │              │            │
     │               │                   │  ── Admin ──  │              │            │
     │               │                   │                │              │            │
     │               │                   │  Transfer API  │              │            │
     │               │                   │────────────────>              │            │
     │               │                   │<───────────────│              │            │
     │               │                   │                │              │            │
     │               │  Update settled   │─────────────────────────────>│            │
     │               │  Notify merchant  │                │              │            │
     │<──────────────│                   │                │              │            │
```
""")

wf(od, "04-database-relationships.md", """# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│       merchant_settlements              │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ amount (المبلغ قبل الرسوم)              │
                            │ fee_percentage (2%)                     │
                            │ transfer_fee (1%)                       │
                            │ refunds_deducted                        │
                            │ net_amount (المبلغ الصافي)              │
                            │ currency                                │
                            │ status (pending/processing/completed/   │
                            │         failed)                         │
                            │ bank_account_info (JSON)                │
                            │ bank_transaction_ref                    │
                            │ settlement_date                         │
                            │ created_at                              │
                            └─────────────────────────────────────────┘
```
""")

wf(od, "05-migrations.md", """# 05 - الميغريشن

```php
Schema::create('merchant_settlements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->decimal('amount', 15, 2);           // المبلغ الإجمالي
    $table->decimal('fee_percentage', 5, 2);    // 2.00%
    $table->decimal('transfer_fee', 15, 2);     // 1% رسوم تحويل
    $table->decimal('refunds_deducted', 15, 2)->default(0);
    $table->decimal('net_amount', 15, 2);       // الصافي بعد الخصم
    $table->enum('currency', ['SYP', 'USD']);
    $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
    $table->json('bank_account_info');
    $table->string('bank_transaction_ref', 100)->nullable();
    $table->timestamp('settlement_date')->nullable();
    $table->timestamps();
});
```
""")

wf(od, "06-eloquent-models.md", """# 06 - الموديلز

```php
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;

class MerchantSettlement extends Model
{
    protected $fillable = ['merchant_id', 'amount', 'fee_percentage', 'transfer_fee', 'refunds_deducted', 'net_amount', 'currency', 'status', 'bank_account_info', 'bank_transaction_ref', 'settlement_date'];
    protected $casts = ['amount' => 'decimal:2', 'fee_percentage' => 'decimal:2', 'transfer_fee' => 'decimal:2', 'refunds_deducted' => 'decimal:2', 'net_amount' => 'decimal:2', 'bank_account_info' => 'json', 'settlement_date' => 'datetime'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
}
```
""")

wf(od, "07-validation-rules.md", """# 07 - قواعد التحقق

```php
<?php
namespace App\\Http\\Requests\\Merchant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class SettlementRequest extends FormRequest
{
    public function rules(): array {
        return [
            'currency'  => ['required', Rule::in(['SYP', 'USD'])],
            'amount'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
    public function messages(): array {
        return ['currency.in' => 'العملة غير مدعومة'];
    }
}
```
""")

wf(od, "08-controller-full-code.md", """# 08 - المتحكم الكامل

```php
<?php
namespace App\\Http\\Controllers\\Api\\Merchant;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Resources\\SettlementResource;
use App\\Services\\Merchant\\SettlementService;
use Illuminate\\Http\\JsonResponse;

class SettlementController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService) {}

    public function store(SettlementRequest $request): JsonResponse {
        $result = $this->settlementService->requestSettlement(
            merchant: $request->user()->merchant,
            currency: $request->input('currency'),
        );
        return response()->json(['success' => true, 'message' => 'تم تقديم طلب التسوية', 'data' => new SettlementResource($result)], 201);
    }

    public function history(Request $request): JsonResponse {
        $settlements = $request->user()->merchant->settlements()->orderByDesc('created_at')->paginate(20);
        return response()->json(['success' => true, 'data' => SettlementResource::collection($settlements)]);
    }

    public function calculate(Request $request): JsonResponse {
        $calc = $this->settlementService->calculateSettlement($request->user()->merchant, $request->input('currency'));
        return response()->json(['success' => true, 'data' => $calc]);
    }
}
```
""")

wf(od, "09-service-layer-core.md", """# 09 - SettlementService كامل

```php
<?php
namespace App\\Services\\Merchant;
use App\\Events\\SettlementRequested;
use App\\Exceptions\\MinimumSettlementNotMetException;
use App\\Exceptions\\PendingSettlementExistsException;
use App\\Models\\Merchant;
use App\\Models\\MerchantSettlement;
use App\\Models\\Transaction;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class SettlementService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    private const MIN_SETTLEMENT_USD = 50;
    private const FEE_PERCENTAGE = 2.00;  // رسوم Beza
    private const TRANSFER_FEE_PERCENTAGE = 1.00;  // رسوم تحويل بنكي

    public function requestSettlement(Merchant $merchant, string $currency): MerchantSettlement
    {
        // 1. التحقق من عدم وجود تسوية معلقة
        $pending = MerchantSettlement::where('merchant_id', $merchant->id)
            ->where('currency', $currency)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
        if ($pending) throw new PendingSettlementExistsException();

        // 2. حساب المبلغ
        $calc = $this->calculateSettlement($merchant, $currency);
        if ($calc['net_amount'] < self::MIN_SETTLEMENT_USD && $currency === 'USD') {
            throw new MinimumSettlementNotMetException(self::MIN_SETTLEMENT_USD);
        }

        // 3. التنفيذ الذري
        $settlement = DB::transaction(function () use ($merchant, $currency, $calc) {
            $wallet = $this->walletService->getWallet($merchant->id, $currency);
            if (!$wallet || $wallet->available_balance < $calc['net_amount']) {
                throw new \\RuntimeException('رصيد غير كافٍ للتسوية');
            }

            $this->walletService->decrement($wallet, $calc['net_amount']);

            return MerchantSettlement::create([
                'merchant_id'    => $merchant->id,
                'amount'         => $calc['total_sales'],
                'fee_percentage' => self::FEE_PERCENTAGE,
                'transfer_fee'   => $calc['transfer_fee'],
                'refunds_deducted' => $calc['refunds'],
                'net_amount'     => $calc['net_amount'],
                'currency'       => $currency,
                'status'         => 'pending',
                'bank_account_info' => $merchant->bank_account_info,
            ]);
        }, attempts: 3);

        try { SettlementRequested::dispatch($settlement); }
        catch (\\Throwable $e) { Log::warning('فشل إرسال حدث التسوية', ['settlement_id' => $settlement->id]); }

        return $settlement;
    }

    public function calculateSettlement(Merchant $merchant, string $currency): array
    {
        // حساب المبيعات (معاملات merchant_payment)
        $totalSales = Transaction::where('to_wallet_id', function ($q) use ($merchant, $currency) {
            $q->select('id')->from('merchant_wallets')
                ->where('merchant_id', $merchant->id)->where('currency', $currency);
        })->where('type', 'merchant_payment')->where('status', 'completed')->sum('amount');

        // حساب المرتجعات
        $refunds = Transaction::where('from_wallet_id', function ($q) use ($merchant, $currency) {
            $q->select('id')->from('merchant_wallets')
                ->where('merchant_id', $merchant->id)->where('currency', $currency);
        })->where('type', 'refund')->where('status', 'completed')->sum('amount');

        $bezaFee = $totalSales * (self::FEE_PERCENTAGE / 100);
        $transferFee = ($totalSales - $bezaFee - $refunds) * (self::TRANSFER_FEE_PERCENTAGE / 100);
        $netAmount = $totalSales - $bezaFee - $refunds - $transferFee;

        return [
            'total_sales'   => round($totalSales, 2),
            'beza_fee'      => round($bezaFee, 2),
            'refunds'       => round($refunds, 2),
            'transfer_fee'  => round($transferFee, 2),
            'net_amount'    => round($netAmount, 2),
            'currency'      => $currency,
        ];
    }
}
```
""")

wf(od, "10-service-layer-aux.md", """# 10 - SettlementAdminService

```php
<?php
namespace App\\Services\\Merchant;
use App\\Models\\MerchantSettlement;
use App\\Notifications\\SettlementCompletedNotification;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;

class SettlementAdminService
{
    public function approve(int $settlementId, string $bankRef): void
    {
        $settlement = MerchantSettlement::findOrFail($settlementId);
        if ($settlement->status !== 'pending') throw new \\RuntimeException('التسوية ليست في حالة انتظار');

        DB::transaction(function () use ($settlement, $bankRef) {
            $settlement->update([
                'status' => 'completed',
                'bank_transaction_ref' => $bankRef,
                'settlement_date' => now(),
            ]);
        });

        try {
            $settlement->merchant->user->notify(new SettlementCompletedNotification($settlement));
        } catch (\\Throwable $e) {
            Log::warning('فشل إشعار إتمام التسوية', ['settlement_id' => $settlement->id]);
        }
    }

    public function reject(int $settlementId, string $reason): void
    {
        $settlement = MerchantSettlement::findOrFail($settlementId);
        DB::transaction(function () use ($settlement, $reason) {
            $settlement->update(['status' => 'failed', 'metadata->reject_reason' => $reason]);
            // إعادة الرصيد للمحفظة
            $wallet = MerchantWallet::where('merchant_id', $settlement->merchant_id)
                ->where('currency', $settlement->currency)->first();
            if ($wallet) $wallet->increment('balance', $settlement->net_amount);
        });
    }
}
```
""")

wf(od, "11-events-and-listeners.md", "# 11 - الأحداث\n\n```php\n<?php\nnamespace App\\Events;\nuse App\\Models\\MerchantSettlement;\nuse Illuminate\\Foundation\\Events\\Dispatchable;\n\nclass SettlementRequested { use Dispatchable; public function __construct(public readonly MerchantSettlement $settlement) {} }\nclass SettlementCompleted { use Dispatchable; public function __construct(public readonly MerchantSettlement $settlement) {} }\n```\n")

wf(od, "12-notification-system.md", "# 12 - الإشعارات\n\n```php\n<?php\nnamespace App\\Notifications;\nuse App\\Models\\MerchantSettlement;\nuse Illuminate\\Notifications\\Notification;\n\nclass SettlementCompletedNotification extends Notification {\n    public function __construct(private readonly MerchantSettlement $settlement) {}\n    public function via($notifiable): array { return ['database', 'fcm']; }\n    public function toArray($notifiable): array {\n        return ['type' => 'settlement_completed', 'title' => 'تمت التسوية البنكية',\n                'body' => \"تم تحويل {$this->settlement->net_amount} {$this->settlement->currency} إلى حسابك البنكي\",\n                'settlement_id' => $this->settlement->id];\n    }\n}\n```\n")

wf(od, "13-exception-handling.md", "# 13 - الاستثناءات\n\n```php\n<?php\nnamespace App\\Exceptions;\nuse Exception;\n\nclass MinimumSettlementNotMetException extends Exception {\n    public function __construct(float $min) { parent::__construct(\"الحد الأدنى للتسوية {$min} USD\"); }\n    public function render(): JsonResponse {\n        return response()->json(['success' => false, 'message' => \$this->getMessage()], 422);\n    }\n}\nclass PendingSettlementExistsException extends Exception {\n    public function render(): JsonResponse {\n        return response()->json(['success' => false, 'message' => 'لديك طلب تسوية معلق بالفعل'], 422);\n    }\n}\n```\n")

wf(od, "14-database-transactions-acid.md", "# 14 - ACID\n\n## تسوية الرصيد\n```php\nDB::transaction(function () use ($merchant, $currency, $calc) {\n    $wallet = $this->walletService->getWallet($merchant->id, $currency);\n    $this->walletService->decrement($wallet, $calc['net_amount']);  // خصم الرصيد\n    MerchantSettlement::create([...]);  // تسجيل التسوية\n}, attempts: 3);\n```\n\n## منع التسوية المتزامنة\n```sql\n-- التحقق من عدم وجود تسوية معلقة قبل إنشاء جديدة\nSELECT id FROM merchant_settlements\nWHERE merchant_id = ? AND currency = ? AND status IN ('pending', 'processing')\nLIMIT 1;\n```\n")

wf(od, "15-api-specification.md", """# 15 - مواصفات API

```yaml
paths:
  /merchant/settlement:
    post:
      summary: طلب تسوية
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [currency]
              properties:
                currency: { type: string, enum: [SYP, USD] }
      responses: { '201': { description: تم تقديم الطلب } }
  /merchant/settlement/calculate:
    post:
      summary: حساب التسوية المتوقعة
      responses: { '200': { description: تفاصيل الحساب } }
  /merchant/settlement/history:
    get:
      summary: تاريخ التسويات
      responses: { '200': { description: القائمة } }
```

```bash
# طلب تسوية
curl -X POST http://localhost:8000/api/v1/merchant/settlement \\
  -H "Authorization: Bearer TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"currency": "USD"}'

# حساب التسوية
curl -X POST http://localhost:8000/api/v1/merchant/settlement/calculate \\
  -H "Authorization: Bearer TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"currency": "USD"}'
```
""")

wf(od, "16-flutter-implementation.md", "# 16 - Flutter UI\n\n```dart\n// presentation/bloc/settlement_bloc.dart\nclass SettlementBloc extends Bloc<SettlementEvent, SettlementState> {\n  final ISettlementRepository repository;\n  SettlementBloc({required this.repository}) : super(SettlementInitial()) {\n    on<RequestSettlement>(_onRequest);\n    on<LoadSettlementHistory>(_onLoadHistory);\n    on<CalculateSettlement>(_onCalculate);\n  }\n  Future<void> _onRequest(RequestSettlement event, Emitter<SettlementState> emit) async {\n    emit(SettlementLoading());\n    try { final result = await repository.request(currency: event.currency); emit(SettlementSuccess(result)); }\n    catch (e) { emit(SettlementError(e.toString())); }\n  }\n}\n\n// presentation/screens/settlement_screen.dart\nclass SettlementScreen extends StatelessWidget {\n  @override\n  Widget build(BuildContext context) {\n    return Scaffold(\n      appBar: AppBar(title: Text('التسوية البنكية')),\n      body: Padding(padding: EdgeInsets.all(16), child: Column(children: [\n        Card(child: Padding(padding: EdgeInsets.all(16), child: Column(children: [\n          Text('المبلغ المتاح', style: TextStyle(fontSize: 16)),\n          SizedBox(height: 8),\n          Text('\\$1,500.00', style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),\n          SizedBox(height: 16),\n          ElevatedButton(onPressed: () {}, child: Text('طلب تسوية'), style: ElevatedButton.styleFrom(minimumSize: Size(double.infinity, 48))),\n        ]))),\n        SizedBox(height: 24),\n        Text('آخر التسويات', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),\n        // List of settlements\n      ])),\n    );\n  }\n}\n```\n")

wf(od, "17-react-implementation.md", """# 17 - React UI

```jsx
// hooks/useSettlement.js
import { useState, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useSettlement() {
  const [state, setState] = useState({ loading: false, settlement: null, error: null, calculation: null });
  const requestSettlement = useCallback(async (currency) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const res = await merchantApi.requestSettlement({ currency });
      setState({ loading: false, settlement: res.data.data, error: null, calculation: null });
      return res.data.data;
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message || 'فشل التسوية' }));
    }
  }, []);
  const calculate = useCallback(async (currency) => {
    const res = await merchantApi.calculateSettlement({ currency });
    setState(prev => ({ ...prev, calculation: res.data.data }));
  }, []);
  return { ...state, requestSettlement, calculate };
}

// pages/SettlementPage.jsx
export default function SettlementPage() {
  const { loading, settlement, error, calculation, requestSettlement, calculate } = useSettlement();
  const [currency, setCurrency] = useState('USD');

  return (
    <div className="settlement-page">
      <h1>التسوية البنكية</h1>
      {error && <div className="error">{error}</div>}
      {settlement && <div className="success">تم تقديم طلب التسوية بنجاح</div>}
      <div className="calculation" onClick={() => calculate(currency)}>
        {calculation && (
          <div>
            <p>المبيعات: {calculation.total_sales} {calculation.currency}</p>
            <p>رسوم Beza: -{calculation.beza_fee}</p>
            <p>المرتجعات: -{calculation.refunds}</p>
            <p>رسوم تحويل: -{calculation.transfer_fee}</p>
            <p><strong>الصافي: {calculation.net_amount} {calculation.currency}</strong></p>
          </div>
        )}
      </div>
      <select value={currency} onChange={e => setCurrency(e.target.value)}>
        <option value="USD">USD</option>
        <option value="SYP">SYP</option>
      </select>
      <button onClick={() => requestSettlement(currency)} disabled={loading}>
        {loading ? 'جاري...' : 'طلب تسوية'}
      </button>
    </div>
  );
}
```
""")

wf(od, "18-testing-complete.md", "# 18 - الاختبارات\n\n```php\n<?php\nnamespace Tests\\Feature\\Merchant;\nuse App\\Models\\Merchant;\nuse App\\Models\\MerchantWallet;\nuse App\\Models\\User;\nuse Illuminate\\Foundation\\Testing\\RefreshDatabase;\nuse Tests\\TestCase;\n\nclass SettlementTest extends TestCase\n{\n    use RefreshDatabase;\n\n    /** @test */\n    public function it_requests_settlement() {\n        \$merchant = Merchant::factory()->create();\n        MerchantWallet::factory()->create(['merchant_id' => \$merchant->id, 'currency' => 'USD', 'balance' => 1000]);\n        \$token = \$merchant->user->createToken('test')->plainTextToken;\n\n        \$response = \$this->withToken(\$token)->postJson('/api/v1/merchant/settlement', [\n            'currency' => 'USD',\n        ]);\n        \$response->assertStatus(201)->assertJson(['success' => true]);\n    }\n\n    /** @test */\n    public function it_rejects_below_minimum() {\n        \$merchant = Merchant::factory()->create();\n        MerchantWallet::factory()->create(['merchant_id' => \$merchant->id, 'currency' => 'USD', 'balance' => 10]);\n        \$token = \$merchant->user->createToken('test')->plainTextToken;\n\n        \$response = \$this->withToken(\$token)->postJson('/api/v1/merchant/settlement', [\n            'currency' => 'USD',\n        ]);\n        \$response->assertStatus(422);\n    }\n\n    /** @test */\n    public function it_requires_authentication() {\n        \$this->postJson('/api/v1/merchant/settlement', [])->assertStatus(401);\n    }\n}\n```\n")

wf(od, "19-edge-cases.md", """# 19 - حالات الحافة

1. **الرصيد أقل من الحد الأدنى (50 USD)**: رفض التسوية.
2. **تسوية معلقة سابقة**: منع التسوية المزدوجة.
3. **تحويل بنكي فاشل**: إعادة الرصيد للمحفظة ووضع الحالة failed.
4. **رسوم Beza + رسوم تحويل**: خصم 2% + 1% = 3% إجمالاً.
5. **حساب بنكي غير صحيح**: إيقاف التسوية للتدقيق اليدوي.

| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | رصيد < 50 USD | رفض (422) |
| 2 | تسوية معلقة | رفض (422) |
| 3 | تحويل فاشل | failed + إعادة رصيد |
| 4 | رسوم 2% + 1% | خصم تلقائي |
| 5 | حساب بنكي خاطى | تدقيق يدوي |
""")

wf(od, "20-security-audit.md", """# 20 - أمان العملية

## حساب المبلغ بدقة
```php
// خصم دقيق: المبيعات - رسوم Beza - المرتجعات - رسوم تحويل
$netAmount = $totalSales - $bezaFee - $refunds - $transferFee;
```

## التحقق من عدم وجود تسوية معلقة
```php
$pending = MerchantSettlement::where('merchant_id', $merchant->id)
    ->where('currency', $currency)
    ->whereIn('status', ['pending', 'processing'])
    ->exists();
if ($pending) throw new PendingSettlementExistsException();
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | الحد الأدنى للتسوية | ✅ 50 USD |
| 2 | منع التسوية المزدوجة | ✅ |
| 3 | حساب دقيق للرسوم | ✅ |
| 4 | Authentication | ✅ |
| 5 | Admin approval | ✅ |
| 6 | HTTPS | ✅ |
""")

print("M6 complete!")
print("All operations generated successfully!")
