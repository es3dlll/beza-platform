# 16 - تطبيق Flutter (Flutter Implementation) - إدارة طلبات التاجر (Merchant Orders)

```dart
// presentation/bloc/order_bloc.dart
class OrderBloc extends Bloc<OrderEvent, OrderState> {
  final IOrderRepository repository;
  OrderBloc({required this.repository}) : super(OrderInitial()) {
    on<LoadOrders>(_onLoad);
    on<UpdateOrderStatus>(_onUpdateStatus);
  }
  Future<void> _onLoad(LoadOrders event, Emitter<OrderState> emit) async {
    emit(OrderLoading());
    try { final orders = await repository.getOrders(status: event.status); emit(OrderLoaded(orders)); }
    catch (e) { emit(OrderError(e.toString())); }
  }
}

// presentation/screens/merchant_orders_screen.dart
class MerchantOrdersScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('الطلبات'), actions: [
        PopupMenuButton<String>(
          onSelected: (s) => context.read<OrderBloc>().add(LoadOrders(status: s)),
          itemBuilder: (_) => ['pending','processing','shipped','delivered'].map((s) => PopupMenuItem(value: s, child: Text(s))).toList(),
        ),
      ]),
      body: BlocBuilder<OrderBloc, OrderState>(
        builder: (context, state) {
          if (state is OrderLoaded) return ListView.builder(
            itemCount: state.orders.length,
            itemBuilder: (_, i) => OrderCard(order: state.orders[i]),
          );
          return Center(child: CircularProgressIndicator());
        },
      ),
    );
  }
}
```
