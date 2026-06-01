# 16 - تطبيق Flutter (Flutter Implementation) - تسجيل تاجر (Merchant Registration)

```dart
// domain/repositories/i_merchant_repository.dart
abstract class IMerchantRepository {
  Future<MerchantEntity> register({
    required String businessName, required String businessType,
    required String commercialRegistration, required String taxId,
    required String ownerPhone, required String ownerName,
    required Map<String, dynamic> bankAccountInfo,
    required List<Map<String, dynamic>> documents,
  });
}

// presentation/bloc/merchant_register_bloc.dart
class MerchantRegisterBloc extends Bloc<MerchantRegisterEvent, MerchantRegisterState> {
  final IMerchantRepository repository;
  MerchantRegisterBloc({required this.repository}) : super(MerchantRegisterInitial()) {
    on<SubmitMerchantRegister>(_onSubmit);
  }
  Future<void> _onSubmit(SubmitMerchantRegister event, Emitter<MerchantRegisterState> emit) async {
    emit(MerchantRegisterLoading());
    try {
      final result = await repository.register(...);
      emit(MerchantRegisterSuccess(result));
    } catch (e) { emit(MerchantRegisterFailure(e.toString())); }
  }
}

// presentation/screens/merchant_register_screen.dart
class MerchantRegisterScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تسجيل تاجر')),
      body: SingleChildScrollView(padding: EdgeInsets.all(16), child: Column(children: [
        TextFormField(decoration: InputDecoration(labelText: 'اسم المتجر')),
        TextFormField(decoration: InputDecoration(labelText: 'السجل التجاري')),
        SizedBox(height: 24),
        ElevatedButton(onPressed: () {}, child: Text('تسجيل')),
      ])),
    );
  }
}
```
