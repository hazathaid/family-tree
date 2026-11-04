import 'dart:async';

import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/family/presentation/family_management_screen.dart';
import 'package:family_tree_mobile/features/family/presentation/family_onboarding_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  group('FamilyManagementScreen', () {
    testWidgets('edits profile, branches and access', (tester) async {
      final families = FakeFamilyRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        familyRepositoryProvider.overrideWithValue(families),
      ], child: testApp(const FamilyManagementScreen())));
      await tester.pumpAndSettle();

      expect(find.text('Keluarga Santoso'), findsOneWidget);
      await tester.enterText(find.byType(TextField).at(0), 'Keluarga Baru');
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Simpan'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('update'));

      await tester.tap(find.text('Cabang'));
      await tester.pumpAndSettle();
      expect(find.text('Cabang Utama'), findsOneWidget);

      tester
          .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Tambah cabang'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      await tester.enterText(
          find
              .descendant(
                  of: find.byType(AlertDialog),
                  matching: find.byType(TextField))
              .first,
          'Cabang Baru');
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Simpan')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('createBranch'));

      await tester.tap(find.byIcon(Icons.more_vert).first);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Ubah').last);
      await tester.pumpAndSettle();
      await tester.enterText(
          find
              .descendant(
                  of: find.byType(AlertDialog),
                  matching: find.byType(TextField))
              .first,
          'Cabang Ubah');
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Simpan')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('updateBranch'));

      await tester.tap(find.byIcon(Icons.more_vert).first);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Hapus').last);
      await tester.pumpAndSettle();
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Hapus')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('deleteBranch'));

      await tester.tap(find.text('Akses'));
      await tester.pumpAndSettle();
      expect(find.text('budi@example.com'), findsOneWidget);

      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Undang'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      await tester.enterText(
          find
              .descendant(
                  of: find.byType(AlertDialog),
                  matching: find.byType(TextField))
              .first,
          'baru@example.com');
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Undang')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('invite'));

      final roleDropdown = tester
          .widget<DropdownButton<String>>(find.byType(DropdownButton<String>));
      roleDropdown.onChanged!('admin');
      await tester.pumpAndSettle();
      expect(families.calls, contains('assignRole'));

      await tester.tap(find.byTooltip('Hapus akses'));
      await tester.pumpAndSettle();
      tester
          .widget<FilledButton>(find.descendant(
              of: find.byType(AlertDialog),
              matching: find.widgetWithText(FilledButton, 'Hapus')))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('removeMembership'));
    });

    testWidgets('shows read-only access and null family states',
        (tester) async {
      const memberFamily = Family(
          uuid: 'family-uuid', name: 'Keluarga', currentUserRole: 'member');
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => memberFamily),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
      ], child: testApp(const FamilyManagementScreen())));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Akses'));
      await tester.pumpAndSettle();
      expect(find.text('Hanya pemilik yang dapat mengelola akses keluarga.'),
          findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => null),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
      ], child: testApp(const FamilyManagementScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Pilih keluarga terlebih dahulu.'), findsOneWidget);
    });

    testWidgets('handles branch and access load failures', (tester) async {
      final families = FakeFamilyRepository()
        ..error = const AppError(AppErrorType.server, 'Gagal');
      await tester.pumpWidget(ProviderScope(overrides: [
        currentFamilyProvider.overrideWith((ref) => testFamily),
        familyRepositoryProvider.overrideWithValue(families),
      ], child: testApp(const FamilyManagementScreen())));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Cabang'));
      await tester.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);

      await tester.tap(find.text('Akses'));
      await tester.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);
    });
  });

  group('FamilyOnboardingScreen', () {
    testWidgets('creates a family and shows field errors', (tester) async {
      final families = FakeFamilyRepository();
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(overrides: [
        familyRepositoryProvider.overrideWithValue(families),
        sessionControllerProvider.overrideWithValue(session),
      ], child: testApp(const FamilyOnboardingScreen())));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField).at(0), 'Keluarga Baru');
      tester
          .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Buat keluarga'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(families.calls, contains('create'));

      families.error =
          const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
        'name': ['Nama keluarga wajib']
      });
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        familyRepositoryProvider.overrideWithValue(families),
        sessionControllerProvider.overrideWithValue(session),
      ], child: testApp(const FamilyOnboardingScreen())));
      await tester.pumpAndSettle();
      tester
          .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Buat keluarga'))
          .onPressed!
          .call();
      await tester.pumpAndSettle();
      expect(find.text('Nama keluarga wajib'), findsOneWidget);
    });
  });

  group('FamilySelectorScreen', () {
    testWidgets('shows loading, error and empty states', (tester) async {
      final pending = Completer<List<Family>>();
      await tester.pumpWidget(ProviderScope(overrides: [
        sessionControllerProvider.overrideWithValue(await createTestSession()),
        familiesProvider.overrideWith((ref) => pending.future),
      ], child: testApp(const FamilySelectorScreen())));
      await tester.pump();
      expect(find.byType(CircularProgressIndicator), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        sessionControllerProvider.overrideWithValue(await createTestSession()),
        familiesProvider.overrideWith(
            (ref) => throw const AppError(AppErrorType.server, 'Gagal')),
      ], child: testApp(const FamilySelectorScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Keluarga tidak dapat dimuat.'), findsOneWidget);

      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        sessionControllerProvider.overrideWithValue(await createTestSession()),
        familiesProvider.overrideWith((ref) => <Family>[]),
      ], child: testApp(const FamilySelectorScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Buat keluarga pertama'), findsOneWidget);
    });

    testWidgets('selects a family from the list', (tester) async {
      final families = FakeFamilyRepository();
      final cache = MemoryScopedCache();
      final session = await createTestSession(cache: cache);
      session.userUuid = 'user-uuid';
      await tester.pumpWidget(ProviderScope(overrides: [
        familyRepositoryProvider.overrideWithValue(families),
        sessionControllerProvider.overrideWithValue(session),
        scopedCacheProvider.overrideWithValue(cache),
        familiesProvider.overrideWith((ref) =>
            [testFamily, const Family(uuid: 'family-b', name: 'Keluarga B')]),
      ], child: testApp(const FamilySelectorScreen())));
      await tester.pumpAndSettle();
      expect(find.text('Keluarga Santoso'), findsOneWidget);
      await tester.tap(find.text('Keluarga B'));
      await tester.pumpAndSettle();
      expect(session.activeFamilyUuid, 'family-b');
    });

    testWidgets('auto selects a single family', (tester) async {
      final cache = MemoryScopedCache();
      final session = await createTestSession(cache: cache);
      session.userUuid = 'user-uuid';
      await tester.pumpWidget(ProviderScope(overrides: [
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
        sessionControllerProvider.overrideWithValue(session),
        scopedCacheProvider.overrideWithValue(cache),
        familiesProvider.overrideWith((ref) => [testFamily]),
      ], child: testApp(const FamilySelectorScreen())));
      await tester.pumpAndSettle();
      expect(session.activeFamilyUuid, 'family-uuid');
      expect(session.status, SessionStatus.authenticated);
    });
  });
}
