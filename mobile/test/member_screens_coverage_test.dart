import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/members/presentation/member_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  testWidgets('directory renders phone cards, filters and paging',
      (tester) async {
    tester.view.physicalSize = const Size(390, 900);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final members = FakeMemberRepository()
      ..memberItems = [richMember, testMember]
      ..memberPage = PageData(
          items: [richMember, testMember],
          currentPage: 1,
          lastPage: 2,
          total: 3);
    final families = FakeFamilyRepository();

    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(families),
    ], child: routedApp(const MemberDirectoryScreen())));
    await tester.pumpAndSettle();

    expect(find.text('Alm. Budi Santoso'), findsOneWidget);
    expect(find.textContaining('Meninggal'), findsWidgets);

    await tester.tap(find.byTooltip('Filter dan urutkan'));
    await tester.pumpAndSettle();
    expect(find.text('Filter anggota'), findsOneWidget);
    await tester.tap(find.text('Terapkan'));
    await tester.pumpAndSettle();

    final nextPage = tester.widget<IconButton>(
        find.widgetWithIcon(IconButton, Icons.chevron_right));
    nextPage.onPressed!.call();
    await tester.pumpAndSettle();
    expect(members.calls.where((call) => call == 'members').length,
        greaterThan(1));
  });

  testWidgets('directory renders tablet table and empty and error states',
      (tester) async {
    tester.view.physicalSize = const Size(900, 900);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: testApp(const MemberDirectoryScreen())));
    await tester.pumpAndSettle();
    expect(find.byType(DataTable), findsOneWidget);
    expect(find.text('Utama'), findsOneWidget);

    members.memberPage =
        const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: testApp(const MemberDirectoryScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Belum ada anggota yang sesuai.'), findsOneWidget);

    members.error = const AppError(AppErrorType.server, 'Gagal');
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: testApp(const MemberDirectoryScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('member detail renders profile and actions', (tester) async {
    tester.view.physicalSize = const Size(1000, 2200);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(
        overrides: [
          currentFamilyProvider.overrideWith((ref) => testFamily),
          memberRepositoryProvider.overrideWithValue(members),
        ],
        child: routedApp(
            const Scaffold(body: MemberDetailScreen(uuid: 'member-uuid')))));
    await tester.pumpAndSettle();

    expect(find.text('Alm. Budi Santoso'), findsWidgets);
    expect(find.textContaining('Ayah'), findsWidgets);
    expect(find.text('Pendiri keluarga.'), findsOneWidget);
    expect(find.text('Siti Santoso'), findsOneWidget);

    await tester.tap(find.text('Kelola dokumen'));
    await tester.pumpAndSettle();
    expect(find.textContaining('route:/members/member-uuid/documents'),
        findsOneWidget);
  });

  testWidgets('member detail shows a load failure', (tester) async {
    final members = FakeMemberRepository()
      ..error = const AppError(AppErrorType.server, 'Gagal');
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const MemberDetailScreen(uuid: 'member-uuid'))));
    await tester.pumpAndSettle();
    expect(find.text('Detail anggota tidak dapat dimuat.'), findsOneWidget);
  });

  testWidgets('member form edits and saves', (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: routedApp(MemberFormScreen(member: richMember))));
    await tester.pumpAndSettle();

    expect(find.text('Tanggal wafat (YYYY-MM-DD)'), findsOneWidget);
    await tester.tap(find.text('Masih hidup'));
    await tester.pumpAndSettle();
    expect(find.text('Tanggal wafat (YYYY-MM-DD)'), findsNothing);

    await tester.scrollUntilVisible(find.text('Simpan'), 300,
        scrollable: find.byType(Scrollable).first);
    _tapSave(tester);
    await tester.pumpAndSettle();
    expect(members.updated, isNotEmpty);
  });

  testWidgets('member form shows field errors and deletes', (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: routedApp(MemberFormScreen(member: richMember))));
    await tester.pumpAndSettle();

    members.error =
        const AppError(AppErrorType.validation, 'Tidak valid', fieldErrors: {
      'religion': ['Agama tidak valid']
    });
    await tester.scrollUntilVisible(find.text('Simpan'), 300,
        scrollable: find.byType(Scrollable).first);
    await tester.scrollUntilVisible(find.text('Simpan'), 300,
        scrollable: find.byType(Scrollable).first);
    _tapSave(tester);
    await tester.pumpAndSettle();
    await tester.drag(find.byType(Scrollable).first, const Offset(0, 1000));
    await tester.pumpAndSettle();
    expect(find.text('Agama tidak valid'), findsOneWidget);

    members.error = null;
    await tester.scrollUntilVisible(find.text('Hapus anggota'), 200,
        scrollable: find.byType(Scrollable).first);
    await tester.tap(find.text('Hapus anggota'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Hapus'));
    await tester.pumpAndSettle();
    expect(members.deleted, contains('member-uuid'));
  });

  testWidgets('member form creates a member after validation', (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
      familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
    ], child: routedApp(const MemberFormScreen())));
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.text('Simpan'), 300,
        scrollable: find.byType(Scrollable).first);
    _tapSave(tester);
    await tester.pumpAndSettle();
    expect(members.created, isEmpty);

    final dynamic state = tester.state(find.byType(MemberFormScreen));
    state.name.text = 'Anggota Baru';
    await state.save();
    await tester.pumpAndSettle();
    expect(members.created, isNotEmpty);
    expect(members.created.single['full_name'], 'Anggota Baru');
  });

  testWidgets('relationship management opens dialog and deletes rows',
      (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const RelationshipManagementScreen())));
    await tester.pumpAndSettle();

    expect(find.text('Budi Santoso → Siti Santoso'), findsOneWidget);
    await tester.tap(find.byTooltip('Tambah relationship'));
    await tester.pumpAndSettle();
    expect(find.text('Tambah relationship'), findsOneWidget);
    await tester.tap(find.text('Batal'));
    await tester.pumpAndSettle();

    await tester.tap(find.byType(PopupMenuButton<String>));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Hapus'));
    await tester.pumpAndSettle();
    expect(members.calls, contains('deleteRelationship'));
  });

  testWidgets('relationship management shows empty and error states',
      (tester) async {
    final members = FakeMemberRepository()..relationshipItems = const [];
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const RelationshipManagementScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Belum ada relationship dasar.'), findsOneWidget);

    members.error = const AppError(AppErrorType.server, 'Gagal');
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const RelationshipManagementScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('relationship resolver picks members and renders a path',
      (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const RelationshipResolverScreen())));
    await tester.pumpAndSettle();

    await _pickMember(tester, 'Anggota sumber');
    await _pickMember(tester, 'Anggota tujuan');
    expect(find.text('Pilih anggota'), findsNothing);
    final findButton = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Temukan relationship'));
    findButton.onPressed!.call();
    await tester.pumpAndSettle();
    expect(find.text('Ayah'), findsOneWidget);
    expect(find.text('Siti → Budi'), findsOneWidget);
  });

  testWidgets('relationship resolver handles missing and same-member results',
      (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final members = FakeMemberRepository()
      ..resolution = const RelationshipResolution(relationship: null, path: []);
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const RelationshipResolverScreen())));
    await tester.pumpAndSettle();

    await _pickMember(tester, 'Anggota sumber');
    await _pickMember(tester, 'Anggota tujuan');
    expect(find.text('Pilih anggota'), findsNothing);
    final findButton = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Temukan relationship'));
    findButton.onPressed!.call();
    await tester.pumpAndSettle();
    expect(find.text('Tidak terhubung'), findsOneWidget);
    expect(find.text('Tidak ditemukan jalur relationship.'), findsOneWidget);
  });

  testWidgets('documents screen deletes a document and handles empty state',
      (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(
        overrides: [
          currentFamilyProvider.overrideWith((ref) => testFamily),
          memberRepositoryProvider.overrideWithValue(members),
        ],
        child: testApp(const MemberDocumentsScreen(
            memberUuid: 'member-uuid', canWrite: true))));
    await tester.pumpAndSettle();
    expect(find.text('Akta Lahir'), findsOneWidget);
    expect(find.byIcon(Icons.note_add), findsOneWidget);

    await tester.tap(find.byTooltip('Hapus'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Hapus'));
    await tester.pumpAndSettle();
    expect(members.calls, contains('deleteDocument'));

    members.documentItems = const [];
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(
        overrides: [
          currentFamilyProvider.overrideWith((ref) => testFamily),
          memberRepositoryProvider.overrideWithValue(members),
        ],
        child: testApp(const MemberDocumentsScreen(
            memberUuid: 'member-uuid', canWrite: false))));
    await tester.pumpAndSettle();
    expect(find.text('Belum ada dokumen'), findsOneWidget);
    expect(find.byIcon(Icons.note_add), findsNothing);
  });

  testWidgets('duplicates screen merges and handles empty state',
      (tester) async {
    final members = FakeMemberRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const MemberDuplicatesScreen())));
    await tester.pumpAndSettle();

    final mergeButton = tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Gabungkan'));
    mergeButton.onPressed!.call();
    await tester.pumpAndSettle();
    expect(find.text('Pilih data yang dipertahankan:'), findsOneWidget);
    final keepTile = tester
        .widget<ListTile>(find.widgetWithText(ListTile, 'Budi Santoso').first);
    keepTile.onTap!.call();
    await tester.pumpAndSettle();
    expect(members.calls, contains('mergeMember'));

    members.duplicateItems = const [];
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => testFamily),
      memberRepositoryProvider.overrideWithValue(members),
    ], child: testApp(const MemberDuplicatesScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Tidak ada kandidat duplikat'), findsOneWidget);
  });
}

Future<void> _pickMember(WidgetTester tester, String label) async {
  final picker = tester.widget<ListTile>(
      find.ancestor(of: find.text(label), matching: find.byType(ListTile)));
  picker.onTap!.call();
  await tester.pumpAndSettle();
  final tile = tester.widget<ListTile>(find
      .descendant(
          of: find.byType(AlertDialog),
          matching: find.widgetWithText(ListTile, 'Alm. Budi Santoso'))
      .first);
  tile.onTap!.call();
  await tester.pumpAndSettle();
}

void _tapSave(WidgetTester tester) {
  tester
      .widget<FilledButton>(find.widgetWithText(FilledButton, 'Simpan'))
      .onPressed!
      .call();
}
