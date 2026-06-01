# 16 - تطبيق Flutter (Flutter Implementation)

## لا حاجة لتطبيق Flutter

المثبت (Installer) هو **تطبيق ويب فقط** ولا يحتاج إلى تطبيق Flutter. الأسباب:

| السبب | التوضيح |
|-------|---------|
| **يعمل مرة واحدة فقط** | التنصيب يتم مرة واحدة عند أول نشر — لا داعي لتطبيق جوال |
| **بيئة الخادم** | المثبت يعمل على الخادم نفسة، ليس على جهاز المستخدم |
| **يتطلب صلاحيات خادم** | فحص PHP extensions وكتابة .env يحتاج صلاحية الخادم |
| **مثبت ويب تقليدي** | مثل واجهات تنصيب WordPress و Laravel |

## ماذا عن لوحة التحكم بعد التنصيب؟

بعد إكمال التنصيب، **لوحة تحكم المشرف** (Admin Dashboard) يمكن الوصول إليها عبر:
1. **React SPA** — واجهة ويب للمشرف
2. **تطبيق Flutter** — (مستقبلاً) تطبيق جوال للمشرف

لكن المثبت نفسه يبقى Web-only.

## البديل: Check if installed من Flutter

تطبيق Flutter الرئيسي يمكنه التحقق من حالة المثبت:

```dart
// services/install_check_service.dart
class InstallCheckService {
  final String baseUrl;

  InstallCheckService(this.baseUrl);

  /// التحقق مما إذا كان النظام مثبتاً
  Future<bool> isInstalled() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/install'),
        headers: {'Accept': 'application/json'},
      );
      // إذا كانت 403 ← المثبت معطل ← النظام مثبت
      // إذا كانت 200 ← المثبت نشط ← النظام غير مثبت
      return response.statusCode == 403;
    } catch (_) {
      // إذا كان الخادم لا يستجيب، نفترض أنه مثبت
      return true;
    }
  }

  /// الحصول على حالة التنصيب (للتشخيص)
  Future<Map<String, dynamic>?> getInstallStatus() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/install'),
        headers: {'Accept': 'application/json'},
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (_) {
      return null;
    }
  }
}
```

## مقارنة بين A1-register و SY1-install

| الخاصية | A1-register | SY1-install |
|---------|-------------|-------------|
| Flutter UI | ✅ RegisterScreen كامل مع BLoC | ❌ لا حاجة |
| REST API | ✅ POST /api/v1/auth/register | ✅ POST /install/* |
| Auth | ✅ JWT token | ❌ لا مصادقة (مافيش مستخدمين) |
| تعقيد UI | متوسط (فورم واحد) | بسيط (Wizard خطوة بخطوة) |
| منصة التشغيل | Android + iOS + Web | Web فقط |

## ملخص

```
Flutter App (Beza)
  ├── قبل التنصيب: →
  │     - يعرض شاشة "النظام غير مثبت"
  │     - يوجه المستخدم إلى /install عبر المتصفح
  │
  ├── بعد التنصيب: →
  │     - يعمل بشكل طبيعي
  │     - يتصل بـ API باستخدام JWT
  │     - لا علاقة له بالمثبت
  │
  └── المثبت نفسه:
        - Web only (React SPA)
        - يعمل على الخادم
        - لا يوجد تطبيق Flutter له
```
