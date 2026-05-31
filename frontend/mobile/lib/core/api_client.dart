import 'dart:convert';
import 'dart:io';
import 'api_response.dart';

class ApiClient {
  final String baseUrl;
  final Map<String, String> defaultHeaders;
  final HttpClient Function()? httpClientFactory;

  ApiClient({
    required this.baseUrl,
    this.defaultHeaders = const {},
    this.httpClientFactory,
  });

  Future<ApiResponse> get(String path, {Map<String, String>? headers}) async {
    final client = httpClientFactory?.call() ?? HttpClient();
    try {
      final request = await client.getUrl(Uri.parse('$baseUrl$path'));
      _addHeaders(request, headers);
      final response = await request.close();
      final body = await response.transform(utf8.decoder).join();
      return ApiResponse.fromJson(
        json.decode(body) as Map<String, dynamic>,
        null,
      );
    } finally {
      client.close();
    }
  }

  Future<ApiResponse> post(
    String path, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
  }) async {
    final client = httpClientFactory?.call() ?? HttpClient();
    try {
      final request = await client.postUrl(Uri.parse('$baseUrl$path'));
      _addHeaders(request, headers, hasBody: true);
      if (body != null) {
        request.write(json.encode(body));
      }
      final response = await request.close();
      final responseBody = await response.transform(utf8.decoder).join();
      return ApiResponse.fromJson(
        json.decode(responseBody) as Map<String, dynamic>,
        null,
      );
    } finally {
      client.close();
    }
  }

  void _addHeaders(
    HttpClientRequest request,
    Map<String, String>? extraHeaders, {
    bool hasBody = false,
  }) {
    defaultHeaders.forEach(request.headers.set);
    extraHeaders?.forEach(request.headers.set);
    request.headers.set('Content-Type', 'application/json');
    request.headers.set('Accept', 'application/json');
    request.headers.set('X-Request-Id', _generateRequestId());
  }

  String _generateRequestId() {
    final now = DateTime.now().microsecondsSinceEpoch;
    final random = (now % 100000).toString().padLeft(5, '0');
    return 'BEZA-${now}-$random';
  }
}
