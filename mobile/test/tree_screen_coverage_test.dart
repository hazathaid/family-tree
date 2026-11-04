import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/tree/tree_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  testWidgets('picks a root, renders tree and uses controls', (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final trees = FakeTreeRepository();
    final members = FakeMemberRepository()
      ..memberValue = testMember
      ..memberItems = [testMember];
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => null),
    ], child: routedApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();
    expect(find.text('Pilih anggota pusat'), findsOneWidget);

    tester
        .widget<FilledButton>(
            find.widgetWithText(FilledButton, 'Pilih anggota pusat'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(find.text('Pilih pusat pohon'), findsWidgets);
    await tester.tap(find.text('Budi Santoso'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Perluas'), findsOneWidget);
    expect(find.text('Budi Santoso'), findsWidgets);

    final mode = tester.widget<DropdownButton<String>>(
        find.byType(DropdownButton<String>).first);
    mode.onChanged!('ancestor');
    await tester.pumpAndSettle();
    final layout = tester.widget<DropdownButton<String>>(
        find.byType(DropdownButton<String>).at(1));
    layout.onChanged!('radial');
    await tester.pumpAndSettle();
    expect(
        trees.calls.where((call) => call == 'generate').length, greaterThan(1));

    for (final label in ['Hanya hidup', 'Daftar aksesibel']) {
      final chip =
          tester.widget<FilterChip>(find.widgetWithText(FilterChip, label));
      chip.onSelected!(true);
      await tester.pumpAndSettle();
    }
    expect(find.byType(ListTile), findsWidgets);

    await tester.tap(find.byTooltip('Perkecil'));
    await tester.tap(find.byTooltip('Perbesar'));
    await tester.tap(find.byTooltip('Pusatkan'));
    await tester.pumpAndSettle();

    final expand = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Perluas (3/20)'));
    expand.onPressed!.call();
    await tester.pumpAndSettle();
    final collapse = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Ciutkan (4)'));
    collapse.onPressed!.call();
    await tester.pumpAndSettle();
    expect(
        trees.calls.where((call) => call == 'generate').length, greaterThan(3));
  });

  testWidgets('opens details and adds a relative', (tester) async {
    tester.view.physicalSize = const Size(1000, 1800);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final trees = FakeTreeRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    ], child: routedApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();

    final dynamic state = tester.state(find.byType(TreeScreen));
    state.showDetails(testTree.nodes.first);
    await tester.pumpAndSettle();
    expect(find.text('Tambah orang tua'), findsOneWidget);

    await tester.tap(find.text('Tambah orang tua'));
    await tester.pumpAndSettle();
    expect(find.text('Tambah orang tua'), findsWidgets);
    await tester.enterText(find.byType(TextFormField), 'Ayah Baru');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Tambahkan'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(trees.calls, contains('createRelative'));
    expect(find.textContaining('Ayah Baru'), findsWidgets);
  });

  testWidgets('exports PNG and PDF previews', (tester) async {
    final trees = FakeTreeRepository()
      ..job = const TreeExportJob(
          uuid: 'export-uuid', status: 'completed', format: 'png');
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    ], child: testApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();

    final exportMenu = tester
        .widget<PopupMenuButton<String>>(find.byType(PopupMenuButton<String>));
    exportMenu.onSelected!('png');
    await tester.pumpAndSettle();
    expect(find.text('Ekspor PNG siap'), findsOneWidget);
    await tester.tap(find.text('Tutup'));
    await tester.pumpAndSettle();

    trees.job = const TreeExportJob(
        uuid: 'export-pdf', status: 'completed', format: 'pdf');
    exportMenu.onSelected!('pdf');
    await tester.pumpAndSettle();
    expect(find.textContaining('PDF'), findsWidgets);
    await tester.tap(find.text('Tutup'));
    await tester.pumpAndSettle();
    expect(trees.calls, contains('downloadExport'));
  });

  testWidgets('handles export failures and empty or failed trees',
      (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final trees = FakeTreeRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    ], child: testApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();

    trees.error = const AppError(AppErrorType.server, 'Ekspor gagal');
    tester
        .widget<PopupMenuButton<String>>(find.byType(PopupMenuButton<String>))
        .onSelected!('png');
    await tester.pumpAndSettle();
    expect(find.text('Ekspor gagal'), findsOneWidget);

    trees
      ..error = null
      ..treeValue = const FamilyTree(
          rootUuid: 'member-uuid',
          mode: 'full',
          depth: 3,
          layout: 'vertical',
          nodes: [],
          edges: [],
          viewportWidth: 900,
          viewportHeight: 700,
          canExpand: false,
          canCollapse: false,
          cached: false);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    ], child: testApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();
    expect(find.text('Pohon keluarga masih kosong.'), findsOneWidget);

    trees.error = const AppError(AppErrorType.server, 'Pohon gagal');
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(FakeMemberRepository()),
      treeRepositoryProvider.overrideWithValue(trees),
      currentMemberUuidProvider.overrideWith((ref) => 'member-uuid'),
    ], child: testApp(const Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();
    expect(find.text('Coba lagi'), findsOneWidget);
  });
}
