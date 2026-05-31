import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/money.dart';
import '../core/providers/transfer_provider.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class TransferScreen extends ConsumerWidget {
  final String baseUrl;

  const TransferScreen({super.key, required this.baseUrl});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(transferStateProvider);
    final notifier = ref.read(transferStateProvider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: const Text('تحويل'),
        leading: state.currentStep != TransferStep.recipient
            ? IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () {
                  if (state.currentStep == TransferStep.confirmation) {
                    notifier.goBackToAmount();
                  } else if (state.currentStep == TransferStep.amount) {
                    notifier.goBackToRecipient();
                  } else {
                    Navigator.pop(context);
                  }
                },
              )
            : null,
      ),
      body: Directionality(
        textDirection: TextDirection.rtl,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: _buildStepContent(context, ref, state, notifier),
        ),
      ),
    );
  }

  Widget _buildStepContent(
    BuildContext context,
    WidgetRef ref,
    TransferState state,
    TransferNotifier notifier,
  ) {
    switch (state.currentStep) {
      case TransferStep.recipient:
        return _RecipientStep(notifier: notifier, state: state);
      case TransferStep.amount:
        return _AmountStep(notifier: notifier, state: state);
      case TransferStep.confirmation:
        return _ConfirmationStep(notifier: notifier, state: state, baseUrl: baseUrl);
    }
  }
}

class _RecipientStep extends StatefulWidget {
  final TransferNotifier notifier;
  final TransferState state;

  const _RecipientStep({required this.notifier, required this.state});

  @override
  State<_RecipientStep> createState() => _RecipientStepState();
}

class _RecipientStepState extends State<_RecipientStep> {
  late TextEditingController _emailController;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController(text: widget.state.recipientEmail ?? '');
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'إلى من تريد التحويل؟',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 8),
        Text(
          'أدخل البريد الإلكتروني للمستلم',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Colors.grey[600],
              ),
        ),
        const SizedBox(height: 24),
        TextField(
          controller: _emailController,
          keyboardType: TextInputType.emailAddress,
          textDirection: TextDirection.ltr,
          decoration: InputDecoration(
            labelText: 'البريد الإلكتروني للمستلم',
            border: const OutlineInputBorder(),
            prefixIcon: const Icon(Icons.person_outline),
            errorText: state.recipientError,
          ),
          onChanged: (value) {
            widget.notifier.setRecipientEmail(value);
          },
          onSubmitted: (_) => widget.notifier.lookupRecipient(),
        ),
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: state.isLookingUpRecipient
              ? null
              : () => widget.notifier.lookupRecipient(),
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
          ),
          child: state.isLookingUpRecipient
              ? const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('بحث', style: TextStyle(fontSize: 16)),
        ),
      ],
    );
  }
}

class _AmountStep extends StatefulWidget {
  final TransferNotifier notifier;
  final TransferState state;

  const _AmountStep({required this.notifier, required this.state});

  @override
  State<_AmountStep> createState() => _AmountStepState();
}

class _AmountStepState extends State<_AmountStep> {
  late TextEditingController _amountController;

  @override
  void initState() {
    super.initState();
    _amountController = TextEditingController(
      text: widget.state.amount != null
          ? (widget.state.amount!.syp).toStringAsFixed(3)
          : '',
    );
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    final balance = state.balance;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (state.recipientName != null)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const CircleAvatar(
                    child: Icon(Icons.person),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'التحويل إلى',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.grey,
                            ),
                      ),
                      Text(
                        state.recipientName!,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      Text(
                        state.recipientEmail ?? '',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.edit),
                    onPressed: () => widget.notifier.goBackToRecipient(),
                  ),
                ],
              ),
            ),
          ),
        const SizedBox(height: 16),
        Text(
          'المبلغ',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 8),
        Text(
          'أدخل المبلغ الذي تريد تحويله',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Colors.grey[600],
              ),
        ),
        const SizedBox(height: 24),
        TextField(
          controller: _amountController,
          keyboardType:
              const TextInputType.numberWithOptions(decimal: true),
          textDirection: TextDirection.ltr,
          decoration: InputDecoration(
            labelText: 'المبلغ (ل.س)',
            border: const OutlineInputBorder(),
            prefixIcon: const Icon(Icons.monetization_on_outlined),
            errorText: state.amountError,
            hintText: 'مثال: 1000.000',
          ),
          onChanged: (value) {
            final parsed = double.tryParse(value);
            if (parsed != null && parsed > 0) {
              widget.notifier.setAmount(Money.fromSYP(parsed));
            } else {
              widget.notifier.setAmount(null);
            }
          },
        ),
        if (balance != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              'الرصيد المتاح: ${balance.format()}',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Colors.grey,
                  ),
            ),
          ),
        const SizedBox(height: 4),
        Text(
          'الحد الأدنى: ${Money.fromFils(1000).format()} - الحد الأقصى: ${Money.fromFils(100000000000).format()}',
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.grey,
              ),
        ),
        const SizedBox(height: 32),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _buildInfoRow(context, 'الرسوم:', Money.fromFils(0).format()),
                const Divider(),
                _buildInfoRow(
                  context,
                  'الصافي للمستلم:',
                  state.amount != null
                      ? (state.amount! - Money.fromFils(0)).format()
                      : '—',
                  isBold: true,
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: () => widget.notifier.proceedToConfirmation(),
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
          ),
          child: const Text('التالي', style: TextStyle(fontSize: 16)),
        ),
      ],
    );
  }

  Widget _buildInfoRow(BuildContext context, String label, String value,
      {bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: Theme.of(context).textTheme.bodyMedium),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
                ),
          ),
        ],
      ),
    );
  }
}

class _ConfirmationStep extends ConsumerWidget {
  final TransferNotifier notifier;
  final TransferState state;
  final String baseUrl;

  const _ConfirmationStep({
    required this.notifier,
    required this.state,
    required this.baseUrl,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (state.isSuccess) {
      return _buildSuccessView(context, ref);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'تأكيد التحويل',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 24),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'المستلم',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 4),
                Text(
                  state.recipientName ?? '',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(
                  state.recipientEmail ?? '',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const Divider(height: 32),
                _buildSummaryRow(context, 'المبلغ:', state.amount?.format() ?? ''),
                const SizedBox(height: 8),
                _buildSummaryRow(context, 'الرسوم:', state.fee?.format() ?? '0.000 ل.س'),
                const Divider(height: 32),
                _buildSummaryRow(
                  context,
                  'المبلغ المحول:',
                  state.netAmount?.format() ?? '',
                  isBold: true,
                ),
                if (state.balance != null && state.amount != null) ...[
                  const SizedBox(height: 8),
                  _buildSummaryRow(
                    context,
                    'الرصيد بعد التحويل:',
                    (state.balance! - state.amount!).format(),
                  ),
                ],
              ],
            ),
          ),
        ),
        if (state.transferError != null) ...[
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.errorContainer,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Icon(Icons.error_outline,
                    color: Theme.of(context).colorScheme.error),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    state.transferError!,
                    style: TextStyle(color: Theme.of(context).colorScheme.error),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: state.isProcessingTransfer
              ? null
              : () => notifier.executeTransfer(),
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            backgroundColor: Theme.of(context).colorScheme.primary,
            foregroundColor: Theme.of(context).colorScheme.onPrimary,
          ),
          child: state.isProcessingTransfer
              ? const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : const Text('تأكيد التحويل', style: TextStyle(fontSize: 16)),
        ),
        if (state.transferError != null) ...[
          const SizedBox(height: 12),
          OutlinedButton(
            onPressed: () => notifier.executeTransfer(),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size(double.infinity, 48),
            ),
            child: const Text('إعادة المحاولة'),
          ),
        ],
        const SizedBox(height: 12),
        TextButton(
          onPressed: () => notifier.goBackToAmount(),
          child: const Text('تعديل المبلغ'),
        ),
      ],
    );
  }

  Widget _buildSuccessView(BuildContext context, WidgetRef ref) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const SizedBox(height: 48),
        Icon(
          Icons.check_circle_outline,
          size: 80,
          color: Theme.of(context).colorScheme.primary,
        ),
        const SizedBox(height: 24),
        Text(
          'تم التحويل بنجاح',
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
              ),
        ),
        const SizedBox(height: 8),
        Text(
          'تم إرسال ${state.netAmount?.format() ?? ''} إلى ${state.recipientName ?? ''}',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyLarge,
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            children: [
              Text(
                'رقم المعاملة',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.grey,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                state.transactionId ?? '',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontFamily: 'monospace',
                    ),
                textDirection: TextDirection.ltr,
              ),
            ],
          ),
        ),
        const SizedBox(height: 32),
        ElevatedButton(
          onPressed: () {
            ref.read(transferStateProvider.notifier).reset();
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (_) => HomeScreen(authService: _createAuthService(ref)),
              ),
            );
          },
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
          ),
          child: const Text('العودة إلى الرئيسية', style: TextStyle(fontSize: 16)),
        ),
      ],
    );
  }

  AuthService _createAuthService(WidgetRef ref) {
    final config = ref.read(appConfigProvider);
    return AuthService(baseUrl: config.apiBaseUrl);
  }

  Widget _buildSummaryRow(BuildContext context, String label, String value,
      {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodyMedium),
        Text(
          value,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              ),
        ),
      ],
    );
  }
}
