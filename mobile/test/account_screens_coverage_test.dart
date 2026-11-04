import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/account/presentation/account_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  testWidgets('account screen navigates and logs out', (tester) async {
    final account = FakeAccountRepository();
    final auth = FakeAuthRepository();
    final session = await createTestSession(token: 'token');
    await tester.pumpWidget(ProviderScope(overrides: [
      currentUserProvider.overrideWith((ref) => testUser),
      accountRepositoryProvider.overrideWithValue(account),
      authRepositoryProvider.overrideWithValue(auth),
      sessionControllerProvider.overrideWithValue(session),
    ], child: testApp(const Scaffold(body: AccountScreen()))));
    await tester.pumpAndSettle();
    expect(find.text('Budi Santoso'), findsOneWidget);
    expect(find.text('budi@example.com'), findsOneWidget);

    await tester.tap(find.text('Profil'));
    await tester.pumpAndSettle();
    expect(find.text('Pilih avatar'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentUserProvider.overrideWith((ref) => testUser),
      accountRepositoryProvider.overrideWithValue(account),
      authRepositoryProvider.overrideWithValue(auth),
      sessionControllerProvider.overrideWithValue(session),
    ], child: testApp(const Scaffold(body: AccountScreen()))));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Keamanan dan sesi'));
    await tester.pumpAndSettle();
    expect(find.text('Ubah kata sandi'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      currentUserProvider.overrideWith((ref) => testUser),
      accountRepositoryProvider.overrideWithValue(account),
      authRepositoryProvider.overrideWithValue(auth),
      sessionControllerProvider.overrideWithValue(session),
    ], child: testApp(const Scaffold(body: AccountScreen()))));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Ganti keluarga'));
    await tester.pumpAndSettle();
    expect(session.status, SessionStatus.needsFamily);

    await tester.tap(find.text('Keluar'));
    await tester.pumpAndSettle();
    expect(auth.calls, contains('logout'));
    expect(session.status, SessionStatus.unauthenticated);
  });

  testWidgets('profile saves and reports failures', (tester) async {
    final account = FakeAccountRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      currentUserProvider.overrideWith((ref) => testUser),
      accountRepositoryProvider.overrideWithValue(account),
    ], child: testApp(const ProfileScreen())));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField).at(0), 'Budi Baru');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Simpan'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(account.calls, contains('updateProfile'));
    expect(find.text('Profil diperbarui.'), findsOneWidget);

    account.error = const AppError(AppErrorType.validation, 'Profil gagal');
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    expect(find.text('Profil gagal'), findsOneWidget);
  });

  testWidgets('preferences load, save and show errors', (tester) async {
    final account = FakeAccountRepository();
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      notificationPreferencesProvider.overrideWith((ref) async =>
          const NotificationPreferences(
              email: true,
              push: true,
              eventReminders: true,
              familyUpdates: true)),
    ], child: testApp(const PreferencesScreen())));
    await tester.pumpAndSettle();

    await tester.tap(find.byType(SwitchListTile).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    expect(account.calls, contains('updatePreferences'));

    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      notificationPreferencesProvider.overrideWith(
          (ref) async => throw const AppError(AppErrorType.server, 'Gagal')),
    ], child: testApp(const PreferencesScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('security changes password and revokes sessions', (tester) async {
    final account = FakeAccountRepository();
    final session = await createTestSession(token: 'token');
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      sessionControllerProvider.overrideWithValue(session),
      accountSessionsProvider.overrideWith((ref) async => account.sessionItems),
    ], child: testApp(const SecurityScreen())));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField).at(0), 'old');
    await tester.enterText(find.byType(TextField).at(1), 'new');
    await tester.tap(find.text('Ubah kata sandi'));
    await tester.pumpAndSettle();
    expect(account.calls, contains('changePassword'));
    expect(session.status, SessionStatus.unauthenticated);

    await tester.tap(find.byTooltip('Cabut sesi'));
    await tester.pumpAndSettle();
    expect(account.calls, contains('revokeSession'));
  });

  testWidgets('security reports password failures and session errors',
      (tester) async {
    final account = FakeAccountRepository()
      ..error = const AppError(AppErrorType.server, 'Gagal');
    final session = await createTestSession(token: 'token');
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      sessionControllerProvider.overrideWithValue(session),
      accountSessionsProvider.overrideWith(
          (ref) async => throw const AppError(AppErrorType.server, 'Gagal')),
    ], child: testApp(const SecurityScreen())));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Ubah kata sandi'));
    await tester.pumpAndSettle();
    expect(find.text('Gagal'), findsWidgets);
    expect(find.text('Muat ulang sesi'), findsOneWidget);
  });

  testWidgets('two factor enable, confirm, regenerate and disable',
      (tester) async {
    final account = FakeAccountRepository()
      ..twoFactorValue = const TwoFactorStatus(
          enabled: false, pending: false, recoveryCodesCount: 0);
    final session = await createTestSession(token: 'token');
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      sessionControllerProvider.overrideWithValue(session),
      accountSessionsProvider.overrideWith((ref) async => []),
    ], child: testApp(const SecurityScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Tidak aktif'), findsOneWidget);

    await tester.tap(find.text('Aktifkan 2FA'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField).last, 'secret');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Konfirmasi'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(find.text('SECRET'), findsOneWidget);
    await tester.tap(find.text('Tutup'));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField).last, '123456');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Konfirmasi'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(find.text('CODE1'), findsOneWidget);
    await tester.tap(find.text('Tutup'));
    await tester.pumpAndSettle();
    expect(account.calls, contains('confirmTwoFactor'));

    account.twoFactorValue = const TwoFactorStatus(
        enabled: true, pending: false, recoveryCodesCount: 2);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      sessionControllerProvider.overrideWithValue(session),
      accountSessionsProvider.overrideWith((ref) async => []),
    ], child: testApp(const SecurityScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Aktif'), findsOneWidget);

    await tester.tap(find.text('Kode pemulihan'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField).last, 'secret');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Konfirmasi'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(find.text('CODE1'), findsOneWidget);
    await tester.tap(find.text('Tutup'));
    await tester.pumpAndSettle();
    expect(account.calls, contains('regenerateRecoveryCodes'));

    await tester.tap(find.text('Nonaktifkan 2FA'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField).last, 'secret');
    tester
        .widget<FilledButton>(find.widgetWithText(FilledButton, 'Konfirmasi'))
        .onPressed!
        .call();
    await tester.pumpAndSettle();
    expect(account.calls, contains('disableTwoFactor'));
  });

  testWidgets('two factor status errors leave controls usable', (tester) async {
    final account = FakeAccountRepository()
      ..error = const AppError(AppErrorType.server, 'Gagal');
    final session = await createTestSession(token: 'token');
    await tester.pumpWidget(ProviderScope(overrides: [
      accountRepositoryProvider.overrideWithValue(account),
      sessionControllerProvider.overrideWithValue(session),
      accountSessionsProvider.overrideWith((ref) async => []),
    ], child: testApp(const SecurityScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Aktifkan 2FA'), findsOneWidget);
  });
}
