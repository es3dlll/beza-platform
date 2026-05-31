import 'dart:convert';
import 'dart:io';

Future<void> main() async {
  final client = HttpClient();

  Future<Map> req(String method, String path, {Map? body, String? token}) async {
    final uri = Uri.parse('http://localhost:8000$path');
    final r = await client.openUrl(method, uri);
    r.headers.set('Content-Type', 'application/json');
    r.headers.set('Accept', 'application/json');
    if (token != null) r.headers.set('Authorization', 'Bearer $token');
    if (body != null) r.write(json.encode(body));
    final resp = await r.close();
    final b = await resp.transform(utf8.decoder).join();
    return {'s': resp.statusCode, 'b': json.decode(b)};
  }

  // Try from e2e test that just ran - maybe we can use an existing token
  // Login - may fail due to rate limit
  var r = await req('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test', 'password': 'admin123',
  });
  print('Login: ${r['s']} ${(r['b']['success'] == true ? 'OK' : r['b']['message'])}');

  if (r['b']['success'] != true) {
    print('Rate limited - will try with saved token');
    client.close();
    return;
  }

  final token = r['b']['data']['token'];

  // Audit logs
  r = await req('GET', '/v1/admin/audit-logs?limit=3', token: token);
  print('Audit: ${r['s']}');
  final logs = r['b']['data'] as List? ?? [];
  print('Count: ${logs.length}');
  for (final log in logs.take(3)) {
    final m = log as Map;
    print('Keys: ${m.keys.join(', ')}');
    print('Full: ${json.encode(m)}');
  }

  client.close();
}
