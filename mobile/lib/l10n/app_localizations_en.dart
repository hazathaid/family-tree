// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Family Tree Indonesia';

  @override
  String get loading => 'Loading…';

  @override
  String get retry => 'Try again';

  @override
  String get cancel => 'Cancel';

  @override
  String get continueAction => 'Continue';

  @override
  String get loadingData => 'Loading';

  @override
  String get loadFailed => 'Unable to load data';

  @override
  String get genericError => 'Something went wrong. Please try again.';

  @override
  String showingStoredData(String time) {
    return 'Showing saved data from $time.';
  }

  @override
  String statusLabel(String label) {
    return 'Status: $label';
  }

  @override
  String get email => 'Email';

  @override
  String get password => 'Password';

  @override
  String get showPassword => 'Show password';

  @override
  String get hidePassword => 'Hide password';

  @override
  String get loginTitle => 'Sign in';

  @override
  String get loginButton => 'Sign in';

  @override
  String get forgotPassword => 'Forgot password?';

  @override
  String get createAccount => 'Create new account';

  @override
  String get registerTitle => 'Register';

  @override
  String get registerButton => 'Register';

  @override
  String get nameLabel => 'Name';

  @override
  String get phoneOptional => 'Phone number (optional)';

  @override
  String get haveAccount => 'Already have an account? Sign in';

  @override
  String get accountCreated =>
      'Account created. Please sign in and verify your email.';

  @override
  String get forgotPasswordTitle => 'Forgot password';

  @override
  String get sendResetLink => 'Send reset link';

  @override
  String get sending => 'Sending…';

  @override
  String get backToLogin => 'Back to sign in';

  @override
  String get resetEmailSent =>
      'If the email is registered, a reset link has been sent.';

  @override
  String get resetPasswordTitle => 'Reset password';

  @override
  String get newPassword => 'New password';

  @override
  String get savePassword => 'Save password';

  @override
  String get passwordChanged => 'Password changed successfully.';

  @override
  String get verifyEmailTitle => 'Verify email';

  @override
  String get verifyEmailPrompt =>
      'Check your email and open the verification link.';

  @override
  String get resendEmail => 'Resend email';

  @override
  String resendIn(int seconds) {
    return 'Resend in ${seconds}s';
  }

  @override
  String get verificationSent => 'Verification link sent.';

  @override
  String get logout => 'Sign out';
}
