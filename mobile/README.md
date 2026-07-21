# Family Tree Mobile

Flutter client untuk Phase 13. Business logic tetap berada di Laravel dan aplikasi ini hanya menangani tampilan, input, penyimpanan sesi, serta notifikasi.

## Menyiapkan project platform

Flutter SDK diperlukan untuk menghasilkan runner Android/iOS dan mengambil dependency. Dari direktori `mobile/`:

```bash
flutter create --platforms=android,ios .
flutter pub get
```

Tambahkan konfigurasi Firebase resmi (`google-services.json` untuk Android dan `GoogleService-Info.plist` untuk iOS), lalu jalankan:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Gunakan URL host lokal yang dapat diakses simulator/perangkat. Token Sanctum disimpan melalui Hive dan otomatis dikirim sebagai Bearer token.

## Verifikasi

```bash
flutter analyze
flutter test
```
