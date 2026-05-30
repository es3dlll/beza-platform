import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/services/auth_service.dart';

class ChangePinScreen extends ConsumerStatefulWidget {
  const ChangePinScreen({super.key});
  @override
  ConsumerState<ChangePinScreen> createState() => _ChangePinScreenState();
}

class _ChangePinScreenState extends ConsumerState<ChangePinScreen> {
  final _oldPinCtrl = TextEditingController();
  final _newPinCtrl = TextEditingController();
  final _confirmPinCtrl = TextEditingController();
  bool _isLoading = false;
  String? _error;
  String? _success;

  @override
  void dispose() {
    _oldPinCtrl.dispose();
    _newPinCtrl.dispose();
    _confirmPinCtrl.dispose();
    super.dispose();
  }

  Future<void> _changePin() async {
    final oldPin = _oldPinCtrl.text.trim();
    final newPin = _newPinCtrl.text.trim();
    final confirmPin = _confirmPinCtrl.text.trim();

    if (oldPin.length != 6 || newPin.length != 6) {
      setState(() => _error = 'يجب أن يتكون رمز PIN من 6 أرقام');
      return;
    }
    if (newPin != confirmPin) {
      setState(() => _error = 'رمز PIN الجديد غير متطابق');
      return;
    }
    if (oldPin == newPin) {
      setState(() => _error = 'رمز PIN الجديد يجب أن يختلف عن القديم');
      return;
    }

    setState(() { _isLoading = true; _error = null; _success = null; });
    try {
      final api = ApiClient();
      final service = AuthService(api);
      await service.changePin(currentPin: oldPin, newPin: newPin, newPinConfirmation: confirmPin);
      setState(() {
        _isLoading = false;
        _success = 'تم تغيير رمز PIN بنجاح';
        _oldPinCtrl.clear();
        _newPinCtrl.clear();
        _confirmPinCtrl.clear();
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'فشل تغيير رمز PIN';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تغيير رمز PIN')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: AppTheme.cardDecoration,
            child: Column(
              children: [
                TextField(
                  controller: _oldPinCtrl,
                  decoration: const InputDecoration(
                    labelText: 'رمز PIN الحالي',
                    prefixIcon: Icon(Icons.lock_outline),
                  ),
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _newPinCtrl,
                  decoration: const InputDecoration(
                    labelText: 'رمز PIN الجديد',
                    prefixIcon: Icon(Icons.lock),
                  ),
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _confirmPinCtrl,
                  decoration: const InputDecoration(
                    labelText: 'تأكيد رمز PIN الجديد',
                    prefixIcon: Icon(Icons.lock),
                  ),
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _changePin,
                    child: _isLoading
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('تغيير الرمز'),
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  Text(_error!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error, fontSize: 14), textAlign: TextAlign.center),
                ],
                if (_success != null) ...[
                  const SizedBox(height: 16),
                  Text(_success!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.success, fontSize: 14), textAlign: TextAlign.center),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
