import 'package:flutter/foundation.dart';

enum AppErrorType {
  offline,
  timeout,
  unauthorized,
  forbidden,
  notFound,
  validation,
  rateLimited,
  server,
  cancelled,
  unknown
}

@immutable
class AppError implements Exception {
  const AppError(this.type, this.message,
      {this.fieldErrors = const {}, this.retryAfter});

  final AppErrorType type;
  final String message;
  final Map<String, List<String>> fieldErrors;
  final Duration? retryAfter;

  bool get isRetryable => switch (type) {
        AppErrorType.offline ||
        AppErrorType.timeout ||
        AppErrorType.rateLimited ||
        AppErrorType.server =>
          true,
        _ => false,
      };

  @override
  String toString() => message;
}
