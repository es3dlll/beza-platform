# 10. تطبيق الجوال (Flutter - Android/iOS)

هيكل المشروع (Clean Architecture + BLoC) مع إضافات:

- ميزة الدفع بدون اتصال (Offline Mode): تخزين المعاملات في Hive ومزامنتها لاحقاً
- دعم NFC لدفع القرب
- مصادقة بيومترية (بصمة/وجه) للعمليات الحساسة
- خريطة للوكلاء باستخدام Google Maps

## 10.1 مكون رئيسي: شاشة الخريطة للوكلاء

```dart
import 'package:google_maps_flutter/google_maps_flutter.dart';

class AgentsMapScreen extends StatefulWidget {
  @override
  _AgentsMapScreenState createState() => _AgentsMapScreenState();
}

class _AgentsMapScreenState extends State<AgentsMapScreen> {
  late GoogleMapController mapController;
  Set<Marker> markers = {};

  @override
  void initState() {
    super.initState();
    fetchNearbyAgents();
  }

  Future<void> fetchNearbyAgents() async {
    final agents = await api.get('/agents/nearby?lat=33.5138&lng=36.2765');
    setState(() {
      markers = agents.data.map((agent) => Marker(
        markerId: MarkerId(agent.id.toString()),
        position: LatLng(agent.latitude, agent.longitude),
        infoWindow: InfoWindow(title: agent.shop_name, snippet: agent.address),
      )).toSet();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('أقرب الوكلاء')),
      body: GoogleMap(
        initialCameraPosition: CameraPosition(target: LatLng(33.5138, 36.2765), zoom: 12),
        markers: markers,
        onMapCreated: (controller) => mapController = controller,
      ),
    );
  }
}
```

> **ملاحظة:** شاشة الوكيل الرئيسية (Agent Dashboard) موثقة بالكامل في [12-agents-system.md](./12-agents-system.md) مع شرح وافي لآلية السحب والإيداع النقدي.
