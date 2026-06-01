# 16 - تطبيق Flutter (Flutter Implementation) - إصدار البطاقة

```dart
// data/models/card_model.dart
class CardModel {
  final int id;
  final String type;
  final String currency;
  final String maskedPan;
  final String expiryDate;
  final String status;
  final double dailyLimit;
  CardModel({required this.id, required this.type, required this.currency, required this.maskedPan, required this.expiryDate, required this.status, required this.dailyLimit});
  factory CardModel.fromJson(Map<String, dynamic> json) => CardModel(
    id: json['id'], type: json['type'], currency: json['currency'],
    maskedPan: json['masked_pan'], expiryDate: json['expiry_date'],
    status: json['status'], dailyLimit: (json['daily_limit'] as num).toDouble(),
  );
}

// domain/repositories/i_card_repository.dart
abstract class ICardRepository {
  Future<CardModel> issueCard({required String type, required String currency, double? dailyLimit, String? shippingAddress});
  Future<List<CardModel>> getCards();
  Future<CardModel> getCard(int id);
}

// data/repositories/card_repository.dart
class CardRepository implements ICardRepository {
  final http.Client client;
  final TokenService _tokenService;

  CardRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<CardModel> issueCard({required String type, required String currency, double? dailyLimit, String? shippingAddress}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/cards/issue'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'type': type, 'currency': currency, 'daily_limit': dailyLimit, 'shipping_address': shippingAddress}),
    );
    if (response.statusCode == 201) return CardModel.fromJson(jsonDecode(response.body)['data']);
    throw Exception(jsonDecode(response.body)['message'] ?? 'فشل إصدار البطاقة');
  }
  @override
  Future<List<CardModel>> getCards() async {
    final token = await _getToken();
    final response = await client.get(
      Uri.parse('https://api.beza.sy/api/v1/cards'),
      headers: {'Authorization': 'Bearer $token'},
    );
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => CardModel.fromJson(e)).toList();
    throw Exception('فشل تحميل البطاقات');
  }
  @override
  Future<CardModel> getCard(int id) async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/cards/$id'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return CardModel.fromJson(jsonDecode(response.body)['data']);
    throw Exception('البطاقة غير موجودة');
  }
}

// presentation/bloc/issue_card_bloc.dart
abstract class IssueCardEvent {}
class SubmitIssueCard extends IssueCardEvent {
  final String type; final String currency; final double? dailyLimit; final String? shippingAddress;
  SubmitIssueCard({required this.type, required this.currency, this.dailyLimit, this.shippingAddress});
}
abstract class IssueCardState {}
class IssueCardInitial extends IssueCardState {}
class IssueCardLoading extends IssueCardState {}
class IssueCardSuccess extends IssueCardState { final CardModel card; IssueCardSuccess(this.card); }
class IssueCardFailure extends IssueCardState { final String error; IssueCardFailure(this.error); }

class IssueCardBloc extends Bloc<IssueCardEvent, IssueCardState> {
  final ICardRepository repository;
  IssueCardBloc({required this.repository}) : super(IssueCardInitial()) {
    on<SubmitIssueCard>(_onSubmit);
  }
  Future<void> _onSubmit(SubmitIssueCard event, Emitter<IssueCardState> emit) async {
    emit(IssueCardLoading());
    try {
      final card = await repository.issueCard(type: event.type, currency: event.currency, dailyLimit: event.dailyLimit, shippingAddress: event.shippingAddress);
      emit(IssueCardSuccess(card));
    } catch (e) { emit(IssueCardFailure(e.toString())); }
  }
}

// presentation/screens/issue_card_screen.dart
class IssueCardScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('إصدار بطاقة جديدة')),
      body: BlocProvider(
        create: (_) => IssueCardBloc(repository: CardRepository(http.Client())),
        child: _IssueCardForm(),
      ),
    );
  }
}

class _IssueCardForm extends StatefulWidget {
  @override
  State<_IssueCardForm> createState() => _IssueCardFormState();
}
class _IssueCardFormState extends State<_IssueCardForm> {
  String _type = 'virtual'; String _currency = 'SYP'; double _dailyLimit = 50000; bool _isPhysical = false;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: EdgeInsets.all(16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text('نوع البطاقة', style: TextStyle(fontWeight: FontWeight.bold)),
        Row(children: [
          ChoiceChip(label: Text('افتراضية'), selected: _type == 'virtual', onSelected: (_) => setState(() => _type = 'virtual')),
          SizedBox(width: 8),
          ChoiceChip(label: Text('فيزيائية'), selected: _type == 'physical', onSelected: (_) => setState(() { _type = 'physical'; _isPhysical = true; })),
        ]),
        SizedBox(height: 16),
        Text('العملة', style: TextStyle(fontWeight: FontWeight.bold)),
        Row(children: [
          ChoiceChip(label: Text('SYP'), selected: _currency == 'SYP', onSelected: (_) => setState(() => _currency = 'SYP')),
          SizedBox(width: 8),
          ChoiceChip(label: Text('USD'), selected: _currency == 'USD', onSelected: (_) => setState(() => _currency = 'USD')),
        ]),
        SizedBox(height: 16),
        TextField(
          decoration: InputDecoration(labelText: 'الحد اليومي (SYP)', border: OutlineInputBorder()),
          keyboardType: TextInputType.number,
          onChanged: (v) => _dailyLimit = double.tryParse(v) ?? 50000,
        ),
        if (_isPhysical) SizedBox(height: 16),
        if (_isPhysical)
          TextField(
            decoration: InputDecoration(labelText: 'عنوان الشحن', border: OutlineInputBorder()),
            maxLines: 3,
          ),
        SizedBox(height: 24),
        BlocConsumer<IssueCardBloc, IssueCardState>(
          listener: (context, state) {
            if (state is IssueCardSuccess) showDialog(context: context, builder: (_) => AlertDialog(
              title: Text('تم بنجاح'), content: Text('تم إصدار البطاقة بنجاح'),
              actions: [TextButton(onPressed: () => Navigator.pop(context), child: Text('حسناً'))],
            ));
          },
          builder: (context, state) {
            if (state is IssueCardLoading) return Center(child: CircularProgressIndicator());
            return ElevatedButton(
              onPressed: () => context.read<IssueCardBloc>().add(SubmitIssueCard(type: _type, currency: _currency, dailyLimit: _dailyLimit)),
              child: Text('إصدار البطاقة', style: TextStyle(fontSize: 18)),
              style: ElevatedButton.styleFrom(padding: EdgeInsets.symmetric(vertical: 16)),
            );
          },
        ),
        if (state is IssueCardFailure) Padding(
          padding: EdgeInsets.only(top: 16),
          child: Text('خطأ: ${(state as IssueCardFailure).error}', style: TextStyle(color: Colors.red)),
        ),
      ]),
    );
  }
}
```
