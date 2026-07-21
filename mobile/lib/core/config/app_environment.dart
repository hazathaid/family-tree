import 'package:flutter/foundation.dart';

enum AppFlavor { development, staging, production }

@immutable
class AppEnvironment {
  const AppEnvironment({required this.flavor, required this.apiBaseUrl});

  factory AppEnvironment.fromDefines() {
    const flavorValue =
        String.fromEnvironment('APP_FLAVOR', defaultValue: 'development');
    const urlValue = String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8000/api/v1',
    );
    final flavor = AppFlavor.values.firstWhere(
      (value) => value.name == flavorValue,
      orElse: () => throw StateError('APP_FLAVOR tidak valid.'),
    );
    return AppEnvironment.validate(flavor: flavor, apiBaseUrl: urlValue);
  }

  factory AppEnvironment.validate(
      {required AppFlavor flavor, required String apiBaseUrl}) {
    final uri = Uri.tryParse(apiBaseUrl);
    if (uri == null ||
        !uri.hasScheme ||
        !uri.hasAuthority ||
        !uri.path.endsWith('/api/v1')) {
      throw StateError(
          'API_BASE_URL harus berupa URL absolut yang berakhir dengan /api/v1.');
    }
    if (flavor == AppFlavor.production && uri.scheme != 'https') {
      throw StateError('Production wajib menggunakan HTTPS.');
    }
    if (uri.scheme != 'https' && uri.scheme != 'http') {
      throw StateError('API_BASE_URL hanya mendukung HTTP atau HTTPS.');
    }
    return AppEnvironment(flavor: flavor, apiBaseUrl: uri);
  }

  final AppFlavor flavor;
  final Uri apiBaseUrl;

  bool get diagnosticsEnabled => kDebugMode && flavor != AppFlavor.production;
  String get sanitizedHost =>
      '${apiBaseUrl.scheme}://${apiBaseUrl.host}${apiBaseUrl.hasPort ? ':${apiBaseUrl.port}' : ''}';
}
