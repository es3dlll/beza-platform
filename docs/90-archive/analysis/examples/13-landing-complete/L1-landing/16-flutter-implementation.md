# 16 - تطبيق Flutter (Flutter Implementation) - 00 - فهرس ملفات L1 (صفحة الهبوط التسويقية)

## Deep Linking من الموقع إلى التطبيق

عند النقر على "تحميل التطبيق" يتم التحقق مما إذا كان التطبيق مثبتاً:

```
موقع Beza التسويقي
     │
     ├── الجهاز → هل التطبيق مثبت؟
     │   ├── ✅ نعم → open app (deep link)
     │   └── ❌ لا → متجر التطبيقات (Google Play / App Store)
     │
     └── iOS: beza://
         Android: https://beza.com/open
```

## Android Deep Link

```xml
<!-- android/app/src/main/AndroidManifest.xml -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="beza" />
</intent-filter>
```

## iOS Universal Link

```xml
<!-- apple-app-site-association (على الخادم) -->
{
  "applinks": {
    "apps": [],
    "details": [
      {
        "appID": "TEAMID.com.beza.app",
        "paths": ["/open"]
      }
    ]
  }
}
```

## Flutter Deep Link Handling

```dart
// main.dart
void main() {
  runApp(BezaApp());
}

class BezaApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Beza',
      initialRoute: '/',
      onGenerateRoute: (settings) {
        // معالجة الـ deep links
        if (settings.name == '/download') {
          return MaterialPageRoute(builder: (_) => const DownloadScreen());
        }
        if (settings.name == '/register') {
          return MaterialPageRoute(builder: (_) => const RegisterScreen());
        }
        return MaterialPageRoute(builder: (_) => const HomeScreen());
      },
    );
  }
}
```

## ربط Flutter Widgets

```dart
// widgets/download_buttons.dart — يمكن إعادة استخدامه في الموقع
class DownloadButtons extends StatelessWidget {
  const DownloadButtons({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Google Play
        GestureDetector(
          onTap: () {
            final uri = Uri.parse('https://play.google.com/store/apps/details?id=com.beza.app');
            launchUrl(uri, mode: LaunchMode.externalApplication);
          },
          child: Image.asset('assets/google-play-badge.png', height: 60),
        ),
        const SizedBox(width: 16),
        // App Store
        GestureDetector(
          onTap: () {
            final uri = Uri.parse('https://apps.apple.com/app/beza/id123456789');
            launchUrl(uri, mode: LaunchMode.externalApplication);
          },
          child: Image.asset('assets/app-store-badge.png', height: 60),
        ),
      ],
    );
  }
}
```

## رابط تحميل التطبيق المباشر من الموقع

```typescript
// components/DownloadLinks.tsx (Next.js)
export default function DownloadLinks() {
  return (
    <div className="download-buttons">
      <a
        href="https://play.google.com/store/apps/details?id=com.beza.app"
        target="_blank"
        rel="noopener noreferrer"
      >
        <img src="/google-play-badge.png" alt="Google Play" />
      </a>
      <a
        href="https://apps.apple.com/app/beza/id123456789"
        target="_blank"
        rel="noopener noreferrer"
      >
        <img src="/app-store-badge.png" alt="App Store" />
      </a>
    </div>
  );
}
```
