import 'package:flutter/material.dart';
import '../core/money.dart';

enum ProductCategory { electronics, fashion, home, food, services, other }

class ProductModel {
  final String id;
  final String name;
  final String sellerName;
  final int priceFils;
  final ProductCategory category;
  final double rating;
  final int reviewCount;
  final bool inStock;

  ProductModel({
    required this.id,
    required this.name,
    required this.sellerName,
    required this.priceFils,
    required this.category,
    this.rating = 0,
    this.reviewCount = 0,
    this.inStock = true,
  });
}

class MarketplaceScreen extends StatefulWidget {
  const MarketplaceScreen({super.key});

  @override
  State<MarketplaceScreen> createState() => _MarketplaceScreenState();
}

class _MarketplaceScreenState extends State<MarketplaceScreen> {
  final _searchController = TextEditingController();
  ProductCategory? _selectedCategory;
  String _searchQuery = '';

  final _products = [
    ProductModel(id: 'p1', name: 'هاتف ذكي', sellerName: 'متجر الإلكترونيات', priceFils: 12_500_000, category: ProductCategory.electronics, rating: 4.5, reviewCount: 120),
    ProductModel(id: 'p2', name: 'حاسوب محمول', sellerName: 'متجر الإلكترونيات', priceFils: 35_000_000, category: ProductCategory.electronics, rating: 4.2, reviewCount: 85),
    ProductModel(id: 'p3', name: 'كنبة 3 مقاعد', sellerName: 'أثاث منزلي', priceFils: 8_000_000, category: ProductCategory.home, rating: 4.7, reviewCount: 34),
    ProductModel(id: 'p4', name: 'فستان', sellerName: 'الأزياء العصرية', priceFils: 1_500_000, category: ProductCategory.fashion, rating: 4.0, reviewCount: 210),
    ProductModel(id: 'p5', name: 'وجبة عائلية', sellerName: 'مطعم الشام', priceFils: 750_000, category: ProductCategory.food, rating: 4.8, reviewCount: 560),
    ProductModel(id: 'p6', name: 'خدمة تصميم', sellerName: 'وكالة الإبداع', priceFils: 5_000_000, category: ProductCategory.services, rating: 4.3, reviewCount: 18, inStock: false),
  ];

  List<ProductModel> get _filteredProducts {
    return _products.where((p) {
      if (_selectedCategory != null && p.category != _selectedCategory) return false;
      if (_searchQuery.isNotEmpty && !p.name.contains(_searchQuery)) return false;
      return true;
    }).toList();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('السوق الرقمي')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _searchController,
              textAlign: TextAlign.right,
              decoration: InputDecoration(
                hintText: 'ابحث عن منتج...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(icon: const Icon(Icons.clear), onPressed: () { _searchController.clear(); setState(() => _searchQuery = ''); })
                    : null,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onChanged: (v) => setState(() => _searchQuery = v),
            ),
          ),
          SizedBox(
            height: 48,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: [
                _buildCategoryChip(null, 'الكل'),
                ...ProductCategory.values.map((c) => _buildCategoryChip(c, c.name)),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _filteredProducts.isEmpty
                ? const Center(child: Text('لا توجد منتجات'))
                : ListView.builder(
                    itemCount: _filteredProducts.length,
                    itemBuilder: (ctx, i) {
                      final p = _filteredProducts[i];
                      final price = Money.fromFils(p.priceFils);
                      return Card(
                        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        child: ListTile(
                          leading: CircleAvatar(child: Icon(_iconFor(p.category))),
                          title: Text(p.name),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(p.sellerName, style: const TextStyle(color: Colors.grey)),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Text(price.format(), style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                                  const SizedBox(width: 12),
                                  Icon(Icons.star, size: 14, color: Colors.amber),
                                  Text('${p.rating} (${p.reviewCount})', style: const TextStyle(fontSize: 12)),
                                  if (!p.inStock) ...[
                                    const SizedBox(width: 12),
                                    const Text('نفد', style: TextStyle(color: Colors.red, fontSize: 12)),
                                  ],
                                ],
                              ),
                            ],
                          ),
                          trailing: p.inStock ? const Icon(Icons.shopping_cart) : null,
                          enabled: p.inStock,
                          onTap: p.inStock ? () {} : null,
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(ProductCategory? category, String label) {
    final selected = _selectedCategory == category;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => setState(() => _selectedCategory = category),
      ),
    );
  }

  IconData _iconFor(ProductCategory c) {
    switch (c) {
      case ProductCategory.electronics: return Icons.phone_android;
      case ProductCategory.fashion: return Icons.checkroom;
      case ProductCategory.home: return Icons.home;
      case ProductCategory.food: return Icons.restaurant;
      case ProductCategory.services: return Icons.handyman;
      case ProductCategory.other: return Icons.inventory_2;
    }
  }
}
