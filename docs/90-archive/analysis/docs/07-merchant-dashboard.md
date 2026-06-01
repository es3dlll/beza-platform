# 7. لوحة تحكم التاجر (Merchant Dashboard)

## 7.1 الهيكل

```
merchant-dashboard/
├── src/
│   ├── pages/
│   │   ├── Dashboard.jsx
│   │   ├── Products/
│   │   │   ├── ProductList.jsx
│   │   │   ├── AddProduct.jsx
│   │   │   └── EditProduct.jsx
│   │   ├── Orders/
│   │   │   ├── OrderList.jsx
│   │   │   └── OrderDetails.jsx
│   │   ├── Payments/
│   │   │   ├── PaymentLinks.jsx
│   │   │   └── CreatePaymentLink.jsx
│   │   ├── Settlements/
│   │   │   └── SettlementHistory.jsx
│   │   └── Settings/
│   │       └── MerchantSettings.jsx
│   └── components/
│       └── Layout/
│           └── MerchantLayout.jsx
```

## 7.2 صفحة إدارة المنتجات

```jsx
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';

export default function ProductList() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['merchant-products'],
    queryFn: () => api.get('/merchant/products').then(res => res.data),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/merchant/products/${id}`),
    onSuccess: () => queryClient.invalidateQueries(['merchant-products']),
  });

  if (isLoading) return <div>جار التحميل...</div>;

  return (
    <div className="bg-white rounded-2xl shadow">
      <div className="p-4 border-b flex justify-between">
        <h2 className="text-xl font-semibold">المنتجات</h2>
        <button className="btn-primary">إضافة منتج جديد</button>
      </div>
      <div className="overflow-x-auto">
        <table className="min-w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="p-3 text-right">المنتج</th>
              <th className="p-3 text-right">السعر</th>
              <th className="p-3 text-right">المخزون</th>
              <th className="p-3 text-right">الحالة</th>
              <th className="p-3 text-right"></th>
            </tr>
          </thead>
          <tbody>
            {data?.data.map(product => (
              <tr key={product.id} className="border-b hover:bg-gray-50">
                <td className="p-3">
                  <div className="flex items-center gap-3">
                    <img src={product.image} className="w-10 h-10 rounded object-cover" />
                    <span>{product.name}</span>
                  </div>
                </td>
                <td className="p-3">{product.price} {product.currency}</td>
                <td className="p-3">{product.stock}</td>
                <td className="p-3">
                  <span className={`px-2 py-1 rounded-full text-xs ${product.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                    {product.is_active ? 'نشط' : 'غير نشط'}
                  </span>
                </td>
                <td className="p-3">
                  <button className="text-indigo-600 ml-2">تعديل</button>
                  <button onClick={() => deleteMutation.mutate(product.id)} className="text-red-600">حذف</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
```
