import 'package:family_tree_mobile/core/config/app_environment.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('production environment rejects clear-text HTTP', () {
    expect(
      () => AppEnvironment.validate(
          flavor: AppFlavor.production,
          apiBaseUrl: 'http://api.example.test/api/v1'),
      throwsStateError,
    );
  });

  test('diagnostics expose host only', () {
    final environment = AppEnvironment(
        flavor: AppFlavor.development,
        apiBaseUrl: Uri.parse('https://api.example.test/api/v1'));
    expect(environment.sanitizedHost, 'https://api.example.test');
  });
}
