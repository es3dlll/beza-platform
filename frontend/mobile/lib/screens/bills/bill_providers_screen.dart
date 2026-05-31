import 'package:flutter/material.dart';
import '../../core/money.dart';

enum BillCategory { electricity, water, telecom, internet, gas, insurance }

class BillProviderModel {
  final String id;
  final String name;
  final BillCategory category;
  final bool isActive;
  final String? supportPhone;

  BillProviderModel({
    required this.id,
    required this.name,
    required this.category,
    required this.isActive,
    this.supportPhone,
  });
}

class BillProvidersScreen extends StatefulWidget {
  const BillProvidersScreen({super.key});

  @override
  State<BillProvidersScreen> createState() => _BillProvidersScreenState();
}

class _BillProvidersScreenState extends State<BillProvidersScreen> {
  final _searchController = TextEditingController();
  BillCategory? _selectedCategory;
  String _searchQuery = '';

  final _providers = [
    BillProviderModel(id: '1', name: 'الشركة العامة للكهرباء', category: BillCategory.electricity, isActive: true, supportPhone: '011 123 4567'),
    BillProviderModel(id: '2', name: 'مؤسسة المياه', category: BillCategory.water, isActive: true, supportPhone: '011 234 5678'),
    BillProviderModel(id: '3', name: 'الاتصالات السورية', category: BillCategory.telecom, isActive: true, supportPhone: '011 345 6789'),
    BillProviderModel(id: '4', name: 'شبكة بيزا', category: BillCategory.internet, isActive: false, supportPhone: null),
    BillProviderModel(id: '5', name: 'الغاز السوري', category: BillCategory.gas, isActive: true, supportPhone: '011 456 7890'),
  ];

  List<BillProviderModel> get _filteredProviders {
    return _providers.where((p) {
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
      appBar: AppBar(title: const Text('مزودو الخدمات')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _searchController,
              textAlign: TextAlign.right,
              decoration: InputDecoration(
                hintText: 'ابحث عن مزود...',
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
                ...BillCategory.values.map((c) => _buildCategoryChip(c, c.name)),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _filteredProviders.isEmpty
                ? const Center(child: Text('لا توجد مزودين'))
                : ListView.builder(
                    itemCount: _filteredProviders.length,
                    itemBuilder: (ctx, i) {
                      final p = _filteredProviders[i];
                      return Card(
                        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        child: ListTile(
                          leading: CircleAvatar(child: Icon(_iconFor(p.category))),
                          title: Text(p.name),
                          subtitle: Text('${p.category.name} · ${p.isActive ? "نشط" : "غير نشط"}'),
                          trailing: p.isActive
                              ? const Icon(Icons.chevron_left)
                              : const Icon(Icons.block, color: Colors.red),
                          enabled: p.isActive,
                          onTap: p.isActive ? () {} : null,
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(BillCategory? category, String label) {
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

  IconData _iconFor(BillCategory c) {
    switch (c) {
      case BillCategory.electricity: return Icons.bolt;
      case BillCategory.water: return Icons.water_drop;
      case BillCategory.telecom: return Icons.phone;
      case BillCategory.internet: return Icons.wifi;
      case BillCategory.gas: return Icons.local_fire_department;
      case BillCategory.insurance: return Icons.shield;
    }
  }
}
