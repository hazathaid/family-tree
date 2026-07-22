import 'dart:io';
import 'dart:typed_data';

import 'package:family_tree_mobile/app/theme/app_theme.dart';
import 'package:family_tree_mobile/core/widgets/async_states.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:golden_toolkit/golden_toolkit.dart';

void main() {
  final comparator = goldenFileComparator;
  if (comparator is LocalFileComparator) {
    goldenFileComparator = _TolerantGoldenComparator(
      Uri.file('${Directory.current.path}/test/design_system_test.dart'),
    );
  }

  testWidgets('primary controls keep a 48dp touch target', (tester) async {
    await tester.pumpWidget(MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
            body:
                FilledButton(onPressed: () {}, child: const Text('Simpan')))));
    expect(tester.getSize(find.byType(FilledButton)).height,
        greaterThanOrEqualTo(48));
  });

  testGoldens('async states render on phone and tablet', (tester) async {
    final builder = DeviceBuilder()
      ..overrideDevicesForAllScenarios(
          devices: [Device.phone, Device.tabletPortrait])
      ..addScenario(
          widget: MaterialApp(
              theme: AppTheme.light(),
              home: const Scaffold(
                  body: AppEmptyState(
                      title: 'Belum ada data',
                      message: 'Data keluarga akan tampil di sini.'))));
    await tester.pumpDeviceBuilder(builder);
    await screenMatchesGolden(tester, 'async_empty_state');
  });
}

class _TolerantGoldenComparator extends LocalFileComparator {
  _TolerantGoldenComparator(super.testFile);

  @override
  Future<bool> compare(Uint8List imageBytes, Uri golden) async {
    final goldenBytes = await getGoldenBytes(golden);
    final result = await GoldenFileComparator.compareLists(
      imageBytes,
      goldenBytes,
    );
    final matches = result.diffPercent <= 0.005;
    result.dispose();

    return matches;
  }
}
