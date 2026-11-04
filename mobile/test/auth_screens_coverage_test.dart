import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/errors/app_error.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/auth/auth_screens.dart';
import 'package:family_tree_mobile/features/auth/login_screen.dart';
import 'package:family_tree_mobile/features/auth/two_factor_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fakes.dart';
import 'support/harness.dart';

void main() {
  group('LoginScreen', () {
    testWidgets('signs in and resolves the session', (tester) async {
      final auth = FakeAuthRepository();
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
        sessionControllerProvider.overrideWithValue(session),
      ], child: routedApp(const LoginScreen())));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField).at(0), 'budi@example.com');
      await tester.enterText(find.byType(TextField).at(1), 'secret');
      await tester.tap(find.text('Masuk'));
      await tester.pumpAndSettle();

      expect(auth.calls, contains('login'));
      expect(session.userUuid, 'user-uuid');
      expect(session.status, SessionStatus.needsFamily);
    });

    testWidgets('routes to two factor and shows login errors', (tester) async {
      final auth = FakeAuthRepository()
        ..loginResult =
            const AuthLoginResult(challengeToken: 'challenge-token');
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
        sessionControllerProvider.overrideWithValue(session),
      ], child: routedApp(const LoginScreen())));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Masuk'));
      await tester.pumpAndSettle();
      expect(
          find.textContaining('route:/two-factor-challenge'), findsOneWidget);

      auth
        ..loginResult = AuthLoginResult(user: testUser)
        ..error =
            const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
          'email': ['Email salah']
        });
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
        sessionControllerProvider.overrideWithValue(session),
      ], child: routedApp(const LoginScreen())));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Masuk'));
      await tester.pumpAndSettle();
      expect(find.text('Email salah'), findsOneWidget);

      await tester.tap(find.byTooltip('Tampilkan kata sandi'));
      await tester.pump();
      expect(find.byIcon(Icons.visibility_off), findsOneWidget);
    });
  });

  group('RegisterScreen', () {
    testWidgets('registers and surfaces validation errors', (tester) async {
      final auth = FakeAuthRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
      ], child: routedApp(const RegisterScreen())));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField).at(0), 'Budi');
      await tester.enterText(find.byType(TextField).at(1), 'budi@example.com');
      await tester.enterText(find.byType(TextField).at(3), 'secret');
      final registerButton = tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Daftar'));
      registerButton.onPressed!.call();
      await tester.pumpAndSettle();
      expect(auth.calls, contains('register'));

      auth.error =
          const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
        'name': ['Nama wajib'],
        'email': ['Email tidak valid'],
        'phone': ['Telepon tidak valid'],
        'password': ['Kata sandi lemah']
      });
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
      ], child: routedApp(const RegisterScreen())));
      await tester.pumpAndSettle();
      final retryButton = tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Daftar'));
      retryButton.onPressed!.call();
      await tester.pumpAndSettle();
      expect(find.text('Nama wajib'), findsOneWidget);
      expect(find.text('Email tidak valid'), findsOneWidget);
      expect(find.text('Telepon tidak valid'), findsOneWidget);
      expect(find.text('Kata sandi lemah'), findsOneWidget);
    });
  });

  group('ForgotPasswordScreen', () {
    testWidgets('sends reset email and shows failures', (tester) async {
      final auth = FakeAuthRepository();
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
      ], child: testApp(const ForgotPasswordScreen())));
      await tester.enterText(find.byType(TextField), 'budi@example.com');
      await tester.tap(find.text('Kirim tautan reset'));
      await tester.pumpAndSettle();
      expect(auth.calls, contains('forgotPassword'));

      auth.error =
          const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
        'email': ['Email tidak ditemukan']
      });
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
      ], child: testApp(const ForgotPasswordScreen())));
      await tester.tap(find.text('Kirim tautan reset'));
      await tester.pumpAndSettle();
      expect(find.text('Email tidak ditemukan'), findsOneWidget);
    });
  });

  group('ResetPasswordScreen', () {
    testWidgets('resets the password and shows failures', (tester) async {
      final auth = FakeAuthRepository();
      await tester.pumpWidget(ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(auth),
          ],
          child: routedApp(
              const ResetPasswordScreen(token: 'token', email: 'a@b.c'))));
      await tester.enterText(find.byType(TextField), 'secret');
      await tester.tap(find.text('Simpan kata sandi'));
      await tester.pumpAndSettle();
      expect(auth.calls, contains('resetPassword'));

      auth.error =
          const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
        'password': ['Kata sandi terlalu pendek']
      });
      await tester.pumpWidget(const SizedBox());
      await tester.pumpWidget(ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(auth),
          ],
          child: routedApp(
              const ResetPasswordScreen(token: 'token', email: 'a@b.c'))));
      await tester.enterText(find.byType(TextField), 'secret');
      await tester.tap(find.text('Simpan kata sandi'));
      await tester.pumpAndSettle();
      expect(find.text('Kata sandi terlalu pendek'), findsOneWidget);
    });
  });

  group('VerificationScreen', () {
    testWidgets('verifies, resends and logs out', (tester) async {
      final auth = FakeAuthRepository();
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(auth),
            familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
            sessionControllerProvider.overrideWithValue(session),
          ],
          child: testApp(const VerificationScreen(
              id: '1', hash: 'hash', query: {'expires': '1'}))));
      await tester.pumpAndSettle();
      expect(auth.calls, contains('verifyEmail'));
      expect(auth.calls, contains('me'));

      await tester.tap(find.text('Kirim ulang email'));
      await tester.pump(const Duration(seconds: 1));
      expect(auth.calls, contains('resendVerification'));
      expect(find.textContaining('Kirim ulang'), findsOneWidget);

      await tester.tap(find.text('Keluar'));
      await tester.pump();
      expect(auth.calls, contains('logout'));
      expect(session.status, SessionStatus.unauthenticated);
      await tester.pumpWidget(const SizedBox());
    });

    testWidgets('shows verification and resend failures', (tester) async {
      final auth = FakeAuthRepository()
        ..error = const AppError(AppErrorType.server, 'Verifikasi gagal');
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(overrides: [
        authRepositoryProvider.overrideWithValue(auth),
        familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
        sessionControllerProvider.overrideWithValue(session),
      ], child: testApp(const VerificationScreen(id: '1', hash: 'hash'))));
      await tester.pumpAndSettle();
      expect(find.text('Verifikasi gagal'), findsOneWidget);

      await tester.tap(find.text('Kirim ulang email'));
      await tester.pump();
      expect(find.text('Verifikasi gagal'), findsOneWidget);
    });
  });

  group('TwoFactorChallengeScreen', () {
    testWidgets('verifies a code and a recovery code', (tester) async {
      final auth = FakeAuthRepository();
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(auth),
            familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
            sessionControllerProvider.overrideWithValue(session),
          ],
          child: testApp(
              const TwoFactorChallengeScreen(challengeToken: 'challenge'))));
      await tester.enterText(find.byType(TextField), '123456');
      await tester.tap(find.text('Verifikasi'));
      await tester.pumpAndSettle();
      expect(auth.calls, contains('twoFactorChallenge'));
      expect(session.userUuid, 'user-uuid');

      await tester.tap(find.text('Gunakan kode pemulihan'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'RECOVERY');
      await tester.tap(find.text('Verifikasi'));
      await tester.pumpAndSettle();
      expect(
          auth.calls.where((call) => call == 'twoFactorChallenge').length, 2);
    });

    testWidgets('shows validation failures', (tester) async {
      final auth = FakeAuthRepository()
        ..error =
            const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
          'code': ['Kode salah']
        });
      final session = await createTestSession();
      await tester.pumpWidget(ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(auth),
            familyRepositoryProvider.overrideWithValue(FakeFamilyRepository()),
            sessionControllerProvider.overrideWithValue(session),
          ],
          child: testApp(
              const TwoFactorChallengeScreen(challengeToken: 'challenge'))));
      await tester.tap(find.text('Verifikasi'));
      await tester.pumpAndSettle();
      expect(find.text('Kode salah'), findsOneWidget);

      await tester.tap(find.text('Gunakan kode pemulihan'));
      await tester.pumpAndSettle();
      auth.error =
          const AppError(AppErrorType.validation, 'Gagal', fieldErrors: {
        'recovery_code': ['Kode pemulihan salah']
      });
      await tester.tap(find.text('Verifikasi'));
      await tester.pumpAndSettle();
      expect(find.text('Kode pemulihan salah'), findsOneWidget);
    });
  });
}
