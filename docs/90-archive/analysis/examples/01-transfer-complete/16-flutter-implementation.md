# 16 - تطبيق Flutter (Flutter Implementation) - التحويل بين المستخدمين (P2P Transfer)

## هيكل الملفات

```
lib/features/transfer/
├── data/
│   ├── models/
│   │   ├── transfer_request_model.dart
│   │   └── transfer_response_model.dart
│   ├── repositories/
│   │   └── transfer_repository.dart
│   └── datasources/
│       └── transfer_remote_datasource.dart
├── domain/
│   ├── entities/
│   │   └── transfer_entity.dart
│   └── repositories/
│       └── i_transfer_repository.dart
└── presentation/
    ├── bloc/
    │   ├── transfer_bloc.dart
    │   ├── transfer_event.dart
    │   └── transfer_state.dart
    ├── screens/
    │   └── transfer_screen.dart
    └── widgets/
        ├── transfer_form.dart
        ├── recipient_field.dart
        ├── amount_field.dart
        ├── currency_selector.dart
        └── pin_field.dart
```

## طبقة المجال (Domain Layer)

```dart
// domain/entities/transfer_entity.dart
class TransferEntity {
  final int id;
  final String referenceNumber;
  final double amount;
  final String currency;
  final String status;
  final double newBalance;
  final String senderName;
  final String receiverName;
  final DateTime createdAt;

  TransferEntity({
    required this.id,
    required this.referenceNumber,
    required this.amount,
    required this.currency,
    required this.status,
    required this.newBalance,
    required this.senderName,
    required this.receiverName,
    required this.createdAt,
  });
}
```

```dart
// domain/repositories/i_transfer_repository.dart
abstract class ITransferRepository {
  Future<TransferEntity> transfer({
    required String toPhone,
    required double amount,
    required String currency,
    required String pin,
    String? description,
  });
}
```

## طبقة البيانات (Data Layer)

```dart
// data/models/transfer_request_model.dart
class TransferRequestModel {
  final String toPhone;
  final double amount;
  final String currency;
  final String pin;
  final String? description;

  TransferRequestModel({
    required this.toPhone,
    required this.amount,
    required this.currency,
    required this.pin,
    this.description,
  });

  Map<String, dynamic> toJson() => {
    'to_phone': toPhone,
    'amount': amount,
    'currency': currency,
    'pin': pin,
    'description': description,
  };
}
```

```dart
// data/models/transfer_response_model.dart
class TransferResponseModel {
  final bool success;
  final String message;
  final TransactionData? transaction;
  final double? newBalance;

  TransferResponseModel.fromJson(Map<String, dynamic> json)
      : success = json['success'] as bool,
        message = json['message'] as String,
        transaction = json['data'] != null
            ? TransactionData.fromJson(json['data']['transaction'])
            : null,
        newBalance = json['data']?['new_balance']?.toDouble();

  TransferEntity toEntity() => TransferEntity(
    id: transaction!.id,
    referenceNumber: transaction!.referenceNumber,
    amount: transaction!.amount,
    currency: transaction!.currency,
    status: transaction!.status,
    newBalance: newBalance!,
    senderName: transaction!.senderName,
    receiverName: transaction!.receiverName,
    createdAt: transaction!.createdAt,
  );
}

class TransactionData {
  final int id;
  final String referenceNumber;
  final double amount;
  final String currency;
  final String status;
  final String senderName;
  final String receiverName;
  final DateTime createdAt;

  TransactionData.fromJson(Map<String, dynamic> json)
      : id = json['id'] as int,
        referenceNumber = json['reference_number'] as String,
        amount = (json['amount'] as num).toDouble(),
        currency = json['currency'] as String,
        status = json['status'] as String,
        senderName = json['sender']['name'] as String,
        receiverName = json['receiver']['name'] as String,
        createdAt = DateTime.parse(json['created_at'] as String);
}
```

```dart
// data/datasources/transfer_remote_datasource.dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class TransferRemoteDataSource {
  final http.Client client;
  final String baseUrl;

  TransferRemoteDataSource({required this.baseUrl, required this.client});

  Future<Map<String, dynamic>> transfer(
    Map<String, dynamic> requestBody,
    String token,
  ) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/transfer'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(requestBody),
    );

    final body = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode == 201) {
      return body;
    } else {
      throw TransferApiException(
        statusCode: response.statusCode,
        message: body['message'] as String? ?? 'فشل التحويل',
      );
    }
  }
}

class TransferApiException implements Exception {
  final int statusCode;
  final String message;
  TransferApiException({required this.statusCode, required this.message});

  @override
  String toString() => message;
}
```

```dart
// data/repositories/transfer_repository.dart
class TransferRepository implements ITransferRepository {
  final TransferRemoteDataSource dataSource;

  TransferRepository({required this.dataSource});

  @override
  Future<TransferEntity> transfer({
    required String toPhone,
    required double amount,
    required String currency,
    required String pin,
    String? description,
  }) async {
    final request = TransferRequestModel(
      toPhone: toPhone,
      amount: amount,
      currency: currency,
      pin: pin,
      description: description,
    );

    final token = await _getToken(); // من Secure Storage
    final response = await dataSource.transfer(request.toJson(), token);

    return TransferResponseModel.fromJson(response).toEntity();
  }

  Future<String> _getToken() async {
    final tokenService = TokenService(FlutterSecureStorage());
    return (await tokenService.getValidToken()) ?? '';
  }
}
```

## طبقة العرض (Presentation Layer) (BLoC)

```dart
// presentation/bloc/transfer_event.dart
abstract class TransferEvent {}

class SubmitTransfer extends TransferEvent {
  final String toPhone;
  final double amount;
  final String currency;
  final String pin;
  final String? description;

  SubmitTransfer({
    required this.toPhone,
    required this.amount,
    required this.currency,
    required this.pin,
    this.description,
  });
}

class ResetTransfer extends TransferEvent {}
```

```dart
// presentation/bloc/transfer_state.dart
abstract class TransferState {}

class TransferInitial extends TransferState {}

class TransferLoading extends TransferState {}

class TransferSuccess extends TransferState {
  final TransferEntity transfer;
  TransferSuccess(this.transfer);
}

class TransferFailure extends TransferState {
  final String error;
  TransferFailure(this.error);
}
```

```dart
// presentation/bloc/transfer_bloc.dart
import 'package:flutter_bloc/flutter_bloc.dart';

class TransferBloc extends Bloc<TransferEvent, TransferState> {
  final ITransferRepository repository;

  TransferBloc({required this.repository})
      : super(TransferInitial()) {
    on<SubmitTransfer>(_onSubmitTransfer);
    on<ResetTransfer>((event, emit) => emit(TransferInitial()));
  }

  Future<void> _onSubmitTransfer(
    SubmitTransfer event,
    Emitter<TransferState> emit,
  ) async {
    emit(TransferLoading());
    try {
      final result = await repository.transfer(
        toPhone: event.toPhone,
        amount: event.amount,
        currency: event.currency,
        pin: event.pin,
        description: event.description,
      );
      emit(TransferSuccess(result));
    } on TransferApiException catch (e) {
      emit(TransferFailure(e.message));
    } catch (e) {
      emit(TransferFailure('حدث خطأ غير متوقع: $e'));
    }
  }
}
```

## طبقة واجهة المستخدم (UI Layer)

```dart
// presentation/screens/transfer_screen.dart
class TransferScreen extends StatelessWidget {
  const TransferScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => TransferBloc(
        repository: TransferRepository(
          dataSource: TransferRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
          ),
        ),
      ),
      child: const TransferView(),
    );
  }
}

class TransferView extends StatelessWidget {
  const TransferView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تحويل')),
      body: BlocListener<TransferBloc, TransferState>(
        listener: (context, state) {
          if (state is TransferSuccess) {
            showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('تم التحويل'),
                content: Text(
                  'تم تحويل ${state.transfer.amount} '
                  '${state.transfer.currency} إلى ${state.transfer.receiverName}',
                ),
                actions: [
                  TextButton(
                    onPressed: () {
                      Navigator.pop(context);
                      context.read<TransferBloc>().add(ResetTransfer());
                    },
                    child: const Text('حسناً'),
                  ),
                ],
              ),
            );
          }
          if (state is TransferFailure) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.error)),
            );
          }
        },
        child: const TransferForm(),
      ),
    );
  }
}
```

```dart
// presentation/widgets/transfer_form.dart
class TransferForm extends StatefulWidget {
  const TransferForm({super.key});

  @override
  State<TransferForm> createState() => _TransferFormState();
}

class _TransferFormState extends State<TransferForm> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _amountController = TextEditingController();
  final _pinController = TextEditingController();
  final _descController = TextEditingController();
  String _currency = 'USD';

  @override
  void dispose() {
    _phoneController.dispose();
    _amountController.dispose();
    _pinController.dispose();
    _descController.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;

    context.read<TransferBloc>().add(SubmitTransfer(
      toPhone: _phoneController.text,
      amount: double.parse(_amountController.text),
      currency: _currency,
      pin: _pinController.text,
      description: _descController.text.isNotEmpty
          ? _descController.text
          : null,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TransferBloc, TransferState>(
      builder: (context, state) {
        return Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              TextFormField(
                controller: _phoneController,
                decoration: const InputDecoration(
                  labelText: 'رقم الهاتف',
                  prefixText: '+963 ',
                ),
                keyboardType: TextInputType.phone,
                validator: (v) =>
                    v?.isEmpty ?? true ? 'رقم الهاتف مطلوب' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _amountController,
                decoration: InputDecoration(
                  labelText: 'المبلغ',
                  suffixText: _currency,
                ),
                keyboardType: TextInputType.number,
                validator: (v) {
                  if (v?.isEmpty ?? true) return 'المبلغ مطلوب';
                  if (double.tryParse(v!) == null) return 'مبلغ غير صحيح';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _currency,
                items: const [
                  DropdownMenuItem(value: 'USD', child: Text('USD')),
                  DropdownMenuItem(value: 'SYP', child: Text('SYP')),
                ],
                onChanged: (v) => setState(() => _currency = v!),
                decoration: const InputDecoration(labelText: 'العملة'),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _pinController,
                decoration: const InputDecoration(labelText: 'PIN'),
                obscureText: true,
                maxLength: 4,
                keyboardType: TextInputType.number,
                validator: (v) =>
                    v?.length != 4 ? 'PIN يجب أن يكون 4 أرقام' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _descController,
                decoration: const InputDecoration(labelText: 'وصف (اختياري)'),
                maxLength: 255,
              ),
              const SizedBox(height: 24),
              SizedBox(
                height: 48,
                child: ElevatedButton(
                  onPressed: state is TransferLoading ? null : _submit,
                  child: state is TransferLoading
                      ? const CircularProgressIndicator()
                      : const Text('تحويل'),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
```
