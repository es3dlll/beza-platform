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

  // Login
  var r = await req('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test', 'password': 'admin123',
  });
  print('Login response: ${json.encode(r['b']).substring(0, 200)}');
  final token = r['b']['data']?['token'] as String?;
  if (token == null) {
    print('No token, aborting');
    client.close();
    return;
  }

  // Get audit logs - show full response
  r = await req('GET', '/v1/admin/audit-logs?limit=2', token: token);
  print('Status: ${r['s']}');

  final data = r['b'];
  print('Full response keys: ${data.keys}');
  if (data['data'] is List) {
    final logs = data['data'] as List;
    print('Logs count: ${logs.length}');
    for (final log in logs.take(2)) {
      print('Log entry keys: ${(log as Map).keys}');
      print('Full entry: ${json.encode(log)}');
    }
  } else {
    print('Full response: ${json.encode(data)}');
  }

  client.close();
}
