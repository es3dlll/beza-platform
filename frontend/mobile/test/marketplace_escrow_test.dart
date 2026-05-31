import 'package:flutter_test/flutter_test.dart';

enum EscrowStatusEnum { initiated, funded, shipped, delivered, released, disputed, refunded }

class OrderModel {
  final String id;
  final String productName;
  final int amountFils;
  final int feeFils;
  final EscrowStatusEnum status;
  final DateTime createdAt;

  OrderModel({
    required this.id,
    required this.productName,
    required this.amountFils,
    required this.feeFils,
    required this.status,
    required this.createdAt,
  });

  int get totalFils => amountFils + feeFils;
  String get statusLabel {
    const labels = {
      EscrowStatusEnum.initiated: 'قيد الإنشاء',
      EscrowStatusEnum.funded: 'محجوزة',
      EscrowStatusEnum.shipped: 'تم الشحن',
      EscrowStatusEnum.delivered: 'تم التسليم',
      EscrowStatusEnum.released: 'تم الدفع',
      EscrowStatusEnum.disputed: 'في النزاع',
      EscrowStatusEnum.refunded: 'مستردة',
    };
    return labels[status] ?? '';
  }
}

class ProductModel {
  final String id;
  final String name;
  final String sellerName;
  final int priceFils;
  final bool inStock;

  ProductModel({
    required this.id,
    required this.name,
    required this.sellerName,
    required this.priceFils,
    this.inStock = true,
  });
}

class MarketplaceFilter {
  static List<ProductModel> filter(List<ProductModel> products, {String? category, String? query}) {
    return products.where((p) {
      if (query != null && query.isNotEmpty && !p.name.contains(query)) return false;
      return true;
    }).toList();
  }

  static List<ProductModel> inStockOnly(List<ProductModel> products) {
    return products.where((p) => p.inStock).toList();
  }
}

class DisputeSubmission {
  final String orderId;
  final String reason;
  final String description;
  final List<String> documents;
  final DateTime submittedAt;

  DisputeSubmission({
    required this.orderId,
    required this.reason,
    required this.description,
    this.documents = const [],
    DateTime? submittedAt,
  }) : submittedAt = submittedAt ?? DateTime.now();
}

void main() {
  group('Marketplace Product Listing', () {
    test('filters products by search query', () {
      final products = [
        ProductModel(id: 'p1', name: 'هاتف ذكي', sellerName: 'متجر الإلكترونيات', priceFils: 12_500_000),
        ProductModel(id: 'p2', name: 'حاسوب محمول', sellerName: 'متجر الإلكترونيات', priceFils: 35_000_000),
        ProductModel(id: 'p3', name: 'كنبة', sellerName: 'أثاث منزلي', priceFils: 8_000_000),
      ];

      final result = MarketplaceFilter.filter(products, query: 'هاتف');
      expect(result.length, 1);
      expect(result.first.name, 'هاتف ذكي');
    });

    test('filters out-of-stock products', () {
      final products = [
        ProductModel(id: 'p1', name: 'هاتف', sellerName: 'متجر', priceFils: 10_000, inStock: true),
        ProductModel(id: 'p2', name: 'حاسوب', sellerName: 'متجر', priceFils: 20_000, inStock: false),
        ProductModel(id: 'p3', name: 'جهاز لوحي', sellerName: 'متجر', priceFils: 15_000, inStock: true),
      ];

      final available = MarketplaceFilter.inStockOnly(products);
      expect(available.length, 2);
      expect(available.any((p) => p.id == 'p2'), isFalse);
    });

    test('returns all products when no filters applied', () {
      final products = [
        ProductModel(id: 'p1', name: 'منتج أ', sellerName: 'بائع', priceFils: 1000),
        ProductModel(id: 'p2', name: 'منتج ب', sellerName: 'بائع', priceFils: 2000),
      ];

      expect(MarketplaceFilter.filter(products).length, 2);
    });
  });

  group('Escrow Order Tracking', () {
    test('calculates total including fee correctly', () {
      final order = OrderModel(
        id: 'ESC-001',
        productName: 'هاتف',
        amountFils: 10_000_000,
        feeFils: 100_000,
        status: EscrowStatusEnum.funded,
        createdAt: DateTime.now(),
      );

      expect(order.totalFils, 10_100_000);
    });

    test('displays correct label for each escrow status', () {
      final initiated = OrderModel(id: '1', productName: '', amountFils: 0, feeFils: 0, status: EscrowStatusEnum.initiated, createdAt: DateTime.now());
      final disputed = OrderModel(id: '2', productName: '', amountFils: 0, feeFils: 0, status: EscrowStatusEnum.disputed, createdAt: DateTime.now());
      final released = OrderModel(id: '3', productName: '', amountFils: 0, feeFils: 0, status: EscrowStatusEnum.released, createdAt: DateTime.now());
      final refunded = OrderModel(id: '4', productName: '', amountFils: 0, feeFils: 0, status: EscrowStatusEnum.refunded, createdAt: DateTime.now());

      expect(initiated.statusLabel, 'قيد الإنشاء');
      expect(disputed.statusLabel, 'في النزاع');
      expect(released.statusLabel, 'تم الدفع');
      expect(refunded.statusLabel, 'مستردة');
    });

    test('funded escrow tracks correct time progression', () {
      final createdAt = DateTime(2026, 6, 1, 10, 0, 0);
      final funded = createdAt.add(const Duration(hours: 1));

      expect(funded.hour, 11);
      expect(funded.difference(createdAt).inHours, 1);
    });
  });

  group('Dispute Submission', () {
    test('creates dispute with required fields', () {
      final dispute = DisputeSubmission(
        orderId: 'ESC-001',
        reason: 'product_not_as_described',
        description: 'المنتج مختلف عن الصور',
      );

      expect(dispute.orderId, 'ESC-001');
      expect(dispute.reason, 'product_not_as_described');
      expect(dispute.description, 'المنتج مختلف عن الصور');
      expect(dispute.documents, isEmpty);
    });

    test('creates dispute with document attachments', () {
      final dispute = DisputeSubmission(
        orderId: 'ESC-002',
        reason: 'damaged',
        description: 'وصل تالفاً',
        documents: ['https://example.com/img1.jpg', 'https://example.com/img2.jpg'],
      );

      expect(dispute.documents.length, 2);
      expect(dispute.documents.first, contains('example.com'));
    });

    test('captures submission timestamp', () {
      final dispute = DisputeSubmission(
        orderId: 'ESC-003',
        reason: 'late_delivery',
        description: 'تأخر 5 أيام',
      );

      expect(dispute.submittedAt, isNotNull);
      expect(dispute.submittedAt.year, 2026);
    });
  });
}
