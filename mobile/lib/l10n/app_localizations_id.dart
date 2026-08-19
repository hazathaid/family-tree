// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Indonesian (`id`).
class AppLocalizationsId extends AppLocalizations {
  AppLocalizationsId([String locale = 'id']) : super(locale);

  @override
  String get appTitle => 'Family Tree Indonesia';

  @override
  String get loading => 'Memuat…';

  @override
  String get retry => 'Coba lagi';

  @override
  String get cancel => 'Batal';

  @override
  String get continueAction => 'Lanjutkan';

  @override
  String get loadingData => 'Memuat';

  @override
  String get loadFailed => 'Tidak dapat memuat data';

  @override
  String get genericError => 'Terjadi kendala. Silakan coba lagi.';

  @override
  String showingStoredData(String time) {
    return 'Menampilkan data tersimpan dari $time.';
  }

  @override
  String statusLabel(String label) {
    return 'Status: $label';
  }

  @override
  String get email => 'Email';

  @override
  String get password => 'Kata sandi';

  @override
  String get showPassword => 'Tampilkan kata sandi';

  @override
  String get hidePassword => 'Sembunyikan kata sandi';

  @override
  String get loginTitle => 'Masuk';

  @override
  String get loginButton => 'Masuk';

  @override
  String get forgotPassword => 'Lupa kata sandi?';

  @override
  String get createAccount => 'Buat akun baru';

  @override
  String get registerTitle => 'Daftar';

  @override
  String get registerButton => 'Daftar';

  @override
  String get nameLabel => 'Nama';

  @override
  String get phoneOptional => 'Nomor telepon (opsional)';

  @override
  String get haveAccount => 'Sudah punya akun? Masuk';

  @override
  String get accountCreated =>
      'Akun dibuat. Silakan masuk dan verifikasi email.';

  @override
  String get forgotPasswordTitle => 'Lupa kata sandi';

  @override
  String get sendResetLink => 'Kirim tautan reset';

  @override
  String get sending => 'Mengirim…';

  @override
  String get backToLogin => 'Kembali ke masuk';

  @override
  String get resetEmailSent =>
      'Jika email terdaftar, tautan reset telah dikirim.';

  @override
  String get resetPasswordTitle => 'Reset kata sandi';

  @override
  String get newPassword => 'Kata sandi baru';

  @override
  String get savePassword => 'Simpan kata sandi';

  @override
  String get passwordChanged => 'Kata sandi berhasil diubah.';

  @override
  String get verifyEmailTitle => 'Verifikasi email';

  @override
  String get verifyEmailPrompt =>
      'Periksa email Anda lalu buka tautan verifikasi.';

  @override
  String get resendEmail => 'Kirim ulang email';

  @override
  String resendIn(int seconds) {
    return 'Kirim ulang dalam ${seconds}d';
  }

  @override
  String get verificationSent => 'Tautan verifikasi dikirim.';

  @override
  String get logout => 'Keluar';
}
