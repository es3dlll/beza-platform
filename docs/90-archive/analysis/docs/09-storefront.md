# 9. المتجر الإلكتروني (E-commerce Storefront)

يمكن للتاجر استخدام نظامنا الجاهز (مشابه لـ Shopify) أو دمج بوابتنا عبر API.

## 9.1 واجهة المتجر الجاهزة (Beza Store)

```
storefront/
├── pages/
│   ├── index.js (قائمة المنتجات)
│   ├── product/[id].js (تفاصيل المنتج)
│   ├── cart.js (سلة التسوق)
│   ├── checkout.js (الدفع)
│   └── account/ (حساب العميل)
```

## 9.2 نموذج صفحة المنتج

```jsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import SEO from '../components/SEO';
import api from '../services/api';

export default function ProductPage() {
  const { id } = useParams();
  const { data: product } = useQuery({
    queryKey: ['product', id],
    queryFn: () => api.get(`/store/products/${id}`).then(res => res.data),
  });

  const [quantity, setQuantity] = useState(1);

  const handleAddToCart = () => {
    // إضافة إلى السلة باستخدام context/state
  };

  if (!product) return <div>Loading...</div>;

  return (
    <>
      <SEO title={product.name} description={product.description} />
      <div className="container mx-auto px-4 py-8">
        <div className="grid md:grid-cols-2 gap-8">
          <img src={product.image} className="w-full rounded-2xl" />
          <div>
            <h1 className="text-3xl font-bold mb-4">{product.name}</h1>
            <p className="text-2xl text-indigo-600 mb-4">{product.price} {product.currency}</p>
            <p className="text-gray-600 mb-6">{product.description}</p>
            <div className="flex gap-4">
              <input type="number" min="1" value={quantity} onChange={e => setQuantity(e.target.value)} className="form-input w-24" />
              <button onClick={handleAddToCart} className="btn-primary">إضافة إلى السلة</button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
```
