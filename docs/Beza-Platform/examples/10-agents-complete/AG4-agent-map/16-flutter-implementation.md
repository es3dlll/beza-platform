# 16 - تطبيق Flutter (Flutter Implementation) - خريطة الوكلاء (Agent Map)

```dart
// domain/repositories/i_agent_map_repository.dart
abstract class IAgentMapRepository {
  Future<List<NearbyAgent>> getNearby(double lat, double lng, {int radius = 5, bool? available});
  Future<AgentDetail> getAgentDetail(int id);
  Future<void> updateLocation(double lat, double lng);
  Future<bool> toggleOnline();
}

// data/models/agent_map_model.dart
class NearbyAgent {
  final int id; final String fullName; final String phone; final double lat; final double lng;
  final double distance; final bool available; final double? rating;
  NearbyAgent({required this.id, required this.fullName, required this.phone, required this.lat, required this.lng, required this.distance, required this.available, this.rating});
  factory NearbyAgent.fromJson(Map<String, dynamic> json) => NearbyAgent(
    id: json['id'], fullName: json['full_name'], phone: json['phone'],
    lat: (json['location_lat'] as num).toDouble(), lng: (json['location_lng'] as num).toDouble(),
    distance: (json['distance'] as num).toDouble(), available: json['available'],
    rating: (json['rating'] as num?)?.toDouble(),
  );
}

class AgentDetail {
  final int id; final String fullName; final String phone; final double lat; final double lng;
  final bool available; final double? rating; final String address;
  AgentDetail({required this.id, required this.fullName, required this.phone, required this.lat, required this.lng, required this.available, this.rating, required this.address});
  factory AgentDetail.fromJson(Map<String, dynamic> json) => AgentDetail(
    id: json['id'], fullName: json['full_name'], phone: json['phone'],
    lat: (json['location_lat'] as num).toDouble(), lng: (json['location_lng'] as num).toDouble(),
    available: json['available'], rating: (json['rating'] as num?)?.toDouble(),
    address: json['address'] ?? '',
  );
}

// data/repositories/agent_map_repository.dart
class AgentMapRepository implements IAgentMapRepository {
  final http.Client client;
  final TokenService _tokenService;

  AgentMapRepository(this.client, this._tokenService);

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  @override
  Future<List<NearbyAgent>> getNearby(double lat, double lng, {int radius = 5, bool? available}) async {
    final params = 'lat=$lat&lng=$lng&radius=$radius${available != null ? '&available=$available' : ''}';
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agents/nearby?$params'));
    if (response.statusCode == 200) return (jsonDecode(response.body)['data'] as List).map((e) => NearbyAgent.fromJson(e)).toList();
    throw Exception('فشل تحميل الوكلاء القريبين');
  }

  @override
  Future<AgentDetail> getAgentDetail(int id) async {
    final response = await client.get(Uri.parse('https://api.beza.sy/api/v1/agents/$id'));
    if (response.statusCode == 200) return AgentDetail.fromJson(jsonDecode(response.body)['data']);
    throw Exception('الوكيل غير موجود');
  }

  @override
  Future<void> updateLocation(double lat, double lng) async {
    final token = await _getToken();
    final response = await client.put(
      Uri.parse('https://api.beza.sy/api/v1/agent/location'),
      headers: {'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'lat': lat, 'lng': lng}),
    );
    if (response.statusCode != 200) throw Exception('فشل تحديث الموقع');
  }

  @override
  Future<bool> toggleOnline() async {
    final token = await _getToken();
    final response = await client.post(Uri.parse('https://api.beza.sy/api/v1/agent/toggle-online'), headers: {'Authorization': 'Bearer $token'});
    if (response.statusCode == 200) return jsonDecode(response.body)['available'];
    throw Exception('فشل تبديل الحالة');
  }
}

// presentation/bloc/agent_map_bloc.dart
abstract class AgentMapEvent {}
class LoadNearby extends AgentMapEvent { final double lat; final double lng; final int radius; final bool? available; LoadNearby(this.lat, this.lng, {this.radius = 5, this.available}); }
class UpdateMyLocation extends AgentMapEvent { final double lat; final double lng; UpdateMyLocation(this.lat, this.lng); }
class ToggleOnline extends AgentMapEvent {}

abstract class AgentMapState {}
class AgentMapInitial extends AgentMapState {}
class AgentMapLoading extends AgentMapState {}
class AgentMapLoaded extends AgentMapState { final List<NearbyAgent> agents; final double myLat; final double myLng; AgentMapLoaded({required this.agents, required this.myLat, required this.myLng}); }
class AgentMapDetailLoaded extends AgentMapState { final AgentDetail detail; AgentMapDetailLoaded(this.detail); }
class AgentMapOnlineToggled extends AgentMapState { final bool available; AgentMapOnlineToggled(this.available); }
class AgentMapFailure extends AgentMapState { final String error; AgentMapFailure(this.error); }

class AgentMapBloc extends Bloc<AgentMapEvent, AgentMapState> {
  final IAgentMapRepository repository;
  AgentMapBloc({required this.repository}) : super(AgentMapInitial()) {
    on<LoadNearby>(_onLoadNearby);
    on<UpdateMyLocation>(_onUpdateLocation);
    on<ToggleOnline>(_onToggleOnline);
  }
  Future<void> _onLoadNearby(LoadNearby event, Emitter<AgentMapState> emit) async {
    emit(AgentMapLoading());
    try { emit(AgentMapLoaded(agents: await repository.getNearby(event.lat, event.lng, radius: event.radius, available: event.available), myLat: event.lat, myLng: event.lng)); }
    catch (e) { emit(AgentMapFailure(e.toString())); }
  }
  Future<void> _onUpdateLocation(UpdateMyLocation event, Emitter<AgentMapState> emit) async {
    try { await repository.updateLocation(event.lat, event.lng); }
    catch (e) { emit(AgentMapFailure(e.toString())); }
  }
  Future<void> _onToggleOnline(ToggleOnline event, Emitter<AgentMapState> emit) async {
    try { emit(AgentMapOnlineToggled(await repository.toggleOnline())); }
    catch (e) { emit(AgentMapFailure(e.toString())); }
  }
}

// presentation/screens/agent_map_screen.dart
class AgentMapScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('الوكلاء القريبون'), actions: [
        BlocBuilder<AgentMapBloc, AgentMapState>(
          builder: (context, state) => Switch(
            value: state is AgentMapOnlineToggled ? state.available : true,
            onChanged: (v) => context.read<AgentMapBloc>().add(ToggleOnline()),
          ),
        ),
      ]),
      body: BlocProvider(
        create: (_) => AgentMapBloc(repository: AgentMapRepository(http.Client()))..add(LoadNearby(33.51, 36.28)),
        child: BlocBuilder<AgentMapBloc, AgentMapState>(
          builder: (context, state) {
            if (state is AgentMapLoading) return Center(child: CircularProgressIndicator());
            if (state is AgentMapLoaded) return GoogleMap(
              initialCameraPosition: CameraPosition(target: LatLng(state.myLat, state.myLng), zoom: 13),
              markers: state.agents.map((a) => Marker(
                markerId: MarkerId(a.id.toString()),
                position: LatLng(a.lat, a.lng),
                infoWindow: InfoWindow(title: a.fullName, snippet: '${a.distance.toStringAsFixed(1)} كم'),
              )).toSet(),
            );
            if (state is AgentMapFailure) return Center(child: Text('خطأ: ${state.error}'));
            return Center(child: Text('اسحب للبحث'));
          },
        ),
      ),
    );
  }
}
```
