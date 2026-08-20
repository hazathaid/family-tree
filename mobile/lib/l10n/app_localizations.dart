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

  /// No description provided for @membersTitle.
  ///
  /// In id, this message translates to:
  /// **'Anggota keluarga'**
  String get membersTitle;

  /// No description provided for @memberFilters.
  ///
  /// In id, this message translates to:
  /// **'Filter anggota'**
  String get memberFilters;

  /// No description provided for @gender.
  ///
  /// In id, this message translates to:
  /// **'Gender'**
  String get gender;

  /// No description provided for @status.
  ///
  /// In id, this message translates to:
  /// **'Status'**
  String get status;

  /// No description provided for @branch.
  ///
  /// In id, this message translates to:
  /// **'Cabang'**
  String get branch;

  /// No description provided for @sort.
  ///
  /// In id, this message translates to:
  /// **'Urutkan'**
  String get sort;

  /// No description provided for @all.
  ///
  /// In id, this message translates to:
  /// **'Semua'**
  String get all;

  /// No description provided for @male.
  ///
  /// In id, this message translates to:
  /// **'Laki-laki'**
  String get male;

  /// No description provided for @female.
  ///
  /// In id, this message translates to:
  /// **'Perempuan'**
  String get female;

  /// No description provided for @unspecified.
  ///
  /// In id, this message translates to:
  /// **'Tidak ditentukan'**
  String get unspecified;

  /// No description provided for @alive.
  ///
  /// In id, this message translates to:
  /// **'Hidup'**
  String get alive;

  /// No description provided for @deceased.
  ///
  /// In id, this message translates to:
  /// **'Meninggal'**
  String get deceased;

  /// No description provided for @sortNameAscending.
  ///
  /// In id, this message translates to:
  /// **'Nama A-Z'**
  String get sortNameAscending;

  /// No description provided for @sortNameDescending.
  ///
  /// In id, this message translates to:
  /// **'Nama Z-A'**
  String get sortNameDescending;

  /// No description provided for @sortNewest.
  ///
  /// In id, this message translates to:
  /// **'Terbaru'**
  String get sortNewest;

  /// No description provided for @sortOldest.
  ///
  /// In id, this message translates to:
  /// **'Terlama'**
  String get sortOldest;

  /// No description provided for @apply.
  ///
  /// In id, this message translates to:
  /// **'Terapkan'**
  String get apply;

  /// No description provided for @manageRelationships.
  ///
  /// In id, this message translates to:
  /// **'Kelola relationship'**
  String get manageRelationships;

  /// No description provided for @relationshipResolver.
  ///
  /// In id, this message translates to:
  /// **'Resolver relationship'**
  String get relationshipResolver;

  /// No description provided for @addMember.
  ///
  /// In id, this message translates to:
  /// **'Tambah anggota'**
  String get addMember;

  /// No description provided for @searchMemberNameOrNickname.
  ///
  /// In id, this message translates to:
  /// **'Cari nama atau panggilan'**
  String get searchMemberNameOrNickname;

  /// No description provided for @filterAndSort.
  ///
  /// In id, this message translates to:
  /// **'Filter dan urutkan'**
  String get filterAndSort;

  /// No description provided for @noMatchingMembers.
  ///
  /// In id, this message translates to:
  /// **'Belum ada anggota yang sesuai.'**
  String get noMatchingMembers;

  /// No description provided for @memberName.
  ///
  /// In id, this message translates to:
  /// **'Nama'**
  String get memberName;

  /// No description provided for @previousPage.
  ///
  /// In id, this message translates to:
  /// **'Halaman sebelumnya'**
  String get previousPage;

  /// No description provided for @nextPage.
  ///
  /// In id, this message translates to:
  /// **'Halaman berikutnya'**
  String get nextPage;

  /// No description provided for @pageOf.
  ///
  /// In id, this message translates to:
  /// **'Halaman {current} dari {total}'**
  String pageOf(int current, int total);

  /// No description provided for @memberDetailLoadFailed.
  ///
  /// In id, this message translates to:
  /// **'Detail anggota tidak dapat dimuat.'**
  String get memberDetailLoadFailed;

  /// No description provided for @editMember.
  ///
  /// In id, this message translates to:
  /// **'Edit anggota'**
  String get editMember;

  /// No description provided for @relationshipToYou.
  ///
  /// In id, this message translates to:
  /// **'{relationship} untuk Anda'**
  String relationshipToYou(String relationship);

  /// No description provided for @inMemory.
  ///
  /// In id, this message translates to:
  /// **'Dalam kenangan'**
  String get inMemory;

  /// No description provided for @basicInformation.
  ///
  /// In id, this message translates to:
  /// **'Informasi dasar'**
  String get basicInformation;

  /// No description provided for @fullName.
  ///
  /// In id, this message translates to:
  /// **'Nama lengkap'**
  String get fullName;

  /// No description provided for @nickname.
  ///
  /// In id, this message translates to:
  /// **'Nama panggilan'**
  String get nickname;

  /// No description provided for @religionBelief.
  ///
  /// In id, this message translates to:
  /// **'Agama/kepercayaan'**
  String get religionBelief;

  /// No description provided for @born.
  ///
  /// In id, this message translates to:
  /// **'Lahir'**
  String get born;

  /// No description provided for @died.
  ///
  /// In id, this message translates to:
  /// **'Wafat'**
  String get died;

  /// No description provided for @noBranch.
  ///
  /// In id, this message translates to:
  /// **'Tidak ada cabang'**
  String get noBranch;

  /// No description provided for @biography.
  ///
  /// In id, this message translates to:
  /// **'Biografi'**
  String get biography;

  /// No description provided for @noBiography.
  ///
  /// In id, this message translates to:
  /// **'Belum ada biografi.'**
  String get noBiography;

  /// No description provided for @basicRelationships.
  ///
  /// In id, this message translates to:
  /// **'Relationship dasar'**
  String get basicRelationships;

  /// No description provided for @noBasicRelationships.
  ///
  /// In id, this message translates to:
  /// **'Belum ada relationship dasar.'**
  String get noBasicRelationships;

  /// No description provided for @relatedContent.
  ///
  /// In id, this message translates to:
  /// **'Konten terkait'**
  String get relatedContent;

  /// No description provided for @relatedPhotos.
  ///
  /// In id, this message translates to:
  /// **'Foto terkait'**
  String get relatedPhotos;

  /// No description provided for @relatedArticles.
  ///
  /// In id, this message translates to:
  /// **'Artikel terkait'**
  String get relatedArticles;

  /// No description provided for @noRelatedContent.
  ///
  /// In id, this message translates to:
  /// **'Belum ada konten terkait untuk ditampilkan.'**
  String get noRelatedContent;

  /// No description provided for @memberSaved.
  ///
  /// In id, this message translates to:
  /// **'Anggota berhasil disimpan.'**
  String get memberSaved;

  /// No description provided for @photoUpdated.
  ///
  /// In id, this message translates to:
  /// **'Foto berhasil diperbarui.'**
  String get photoUpdated;

  /// No description provided for @deleteMemberTitle.
  ///
  /// In id, this message translates to:
  /// **'Hapus {name}?'**
  String deleteMemberTitle(String name);

  /// No description provided for @deleteMemberConfirmation.
  ///
  /// In id, this message translates to:
  /// **'Anggota akan dihapus secara lunak dan tidak tampil lagi.'**
  String get deleteMemberConfirmation;

  /// No description provided for @delete.
  ///
  /// In id, this message translates to:
  /// **'Hapus'**
  String get delete;

  /// No description provided for @birthDate.
  ///
  /// In id, this message translates to:
  /// **'Tanggal lahir (YYYY-MM-DD)'**
  String get birthDate;

  /// No description provided for @birthPlace.
  ///
  /// In id, this message translates to:
  /// **'Tempat lahir'**
  String get birthPlace;

  /// No description provided for @stillAlive.
  ///
  /// In id, this message translates to:
  /// **'Masih hidup'**
  String get stillAlive;

  /// No description provided for @deathDate.
  ///
  /// In id, this message translates to:
  /// **'Tanggal wafat (YYYY-MM-DD)'**
  String get deathDate;

  /// No description provided for @deathPlace.
  ///
  /// In id, this message translates to:
  /// **'Tempat wafat'**
  String get deathPlace;

  /// No description provided for @saving.
  ///
  /// In id, this message translates to:
  /// **'Menyimpan...'**
  String get saving;

  /// No description provided for @save.
  ///
  /// In id, this message translates to:
  /// **'Simpan'**
  String get save;

  /// No description provided for @replacePhoto.
  ///
  /// In id, this message translates to:
  /// **'Ganti foto'**
  String get replacePhoto;

  /// No description provided for @deleteMember.
  ///
  /// In id, this message translates to:
  /// **'Hapus anggota'**
  String get deleteMember;

  /// No description provided for @addRelationship.
  ///
  /// In id, this message translates to:
  /// **'Tambah relationship'**
  String get addRelationship;

  /// No description provided for @editRelationship.
  ///
  /// In id, this message translates to:
  /// **'Edit relationship'**
  String get editRelationship;

  /// No description provided for @relationshipType.
  ///
  /// In id, this message translates to:
  /// **'Tipe dasar'**
  String get relationshipType;

  /// No description provided for @sourceMember.
  ///
  /// In id, this message translates to:
  /// **'Anggota sumber'**
  String get sourceMember;

  /// No description provided for @targetMember.
  ///
  /// In id, this message translates to:
  /// **'Anggota tujuan'**
  String get targetMember;

  /// No description provided for @source.
  ///
  /// In id, this message translates to:
  /// **'Sumber'**
  String get source;

  /// No description provided for @target.
  ///
  /// In id, this message translates to:
  /// **'Tujuan'**
  String get target;

  /// No description provided for @selectMember.
  ///
  /// In id, this message translates to:
  /// **'Pilih anggota'**
  String get selectMember;

  /// No description provided for @findRelationship.
  ///
  /// In id, this message translates to:
  /// **'Temukan relationship'**
  String get findRelationship;

  /// No description provided for @calculating.
  ///
  /// In id, this message translates to:
  /// **'Menghitung...'**
  String get calculating;

  /// No description provided for @notConnected.
  ///
  /// In id, this message translates to:
  /// **'Tidak terhubung'**
  String get notConnected;

  /// No description provided for @sameMember.
  ///
  /// In id, this message translates to:
  /// **'Anggota yang sama.'**
  String get sameMember;

  /// No description provided for @relationshipPathNotFound.
  ///
  /// In id, this message translates to:
  /// **'Tidak ditemukan jalur relationship.'**
  String get relationshipPathNotFound;

  /// No description provided for @selectMemberTitle.
  ///
  /// In id, this message translates to:
  /// **'Pilih anggota'**
  String get selectMemberTitle;

  /// No description provided for @searchMembers.
  ///
  /// In id, this message translates to:
  /// **'Cari anggota'**
  String get searchMembers;

  /// No description provided for @pageFraction.
  ///
  /// In id, this message translates to:
  /// **'{current} / {total}'**
  String pageFraction(int current, int total);

  /// No description provided for @noValue.
  ///
  /// In id, this message translates to:
  /// **'-'**
  String get noValue;

  /// No description provided for @noBranchShort.
  ///
  /// In id, this message translates to:
  /// **'Tanpa cabang'**
  String get noBranchShort;

  /// No description provided for @memberSemantics.
  ///
  /// In id, this message translates to:
  /// **'{name}, {status}'**
  String memberSemantics(String name, String status);

  /// No description provided for @islam.
  ///
  /// In id, this message translates to:
  /// **'Islam'**
  String get islam;

  /// No description provided for @christian.
  ///
  /// In id, this message translates to:
  /// **'Kristen'**
  String get christian;

  /// No description provided for @catholic.
  ///
  /// In id, this message translates to:
  /// **'Katolik'**
  String get catholic;

  /// No description provided for @hindu.
  ///
  /// In id, this message translates to:
  /// **'Hindu'**
  String get hindu;

  /// No description provided for @buddhist.
  ///
  /// In id, this message translates to:
  /// **'Buddha'**
  String get buddhist;

  /// No description provided for @confucian.
  ///
  /// In id, this message translates to:
  /// **'Konghucu'**
  String get confucian;

  /// No description provided for @belief.
  ///
  /// In id, this message translates to:
  /// **'Penghayat kepercayaan'**
  String get belief;

  /// No description provided for @other.
  ///
  /// In id, this message translates to:
  /// **'Lainnya'**
  String get other;

  /// No description provided for @father.
  ///
  /// In id, this message translates to:
  /// **'Ayah'**
  String get father;

  /// No description provided for @mother.
  ///
  /// In id, this message translates to:
  /// **'Ibu'**
  String get mother;

  /// No description provided for @child.
  ///
  /// In id, this message translates to:
  /// **'Anak'**
  String get child;

  /// No description provided for @husband.
  ///
  /// In id, this message translates to:
  /// **'Suami'**
  String get husband;

  /// No description provided for @wife.
  ///
  /// In id, this message translates to:
  /// **'Istri'**
  String get wife;

  /// No description provided for @family.
  ///
  /// In id, this message translates to:
  /// **'Keluarga'**
  String get family;

  /// No description provided for @fullNameRequired.
  ///
  /// In id, this message translates to:
  /// **'Nama lengkap wajib diisi.'**
  String get fullNameRequired;

  /// No description provided for @dashboardWelcome.
  ///
  /// In id, this message translates to:
  /// **'Selamat datang di {family}'**
  String dashboardWelcome(String family);

  /// No description provided for @dashboardYourFamily.
  ///
  /// In id, this message translates to:
  /// **'keluarga Anda'**
  String get dashboardYourFamily;

  /// No description provided for @dashboardLoadFailed.
  ///
  /// In id, this message translates to:
  /// **'Dashboard tidak dapat dimuat.'**
  String get dashboardLoadFailed;

  /// No description provided for @dashboardTotalMembers.
  ///
  /// In id, this message translates to:
  /// **'Total anggota'**
  String get dashboardTotalMembers;

  /// No description provided for @dashboardLivingMembers.
  ///
  /// In id, this message translates to:
  /// **'Anggota hidup'**
  String get dashboardLivingMembers;

  /// No description provided for @dashboardArticles.
  ///
  /// In id, this message translates to:
  /// **'Artikel'**
  String get dashboardArticles;

  /// No description provided for @dashboardPhotos.
  ///
  /// In id, this message translates to:
  /// **'Foto'**
  String get dashboardPhotos;

  /// No description provided for @dashboardEvents.
  ///
  /// In id, this message translates to:
  /// **'Acara'**
  String get dashboardEvents;

  /// No description provided for @dashboardRecentActivity.
  ///
  /// In id, this message translates to:
  /// **'Aktivitas terbaru'**
  String get dashboardRecentActivity;

  /// No description provided for @dashboardUpcomingBirthdays.
  ///
  /// In id, this message translates to:
  /// **'Ulang tahun mendatang'**
  String get dashboardUpcomingBirthdays;

  /// No description provided for @dashboardUpcomingEvents.
  ///
  /// In id, this message translates to:
  /// **'Acara mendatang'**
  String get dashboardUpcomingEvents;

  /// No description provided for @dashboardNotifications.
  ///
  /// In id, this message translates to:
  /// **'Notifikasi ({count} belum dibaca)'**
  String dashboardNotifications(int count);

  /// No description provided for @dashboardFamilyFacts.
  ///
  /// In id, this message translates to:
  /// **'Fakta keluarga'**
  String get dashboardFamilyFacts;

  /// No description provided for @dashboardOriginCity.
  ///
  /// In id, this message translates to:
  /// **'Kota asal: {city}'**
  String dashboardOriginCity(String city);

  /// No description provided for @dashboardOldestMember.
  ///
  /// In id, this message translates to:
  /// **'Anggota tertua: {name}'**
  String dashboardOldestMember(String name);

  /// No description provided for @dashboardYoungestMember.
  ///
  /// In id, this message translates to:
  /// **'Anggota termuda: {name}'**
  String dashboardYoungestMember(String name);

  /// No description provided for @dashboardRecentMembers.
  ///
  /// In id, this message translates to:
  /// **'Anggota terbaru'**
  String get dashboardRecentMembers;

  /// No description provided for @dashboardSeeAll.
  ///
  /// In id, this message translates to:
  /// **'Lihat semua'**
  String get dashboardSeeAll;

  /// No description provided for @noData.
  ///
  /// In id, this message translates to:
  /// **'Belum ada data.'**
  String get noData;

  /// No description provided for @familyOnboardingTitle.
  ///
  /// In id, this message translates to:
  /// **'Buat keluarga'**
  String get familyOnboardingTitle;

  /// No description provided for @familyOnboardingHeadline.
  ///
  /// In id, this message translates to:
  /// **'Mulai dokumentasikan keluarga'**
  String get familyOnboardingHeadline;

  /// No description provided for @familyOnboardingBody.
  ///
  /// In id, this message translates to:
  /// **'Anda akan menjadi pemilik keluarga dan dapat mengundang anggota nanti.'**
  String get familyOnboardingBody;

  /// No description provided for @familyName.
  ///
  /// In id, this message translates to:
  /// **'Nama keluarga'**
  String get familyName;

  /// No description provided for @originCityOptional.
  ///
  /// In id, this message translates to:
  /// **'Kota asal (opsional)'**
  String get originCityOptional;

  /// No description provided for @descriptionOptional.
  ///
  /// In id, this message translates to:
  /// **'Deskripsi (opsional)'**
  String get descriptionOptional;

  /// No description provided for @creating.
  ///
  /// In id, this message translates to:
  /// **'Membuat…'**
  String get creating;

  /// No description provided for @selectFamilyTitle.
  ///
  /// In id, this message translates to:
  /// **'Pilih keluarga'**
  String get selectFamilyTitle;

  /// No description provided for @familiesLoadFailed.
  ///
  /// In id, this message translates to:
  /// **'Keluarga tidak dapat dimuat.'**
  String get familiesLoadFailed;

  /// No description provided for @createFirstFamily.
  ///
  /// In id, this message translates to:
  /// **'Buat keluarga pertama'**
  String get createFirstFamily;

  /// No description provided for @selectFamilyFirst.
  ///
  /// In id, this message translates to:
  /// **'Pilih keluarga terlebih dahulu.'**
  String get selectFamilyFirst;

  /// No description provided for @manageFamilyTitle.
  ///
  /// In id, this message translates to:
  /// **'Kelola keluarga'**
  String get manageFamilyTitle;

  /// No description provided for @tabProfile.
  ///
  /// In id, this message translates to:
  /// **'Profil'**
  String get tabProfile;

  /// No description provided for @tabBranches.
  ///
  /// In id, this message translates to:
  /// **'Cabang'**
  String get tabBranches;

  /// No description provided for @tabAccess.
  ///
  /// In id, this message translates to:
  /// **'Akses'**
  String get tabAccess;

  /// No description provided for @familySettingsSaved.
  ///
  /// In id, this message translates to:
  /// **'Pengaturan keluarga disimpan.'**
  String get familySettingsSaved;

  /// No description provided for @logoUpdated.
  ///
  /// In id, this message translates to:
  /// **'Logo diperbarui.'**
  String get logoUpdated;

  /// No description provided for @coverUpdated.
  ///
  /// In id, this message translates to:
  /// **'Sampul diperbarui.'**
  String get coverUpdated;

  /// No description provided for @familyPrivacyTitle.
  ///
  /// In id, this message translates to:
  /// **'Privasi: hanya anggota keluarga'**
  String get familyPrivacyTitle;

  /// No description provided for @familyPrivacyBody.
  ///
  /// In id, this message translates to:
  /// **'Privasi keluarga mengikuti keanggotaan dan tidak dapat diubah. Preferensi notifikasi dikelola per akun.'**
  String get familyPrivacyBody;

  /// No description provided for @originCity.
  ///
  /// In id, this message translates to:
  /// **'Kota asal'**
  String get originCity;

  /// No description provided for @description.
  ///
  /// In id, this message translates to:
  /// **'Deskripsi'**
  String get description;

  /// No description provided for @replaceLogo.
  ///
  /// In id, this message translates to:
  /// **'Ganti logo'**
  String get replaceLogo;

  /// No description provided for @replaceCover.
  ///
  /// In id, this message translates to:
  /// **'Ganti sampul'**
  String get replaceCover;

  /// No description provided for @addBranch.
  ///
  /// In id, this message translates to:
  /// **'Tambah cabang'**
  String get addBranch;

  /// No description provided for @editBranch.
  ///
  /// In id, this message translates to:
  /// **'Ubah cabang'**
  String get editBranch;

  /// No description provided for @deleteBranchTitle.
  ///
  /// In id, this message translates to:
  /// **'Hapus {name}?'**
  String deleteBranchTitle(String name);

  /// No description provided for @deleteBranchConfirmation.
  ///
  /// In id, this message translates to:
  /// **'Cabang akan dihapus. Anggota yang terkait tetap dipertahankan sesuai aturan server.'**
  String get deleteBranchConfirmation;

  /// No description provided for @noBranches.
  ///
  /// In id, this message translates to:
  /// **'Belum ada cabang.'**
  String get noBranches;

  /// No description provided for @editLabel.
  ///
  /// In id, this message translates to:
  /// **'Ubah'**
  String get editLabel;

  /// No description provided for @inviteMember.
  ///
  /// In id, this message translates to:
  /// **'Undang anggota'**
  String get inviteMember;

  /// No description provided for @roleMember.
  ///
  /// In id, this message translates to:
  /// **'Member'**
  String get roleMember;

  /// No description provided for @roleAdmin.
  ///
  /// In id, this message translates to:
  /// **'Admin'**
  String get roleAdmin;

  /// No description provided for @roleOwner.
  ///
  /// In id, this message translates to:
  /// **'Owner'**
  String get roleOwner;

  /// No description provided for @invite.
  ///
  /// In id, this message translates to:
  /// **'Undang'**
  String get invite;

  /// No description provided for @deleteAccessTitle.
  ///
  /// In id, this message translates to:
  /// **'Hapus akses {name}?'**
  String deleteAccessTitle(String name);

  /// No description provided for @deleteOwnerConfirmation.
  ///
  /// In id, this message translates to:
  /// **'Pemilik terakhir tidak dapat dihapus.'**
  String get deleteOwnerConfirmation;

  /// No description provided for @ownerOnlyAccess.
  ///
  /// In id, this message translates to:
  /// **'Hanya pemilik yang dapat mengelola akses keluarga.'**
  String get ownerOnlyAccess;

  /// No description provided for @deleteAccess.
  ///
  /// In id, this message translates to:
  /// **'Hapus akses'**
  String get deleteAccess;

  /// No description provided for @requestFailed.
  ///
  /// In id, this message translates to:
  /// **'Permintaan tidak berhasil. Silakan coba lagi.'**
  String get requestFailed;

  /// No description provided for @familyArticles.
  ///
  /// In id, this message translates to:
  /// **'Artikel keluarga'**
  String get familyArticles;

  /// No description provided for @writeArticle.
  ///
  /// In id, this message translates to:
  /// **'Tulis artikel'**
  String get writeArticle;

  /// No description provided for @searchArticles.
  ///
  /// In id, this message translates to:
  /// **'Cari artikel'**
  String get searchArticles;

  /// No description provided for @published.
  ///
  /// In id, this message translates to:
  /// **'Terbit'**
  String get published;

  /// No description provided for @draft.
  ///
  /// In id, this message translates to:
  /// **'Draf'**
  String get draft;

  /// No description provided for @featuredArticles.
  ///
  /// In id, this message translates to:
  /// **'Pilihan keluarga'**
  String get featuredArticles;

  /// No description provided for @noArticles.
  ///
  /// In id, this message translates to:
  /// **'Belum ada artikel yang dapat ditampilkan.'**
  String get noArticles;

  /// No description provided for @writeComment.
  ///
  /// In id, this message translates to:
  /// **'Tulis komentar'**
  String get writeComment;

  /// No description provided for @editComment.
  ///
  /// In id, this message translates to:
  /// **'Edit komentar'**
  String get editComment;

  /// No description provided for @articleDetail.
  ///
  /// In id, this message translates to:
  /// **'Detail artikel'**
  String get articleDetail;

  /// No description provided for @publish.
  ///
  /// In id, this message translates to:
  /// **'Terbitkan'**
  String get publish;

  /// No description provided for @featureArticle.
  ///
  /// In id, this message translates to:
  /// **'Jadikan pilihan'**
  String get featureArticle;

  /// No description provided for @unfeatureArticle.
  ///
  /// In id, this message translates to:
  /// **'Hapus pilihan'**
  String get unfeatureArticle;

  /// No description provided for @featuredImageLabel.
  ///
  /// In id, this message translates to:
  /// **'Gambar utama {title}'**
  String featuredImageLabel(String title);

  /// No description provided for @like.
  ///
  /// In id, this message translates to:
  /// **'Suka'**
  String get like;

  /// No description provided for @unlike.
  ///
  /// In id, this message translates to:
  /// **'Batal suka'**
  String get unlike;

  /// No description provided for @likesCount.
  ///
  /// In id, this message translates to:
  /// **'{count} suka'**
  String likesCount(int count);

  /// No description provided for @commentsLabel.
  ///
  /// In id, this message translates to:
  /// **'Komentar'**
  String get commentsLabel;

  /// No description provided for @editArticle.
  ///
  /// In id, this message translates to:
  /// **'Edit artikel'**
  String get editArticle;

  /// No description provided for @titleLabel.
  ///
  /// In id, this message translates to:
  /// **'Judul'**
  String get titleLabel;

  /// No description provided for @category.
  ///
  /// In id, this message translates to:
  /// **'Kategori'**
  String get category;

  /// No description provided for @excerpt.
  ///
  /// In id, this message translates to:
  /// **'Ringkasan'**
  String get excerpt;

  /// No description provided for @contentLabel.
  ///
  /// In id, this message translates to:
  /// **'Isi (teks dan paragraf aman)'**
  String get contentLabel;

  /// No description provided for @featuredImageSizeLimit.
  ///
  /// In id, this message translates to:
  /// **'Gambar utama maksimal 10 MB.'**
  String get featuredImageSizeLimit;

  /// No description provided for @chooseFeaturedImage.
  ///
  /// In id, this message translates to:
  /// **'Pilih gambar utama'**
  String get chooseFeaturedImage;

  /// No description provided for @saveDraft.
  ///
  /// In id, this message translates to:
  /// **'Simpan draf'**
  String get saveDraft;

  /// No description provided for @albumGallery.
  ///
  /// In id, this message translates to:
  /// **'Album & galeri'**
  String get albumGallery;

  /// No description provided for @createAlbum.
  ///
  /// In id, this message translates to:
  /// **'Buat album'**
  String get createAlbum;

  /// No description provided for @editAlbum.
  ///
  /// In id, this message translates to:
  /// **'Ubah album'**
  String get editAlbum;

  /// No description provided for @uploadPhoto.
  ///
  /// In id, this message translates to:
  /// **'Unggah foto'**
  String get uploadPhoto;

  /// No description provided for @album.
  ///
  /// In id, this message translates to:
  /// **'Album'**
  String get album;

  /// No description provided for @allPhotos.
  ///
  /// In id, this message translates to:
  /// **'Semua foto'**
  String get allPhotos;

  /// No description provided for @deleteAlbum.
  ///
  /// In id, this message translates to:
  /// **'Hapus album'**
  String get deleteAlbum;

  /// No description provided for @deleteAlbumTitle.
  ///
  /// In id, this message translates to:
  /// **'Hapus album {name}?'**
  String deleteAlbumTitle(String name);

  /// No description provided for @deleteAlbumConfirmation.
  ///
  /// In id, this message translates to:
  /// **'Album akan dihapus. Foto di dalamnya tetap disimpan tanpa album.'**
  String get deleteAlbumConfirmation;

  /// No description provided for @noPhotos.
  ///
  /// In id, this message translates to:
  /// **'Belum ada foto.'**
  String get noPhotos;

  /// No description provided for @familyPhoto.
  ///
  /// In id, this message translates to:
  /// **'Foto keluarga'**
  String get familyPhoto;

  /// No description provided for @photoValidation.
  ///
  /// In id, this message translates to:
  /// **'Foto harus JPG, PNG, atau WebP dan maksimal 10 MB.'**
  String get photoValidation;

  /// No description provided for @gallery.
  ///
  /// In id, this message translates to:
  /// **'Galeri'**
  String get gallery;

  /// No description provided for @camera.
  ///
  /// In id, this message translates to:
  /// **'Kamera'**
  String get camera;

  /// No description provided for @photoPreview.
  ///
  /// In id, this message translates to:
  /// **'Pratinjau foto'**
  String get photoPreview;

  /// No description provided for @caption.
  ///
  /// In id, this message translates to:
  /// **'Keterangan'**
  String get caption;

  /// No description provided for @noAlbum.
  ///
  /// In id, this message translates to:
  /// **'Tanpa album'**
  String get noAlbum;

  /// No description provided for @capturedDate.
  ///
  /// In id, this message translates to:
  /// **'Tanggal pengambilan'**
  String get capturedDate;

  /// No description provided for @notSpecified.
  ///
  /// In id, this message translates to:
  /// **'Tidak ditentukan'**
  String get notSpecified;

  /// No description provided for @upload.
  ///
  /// In id, this message translates to:
  /// **'Unggah'**
  String get upload;

  /// No description provided for @photoDetail.
  ///
  /// In id, this message translates to:
  /// **'Detail foto'**
  String get photoDetail;

  /// No description provided for @tagMembers.
  ///
  /// In id, this message translates to:
  /// **'Tag anggota'**
  String get tagMembers;

  /// No description provided for @deletePhoto.
  ///
  /// In id, this message translates to:
  /// **'Hapus foto'**
  String get deletePhoto;

  /// No description provided for @photoAlbumLabel.
  ///
  /// In id, this message translates to:
  /// **'Album: {name}'**
  String photoAlbumLabel(String name);

  /// No description provided for @takenAt.
  ///
  /// In id, this message translates to:
  /// **'Diambil: {date}'**
  String takenAt(String date);

  /// No description provided for @familyEvents.
  ///
  /// In id, this message translates to:
  /// **'Acara keluarga'**
  String get familyEvents;

  /// No description provided for @createEvent.
  ///
  /// In id, this message translates to:
  /// **'Buat acara'**
  String get createEvent;

  /// No description provided for @searchEvents.
  ///
  /// In id, this message translates to:
  /// **'Cari acara'**
  String get searchEvents;

  /// No description provided for @upcoming.
  ///
  /// In id, this message translates to:
  /// **'Mendatang'**
  String get upcoming;

  /// No description provided for @noEvents.
  ///
  /// In id, this message translates to:
  /// **'Belum ada acara.'**
  String get noEvents;

  /// No description provided for @deviceTime.
  ///
  /// In id, this message translates to:
  /// **'Waktu perangkat: {zone}'**
  String deviceTime(String zone);

  /// No description provided for @eventDetail.
  ///
  /// In id, this message translates to:
  /// **'Detail acara'**
  String get eventDetail;

  /// No description provided for @editEvent.
  ///
  /// In id, this message translates to:
  /// **'Edit acara'**
  String get editEvent;

  /// No description provided for @deleteEvent.
  ///
  /// In id, this message translates to:
  /// **'Hapus acara'**
  String get deleteEvent;

  /// No description provided for @confirmAttendance.
  ///
  /// In id, this message translates to:
  /// **'Konfirmasi kehadiran'**
  String get confirmAttendance;

  /// No description provided for @yes.
  ///
  /// In id, this message translates to:
  /// **'Ya'**
  String get yes;

  /// No description provided for @maybe.
  ///
  /// In id, this message translates to:
  /// **'Mungkin'**
  String get maybe;

  /// No description provided for @no.
  ///
  /// In id, this message translates to:
  /// **'Tidak'**
  String get no;

  /// No description provided for @attendeesCount.
  ///
  /// In id, this message translates to:
  /// **'Peserta ({count})'**
  String attendeesCount(int count);

  /// No description provided for @location.
  ///
  /// In id, this message translates to:
  /// **'Lokasi'**
  String get location;

  /// No description provided for @dateTimeLabel.
  ///
  /// In id, this message translates to:
  /// **'Tanggal & waktu'**
  String get dateTimeLabel;

  /// No description provided for @pickTime.
  ///
  /// In id, this message translates to:
  /// **'Pilih waktu'**
  String get pickTime;

  /// No description provided for @familyTimeline.
  ///
  /// In id, this message translates to:
  /// **'Linimasa keluarga'**
  String get familyTimeline;

  /// No description provided for @noActivity.
  ///
  /// In id, this message translates to:
  /// **'Belum ada aktivitas.'**
  String get noActivity;

  /// No description provided for @familySearch.
  ///
  /// In id, this message translates to:
  /// **'Pencarian keluarga'**
  String get familySearch;

  /// No description provided for @keyword.
  ///
  /// In id, this message translates to:
  /// **'Kata kunci'**
  String get keyword;

  /// No description provided for @advancedFilters.
  ///
  /// In id, this message translates to:
  /// **'Filter lanjutan'**
  String get advancedFilters;

  /// No description provided for @birthDeathCity.
  ///
  /// In id, this message translates to:
  /// **'Kota lahir/meninggal'**
  String get birthDeathCity;

  /// No description provided for @livingStatus.
  ///
  /// In id, this message translates to:
  /// **'Status hidup'**
  String get livingStatus;

  /// No description provided for @relativeGeneration.
  ///
  /// In id, this message translates to:
  /// **'Generasi relatif'**
  String get relativeGeneration;

  /// No description provided for @rootMemberUuid.
  ///
  /// In id, this message translates to:
  /// **'UUID anggota akar'**
  String get rootMemberUuid;

  /// No description provided for @generationFilterHelper.
  ///
  /// In id, this message translates to:
  /// **'Wajib bila filter generasi digunakan'**
  String get generationFilterHelper;

  /// No description provided for @searchAction.
  ///
  /// In id, this message translates to:
  /// **'Cari'**
  String get searchAction;

  /// No description provided for @noSearchResults.
  ///
  /// In id, this message translates to:
  /// **'Tidak ada hasil yang cocok.'**
  String get noSearchResults;

  /// No description provided for @membersGroup.
  ///
  /// In id, this message translates to:
  /// **'Anggota'**
  String get membersGroup;

  /// No description provided for @generationLabel.
  ///
  /// In id, this message translates to:
  /// **'Generasi {generation}'**
  String generationLabel(int generation);

  /// No description provided for @articlesGroup.
  ///
  /// In id, this message translates to:
  /// **'Artikel'**
  String get articlesGroup;

  /// No description provided for @eventsGroup.
  ///
  /// In id, this message translates to:
  /// **'Acara'**
  String get eventsGroup;

  /// No description provided for @loadMore.
  ///
  /// In id, this message translates to:
  /// **'Muat berikutnya'**
  String get loadMore;

  /// No description provided for @reportsTitle.
  ///
  /// In id, this message translates to:
  /// **'Laporan & insight'**
  String get reportsTitle;

  /// No description provided for @reportFromDate.
  ///
  /// In id, this message translates to:
  /// **'Dari {date}'**
  String reportFromDate(String date);

  /// No description provided for @reportToDate.
  ///
  /// In id, this message translates to:
  /// **'Sampai {date} {zone}'**
  String reportToDate(String date, String zone);

  /// No description provided for @reportsActiveUsers.
  ///
  /// In id, this message translates to:
  /// **'Pengguna aktif'**
  String get reportsActiveUsers;

  /// No description provided for @reportsUploads.
  ///
  /// In id, this message translates to:
  /// **'Foto'**
  String get reportsUploads;

  /// No description provided for @reportsArticles.
  ///
  /// In id, this message translates to:
  /// **'Artikel'**
  String get reportsArticles;

  /// No description provided for @generationDistribution.
  ///
  /// In id, this message translates to:
  /// **'Distribusi generasi'**
  String get generationDistribution;

  /// No description provided for @reportCities.
  ///
  /// In id, this message translates to:
  /// **'Kota'**
  String get reportCities;

  /// No description provided for @memberGrowth.
  ///
  /// In id, this message translates to:
  /// **'Pertumbuhan anggota'**
  String get memberGrowth;

  /// No description provided for @activityTrend.
  ///
  /// In id, this message translates to:
  /// **'Tren aktivitas'**
  String get activityTrend;

  /// No description provided for @contributionRanking.
  ///
  /// In id, this message translates to:
  /// **'Kontribusi & peringkat'**
  String get contributionRanking;

  /// No description provided for @contributionPointsLabel.
  ///
  /// In id, this message translates to:
  /// **'{points} poin kontribusi'**
  String contributionPointsLabel(int points);

  /// No description provided for @pointsLabel.
  ///
  /// In id, this message translates to:
  /// **'{points} poin'**
  String pointsLabel(int points);

  /// No description provided for @yourContribution.
  ///
  /// In id, this message translates to:
  /// **'Kontribusi Anda'**
  String get yourContribution;

  /// No description provided for @myBadges.
  ///
  /// In id, this message translates to:
  /// **'Badge saya'**
  String get myBadges;

  /// No description provided for @noBadges.
  ///
  /// In id, this message translates to:
  /// **'Belum ada badge.'**
  String get noBadges;

  /// No description provided for @familyUserRanking.
  ///
  /// In id, this message translates to:
  /// **'Peringkat pengguna keluarga'**
  String get familyUserRanking;

  /// No description provided for @familyRanking.
  ///
  /// In id, this message translates to:
  /// **'Peringkat keluarga'**
  String get familyRanking;

  /// No description provided for @groupSemantics.
  ///
  /// In id, this message translates to:
  /// **'{title}, {count} hasil'**
  String groupSemantics(String title, int count);

  /// No description provided for @dataSemantics.
  ///
  /// In id, this message translates to:
  /// **'Data {title}'**
  String dataSemantics(String title);

  /// No description provided for @reportRowSemantics.
  ///
  /// In id, this message translates to:
  /// **'{label}: {total}'**
  String reportRowSemantics(String label, int total);

  /// No description provided for @noRankings.
  ///
  /// In id, this message translates to:
  /// **'Belum ada peringkat.'**
  String get noRankings;

  /// No description provided for @notificationsTitle.
  ///
  /// In id, this message translates to:
  /// **'Notifikasi'**
  String get notificationsTitle;

  /// No description provided for @markAllRead.
  ///
  /// In id, this message translates to:
  /// **'Baca semua'**
  String get markAllRead;

  /// No description provided for @noNotifications.
  ///
  /// In id, this message translates to:
  /// **'Belum ada notifikasi.'**
  String get noNotifications;

  /// No description provided for @exportShareFailed.
  ///
  /// In id, this message translates to:
  /// **'Ekspor tidak dapat dibuka atau dibagikan.'**
  String get exportShareFailed;

  /// No description provided for @exportReady.
  ///
  /// In id, this message translates to:
  /// **'Ekspor {format} siap'**
  String exportReady(String format);

  /// No description provided for @previewUnavailable.
  ///
  /// In id, this message translates to:
  /// **'Pratinjau tidak tersedia.'**
  String get previewUnavailable;

  /// No description provided for @pdfExportReady.
  ///
  /// In id, this message translates to:
  /// **'PDF berhasil dibuat ({size} KB). Bagikan untuk membuka atau menyimpannya.'**
  String pdfExportReady(int size);

  /// No description provided for @close.
  ///
  /// In id, this message translates to:
  /// **'Tutup'**
  String get close;

  /// No description provided for @share.
  ///
  /// In id, this message translates to:
  /// **'Bagikan'**
  String get share;

  /// No description provided for @relationshipUnavailable.
  ///
  /// In id, this message translates to:
  /// **'Relationship tidak tersedia'**
  String get relationshipUnavailable;

  /// No description provided for @openMemberDetail.
  ///
  /// In id, this message translates to:
  /// **'Buka detail anggota'**
  String get openMemberDetail;

  /// No description provided for @addParent.
  ///
  /// In id, this message translates to:
  /// **'Tambah orang tua'**
  String get addParent;

  /// No description provided for @addSpouse.
  ///
  /// In id, this message translates to:
  /// **'Tambah pasangan'**
  String get addSpouse;

  /// No description provided for @addChild.
  ///
  /// In id, this message translates to:
  /// **'Tambah anak'**
  String get addChild;

  /// No description provided for @relativeLabelParent.
  ///
  /// In id, this message translates to:
  /// **'orang tua'**
  String get relativeLabelParent;

  /// No description provided for @relativeLabelSpouse.
  ///
  /// In id, this message translates to:
  /// **'pasangan'**
  String get relativeLabelSpouse;

  /// No description provided for @relativeLabelChild.
  ///
  /// In id, this message translates to:
  /// **'anak'**
  String get relativeLabelChild;

  /// No description provided for @addRelative.
  ///
  /// In id, this message translates to:
  /// **'Tambah {relation}'**
  String addRelative(String relation);

  /// No description provided for @forMember.
  ///
  /// In id, this message translates to:
  /// **'Untuk {name}'**
  String forMember(String name);

  /// No description provided for @nameRequired.
  ///
  /// In id, this message translates to:
  /// **'Nama wajib diisi.'**
  String get nameRequired;

  /// No description provided for @addAction.
  ///
  /// In id, this message translates to:
  /// **'Tambahkan'**
  String get addAction;

  /// No description provided for @relativeAdded.
  ///
  /// In id, this message translates to:
  /// **'{name} berhasil ditambahkan.'**
  String relativeAdded(String name);

  /// No description provided for @relativeAddFailed.
  ///
  /// In id, this message translates to:
  /// **'Anggota belum dapat ditambahkan.'**
  String get relativeAddFailed;

  /// No description provided for @preparingExport.
  ///
  /// In id, this message translates to:
  /// **'Menyiapkan ekspor…'**
  String get preparingExport;

  /// No description provided for @cancelExport.
  ///
  /// In id, this message translates to:
  /// **'Batalkan ekspor'**
  String get cancelExport;

  /// No description provided for @pickTreeRoot.
  ///
  /// In id, this message translates to:
  /// **'Pilih pusat pohon'**
  String get pickTreeRoot;

  /// No description provided for @chooseCenter.
  ///
  /// In id, this message translates to:
  /// **'Pilih pusat'**
  String get chooseCenter;

  /// No description provided for @exportTree.
  ///
  /// In id, this message translates to:
  /// **'Ekspor pohon'**
  String get exportTree;

  /// No description provided for @exportPng.
  ///
  /// In id, this message translates to:
  /// **'Ekspor PNG'**
  String get exportPng;

  /// No description provided for @exportPdf.
  ///
  /// In id, this message translates to:
  /// **'Ekspor PDF'**
  String get exportPdf;

  /// No description provided for @ancestor.
  ///
  /// In id, this message translates to:
  /// **'Leluhur'**
  String get ancestor;

  /// No description provided for @descendant.
  ///
  /// In id, this message translates to:
  /// **'Keturunan'**
  String get descendant;

  /// No description provided for @fullTree.
  ///
  /// In id, this message translates to:
  /// **'Lengkap'**
  String get fullTree;

  /// No description provided for @vertical.
  ///
  /// In id, this message translates to:
  /// **'Vertikal'**
  String get vertical;

  /// No description provided for @horizontal.
  ///
  /// In id, this message translates to:
  /// **'Horizontal'**
  String get horizontal;

  /// No description provided for @radial.
  ///
  /// In id, this message translates to:
  /// **'Radial'**
  String get radial;

  /// No description provided for @compact.
  ///
  /// In id, this message translates to:
  /// **'Ringkas'**
  String get compact;

  /// No description provided for @collapseDepth.
  ///
  /// In id, this message translates to:
  /// **'Ciutkan ({depth})'**
  String collapseDepth(int depth);

  /// No description provided for @expandDepth.
  ///
  /// In id, this message translates to:
  /// **'Perluas ({depth}/20)'**
  String expandDepth(int depth);

  /// No description provided for @livingOnly.
  ///
  /// In id, this message translates to:
  /// **'Hanya hidup'**
  String get livingOnly;

  /// No description provided for @semanticList.
  ///
  /// In id, this message translates to:
  /// **'Daftar aksesibel'**
  String get semanticList;

  /// No description provided for @searchFocusMember.
  ///
  /// In id, this message translates to:
  /// **'Cari/fokus anggota'**
  String get searchFocusMember;

  /// No description provided for @zoomOut.
  ///
  /// In id, this message translates to:
  /// **'Perkecil'**
  String get zoomOut;

  /// No description provided for @zoomIn.
  ///
  /// In id, this message translates to:
  /// **'Perbesar'**
  String get zoomIn;

  /// No description provided for @centerTree.
  ///
  /// In id, this message translates to:
  /// **'Pusatkan'**
  String get centerTree;

  /// No description provided for @pickCenterMember.
  ///
  /// In id, this message translates to:
  /// **'Pilih anggota pusat'**
  String get pickCenterMember;

  /// No description provided for @loadingTree.
  ///
  /// In id, this message translates to:
  /// **'Memuat pohon keluarga'**
  String get loadingTree;

  /// No description provided for @treeEmpty.
  ///
  /// In id, this message translates to:
  /// **'Pohon keluarga masih kosong.'**
  String get treeEmpty;

  /// No description provided for @treeUnknownRelationship.
  ///
  /// In id, this message translates to:
  /// **'Tidak diketahui'**
  String get treeUnknownRelationship;

  /// No description provided for @treeRelationshipUnknown.
  ///
  /// In id, this message translates to:
  /// **'relationship tidak diketahui'**
  String get treeRelationshipUnknown;

  /// No description provided for @treeAliveLower.
  ///
  /// In id, this message translates to:
  /// **'hidup'**
  String get treeAliveLower;

  /// No description provided for @treeDeceasedLower.
  ///
  /// In id, this message translates to:
  /// **'meninggal'**
  String get treeDeceasedLower;

  /// No description provided for @treeNodeSemantics.
  ///
  /// In id, this message translates to:
  /// **'{name}, {relation}, {status}'**
  String treeNodeSemantics(String name, String relation, String status);

  /// No description provided for @showingNodes.
  ///
  /// In id, this message translates to:
  /// **'Menampilkan {shown} dari {total} node'**
  String showingNodes(int shown, int total);

  /// No description provided for @accountTitle.
  ///
  /// In id, this message translates to:
  /// **'Akun'**
  String get accountTitle;

  /// No description provided for @profile.
  ///
  /// In id, this message translates to:
  /// **'Profil'**
  String get profile;

  /// No description provided for @notificationPreferences.
  ///
  /// In id, this message translates to:
  /// **'Preferensi notifikasi'**
  String get notificationPreferences;

  /// No description provided for @securitySessions.
  ///
  /// In id, this message translates to:
  /// **'Keamanan dan sesi'**
  String get securitySessions;

  /// No description provided for @switchFamily.
  ///
  /// In id, this message translates to:
  /// **'Ganti keluarga'**
  String get switchFamily;

  /// No description provided for @avatarSizeLimit.
  ///
  /// In id, this message translates to:
  /// **'Ukuran avatar maksimal 5 MB.'**
  String get avatarSizeLimit;

  /// No description provided for @profileUpdated.
  ///
  /// In id, this message translates to:
  /// **'Profil diperbarui.'**
  String get profileUpdated;

  /// No description provided for @chooseAvatar.
  ///
  /// In id, this message translates to:
  /// **'Pilih avatar'**
  String get chooseAvatar;

  /// No description provided for @phone.
  ///
  /// In id, this message translates to:
  /// **'Telepon'**
  String get phone;

  /// No description provided for @currentPasswordLabel.
  ///
  /// In id, this message translates to:
  /// **'Kata sandi saat ini (jika email berubah)'**
  String get currentPasswordLabel;

  /// No description provided for @push.
  ///
  /// In id, this message translates to:
  /// **'Push'**
  String get push;

  /// No description provided for @eventReminders.
  ///
  /// In id, this message translates to:
  /// **'Pengingat acara'**
  String get eventReminders;

  /// No description provided for @familyUpdates.
  ///
  /// In id, this message translates to:
  /// **'Pembaruan keluarga'**
  String get familyUpdates;

  /// No description provided for @security.
  ///
  /// In id, this message translates to:
  /// **'Keamanan'**
  String get security;

  /// No description provided for @currentPassword.
  ///
  /// In id, this message translates to:
  /// **'Kata sandi saat ini'**
  String get currentPassword;

  /// No description provided for @changePassword.
  ///
  /// In id, this message translates to:
  /// **'Ubah kata sandi'**
  String get changePassword;

  /// No description provided for @deviceSessions.
  ///
  /// In id, this message translates to:
  /// **'Sesi perangkat'**
  String get deviceSessions;

  /// No description provided for @reloadSessions.
  ///
  /// In id, this message translates to:
  /// **'Muat ulang sesi'**
  String get reloadSessions;

  /// No description provided for @thisDevice.
  ///
  /// In id, this message translates to:
  /// **'Perangkat ini'**
  String get thisDevice;

  /// No description provided for @lastActive.
  ///
  /// In id, this message translates to:
  /// **'Terakhir aktif: {time}'**
  String lastActive(String time);

  /// No description provided for @revokeSession.
  ///
  /// In id, this message translates to:
  /// **'Cabut sesi'**
  String get revokeSession;

  /// No description provided for @diagnosticsTitle.
  ///
  /// In id, this message translates to:
  /// **'Diagnostics'**
  String get diagnosticsTitle;

  /// No description provided for @diagnosticsEnvironment.
  ///
  /// In id, this message translates to:
  /// **'Environment'**
  String get diagnosticsEnvironment;

  /// No description provided for @diagnosticsApiHost.
  ///
  /// In id, this message translates to:
  /// **'API host'**
  String get diagnosticsApiHost;

  /// No description provided for @diagnosticsConnectivity.
  ///
  /// In id, this message translates to:
  /// **'Connectivity'**
  String get diagnosticsConnectivity;

  /// No description provided for @checking.
  ///
  /// In id, this message translates to:
  /// **'Memeriksa…'**
  String get checking;

  /// No description provided for @diagnosticsPrivacy.
  ///
  /// In id, this message translates to:
  /// **'Token, credential, payload, dan data pribadi tidak ditampilkan.'**
  String get diagnosticsPrivacy;

  /// No description provided for @navDashboard.
  ///
  /// In id, this message translates to:
  /// **'Dashboard'**
  String get navDashboard;

  /// No description provided for @navActivity.
  ///
  /// In id, this message translates to:
  /// **'Aktivitas'**
  String get navActivity;

  /// No description provided for @navMore.
  ///
  /// In id, this message translates to:
  /// **'Lainnya'**
  String get navMore;

  /// No description provided for @startingApp.
  ///
  /// In id, this message translates to:
  /// **'Memulai aplikasi'**
  String get startingApp;

  /// No description provided for @familySettings.
  ///
  /// In id, this message translates to:
  /// **'Pengaturan keluarga'**
  String get familySettings;

  /// No description provided for @photosAlbums.
  ///
  /// In id, this message translates to:
  /// **'Foto & album'**
  String get photosAlbums;

  /// No description provided for @searchMenu.
  ///
  /// In id, this message translates to:
  /// **'Pencarian'**
  String get searchMenu;

  /// No description provided for @reportsMenu.
  ///
  /// In id, this message translates to:
  /// **'Laporan'**
  String get reportsMenu;
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
