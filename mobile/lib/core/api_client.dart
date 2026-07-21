import 'dart:async';
import 'dart:math';

import 'package:dio/dio.dart';

import 'errors/app_error.dart';
import 'storage/secure_token_store.dart';

typedef ApiException = AppError;

class ApiClient {
  ApiClient(
      {required String baseUrl,
      TokenStore? tokenStore,
      Dio? dio,
      Future<void> Function()? onUnauthorized})
      : _dio = dio ?? Dio(),
        _tokenStore = tokenStore ?? MemoryTokenStore(),
        _onUnauthorized = onUnauthorized {
    _dio.options = BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 30),
      sendTimeout: const Duration(seconds: 30),
      headers: const {'Accept': 'application/json'},
    );
    _dio.interceptors
        .add(InterceptorsWrapper(onRequest: (options, handler) async {
      final token = await _tokenStore.read();
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      handler.next(options);
    }));
  }

  final Dio _dio;
  final TokenStore _tokenStore;
  final Future<void> Function()? _onUnauthorized;
  final Random _random = Random();

  Future<dynamic> get(String path,
          {Map<String, dynamic>? query, CancelToken? cancelToken}) =>
      _request(
          () => _dio.get<dynamic>(path,
              queryParameters: query, cancelToken: cancelToken),
          idempotent: true);

  Future<dynamic> post(String path,
          {Map<String, dynamic>? data, CancelToken? cancelToken}) =>
      _request(
          () => _dio.post<dynamic>(path, data: data, cancelToken: cancelToken));

  Future<void> saveToken(String token) => _tokenStore.write(token);
  Future<void> clearToken() => _tokenStore.clear();
  Future<bool> hasToken() async =>
      (await _tokenStore.read())?.isNotEmpty ?? false;

  Future<dynamic> _request(Future<Response<dynamic>> Function() request,
      {bool idempotent = false}) async {
    var attempt = 0;
    while (true) {
      try {
        final response = await request();
        final body = response.data;
        if (body is! Map<String, dynamic>) {
          throw const AppError(
              AppErrorType.server, 'Respons server tidak valid.');
        }
        if (body['success'] != true) {
          throw _mapBody(body, response.statusCode);
        }
        return body['data'];
      } on DioException catch (error) {
        final mapped = await _mapDio(error);
        if (!idempotent ||
            attempt >= 2 ||
            !mapped.isRetryable ||
            mapped.type == AppErrorType.cancelled) {
          throw mapped;
        }
        final retryAfter = mapped.retryAfter ??
            Duration(milliseconds: 250 * (1 << attempt) + _random.nextInt(150));
        attempt++;
        await Future<void>.delayed(retryAfter);
      }
    }
  }

  AppError _mapBody(Map<String, dynamic> body, int? status) {
    final message = body['message'] as String? ?? 'Permintaan tidak berhasil.';
    final fields = <String, List<String>>{};
    final errors = body['errors'];
    if (errors is Map<String, dynamic>) {
      for (final entry in errors.entries) {
        fields[entry.key] = (entry.value as List<dynamic>? ?? const [])
            .map((value) => '$value')
            .toList();
      }
    }
    return AppError(_statusType(status), message, fieldErrors: fields);
  }

  Future<AppError> _mapDio(DioException error) async {
    if (CancelToken.isCancel(error)) {
      return const AppError(AppErrorType.cancelled, 'Permintaan dibatalkan.');
    }
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.sendTimeout) {
      return const AppError(
          AppErrorType.timeout, 'Server terlalu lama merespons.');
    }
    final status = error.response?.statusCode;
    if (status == 401) {
      await clearToken();
      await _onUnauthorized?.call();
    }
    final body = error.response?.data;
    final retryHeader = error.response?.headers.value('retry-after');
    final retrySeconds = int.tryParse(retryHeader ?? '');
    if (body is Map<String, dynamic>) {
      final mapped = _mapBody(body, status);
      return AppError(mapped.type, mapped.message,
          fieldErrors: mapped.fieldErrors,
          retryAfter:
              retrySeconds == null ? null : Duration(seconds: retrySeconds));
    }
    if (error.type == DioExceptionType.connectionError) {
      return const AppError(
          AppErrorType.offline, 'Tidak dapat terhubung ke server.');
    }
    return AppError(
        _statusType(status),
        status != null && status >= 500
            ? 'Server sedang bermasalah. Silakan coba lagi.'
            : 'Permintaan tidak berhasil.');
  }

  AppErrorType _statusType(int? status) => switch (status) {
        401 => AppErrorType.unauthorized,
        403 => AppErrorType.forbidden,
        404 => AppErrorType.notFound,
        408 => AppErrorType.timeout,
        422 => AppErrorType.validation,
        429 => AppErrorType.rateLimited,
        int value when value >= 500 => AppErrorType.server,
        _ => AppErrorType.unknown,
      };
}
