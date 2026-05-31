# 03 - تدفق الإشعارات (Notification Flow)

## تدفق الإرسال

```
1. حدث (مثل استلام تحويل)
        │
2. NotificationService.send(user, type, data)
        │
3. تحديد القنوات حسب النوع
        │
4. إنشاء NotificationRecords
        │
5. دفع إلى Queue (Redis)
        │
6. معالجة غير متزامنة
   ├── FCM → إرسال push
   ├── Twilio → إرسال SMS
   ├── Mail → إرسال email
   └── Database → حفظ in-app
        │
7. تحديث حالة الإرسال (sent/failed)
```

## تدفق الاستلام (API)

```
GET /api/v1/notifications
        │
1. مصادقة المستخدم
2. جلب الإشعارات غير المقروءة
3. ترتيب حسب التاريخ (الأحدث أولاً)
4. تصغير البيانات (pagination)

POST /api/v1/notifications/{id}/read
        │
1. التحقق من الملكية
2. تحديث read_at
3. return 200
```
