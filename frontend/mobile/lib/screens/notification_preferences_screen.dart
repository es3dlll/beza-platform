import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';

class NotificationPreference {
  final String typeKey;
  final String label;
  final String icon;
  bool inApp;
  bool email;
  bool sms;

  NotificationPreference({
    required this.typeKey,
    required this.label,
    required this.icon,
    this.inApp = true,
    this.email = false,
    this.sms = false,
  });

  Map<String, dynamic> toJson() => {
    'type_key': typeKey, 'label': label, 'icon': icon,
    'in_app': inApp, 'email': email, 'sms': sms,
  };

  factory NotificationPreference.fromJson(Map<String, dynamic> json) => NotificationPreference(
    typeKey: json['type_key'] ?? '',
    label: json['label'] ?? '',
    icon: json['icon'] ?? '',
    inApp: json['in_app'] ?? true,
    email: json['email'] ?? false,
    sms: json['sms'] ?? false,
  );
}

class LocalPreferencesStore {
  final String filePath;
  List<NotificationPreference> _preferences = [];
  bool _loaded = false;

  LocalPreferencesStore({String? path}) : filePath = path ?? 'notification_prefs.json';

  static final List<NotificationPreference> defaults = [
    NotificationPreference(typeKey: 'remittance', label: 'التحويلات المالية', icon: 'swap_horiz', inApp: true, email: true, sms: false),
    NotificationPreference(typeKey: 'bill', label: 'الفواتير', icon: 'receipt_long', inApp: true, email: true, sms: true),
    NotificationPreference(typeKey: 'escrow', label: 'الضمان المالي', icon: 'security', inApp: true, email: true, sms: false),
    NotificationPreference(typeKey: 'fraud', label: 'التنبيهات الأمنية', icon: 'warning_amber', inApp: true, email: true, sms: true),
    NotificationPreference(typeKey: 'system', label: 'تحديثات النظام', icon: 'settings', inApp: true, email: false, sms: false),
    NotificationPreference(typeKey: 'promotion', label: 'العروض والتسويق', icon: 'campaign', inApp: false, email: false, sms: false),
  ];

  Future<void> load() async {
    try {
      final file = File(filePath);
      if (await file.exists()) {
        final content = await file.readAsString();
        final list = jsonDecode(content) as List;
        _preferences = list.map((e) => NotificationPreference.fromJson(e as Map<String, dynamic>)).toList();
      } else {
        _preferences = defaults.map((p) => NotificationPreference(
          typeKey: p.typeKey, label: p.label, icon: p.icon,
          inApp: p.inApp, email: p.email, sms: p.sms,
        )).toList();
      }
    } catch (_) {
      _preferences = defaults.map((p) => NotificationPreference(
        typeKey: p.typeKey, label: p.label, icon: p.icon,
        inApp: p.inApp, email: p.email, sms: p.sms,
      )).toList();
    }
    _loaded = true;
  }

  Future<void> save() async {
    final content = jsonEncode(_preferences.map((p) => p.toJson()).toList());
    await File(filePath).writeAsString(content);
  }

  List<NotificationPreference> get preferences => _preferences;
  bool get isLoaded => _loaded;

  void update(String typeKey, {bool? inApp, bool? email, bool? sms}) {
    final idx = _preferences.indexWhere((p) => p.typeKey == typeKey);
    if (idx >= 0) {
      if (inApp != null) _preferences[idx].inApp = inApp;
      if (email != null) _preferences[idx].email = email;
      if (sms != null) _preferences[idx].sms = sms;
    }
  }
}

class NotificationPreferencesScreen extends StatefulWidget {
  final LocalPreferencesStore store;

  const NotificationPreferencesScreen({super.key, required this.store});

  @override
  State<NotificationPreferencesScreen> createState() => _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState extends State<NotificationPreferencesScreen> {
  @override
  void initState() {
    super.initState();
    widget.store.load();
  }

  @override
  Widget build(BuildContext context) {
    final prefs = widget.store.preferences;

    return Scaffold(
      appBar: AppBar(title: const Text('تفضيلات الإشعارات')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('تحكم في الإشعارات التي ترغب باستلامها وعبر أي قناة', style: TextStyle(color: Colors.grey, fontSize: 14)),
          const SizedBox(height: 16),
          ...prefs.map((p) => Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(p.label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _buildChannelToggle(p, 'التطبيق', p.inApp, (v) => _update(p.typeKey, inApp: v)),
                      const SizedBox(width: 12),
                      _buildChannelToggle(p, 'البريد', p.email, (v) => _update(p.typeKey, email: v)),
                      const SizedBox(width: 12),
                      _buildChannelToggle(p, 'رسالة', p.sms, (v) => _update(p.typeKey, sms: v)),
                    ],
                  ),
                ],
              ),
            ),
          )),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () async {
                await widget.store.save();
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم حفظ التفضيلات')));
                }
              },
              icon: const Icon(Icons.save),
              label: const Text('حفظ التفضيلات'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChannelToggle(NotificationPreference p, String label, bool value, ValueChanged<bool> onChanged) {
    return FilterChip(
      label: Text(label, style: TextStyle(fontSize: 12)),
      selected: value,
      onSelected: onChanged,
    );
  }

  void _update(String typeKey, {bool? inApp, bool? email, bool? sms}) {
    setState(() => widget.store.update(typeKey, inApp: inApp, email: email, sms: sms));
  }
}
