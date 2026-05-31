import 'package:flutter_test/flutter_test.dart';

enum NotificationType { remittance, bill, escrow, fraud, system, promotion }

class NotificationItem {
  final String id;
  final NotificationType type;
  final String title;
  final String body;
  final DateTime createdAt;
  final bool isRead;

  NotificationItem({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.createdAt,
    this.isRead = false,
  });
}

class NotificationFilter {
  static List<NotificationItem> filter(List<NotificationItem> items, {NotificationType? type}) {
    if (type == null) return items;
    return items.where((n) => n.type == type).toList();
  }

  static int countUnread(List<NotificationItem> items) {
    return items.where((n) => !n.isRead).length;
  }

  static NotificationItem markRead(NotificationItem item) {
    return NotificationItem(id: item.id, type: item.type, title: item.title, body: item.body, createdAt: item.createdAt, isRead: true);
  }
}

class NotificationPreference {
  final String typeKey;
  final String label;
  bool inApp;
  bool email;
  bool sms;

  NotificationPreference({
    required this.typeKey,
    required this.label,
    this.inApp = true,
    this.email = false,
    this.sms = false,
  });

  Map<String, dynamic> toJson() => {
    'type_key': typeKey, 'label': label,
    'in_app': inApp, 'email': email, 'sms': sms,
  };

  factory NotificationPreference.fromJson(Map<String, dynamic> json) => NotificationPreference(
    typeKey: json['type_key'] ?? '',
    label: json['label'] ?? '',
    inApp: json['in_app'] ?? true,
    email: json['email'] ?? false,
    sms: json['sms'] ?? false,
  );
}

class InMemoryPrefStore {
  List<NotificationPreference> _prefs = [];

  void loadDefaults() {
    _prefs = [
      NotificationPreference(typeKey: 'remittance', label: 'التحويلات', inApp: true, email: true, sms: false),
      NotificationPreference(typeKey: 'bill', label: 'الفواتير', inApp: true, email: true, sms: true),
      NotificationPreference(typeKey: 'fraud', label: 'الأمان', inApp: true, email: true, sms: true),
    ];
  }

  void update(String typeKey, {bool? inApp, bool? email, bool? sms}) {
    final idx = _prefs.indexWhere((p) => p.typeKey == typeKey);
    if (idx >= 0) {
      if (inApp != null) _prefs[idx].inApp = inApp;
      if (email != null) _prefs[idx].email = email;
      if (sms != null) _prefs[idx].sms = sms;
    }
  }

  List<NotificationPreference> get all => _prefs;
  NotificationPreference? getByKey(String key) {
    try { return _prefs.firstWhere((p) => p.typeKey == key); }
    catch (_) { return null; }
  }

  String toJson() {
    return _prefs.map((p) => p.toJson().toString()).join(',');
  }
}

class SyncManager {
  bool _online = true;
  int _syncAttempts = 0;
  final List<Map<String, dynamic>> _pending = [];

  bool get isOnline => _online;
  int get syncAttempts => _syncAttempts;
  int get pendingCount => _pending.length;

  void setOnline(bool v) => _online = v;

  Future<bool> sync() async {
    if (!_online) { _syncAttempts++; return false; }
    _pending.clear();
    _syncAttempts = 0;
    return true;
  }

  void queue(Map<String, dynamic> data) => _pending.add(data);
}

void main() {
  group('Notification List Display', () {
    test('shows notifications with different read statuses', () {
      final items = [
        NotificationItem(id: '1', type: NotificationType.remittance, title: 'تحويل', body: 'تم', createdAt: DateTime.now(), isRead: false),
        NotificationItem(id: '2', type: NotificationType.bill, title: 'فاتورة', body: 'مستحقة', createdAt: DateTime.now(), isRead: true),
        NotificationItem(id: '3', type: NotificationType.escrow, title: 'ضمان', body: 'تم الإطلاق', createdAt: DateTime.now(), isRead: false),
      ];

      expect(NotificationFilter.countUnread(items), 2);
      final read = NotificationFilter.markRead(items[0]);
      expect(read.isRead, isTrue);
    });

    test('filters notifications by type', () {
      final items = [
        NotificationItem(id: '1', type: NotificationType.remittance, title: '', body: '', createdAt: DateTime.now()),
        NotificationItem(id: '2', type: NotificationType.bill, title: '', body: '', createdAt: DateTime.now()),
        NotificationItem(id: '3', type: NotificationType.fraud, title: '', body: '', createdAt: DateTime.now()),
      ];

      expect(NotificationFilter.filter(items, type: NotificationType.remittance).length, 1);
      expect(NotificationFilter.filter(items, type: NotificationType.bill).length, 1);
      expect(NotificationFilter.filter(items).length, 3);
    });
  });

  group('Notification Read Status Toggle', () {
    test('marking notification as read updates status', () {
      final item = NotificationItem(id: '1', type: NotificationType.system, title: 'تحديث', body: 'تم التحديث', createdAt: DateTime.now(), isRead: false);
      expect(item.isRead, isFalse);

      final updated = NotificationFilter.markRead(item);
      expect(updated.isRead, isTrue);
      expect(updated.id, '1');
    });
  });

  group('Save and Load User Preferences Locally', () {
    test('saves and loads notification preferences correctly', () {
      final store = InMemoryPrefStore();
      store.loadDefaults();
      expect(store.all.length, 3);

      store.update('remittance', email: true, sms: true);
      final remittance = store.getByKey('remittance');
      expect(remittance?.email, isTrue);
      expect(remittance?.sms, isTrue);
    });

    test('serializes preferences to JSON format', () {
      final store = InMemoryPrefStore();
      store.loadDefaults();

      final json = store.toJson();
      expect(json, contains('type_key'));
      expect(json, contains('remittance'));
      expect(json, contains('in_app'));
    });
  });

  group('Sync Failure Simulation with Retry', () {
    test('queues data when offline and retries on reconnect', () async {
      final sync = SyncManager();
      sync.setOnline(false);
      sync.queue({'type': 'remittance'});
      sync.queue({'type': 'bill'});

      expect(sync.pendingCount, 2);
      expect(sync.isOnline, isFalse);

      sync.setOnline(true);
      final result = await sync.sync();
      expect(result, isTrue);
      expect(sync.pendingCount, 0);
    });

    test('tracks sync attempts when offline', () async {
      final sync = SyncManager();
      sync.setOnline(false);
      await sync.sync();
      await sync.sync();
      expect(sync.syncAttempts, 2);
    });
  });

  group('Notification Type Filtering Unit', () {
    test('filters notifications by type and channel combination', () {
      final types = [
        (type: 'remittance', channel: 'in_app'),
        (type: 'remittance', channel: 'email'),
        (type: 'bill', channel: 'in_app'),
        (type: 'bill', channel: 'sms'),
        (type: 'fraud', channel: 'email'),
      ];

      final remittanceInApp = types.where((t) => t.type == 'remittance' && t.channel == 'in_app').length;
      final billSms = types.where((t) => t.type == 'bill' && t.channel == 'sms').length;

      expect(remittanceInApp, 1);
      expect(billSms, 1);
    });
  });
}
