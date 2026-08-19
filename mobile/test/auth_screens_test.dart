import 'package:family_tree_mobile/features/auth/auth_screens.dart';
import 'package:family_tree_mobile/l10n/app_localizations.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _app(Widget home) => ProviderScope(
    child: MaterialApp(
  locale: const Locale('id'),
  localizationsDelegates: AppLocalizations.localizationsDelegates,
  supportedLocales: AppLocalizations.supportedLocales,
  home: home,
));

void main() {
  testWidgets('register form remains usable with large text', (tester) async {
    await tester.pumpWidget(_app(MediaQuery(
        data: MediaQueryData(textScaler: TextScaler.linear(2)),
        child: const RegisterScreen())));
    expect(find.text('Daftar'), findsWidgets);
    expect(find.text('Nama'), findsOneWidget);
    expect(find.text('Buat akun baru'), findsNothing);
    expect(tester.takeException(), isNull);
  });

  testWidgets('reset screen disables submit without signed-link values',
      (tester) async {
    await tester
        .pumpWidget(_app(const ResetPasswordScreen(token: '', email: '')));
    final button = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Simpan kata sandi'));
    expect(button.onPressed, isNull);
  });
}
