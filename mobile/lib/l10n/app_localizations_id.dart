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
  String pageOf(int current, int total) {
    return 'Halaman $current dari $total';
  }

  @override
  String get memberDetailLoadFailed => 'Detail anggota tidak dapat dimuat.';

  @override
  String get editMember => 'Edit anggota';

  @override
  String relationshipToYou(String relationship) {
    return '$relationship untuk Anda';
  }

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
  String deleteMemberTitle(String name) {
    return 'Hapus $name?';
  }

  @override
  String get deleteMemberConfirmation =>
      'Anggota akan dihapus secara lunak dan tidak tampil lagi.';

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
  String pageFraction(int current, int total) {
    return '$current / $total';
  }

  @override
  String get noValue => '-';

  @override
  String get noBranchShort => 'Tanpa cabang';

  @override
  String memberSemantics(String name, String status) {
    return '$name, $status';
  }

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

  @override
  String dashboardWelcome(String family) {
    return 'Selamat datang di $family';
  }

  @override
  String get dashboardYourFamily => 'keluarga Anda';

  @override
  String get dashboardLoadFailed => 'Dashboard tidak dapat dimuat.';

  @override
  String get dashboardTotalMembers => 'Total anggota';

  @override
  String get dashboardLivingMembers => 'Anggota hidup';

  @override
  String get dashboardArticles => 'Artikel';

  @override
  String get dashboardPhotos => 'Foto';

  @override
  String get dashboardEvents => 'Acara';

  @override
  String get dashboardRecentActivity => 'Aktivitas terbaru';

  @override
  String get dashboardUpcomingBirthdays => 'Ulang tahun mendatang';

  @override
  String get dashboardUpcomingEvents => 'Acara mendatang';

  @override
  String dashboardNotifications(int count) {
    return 'Notifikasi ($count belum dibaca)';
  }

  @override
  String get dashboardFamilyFacts => 'Fakta keluarga';

  @override
  String dashboardOriginCity(String city) {
    return 'Kota asal: $city';
  }

  @override
  String dashboardOldestMember(String name) {
    return 'Anggota tertua: $name';
  }

  @override
  String dashboardYoungestMember(String name) {
    return 'Anggota termuda: $name';
  }

  @override
  String get dashboardRecentMembers => 'Anggota terbaru';

  @override
  String get dashboardSeeAll => 'Lihat semua';

  @override
  String get noData => 'Belum ada data.';

  @override
  String get familyOnboardingTitle => 'Buat keluarga';

  @override
  String get familyOnboardingHeadline => 'Mulai dokumentasikan keluarga';

  @override
  String get familyOnboardingBody =>
      'Anda akan menjadi pemilik keluarga dan dapat mengundang anggota nanti.';

  @override
  String get familyName => 'Nama keluarga';

  @override
  String get originCityOptional => 'Kota asal (opsional)';

  @override
  String get descriptionOptional => 'Deskripsi (opsional)';

  @override
  String get creating => 'Membuat…';

  @override
  String get selectFamilyTitle => 'Pilih keluarga';

  @override
  String get familiesLoadFailed => 'Keluarga tidak dapat dimuat.';

  @override
  String get createFirstFamily => 'Buat keluarga pertama';

  @override
  String get selectFamilyFirst => 'Pilih keluarga terlebih dahulu.';

  @override
  String get manageFamilyTitle => 'Kelola keluarga';

  @override
  String get tabProfile => 'Profil';

  @override
  String get tabBranches => 'Cabang';

  @override
  String get tabAccess => 'Akses';

  @override
  String get familySettingsSaved => 'Pengaturan keluarga disimpan.';

  @override
  String get logoUpdated => 'Logo diperbarui.';

  @override
  String get coverUpdated => 'Sampul diperbarui.';

  @override
  String get familyPrivacyTitle => 'Privasi: hanya anggota keluarga';

  @override
  String get familyPrivacyBody =>
      'Privasi keluarga mengikuti keanggotaan dan tidak dapat diubah. Preferensi notifikasi dikelola per akun.';

  @override
  String get originCity => 'Kota asal';

  @override
  String get description => 'Deskripsi';

  @override
  String get replaceLogo => 'Ganti logo';

  @override
  String get replaceCover => 'Ganti sampul';

  @override
  String get addBranch => 'Tambah cabang';

  @override
  String get editBranch => 'Ubah cabang';

  @override
  String deleteBranchTitle(String name) {
    return 'Hapus $name?';
  }

  @override
  String get deleteBranchConfirmation =>
      'Cabang akan dihapus. Anggota yang terkait tetap dipertahankan sesuai aturan server.';

  @override
  String get noBranches => 'Belum ada cabang.';

  @override
  String get editLabel => 'Ubah';

  @override
  String get inviteMember => 'Undang anggota';

  @override
  String get roleMember => 'Member';

  @override
  String get roleAdmin => 'Admin';

  @override
  String get roleOwner => 'Owner';

  @override
  String get invite => 'Undang';

  @override
  String deleteAccessTitle(String name) {
    return 'Hapus akses $name?';
  }

  @override
  String get deleteOwnerConfirmation => 'Pemilik terakhir tidak dapat dihapus.';

  @override
  String get ownerOnlyAccess =>
      'Hanya pemilik yang dapat mengelola akses keluarga.';

  @override
  String get deleteAccess => 'Hapus akses';

  @override
  String get requestFailed => 'Permintaan tidak berhasil. Silakan coba lagi.';

  @override
  String get familyArticles => 'Artikel keluarga';

  @override
  String get writeArticle => 'Tulis artikel';

  @override
  String get searchArticles => 'Cari artikel';

  @override
  String get published => 'Terbit';

  @override
  String get draft => 'Draf';

  @override
  String get featuredArticles => 'Pilihan keluarga';

  @override
  String get noArticles => 'Belum ada artikel yang dapat ditampilkan.';

  @override
  String get writeComment => 'Tulis komentar';

  @override
  String get editComment => 'Edit komentar';

  @override
  String get articleDetail => 'Detail artikel';

  @override
  String get publish => 'Terbitkan';

  @override
  String get featureArticle => 'Jadikan pilihan';

  @override
  String get unfeatureArticle => 'Hapus pilihan';

  @override
  String featuredImageLabel(String title) {
    return 'Gambar utama $title';
  }

  @override
  String get like => 'Suka';

  @override
  String get unlike => 'Batal suka';

  @override
  String likesCount(int count) {
    return '$count suka';
  }

  @override
  String get commentsLabel => 'Komentar';

  @override
  String get editArticle => 'Edit artikel';

  @override
  String get titleLabel => 'Judul';

  @override
  String get category => 'Kategori';

  @override
  String get excerpt => 'Ringkasan';

  @override
  String get contentLabel => 'Isi (teks dan paragraf aman)';

  @override
  String get featuredImageSizeLimit => 'Gambar utama maksimal 10 MB.';

  @override
  String get chooseFeaturedImage => 'Pilih gambar utama';

  @override
  String get saveDraft => 'Simpan draf';

  @override
  String get albumGallery => 'Album & galeri';

  @override
  String get createAlbum => 'Buat album';

  @override
  String get editAlbum => 'Ubah album';

  @override
  String get uploadPhoto => 'Unggah foto';

  @override
  String get album => 'Album';

  @override
  String get allPhotos => 'Semua foto';

  @override
  String get deleteAlbum => 'Hapus album';

  @override
  String deleteAlbumTitle(String name) {
    return 'Hapus album $name?';
  }

  @override
  String get deleteAlbumConfirmation =>
      'Album akan dihapus. Foto di dalamnya tetap disimpan tanpa album.';

  @override
  String get noPhotos => 'Belum ada foto.';

  @override
  String get familyPhoto => 'Foto keluarga';

  @override
  String get photoValidation =>
      'Foto harus JPG, PNG, atau WebP dan maksimal 10 MB.';

  @override
  String get gallery => 'Galeri';

  @override
  String get camera => 'Kamera';

  @override
  String get photoPreview => 'Pratinjau foto';

  @override
  String get caption => 'Keterangan';

  @override
  String get noAlbum => 'Tanpa album';

  @override
  String get capturedDate => 'Tanggal pengambilan';

  @override
  String get notSpecified => 'Tidak ditentukan';

  @override
  String get upload => 'Unggah';

  @override
  String get photoDetail => 'Detail foto';

  @override
  String get tagMembers => 'Tag anggota';

  @override
  String get deletePhoto => 'Hapus foto';

  @override
  String photoAlbumLabel(String name) {
    return 'Album: $name';
  }

  @override
  String takenAt(String date) {
    return 'Diambil: $date';
  }

  @override
  String get familyEvents => 'Acara keluarga';

  @override
  String get createEvent => 'Buat acara';

  @override
  String get searchEvents => 'Cari acara';

  @override
  String get upcoming => 'Mendatang';

  @override
  String get noEvents => 'Belum ada acara.';

  @override
  String deviceTime(String zone) {
    return 'Waktu perangkat: $zone';
  }

  @override
  String get eventDetail => 'Detail acara';

  @override
  String get editEvent => 'Edit acara';

  @override
  String get deleteEvent => 'Hapus acara';

  @override
  String get confirmAttendance => 'Konfirmasi kehadiran';

  @override
  String get yes => 'Ya';

  @override
  String get maybe => 'Mungkin';

  @override
  String get no => 'Tidak';

  @override
  String attendeesCount(int count) {
    return 'Peserta ($count)';
  }

  @override
  String get location => 'Lokasi';

  @override
  String get dateTimeLabel => 'Tanggal & waktu';

  @override
  String get pickTime => 'Pilih waktu';

  @override
  String get familyTimeline => 'Linimasa keluarga';

  @override
  String get noActivity => 'Belum ada aktivitas.';

  @override
  String get familySearch => 'Pencarian keluarga';

  @override
  String get keyword => 'Kata kunci';

  @override
  String get advancedFilters => 'Filter lanjutan';

  @override
  String get birthDeathCity => 'Kota lahir/meninggal';

  @override
  String get livingStatus => 'Status hidup';

  @override
  String get relativeGeneration => 'Generasi relatif';

  @override
  String get rootMemberUuid => 'UUID anggota akar';

  @override
  String get generationFilterHelper => 'Wajib bila filter generasi digunakan';

  @override
  String get searchAction => 'Cari';

  @override
  String get noSearchResults => 'Tidak ada hasil yang cocok.';

  @override
  String get membersGroup => 'Anggota';

  @override
  String generationLabel(int generation) {
    return 'Generasi $generation';
  }

  @override
  String get articlesGroup => 'Artikel';

  @override
  String get eventsGroup => 'Acara';

  @override
  String get loadMore => 'Muat berikutnya';

  @override
  String get reportsTitle => 'Laporan & insight';

  @override
  String reportFromDate(String date) {
    return 'Dari $date';
  }

  @override
  String reportToDate(String date, String zone) {
    return 'Sampai $date $zone';
  }

  @override
  String get reportsActiveUsers => 'Pengguna aktif';

  @override
  String get reportsUploads => 'Foto';

  @override
  String get reportsArticles => 'Artikel';

  @override
  String get generationDistribution => 'Distribusi generasi';

  @override
  String get reportCities => 'Kota';

  @override
  String get memberGrowth => 'Pertumbuhan anggota';

  @override
  String get activityTrend => 'Tren aktivitas';

  @override
  String get contributionRanking => 'Kontribusi & peringkat';

  @override
  String contributionPointsLabel(int points) {
    return '$points poin kontribusi';
  }

  @override
  String pointsLabel(int points) {
    return '$points poin';
  }

  @override
  String get yourContribution => 'Kontribusi Anda';

  @override
  String get myBadges => 'Badge saya';

  @override
  String get noBadges => 'Belum ada badge.';

  @override
  String get familyUserRanking => 'Peringkat pengguna keluarga';

  @override
  String get familyRanking => 'Peringkat keluarga';

  @override
  String groupSemantics(String title, int count) {
    return '$title, $count hasil';
  }

  @override
  String dataSemantics(String title) {
    return 'Data $title';
  }

  @override
  String reportRowSemantics(String label, int total) {
    return '$label: $total';
  }

  @override
  String get noRankings => 'Belum ada peringkat.';

  @override
  String get notificationsTitle => 'Notifikasi';

  @override
  String get markAllRead => 'Baca semua';

  @override
  String get noNotifications => 'Belum ada notifikasi.';

  @override
  String get exportShareFailed => 'Ekspor tidak dapat dibuka atau dibagikan.';

  @override
  String exportReady(String format) {
    return 'Ekspor $format siap';
  }

  @override
  String get previewUnavailable => 'Pratinjau tidak tersedia.';

  @override
  String pdfExportReady(int size) {
    return 'PDF berhasil dibuat ($size KB). Bagikan untuk membuka atau menyimpannya.';
  }

  @override
  String get close => 'Tutup';

  @override
  String get share => 'Bagikan';

  @override
  String get relationshipUnavailable => 'Relationship tidak tersedia';

  @override
  String get openMemberDetail => 'Buka detail anggota';

  @override
  String get addParent => 'Tambah orang tua';

  @override
  String get addSpouse => 'Tambah pasangan';

  @override
  String get addChild => 'Tambah anak';

  @override
  String get relativeLabelParent => 'orang tua';

  @override
  String get relativeLabelSpouse => 'pasangan';

  @override
  String get relativeLabelChild => 'anak';

  @override
  String addRelative(String relation) {
    return 'Tambah $relation';
  }

  @override
  String forMember(String name) {
    return 'Untuk $name';
  }

  @override
  String get nameRequired => 'Nama wajib diisi.';

  @override
  String get addAction => 'Tambahkan';

  @override
  String relativeAdded(String name) {
    return '$name berhasil ditambahkan.';
  }

  @override
  String get relativeAddFailed => 'Anggota belum dapat ditambahkan.';

  @override
  String get preparingExport => 'Menyiapkan ekspor…';

  @override
  String get cancelExport => 'Batalkan ekspor';

  @override
  String get pickTreeRoot => 'Pilih pusat pohon';

  @override
  String get chooseCenter => 'Pilih pusat';

  @override
  String get exportTree => 'Ekspor pohon';

  @override
  String get exportPng => 'Ekspor PNG';

  @override
  String get exportPdf => 'Ekspor PDF';

  @override
  String get ancestor => 'Leluhur';

  @override
  String get descendant => 'Keturunan';

  @override
  String get fullTree => 'Lengkap';

  @override
  String get vertical => 'Vertikal';

  @override
  String get horizontal => 'Horizontal';

  @override
  String get radial => 'Radial';

  @override
  String get compact => 'Ringkas';

  @override
  String collapseDepth(int depth) {
    return 'Ciutkan ($depth)';
  }

  @override
  String expandDepth(int depth) {
    return 'Perluas ($depth/20)';
  }

  @override
  String get livingOnly => 'Hanya hidup';

  @override
  String get semanticList => 'Daftar aksesibel';

  @override
  String get searchFocusMember => 'Cari/fokus anggota';

  @override
  String get zoomOut => 'Perkecil';

  @override
  String get zoomIn => 'Perbesar';

  @override
  String get centerTree => 'Pusatkan';

  @override
  String get pickCenterMember => 'Pilih anggota pusat';

  @override
  String get loadingTree => 'Memuat pohon keluarga';

  @override
  String get treeEmpty => 'Pohon keluarga masih kosong.';

  @override
  String get treeUnknownRelationship => 'Tidak diketahui';

  @override
  String get treeRelationshipUnknown => 'relationship tidak diketahui';

  @override
  String get treeAliveLower => 'hidup';

  @override
  String get treeDeceasedLower => 'meninggal';

  @override
  String treeNodeSemantics(String name, String relation, String status) {
    return '$name, $relation, $status';
  }

  @override
  String showingNodes(int shown, int total) {
    return 'Menampilkan $shown dari $total node';
  }

  @override
  String get accountTitle => 'Akun';

  @override
  String get profile => 'Profil';

  @override
  String get notificationPreferences => 'Preferensi notifikasi';

  @override
  String get securitySessions => 'Keamanan dan sesi';

  @override
  String get switchFamily => 'Ganti keluarga';

  @override
  String get avatarSizeLimit => 'Ukuran avatar maksimal 5 MB.';

  @override
  String get profileUpdated => 'Profil diperbarui.';

  @override
  String get chooseAvatar => 'Pilih avatar';

  @override
  String get phone => 'Telepon';

  @override
  String get currentPasswordLabel => 'Kata sandi saat ini (jika email berubah)';

  @override
  String get push => 'Push';

  @override
  String get eventReminders => 'Pengingat acara';

  @override
  String get familyUpdates => 'Pembaruan keluarga';

  @override
  String get security => 'Keamanan';

  @override
  String get currentPassword => 'Kata sandi saat ini';

  @override
  String get changePassword => 'Ubah kata sandi';

  @override
  String get deviceSessions => 'Sesi perangkat';

  @override
  String get reloadSessions => 'Muat ulang sesi';

  @override
  String get thisDevice => 'Perangkat ini';

  @override
  String lastActive(String time) {
    return 'Terakhir aktif: $time';
  }

  @override
  String get revokeSession => 'Cabut sesi';

  @override
  String get diagnosticsTitle => 'Diagnostics';

  @override
  String get diagnosticsEnvironment => 'Environment';

  @override
  String get diagnosticsApiHost => 'API host';

  @override
  String get diagnosticsConnectivity => 'Connectivity';

  @override
  String get checking => 'Memeriksa…';

  @override
  String get diagnosticsPrivacy =>
      'Token, credential, payload, dan data pribadi tidak ditampilkan.';

  @override
  String get navDashboard => 'Dashboard';

  @override
  String get navActivity => 'Aktivitas';

  @override
  String get navMore => 'Lainnya';

  @override
  String get startingApp => 'Memulai aplikasi';

  @override
  String get familySettings => 'Pengaturan keluarga';

  @override
  String get photosAlbums => 'Foto & album';

  @override
  String get searchMenu => 'Pencarian';

  @override
  String get reportsMenu => 'Laporan';
}
