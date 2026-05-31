# 16 - تطبيق Flutter (Flutter Implementation) - إدارة النزاعات (Disputes)

## Submit Dispute Screen

```dart
class SubmitDisputeScreen extends StatefulWidget {
  final int transactionId;
  const SubmitDisputeScreen({super.key, required this.transactionId});

  @override
  State<SubmitDisputeScreen> createState() => _SubmitDisputeScreenState();
}

class _SubmitDisputeScreenState extends State<SubmitDisputeScreen> {
  final _formKey = GlobalKey<FormState>();
  final _reasonController = TextEditingController();
  final _descController = TextEditingController();
  List<File> _evidenceFiles = [];

  @override
  void dispose() {
    _reasonController.dispose();
    _descController.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;

    context.read<DisputeBloc>().add(SubmitDispute(
      transactionId: widget.transactionId,
      reason: _reasonController.text,
      description: _descController.text,
      evidenceFiles: _evidenceFiles,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تقديم نزاع')),
      body: BlocListener<DisputeBloc, DisputeState>(
        listener: (context, state) {
          if (state is DisputeSubmitted) {
            showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('تم تقديم النزاع'),
                content: const Text('سيتم مراجعة نزاعك خلال 48 ساعة'),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('حسناً'),
                  ),
                ],
              ),
            );
          }
        },
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              TextFormField(
                controller: _reasonController,
                decoration: const InputDecoration(labelText: 'سبب النزاع'),
                validator: (v) => v?.isEmpty ?? true ? 'مطلوب' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _descController,
                decoration: const InputDecoration(labelText: 'وصف المشكلة'),
                maxLines: 5,
                validator: (v) => (v?.length ?? 0) < 20 ? '20 حرفاً على الأقل' : null,
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _pickFiles,
                icon: const Icon(Icons.attach_file),
                label: Text('إرفاق أدلة (${_evidenceFiles.length}/5)'),
              ),
              if (_evidenceFiles.isNotEmpty) ...[
                const SizedBox(height: 8),
                ..._evidenceFiles.map((f) => ListTile(
                  leading: const Icon(Icons.insert_drive_file),
                  title: Text(f.path.split('/').last),
                  trailing: IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => setState(() => _evidenceFiles.remove(f)),
                  ),
                )),
              ],
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: _submit,
                child: const Text('تقديم النزاع'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _pickFiles() async {
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'png', 'pdf'],
    );
    if (result != null) {
      setState(() {
        _evidenceFiles = result.files.map((f) => File(f.path!)).toList();
      });
    }
  }
}
```
