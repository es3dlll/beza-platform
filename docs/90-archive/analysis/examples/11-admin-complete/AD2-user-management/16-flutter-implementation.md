# 16 - تطبيق Flutter (Flutter Implementation) - إدارة المستخدمين (Admin User Management)

ملاحظة: إدارة المستخدمين مخصصة للمشرف وتُبنى في React Admin. لكن يمكن عرض قائمة المستخدمين في Flutter للتطبيقات الإدارية.

## User List Screen

```dart
// data/datasources/admin_remote_datasource.dart
class AdminRemoteDataSource {
  final http.Client client;
  final String baseUrl;
  final TokenService _tokenService;

  AdminRemoteDataSource({
    required this.baseUrl,
    required this.client,
    required TokenService tokenService,
  }) : _tokenService = tokenService;

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  Future<Map<String, dynamic>> getUsers({String? search, String? status}) async {
    final token = await _getToken();
    final queryParams = <String, String>{};
    if (search != null) queryParams['search'] = search;
    if (status != null) queryParams['status'] = status;
    final uri = Uri.parse('$baseUrl/api/v1/admin/users').replace(queryParameters: queryParams.isNotEmpty ? queryParams : null);
    final response = await client.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    });
    if (response.statusCode == 200) return jsonDecode(response.body)['data'];
    throw Exception('فشل تحميل المستخدمين');
  }
}

// lib/features/admin/presentation/screens/user_list_screen.dart
class UserListScreen extends StatelessWidget {
  const UserListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => UserListBloc(
        repository: UserRepository(
          dataSource: AdminRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
            tokenService: TokenService(FlutterSecureStorage()),
          ),
        ),
      )..add(LoadUsers()),
      child: const UserListView(),
    );
  }
}

class UserListView extends StatelessWidget {
  const UserListView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إدارة المستخدمين')),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: const InputDecoration(
                hintText: 'بحث بالاسم أو الهاتف',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) {
                context.read<UserListBloc>().add(SearchUsers(value));
              },
            ),
          ),
          // Filter chips
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                FilterChip(
                  label: const Text('الكل'),
                  selected: true,
                  onSelected: (_) {},
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('نشط'),
                  selected: false,
                  onSelected: (_) {},
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('معلق'),
                  selected: false,
                  onSelected: (_) {},
                ),
              ],
            ),
          ),
          // User list
          Expanded(
            child: BlocBuilder<UserListBloc, UserListState>(
              builder: (context, state) {
                if (state is UserListLoading) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (state is UserListError) {
                  return Center(child: Text(state.message));
                }
                if (state is UserListLoaded) {
                  return ListView.builder(
                    itemCount: state.users.length,
                    itemBuilder: (context, index) {
                      final user = state.users[index];
                      return ListTile(
                        leading: CircleAvatar(
                          child: Text(user.name[0]),
                        ),
                        title: Text(user.name),
                        subtitle: Text(user.phone),
                        trailing: _buildStatusChip(user.status),
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => UserDetailScreen(userId: user.id),
                            ),
                          );
                        },
                      );
                    },
                  );
                }
                return const SizedBox();
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    switch (status) {
      case 'active':
        color = Colors.green;
        break;
      case 'suspended':
        color = Colors.orange;
        break;
      case 'blocked':
        color = Colors.red;
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(status, style: TextStyle(color: color, fontSize: 12)),
    );
  }
}
```
