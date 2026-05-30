class ResponseWrapper<T> {
  final bool success;
  final T? data;
  final Map<String, dynamic>? meta;
  final ResponseError? error;

  ResponseWrapper({
    required this.success,
    this.data,
    this.meta,
    this.error,
  });

  factory ResponseWrapper.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? dataParser,
  ) {
    return ResponseWrapper(
      success: json['success'] as bool? ?? true,
      data: json['data'] != null && dataParser != null
          ? dataParser(json['data'])
          : null,
      meta: json['meta'] as Map<String, dynamic>?,
      error: json['error'] != null
          ? ResponseError.fromJson(json['error'] as Map<String, dynamic>)
          : null,
    );
  }
}

class ResponseError {
  final String? code;
  final String? message;
  final String? messageAr;
  final Map<String, dynamic>? details;

  ResponseError({this.code, this.message, this.messageAr, this.details});

  factory ResponseError.fromJson(Map<String, dynamic> json) {
    return ResponseError(
      code: json['code'] as String?,
      message: json['message'] as String?,
      messageAr: json['message_ar'] as String?,
      details: json['details'] as Map<String, dynamic>?,
    );
  }

  String get displayMessage => messageAr ?? message ?? 'خطأ غير معروف';
}

class PaginatedData<T> {
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;
  final int? from;
  final int? to;

  PaginatedData({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
    this.from,
    this.to,
  });

  factory PaginatedData.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic) itemParser,
  ) {
    final dataList = (json['data'] as List<dynamic>?) ?? [];
    return PaginatedData(
      items: dataList.map((e) => itemParser(e)).toList(),
      currentPage: json['current_page'] as int? ?? 1,
      lastPage: json['last_page'] as int? ?? 1,
      total: json['total'] as int? ?? 0,
      perPage: json['per_page'] as int? ?? 15,
      from: json['from'] as int?,
      to: json['to'] as int?,
    );
  }
}
