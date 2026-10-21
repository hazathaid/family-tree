import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Indonesian and English ARB files expose the same keys and placeholders',
      () {
    final id = _load('lib/l10n/app_id.arb');
    final en = _load('lib/l10n/app_en.arb');

    final idKeys = id.keys.where((key) => !key.startsWith('@')).toSet();
    final enKeys = en.keys.where((key) => !key.startsWith('@')).toSet();

    expect(idKeys, enKeys,
        reason: 'Both locales must define exactly the same translation keys.');
    expect(idKeys, isNotEmpty);

    for (final key in idKeys) {
      expect((id[key] as String).trim(), isNotEmpty,
          reason: 'app_id.arb has an empty value for $key');
      expect((en[key] as String).trim(), isNotEmpty,
          reason: 'app_en.arb has an empty value for $key');

      final idPlaceholders =
          ((id['@$key'] as Map<String, dynamic>?)?['placeholders']
                  as Map<String, dynamic>?) ??
              const {};
      final enPlaceholders =
          ((en['@$key'] as Map<String, dynamic>?)?['placeholders']
                  as Map<String, dynamic>?) ??
              const {};
      expect(idPlaceholders.keys.toSet(), enPlaceholders.keys.toSet(),
          reason: 'Placeholder mismatch for $key');
    }
  });
}

Map<String, dynamic> _load(String path) =>
    jsonDecode(File(path).readAsStringSync()) as Map<String, dynamic>;
