# AG4 - خريطة الوكلاء

## الوصف
عرض أقرب الوكلاء للمستخدم على الخريطة.

## المدخلات
| الحقل | النوع |
|-------|-------|
| lat | float |
| lng | float |
| radius | integer (km, default: 10) |

## المخرجات
مصفوفة وكلاء: id, shop_name, address, city, latitude, longitude, distance

## سير العمل
1. البحث عن الوكلاء النشطين (status = active)
2. حساب المسافة (Haversine formula)
3. ترتيب حسب الأقرب
4. Response

## API Endpoint
`GET /api/v1/agents/nearby?lat=33.51&lng=36.27`

## واجهات المستخدم
- Flutter: AgentsMapScreen (Google Maps)
- React SPA: AgentMapPage

## أولوية التنفيذ
P2

## اختبارات
- البحث ضمن 10 كم ← 200
- البحث بدون نتائج ← 200 (مصفوفة فارغة)
- البحث بدون إحداثيات ← 400
