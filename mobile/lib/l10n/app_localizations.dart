import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_en.dart';
import 'app_localizations_id.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
      : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
    delegate,
    GlobalMaterialLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
  ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('en'),
    Locale('id')
  ];

  /// No description provided for @appTitle.
  ///
  /// In id, this message translates to:
  /// **'Family Tree Indonesia'**
  String get appTitle;

  /// No description provided for @loading.
  ///
  /// In id, this message translates to:
  /// **'Memuat…'**
  String get loading;

  /// No description provided for @retry.
  ///
  /// In id, this message translates to:
  /// **'Coba lagi'**
  String get retry;

  /// No description provided for @cancel.
  ///
  /// In id, this message translates to:
  /// **'Batal'**
  String get cancel;

  /// No description provided for @continueAction.
  ///
  /// In id, this message translates to:
  /// **'Lanjutkan'**
  String get continueAction;

  /// No description provided for @loadingData.
  ///
  /// In id, this message translates to:
  /// **'Memuat'**
  String get loadingData;

  /// No description provided for @loadFailed.
  ///
  /// In id, this message translates to:
  /// **'Tidak dapat memuat data'**
  String get loadFailed;

  /// No description provided for @genericError.
  ///
  /// In id, this message translates to:
  /// **'Terjadi kendala. Silakan coba lagi.'**
  String get genericError;

  /// No description provided for @showingStoredData.
  ///
  /// In id, this message translates to:
  /// **'Menampilkan data tersimpan dari {time}.'**
  String showingStoredData(String time);

  /// No description provided for @statusLabel.
  ///
  /// In id, this message translates to:
  /// **'Status: {label}'**
  String statusLabel(String label);

  /// No description provided for @email.
  ///
  /// In id, this message translates to:
  /// **'Email'**
  String get email;

  /// No description provided for @password.
  ///
  /// In id, this message translates to:
  /// **'Kata sandi'**
  String get password;

  /// No description provided for @showPassword.
  ///
  /// In id, this message translates to:
  /// **'Tampilkan kata sandi'**
  String get showPassword;

  /// No description provided for @hidePassword.
  ///
  /// In id, this message translates to:
  /// **'Sembunyikan kata sandi'**
  String get hidePassword;

  /// No description provided for @loginTitle.
  ///
  /// In id, this message translates to:
  /// **'Masuk'**
  String get loginTitle;

  /// No description provided for @loginButton.
  ///
  /// In id, this message translates to:
  /// **'Masuk'**
  String get loginButton;

  /// No description provided for @forgotPassword.
  ///
  /// In id, this message translates to:
  /// **'Lupa kata sandi?'**
  String get forgotPassword;

  /// No description provided for @createAccount.
  ///
  /// In id, this message translates to:
  /// **'Buat akun baru'**
  String get createAccount;

  /// No description provided for @registerTitle.
  ///
  /// In id, this message translates to:
  /// **'Daftar'**
  String get registerTitle;

  /// No description provided for @registerButton.
  ///
  /// In id, this message translates to:
  /// **'Daftar'**
  String get registerButton;

  /// No description provided for @nameLabel.
  ///
  /// In id, this message translates to:
  /// **'Nama'**
  String get nameLabel;

  /// No description provided for @phoneOptional.
  ///
  /// In id, this message translates to:
  /// **'Nomor telepon (opsional)'**
  String get phoneOptional;

  /// No description provided for @haveAccount.
  ///
  /// In id, this message translates to:
  /// **'Sudah punya akun? Masuk'**
  String get haveAccount;

  /// No description provided for @accountCreated.
  ///
  /// In id, this message translates to:
  /// **'Akun dibuat. Silakan masuk dan verifikasi email.'**
  String get accountCreated;

  /// No description provided for @forgotPasswordTitle.
  ///
  /// In id, this message translates to:
  /// **'Lupa kata sandi'**
  String get forgotPasswordTitle;

  /// No description provided for @sendResetLink.
  ///
  /// In id, this message translates to:
  /// **'Kirim tautan reset'**
  String get sendResetLink;

  /// No description provided for @sending.
  ///
  /// In id, this message translates to:
  /// **'Mengirim…'**
  String get sending;

  /// No description provided for @backToLogin.
  ///
  /// In id, this message translates to:
  /// **'Kembali ke masuk'**
  String get backToLogin;

  /// No description provided for @resetEmailSent.
  ///
  /// In id, this message translates to:
  /// **'Jika email terdaftar, tautan reset telah dikirim.'**
  String get resetEmailSent;

  /// No description provided for @resetPasswordTitle.
  ///
  /// In id, this message translates to:
  /// **'Reset kata sandi'**
  String get resetPasswordTitle;

  /// No description provided for @newPassword.
  ///
  /// In id, this message translates to:
  /// **'Kata sandi baru'**
  String get newPassword;

  /// No description provided for @savePassword.
  ///
  /// In id, this message translates to:
  /// **'Simpan kata sandi'**
  String get savePassword;

  /// No description provided for @passwordChanged.
  ///
  /// In id, this message translates to:
  /// **'Kata sandi berhasil diubah.'**
  String get passwordChanged;

  /// No description provided for @verifyEmailTitle.
  ///
  /// In id, this message translates to:
  /// **'Verifikasi email'**
  String get verifyEmailTitle;

  /// No description provided for @verifyEmailPrompt.
  ///
  /// In id, this message translates to:
  /// **'Periksa email Anda lalu buka tautan verifikasi.'**
  String get verifyEmailPrompt;

  /// No description provided for @resendEmail.
  ///
  /// In id, this message translates to:
  /// **'Kirim ulang email'**
  String get resendEmail;

  /// No description provided for @resendIn.
  ///
  /// In id, this message translates to:
  /// **'Kirim ulang dalam {seconds}d'**
  String resendIn(int seconds);

  /// No description provided for @verificationSent.
  ///
  /// In id, this message translates to:
  /// **'Tautan verifikasi dikirim.'**
  String get verificationSent;

  /// No description provided for @logout.
  ///
  /// In id, this message translates to:
  /// **'Keluar'**
  String get logout;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['en', 'id'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'en':
      return AppLocalizationsEn();
    case 'id':
      return AppLocalizationsId();
  }

  throw FlutterError(
      'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
      'an issue with the localizations generation tool. Please file an issue '
      'on GitHub with a reproducible sample app and the gen-l10n configuration '
      'that was used.');
}
