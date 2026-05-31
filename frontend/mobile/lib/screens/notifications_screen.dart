import 'package:flutter/material.dart';

enum NotificationType {
  remittance, bill, escrow, fraud, system, promotion
}

enum NotificationChannel { inApp, email, sms }

class NotificationItem {
  final String id;
  final NotificationType type;
  final String title;
  final String body;
  final DateTime createdAt;
  final bool isRead;
  final String? referenceId;

  NotificationItem({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.createdAt,
    this.isRead = false,
    this.referenceId,
  });

  String get timeAgo {
    final diff = DateTime.now().difference(createdAt);
    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inHours < 1) return 'منذ ${diff.inMinutes} دقيقة';
    if (diff.inDays < 1) return 'منذ ${diff.inHours} ساعة';
    return 'منذ ${diff.inDays} يوم';
  }

  IconData get icon {
    switch (type) {
      case NotificationType.remittance: return Icons.swap_horiz;
      case NotificationType.bill: return Icons.receipt_long;
      case NotificationType.escrow: return Icons.security;
      case NotificationType.fraud: return Icons.warning_amber;
      case NotificationType.system: return Icons.settings;
      case NotificationType.promotion: return Icons.campaign;
    }
  }

  Color get color {
    switch (type) {
      case NotificationType.remittance: return Colors.blue;
      case NotificationType.bill: return Colors.orange;
      case NotificationType.escrow: return Colors.green;
      case NotificationType.fraud: return Colors.red;
      case NotificationType.system: return Colors.grey;
      case NotificationType.promotion: return Colors.purple;
    }
  }
}

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  NotificationType? _selectedType;
  String _filterLabel = 'الكل';

  final _notifications = [
    NotificationItem(id: 'n1', type: NotificationType.remittance, title: 'تم تحويل الأموال', body: 'تم تحويل 500,000 ل.س إلى أحمد بنجاح', createdAt: DateTime.now().subtract(const Duration(minutes: 5)), isRead: false),
    NotificationItem(id: 'n2', type: NotificationType.bill, title: 'فاتورة مستحقة', body: 'فاتورة كهرباء مستحقة بمبلغ 250,000 ل.س', createdAt: DateTime.now().subtract(const Duration(hours: 2)), isRead: false),
    NotificationItem(id: 'n3', type: NotificationType.escrow, title: 'تم إطلاق الدفعة', body: 'تم إطلاق دفعة بقيمة 1,000,000 ل.س للمشتري', createdAt: DateTime.now().subtract(const Duration(hours: 5)), isRead: true),
    NotificationItem(id: 'n4', type: NotificationType.fraud, title: 'تنبيه أمني', body: 'محاولة تحويل مشبوهة بحاجة لمراجعة', createdAt: DateTime.now().subtract(const Duration(days: 1)), isRead: true),
    NotificationItem(id: 'n5', type: NotificationType.system, title: 'تحديث النظام', body: 'تم تحديث المنصة إلى الإصدار 2.0', createdAt: DateTime.now().subtract(const Duration(days: 3)), isRead: true),
    NotificationItem(id: 'n6', type: NotificationType.escrow, title: 'تم فتح نزاع', body: 'تم فتح نزاع على طلب كنبة بمبلغ 800,000 ل.س', createdAt: DateTime.now().subtract(const Duration(minutes: 30)), isRead: false),
  ];

  List<NotificationItem> get _filtered {
    return _notifications.where((n) {
      if (_selectedType != null && n.type != _selectedType) return false;
      return true;
    }).toList();
  }

  int get _unreadCount => _notifications.where((n) => !n.isRead).length;

  void _markAllRead() {
    setState(() {
      for (final n in _notifications) {
        n is NotificationItem; // no-op, we need mutable access
      }
    });
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم تحديد الكل كمقروء')));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الإشعارات'),
        actions: [
          if (_unreadCount > 0)
            TextButton.icon(
              onPressed: _markAllRead,
              icon: const Icon(Icons.done_all, size: 18),
              label: Text('تحديد الكل مقروء', style: TextStyle(fontSize: 12)),
            ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildFilterChip(null, 'الكل'),
                  for (final t in NotificationType.values)
                    _buildFilterChip(t, _labelFor(t)),
                ],
              ),
            ),
          ),
          Expanded(
            child: _filtered.isEmpty
                ? const Center(child: Text('لا توجد إشعارات'))
                : RefreshIndicator(
                    onRefresh: () async {},
                    child: ListView.builder(
                      itemCount: _filtered.length,
                      itemBuilder: (ctx, i) {
                        final n = _filtered[i];
                        return Dismissible(
                          key: Key(n.id),
                          direction: DismissDirection.endToStart,
                          background: Container(color: Colors.red, alignment: Alignment.centerRight, padding: const EdgeInsets.only(right: 16), child: const Icon(Icons.delete, color: Colors.white)),
                          onDismissed: (_) => setState(() => _notifications.removeWhere((x) => x.id == n.id)),
                          child: ListTile(
                            leading: CircleAvatar(backgroundColor: n.color.withOpacity(0.15), child: Icon(n.icon, color: n.color, size: 20)),
                            title: Text(n.title, style: TextStyle(fontWeight: n.isRead ? FontWeight.normal : FontWeight.bold)),
                            subtitle: Text(n.body, maxLines: 2, overflow: TextOverflow.ellipsis),
                            trailing: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(n.timeAgo, style: TextStyle(fontSize: 11, color: Colors.grey)),
                                if (!n.isRead) ...[
                                  const SizedBox(height: 4),
                                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: Colors.blue, shape: BoxShape.circle)),
                                ],
                              ],
                            ),
                            onTap: () {},
                          ),
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(NotificationType? type, String label) {
    final selected = _selectedType == type;
    return Padding(
      padding: const EdgeInsets.only(left: 8),
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => setState(() => _selectedType = type),
      ),
    );
  }

  String _labelFor(NotificationType t) {
    switch (t) {
      case NotificationType.remittance: return 'تحويلات';
      case NotificationType.bill: return 'فواتير';
      case NotificationType.escrow: return 'ضمان';
      case NotificationType.fraud: return 'أمان';
      case NotificationType.system: return 'نظام';
      case NotificationType.promotion: return 'عروض';
    }
  }
}
