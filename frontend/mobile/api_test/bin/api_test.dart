import 'dart:convert';
import 'dart:io';
final String baseUrl = 'http://localhost:8000';

Future<Map<String, dynamic>> _request(
  String method,
  String path, {
  Map<String, dynamic>? body,
  String? token,
}) async {
  final uri = Uri.parse('$baseUrl$path');
  final client = HttpClient();
  final stopwatch = Stopwatch()..start();

  try {
    final request = await client.openUrl(method, uri);
    request.headers.set('Content-Type', 'application/json');
    request.headers.set('Accept', 'application/json');
    if (token != null) {
      request.headers.set('Authorization', 'Bearer $token');
    }
    if (body != null) {
      request.write(json.encode(body));
    }
    final response = await request.close();
    final responseBody = await response.transform(utf8.decoder).join();
    stopwatch.stop();

    return {
      'status': response.statusCode,
      'body': json.decode(responseBody),
      'time_ms': stopwatch.elapsedMilliseconds,
      'headers': {},
    };
  } catch (e) {
    stopwatch.stop();
    return {
      'status': 0,
      'body': {'error': e.toString()},
      'time_ms': stopwatch.elapsedMilliseconds,
      'headers': {},
    };
  } finally {
    client.close();
  }
}

void _printResult(String label, Map<String, dynamic> result) {
  final status = result['status'];
  final time = result['time_ms'];
  final body = result['body'] as Map<String, dynamic>;
  final success = body['success'] == true;

  if (status == 200 && success) {
    print('  [PASS] $status in ${time}ms');
  } else if (status == 200 && !success) {
    print('  [FAIL] $status (API error: ${body['message']}) ${time}ms');
  } else {
    final msg = body['message'] ?? body['error'] ?? 'unknown';
    print('  [FAIL] HTTP $status: $msg ${time}ms');
  }
}

void main() async {
  print('=' * 60);
  print('  بيزا — API Integration Test Suite');
  print('  Target: $baseUrl');
  print('  Time:   ${DateTime.now()}');
  print('=' * 60);

  // 1. Health check
  print('\n[1] Health Check');
  var res = await _request('GET', '/v1/core/health');
  _printResult('health', res);

  // 2. Login - wrong password
  print('\n[2] Login - wrong password');
  res = await _request('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test',
    'password': 'wrongpassword',
  });
  _printResult('wrong password', res);

  // 3. Login - valid credentials
  print('\n[3] Login - valid credentials');
  res = await _request('POST', '/v1/auth/login', body: {
    'email': 'admin@beza.test',
    'password': 'admin123',
  });
  _printResult('login', res);
  final token = (res['body'] as Map<String, dynamic>)?['data']?['token'] as String?;
  if (token == null) {
    print('  [FATAL] No token received - aborting');
    exit(1);
  }
  print('  Token: ${token.substring(0, 20)}...');

  // 4. User lookup
  print('\n[4] User lookup');
  res = await _request('GET', '/v1/users/lookup/user2@beza.test', token: token);
  _printResult('lookup user2', res);

  res = await _request('GET', '/v1/users/lookup/nonexistent@test.com', token: token);
  _printResult('lookup nonexistent', res);

  // 5. Balance check
  print('\n[5] Balance check');
  res = await _request('GET', '/v1/wallet/balance', token: token);
  _printResult('balance', res);

  // 6. Transfer
  print('\n[6] Transfer');
  res = await _request('POST', '/v1/wallet/transfer', token: token, body: {
    'recipient_email': 'user2@beza.test',
    'amount': 1000,
    'description': 'Test transfer from API test',
  });
  _printResult('transfer 1000 fils', res);
  if (res['status'] == 200) {
    final transfer = (res['body'] as Map<String, dynamic>)?['data'];
    if (transfer != null) {
      print('  Transfer ID: ${transfer['id']}');
    }
  }

  // 7. Audit logs (admin-only)
  print('\n[7] Audit logs');
  res = await _request('GET', '/v1/admin/audit-logs?limit=5', token: token);
  _printResult('audit logs', res);
  if (res['status'] == 200) {
    final body = res['body'] as Map<String, dynamic>;
    final logs = body['data'] as List?;
    if (logs != null) {
      print('  Found ${logs.length} log entries');
      for (final log in logs) {
        print('    - ${log['type']}: ${log['status']} (${log['created_at']})');
      }
    }
  }

  // 8. Register new user
  print('\n[8] Register new user');
  res = await _request('POST', '/v1/auth/register', body: {
    'name': 'Test User',
    'email': 'test-api-${DateTime.now().millisecondsSinceEpoch}@test.com',
    'password': 'password123',
  });
  _printResult('register', res);

  // 9. Verify token still valid (no logout endpoint)
  print('\n[9] Verify token still valid');
  res = await _request('GET', '/v1/wallet/balance', token: token);
  _printResult('balance check', res);

  print('\n' + '=' * 60);
  print('  Testing complete');
  print('=' * 60);
}
