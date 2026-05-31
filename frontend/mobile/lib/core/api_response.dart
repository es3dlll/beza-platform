class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final dynamic errors;
  final String timestamp;
  final String? requestId;

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
    required this.timestamp,
    this.requestId,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromJsonT,
  ) {
    return ApiResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null && fromJsonT != null
          ? fromJsonT(json['data'])
          : json['data'] as T?,
      errors: json['errors'],
      timestamp: json['timestamp'] as String,
      requestId: json['request_id'] as String?,
    );
  }
}
