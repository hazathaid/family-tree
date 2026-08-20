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

  @override
  String get membersTitle => 'Anggota keluarga';
  @override
  String get memberFilters => 'Filter anggota';
  @override
  String get gender => 'Gender';
  @override
  String get status => 'Status';
  @override
  String get branch => 'Cabang';
  @override
  String get sort => 'Urutkan';
  @override
  String get all => 'Semua';
  @override
  String get male => 'Laki-laki';
  @override
  String get female => 'Perempuan';
  @override
  String get unspecified => 'Tidak ditentukan';
  @override
  String get alive => 'Hidup';
  @override
  String get deceased => 'Meninggal';
  @override
  String get sortNameAscending => 'Nama A-Z';
  @override
  String get sortNameDescending => 'Nama Z-A';
  @override
  String get sortNewest => 'Terbaru';
  @override
  String get sortOldest => 'Terlama';
  @override
  String get apply => 'Terapkan';
  @override
  String get manageRelationships => 'Kelola relationship';
  @override
  String get relationshipResolver => 'Resolver relationship';
  @override
  String get addMember => 'Tambah anggota';
  @override
  String get searchMemberNameOrNickname => 'Cari nama atau panggilan';
  @override
  String get filterAndSort => 'Filter dan urutkan';
  @override
  String get noMatchingMembers => 'Belum ada anggota yang sesuai.';
  @override
  String get memberName => 'Nama';
  @override
  String get previousPage => 'Halaman sebelumnya';
  @override
  String get nextPage => 'Halaman berikutnya';
  @override
  String pageOf(int current, int total) => 'Halaman $current dari $total';
  @override
  String get memberDetailLoadFailed => 'Detail anggota tidak dapat dimuat.';
  @override
  String get editMember => 'Edit anggota';
  @override
  String relationshipToYou(String relationship) => '$relationship untuk Anda';
  @override
  String get inMemory => 'Dalam kenangan';
  @override
  String get basicInformation => 'Informasi dasar';
  @override
  String get fullName => 'Nama lengkap';
  @override
  String get nickname => 'Nama panggilan';
  @override
  String get religionBelief => 'Agama/kepercayaan';
  @override
  String get born => 'Lahir';
  @override
  String get died => 'Wafat';
  @override
  String get noBranch => 'Tidak ada cabang';
  @override
  String get biography => 'Biografi';
  @override
  String get noBiography => 'Belum ada biografi.';
  @override
  String get basicRelationships => 'Relationship dasar';
  @override
  String get noBasicRelationships => 'Belum ada relationship dasar.';
  @override
  String get relatedContent => 'Konten terkait';
  @override
  String get relatedPhotos => 'Foto terkait';
  @override
  String get relatedArticles => 'Artikel terkait';
  @override
  String get noRelatedContent => 'Belum ada konten terkait untuk ditampilkan.';
  @override
  String get memberSaved => 'Anggota berhasil disimpan.';
  @override
  String get photoUpdated => 'Foto berhasil diperbarui.';
  @override
  String deleteMemberTitle(String name) => 'Hapus $name?';
  @override
  String get deleteMemberConfirmation => 'Anggota akan dihapus secara lunak dan tidak tampil lagi.';
  @override
  String get delete => 'Hapus';
  @override
  String get birthDate => 'Tanggal lahir (YYYY-MM-DD)';
  @override
  String get birthPlace => 'Tempat lahir';
  @override
  String get stillAlive => 'Masih hidup';
  @override
  String get deathDate => 'Tanggal wafat (YYYY-MM-DD)';
  @override
  String get deathPlace => 'Tempat wafat';
  @override
  String get saving => 'Menyimpan...';
  @override
  String get save => 'Simpan';
  @override
  String get replacePhoto => 'Ganti foto';
  @override
  String get deleteMember => 'Hapus anggota';
  @override
  String get addRelationship => 'Tambah relationship';
  @override
  String get editRelationship => 'Edit relationship';
  @override
  String get relationshipType => 'Tipe dasar';
  @override
  String get sourceMember => 'Anggota sumber';
  @override
  String get targetMember => 'Anggota tujuan';
  @override
  String get source => 'Sumber';
  @override
  String get target => 'Tujuan';
  @override
  String get selectMember => 'Pilih anggota';
  @override
  String get findRelationship => 'Temukan relationship';
  @override
  String get calculating => 'Menghitung...';
  @override
  String get notConnected => 'Tidak terhubung';
  @override
  String get sameMember => 'Anggota yang sama.';
  @override
  String get relationshipPathNotFound => 'Tidak ditemukan jalur relationship.';
  @override
  String get selectMemberTitle => 'Pilih anggota';
  @override
  String get searchMembers => 'Cari anggota';
  @override
  String pageFraction(int current, int total) => '$current / $total';
  @override
  String get noValue => '-';
  @override
  String get noBranchShort => 'Tanpa cabang';
  @override
  String memberSemantics(String name, String status) => '$name, $status';
  @override
  String get islam => 'Islam';
  @override
  String get christian => 'Kristen';
  @override
  String get catholic => 'Katolik';
  @override
  String get hindu => 'Hindu';
  @override
  String get buddhist => 'Buddha';
  @override
  String get confucian => 'Konghucu';
  @override
  String get belief => 'Penghayat kepercayaan';
  @override
  String get other => 'Lainnya';
  @override
  String get father => 'Ayah';
  @override
  String get mother => 'Ibu';
  @override
  String get child => 'Anak';
  @override
  String get husband => 'Suami';
  @override
  String get wife => 'Istri';
  @override
  String get family => 'Keluarga';
  @override
  String get fullNameRequired => 'Nama lengkap wajib diisi.';
}
