# 17 - تطبيق React (React Implementation) - منتجات التاجر (Merchant Products)

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
