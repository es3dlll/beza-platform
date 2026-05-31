import 'dart:convert';
import 'dart:io';

final String baseUrl = 'http://localhost:8000';

Future<void> main() async {
  final client = HttpClient();

  Future<Map<String, dynamic>> req(
    String method,
    String path, {
    Map<String, dynamic>? body,
    String? token,
  }) async {
    final uri = Uri.parse('$baseUrl$path');
    final sw = Stopwatch()..start();
    try {
      final r = await client.openUrl(method, uri);
      r.headers.set('Content-Type', 'application/json');
      r.headers.set('Accept', 'application/json');
      if (token != null) r.headers.set('Authorization', 'Bearer $token');
      if (body != null) r.write(json.encode(body));
      final resp = await r.close();
      final b = await resp.transform(utf8.decoder).join();
      sw.stop();
      return {'s': resp.statusCode, 'b': json.decode(b), 't': sw.elapsedMilliseconds};
    } catch (e) {
      sw.stop();
      return {'s': 0, 'b': {'error': '$e'}, 't': sw.elapsedMilliseconds};
    }
  }

  void p(String label, Map<String, dynamic> r) {
    final ok = r['s'] == 200;
    print('  ${ok ? "[PASS]" : "[INFO]"} HTTP ${r['s']} ${r['t']}ms');
    if (!ok) {
      final b = r['b'] as Map;
      print('    ${b['message'] ?? b['error'] ?? ''}');
    }
  }

  // 1. Health
  print('\n[1] Health Check');
  var r = await req('GET', '/v1/core/health');
  p('health', r);

  // 2. Login
  print('\n[2] Login - admin@beza.test');
  r = await req('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test', 'password': 'admin123',
  });
  p('login', r);
  final token = (r['b']['data']?['token'] ?? '') as String;
  print('  Token: ${token.substring(0, 20)}...');

  // 3. Lookup user2
  print('\n[3] Lookup user2@beza.test');
  r = await req('GET', '/v1/users/lookup/user2@beza.test', token: token);
  p('lookup', r);
  print('  Body: ${json.encode(r['b'])}');

  // 4. Balance
  print('\n[4] Balance');
  r = await req('GET', '/v1/wallet/balance', token: token);
  p('balance', r);
  final bal = r['b']['data'];
  print('  Balance: ${bal['balance_fils']} fils (${bal['currency']})');

  // 5. Get user2 wallet ID - we need to find this
  print('\n[5] Checking wallet structure...');
  // The lookup returns user data, we need wallet info
  // Let's check if there's a wallet endpoint
  r = await req('GET', '/v1/wallet/balance', token: token);
  print('  Admin wallet: ${json.encode(r['b']['data'])}');

  // 6. Let's try to figure out the wallet ID for user2
  // Check if the lookup returns wallet data
  print('\n[6] Looking up user2 details...');
  r = await req('GET', '/v1/users/lookup/user2@beza.test', token: token);
  if (r['s'] == 200) {
    print('  Full response: ${json.encode(r['b'])}');
  }

  client.close();
}
