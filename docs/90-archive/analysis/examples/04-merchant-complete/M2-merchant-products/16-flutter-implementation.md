# 16 - تطبيق Flutter (Flutter Implementation) - منتجات التاجر (Merchant Products)

```dart
// presentation/bloc/product_bloc.dart
class ProductBloc extends Bloc<ProductEvent, ProductState> {
  final IProductRepository repository;
  ProductBloc({required this.repository}) : super(ProductInitial()) {
    on<LoadProducts>(_onLoad);
    on<CreateProduct>(_onCreate);
    on<DeleteProduct>(_onDelete);
  }
  Future<void> _onLoad(LoadProducts event, Emitter<ProductState> emit) async {
    emit(ProductLoading());
    try { final products = await repository.getProducts(); emit(ProductLoaded(products)); }
    catch (e) { emit(ProductError(e.toString())); }
  }
}

// presentation/screens/merchant_products_screen.dart
class MerchantProductsScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('منتجاتي')),
      body: BlocBuilder<ProductBloc, ProductState>(
        builder: (context, state) {
          if (state is ProductLoading) return CircularProgressIndicator();
          if (state is ProductLoaded) return ListView.builder(
            itemCount: state.products.length,
            itemBuilder: (_, i) => ListTile(title: Text(state.products[i].name), subtitle: Text('${state.products[i].priceUsd} USD'));
          return Text('لا توجد منتجات');
        },
      ),
      floatingActionButton: FloatingActionButton(child: Icon(Icons.add), onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AddProductScreen()))),
    );
  }
}
```
