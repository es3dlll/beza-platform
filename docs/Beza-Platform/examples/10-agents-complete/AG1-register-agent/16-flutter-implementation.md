# 16 - تطبيق Flutter (Flutter Implementation) - تسجيل وكيل جديد (Register Agent)

```dart
// data/models/agent_model.dart
class AgentModel {
  final int id; final int userId; final String fullName; final String phone;
  final String status; final double? rating; final bool available; final DateTime createdAt;
  AgentModel({required this.id, required this.userId, required this.fullName, required this.phone, required this.status, this.rating, required this.available, required this.createdAt});
  factory AgentModel.fromJson(Map<String, dynamic> json) => AgentModel(
    id: json['id'], userId: json['user_id'], fullName: json['full_name'], phone: json['phone'],
    status: json['status'], rating: (json['rating'] as num?)?.toDouble(), available: json['available'] ?? false,
    createdAt: DateTime.parse(json['created_at']),
  );
}

// domain/repositories/i_agent_repository.dart
abstract class IAgentRepository {
  Future<AgentModel> register({required String fullName, required String phone, required String idNumber, String? idPhoto, double? lat, double? lng, String? address});
  Future<AgentModel> getProfile();
}

// data/repositories/agent_repository.dart
class AgentRepository implements IAgentRepository {
  final http.Client client;
  final TokenService _tokenService;

  AgentRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<AgentModel> register({required String fullName, required String phone, required String idNumber, String? idPhoto, double? lat, double? lng, String? address}) async {
    final token = await _getToken();
    final response = await client.post(
      Uri.parse('https://api.beza.sy/api/v1/agents/register'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'full_name': fullName, 'phone': phone, 'id_number': idNumber, 'id_photo': idPhoto, 'location_lat': lat, 'location_lng': lng, 'address': address}),
    );
    if (response.statusCode == 201) return AgentModel.fromJson(jsonDecode(response.body)['data']);
    throw Exception(jsonDecode(response.body)['message'] ?? 'فشل التسجيل');
  }

  @override
  Future<AgentModel> getProfile() async {
    final token = await _getToken();
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agent/profile'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return AgentModel.fromJson(jsonDecode(response.body)['data']);
    throw Exception('فشل تحميل الملف الشخصي');
  }
}

// presentation/bloc/agent_register_bloc.dart
abstract class AgentRegisterEvent {}
class SubmitAgentRegister extends AgentRegisterEvent {
  final String fullName; final String phone; final String idNumber; final String? idPhoto;
  final double? lat; final double? lng; final String? address;
  SubmitAgentRegister({required this.fullName, required this.phone, required this.idNumber, this.idPhoto, this.lat, this.lng, this.address});
}

abstract class AgentRegisterState {}
class AgentRegisterInitial extends AgentRegisterState {}
class AgentRegisterLoading extends AgentRegisterState {}
class AgentRegisterSuccess extends AgentRegisterState { final AgentModel agent; AgentRegisterSuccess(this.agent); }
class AgentRegisterFailure extends AgentRegisterState { final String error; AgentRegisterFailure(this.error); }

class AgentRegisterBloc extends Bloc<AgentRegisterEvent, AgentRegisterState> {
  final IAgentRepository repository;
  AgentRegisterBloc({required this.repository}) : super(AgentRegisterInitial()) {
    on<SubmitAgentRegister>(_onSubmit);
  }
  Future<void> _onSubmit(SubmitAgentRegister event, Emitter<AgentRegisterState> emit) async {
    emit(AgentRegisterLoading());
    try {
      final agent = await repository.register(fullName: event.fullName, phone: event.phone, idNumber: event.idNumber, idPhoto: event.idPhoto, lat: event.lat, lng: event.lng, address: event.address);
      emit(AgentRegisterSuccess(agent));
    } catch (e) { emit(AgentRegisterFailure(e.toString())); }
  }
}

// presentation/screens/agent_register_screen.dart
class AgentRegisterScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('التسجيل كموزع')),
      body: BlocProvider(
        create: (_) => AgentRegisterBloc(repository: AgentRepository(http.Client())),
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('بيانات التسجيل', style: Theme.of(context).textTheme.headlineSmall),
            SizedBox(height: 16),
            TextFormField(decoration: InputDecoration(labelText: 'الاسم الكامل', border: OutlineInputBorder(), prefixIcon: Icon(Icons.person))),
            SizedBox(height: 12),
            TextFormField(decoration: InputDecoration(labelText: 'رقم الهاتف', border: OutlineInputBorder(), prefixIcon: Icon(Icons.phone)), keyboardType: TextInputType.phone),
            SizedBox(height: 12),
            TextFormField(decoration: InputDecoration(labelText: 'رقم الهوية', border: OutlineInputBorder(), prefixIcon: Icon(Icons.badge))),
            SizedBox(height: 12),
            TextFormField(decoration: InputDecoration(labelText: 'العنوان', border: OutlineInputBorder(), prefixIcon: Icon(Icons.location_on)), maxLines: 2),
            SizedBox(height: 24),
            BlocConsumer<AgentRegisterBloc, AgentRegisterState>(
              listener: (context, state) {
                if (state is AgentRegisterSuccess) Navigator.pushReplacementNamed(context, '/agent/dashboard');
              },
              builder: (context, state) {
                if (state is AgentRegisterLoading) return Center(child: CircularProgressIndicator());
                return ElevatedButton(
                  onPressed: () => context.read<AgentRegisterBloc>().add(SubmitAgentRegister(fullName: '', phone: '', idNumber: '')),
                  child: Text('تسجيل كموزع', style: TextStyle(fontSize: 18)),
                  style: ElevatedButton.styleFrom(padding: EdgeInsets.symmetric(vertical: 16)),
                );
              },
            ),
          ]),
        ),
      ),
    );
  }
}
```
