import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:beza_platform/main.dart';

void main() {
  testWidgets('App can be created', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: BezaApp()));
    expect(find.byType(ProviderScope), findsOneWidget);
  });
}
