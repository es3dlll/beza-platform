# 16 - تطبيق Flutter (Flutter Implementation) - الموافقة على التجار والوكلاء (Approval)

## MerchantApplicationList

```dart
class MerchantApplicationList extends StatelessWidget {
  const MerchantApplicationList({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => MerchantApprovalBloc(
        repository: ApprovalRepository(
          dataSource: AdminRemoteDataSource(
            baseUrl: 'http://localhost:8000',
            client: http.Client(),
            tokenService: TokenService(FlutterSecureStorage()),
          ),
        ),
      )..add(LoadMerchantApplications()),
      child: Scaffold(
        appBar: AppBar(title: const Text('طلبات التجار')),
        body: BlocBuilder<MerchantApprovalBloc, MerchantApprovalState>(
          builder: (context, state) {
            if (state is MerchantApprovalLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (state is MerchantApprovalLoaded) {
              return ListView.builder(
                itemCount: state.applications.length,
                itemBuilder: (context, index) {
                  final app = state.applications[index];
                  return Card(
                    margin: const EdgeInsets.all(8),
                    child: ExpansionTile(
                      leading: CircleAvatar(child: Text(app.businessName[0])),
                      title: Text(app.businessName),
                      subtitle: Text('مقدم: ${app.userName}'),
                      children: [
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            children: [
                              _InfoRow('رقم السجل', app.commercialRegNo),
                              _InfoRow('النشاط', app.businessType),
                              _InfoRow('الهاتف', app.phone),
                              const SizedBox(height: 16),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                children: [
                                  ElevatedButton.icon(
                                    onPressed: () {
                                      context.read<MerchantApprovalBloc>()
                                        .add(ApproveMerchant(app.id));
                                    },
                                    icon: const Icon(Icons.check),
                                    label: const Text('موافقة'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.green,
                                    ),
                                  ),
                                  ElevatedButton.icon(
                                    onPressed: () => _showRejectDialog(context, app.id),
                                    icon: const Icon(Icons.close),
                                    label: const Text('رفض'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.red,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              );
            }
            return const SizedBox();
          },
        ),
      ),
    );
  }

  void _showRejectDialog(BuildContext context, int applicationId) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('سبب الرفض'),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(hintText: 'اكتب سبب الرفض...'),
          maxLines: 3,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () {
              context.read<MerchantApprovalBloc>()
                .add(RejectMerchant(applicationId, controller.text));
              Navigator.pop(ctx);
            },
            child: const Text('رفض'),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
          ),
        ],
      ),
    );
  }
}
```
