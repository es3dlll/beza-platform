import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';

export 'mocks.mocks.dart';

GoRouter mockGoRouter({
  String initialLocation = '/splash',
  List<GoRoute> routes = const [],
  String? redirectLocation,
}) {
  return GoRouter(
    initialLocation: initialLocation,
    debugLogDiagnostics: false,
    routes: routes,
  );
}

Widget wrapWithMaterialApp(Widget child, {bool rtl = false}) {
  return MaterialApp(
    localizationsDelegates: const [],
    supportedLocales: const [Locale('ar'), Locale('en')],
    locale: rtl ? const Locale('ar') : null,
    home: Directionality(
      textDirection: rtl ? TextDirection.rtl : TextDirection.ltr,
      child: child,
    ),
  );
}

ProviderScope createTestProviders({
  required Widget child,
  List<Override> overrides = const [],
}) {
  return ProviderScope(
    overrides: overrides,
    child: child,
  );
}

DioException createDioException({
  int? statusCode,
  DioExceptionType type = DioExceptionType.badResponse,
  dynamic data,
}) {
  return DioException(
    requestOptions: RequestOptions(path: '/test'),
    type: type,
    response: data != null || statusCode != null
        ? Response(
            requestOptions: RequestOptions(path: '/test'),
            statusCode: statusCode,
            data: data,
          )
        : null,
  );
}
