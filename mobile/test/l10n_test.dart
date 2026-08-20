import 'package:family_tree_mobile/l10n/app_localizations.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('both supported locales resolve every translation key', () async {
    WidgetsFlutterBinding.ensureInitialized();

    expect(AppLocalizations.supportedLocales, contains(const Locale('id')));
    expect(AppLocalizations.supportedLocales, contains(const Locale('en')));

    for (final locale in AppLocalizations.supportedLocales) {
      final l10n = lookupAppLocalizations(locale);
      expect(l10n, isNotNull, reason: 'Missing localizations for $locale');
      expect(l10n.appTitle, isNotEmpty);
      expect(l10n.loginButton, isNotEmpty);
      expect(l10n.registerTitle, isNotEmpty);
      expect(l10n.savePassword, isNotEmpty);
      expect(l10n.verifyEmailPrompt, isNotEmpty);
      expect(l10n.showingStoredData('now'), isNotEmpty);
      expect(l10n.resendIn(10), contains('10'));
      expect(l10n.statusLabel('owner'), isNotEmpty);
      expect(l10n.membersTitle, isNotEmpty);
      expect(l10n.family, isNotEmpty);
      expect(l10n.pageOf(1, 2), isNotEmpty);
      expect(l10n.memberSemantics('Ada', l10n.alive), isNotEmpty);
      expect(l10n.deleteMemberTitle('Ada'), isNotEmpty);
    }
  });
}
