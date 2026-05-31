import 'dart:convert';
import 'dart:io';

Future<void> main() async {
  final client = HttpClient();

  Future<Map> req(String method, String path, {Map? body, String? token}) async {
    final uri = Uri.parse('http://localhost:8000$path');
    final sw = Stopwatch()..start();
    final r = await client.openUrl(method, uri);
    r.headers.set('Content-Type', 'application/json');
    r.headers.set('Accept', 'application/json');
    if (token != null) r.headers.set('Authorization', 'Bearer $token');
    if (body != null) r.write(json.encode(body));
    final resp = await r.close();
    final b = await resp.transform(utf8.decoder).join();
    sw.stop();
    return {'s': resp.statusCode, 'b': json.decode(b), 't': sw.elapsedMilliseconds};
  }

  void pr(String label, Map r) {
    final ok = r['s'] == 200;
    print('  ${ok ? "PASS" : "INFO"} ${r['s']} ${r['t']}ms');
    if (r['b']['message'] != null) print('    ${r['b']['message']}');
  }

  int passed = 0, failed = 0;

  void test(String label, Map r, {bool expectSuccess = true}) {
    bool ok;
    if (expectSuccess) {
      ok = (r['s'] == 200 || r['s'] == 201) && r['b']['success'] == true;
    } else {
      ok = r['s'] != 200 || r['b']['success'] == false;
    }
    if (ok) { passed++; print('  [PASS] $label ${r['t']}ms'); }
    else { failed++; print('  [FAIL] $label ${r['s']} ${r['t']}ms: ${r['b']['message'] ?? r['b']}'); }
  }

  print('=' * 60);
  print('  بيزا End-to-End API Test');
  print('  Target: http://localhost:8000');
  print('=' + '-' * 58);

  // 1. Health
  print('\n1. System Health');
  var r = await req('GET', '/v1/core/health');
  test('core health', r);

  // 2. Login
  print('\n2. Authentication');
  r = await req('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test', 'password': 'admin123',
  });
  test('admin login', r);
  final token = r['b']['data']['token'] as String;

  // 3. Wrong password
  r = await req('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test', 'password': 'wrongpass',
  });
  test('wrong password', r, expectSuccess: false);

  // 4. Lookup existing user
  print('\n3. User Lookup');
  r = await req('GET', '/v1/users/lookup/user2@beza.test', token: token);
  test('lookup user2', r);
  final user2WalletId = r['b']['data']['wallet_id'] as String;
  print('    User2 wallet: $user2WalletId');

  // 5. Lookup nonexistent
  r = await req('GET', '/v1/users/lookup/ghost@nowhere.xyz', token: token);
  test('lookup nonexistent', r, expectSuccess: false);

  // 6. Balance
  print('\n4. Wallet Balance');
  r = await req('GET', '/v1/wallet/balance', token: token);
  test('admin balance', r);
  final bal = r['b']['data']['balance_fils'];
  print('    Admin balance: $bal fils');

  // 7. Transfer
  print('\n5. Transfer');
  r = await req('POST', '/v1/wallet/transfer', token: token, body: {
    'to_wallet_id': user2WalletId,
    'amount_fils': 500,
    'currency': 'SYP',
  });
  test('transfer 500 fils', r);
  if (r['s'] == 200) {
    print('    Entry ID: ${r['b']['data']['entry_id']}');
  }

  // 8. Verify balance changed
  r = await req('GET', '/v1/wallet/balance', token: token);
  test('balance after transfer', r);
  final newBal = r['b']['data']['balance_fils'];
  print('    Balance: $newBal fils (was $bal, diff: ${bal - newBal})');

  // 9. Audit logs
  print('\n6. Audit Logs');
  r = await req('GET', '/v1/admin/audit-logs?limit=5', token: token);
  test('audit logs', r);
  if (r['s'] == 200) {
    final logs = r['b']['data'] as List? ?? [];
    print('    Entries: ${logs.length}');
    for (final log in logs.take(5)) {
      print('    - ${log['type'] ?? '?'} | ${log['status'] ?? '?'} | ${log['amount_fils'] ?? '?'}');
    }
  }

  // 10. Registration
  print('\n7. Registration');
  final testEmail = 'test-e2e-${DateTime.now().millisecondsSinceEpoch}@test.com';
  r = await req('POST', '/v1/auth/register', body: {
    'name': 'E2E User',
    'email': testEmail,
    'password': 'password123',
  });
  test('register new user', r);

  // 11. Login as new user
  r = await req('POST', '/v1/auth/login', body: {
    'email': testEmail, 'password': 'password123',
  });
  test('new user login', r);

  // 12. New user balance
  final newToken = r['b']['data']['token'] as String;
  r = await req('GET', '/v1/wallet/balance', token: newToken);
  test('new user balance', r);

  print('\n' + '=' * 60);
  print('  Results: $passed passed, $failed failed');
  print('=' * 60);

  client.close();
}
