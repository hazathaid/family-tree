# Family Tree Mobile

Flutter client untuk Family Tree Platform Indonesia. Laravel tetap menjadi sumber kebenaran untuk validasi, authorization, relationship calculation, tree generation, sanitasi, dan seluruh aturan domain.

## Runtime

- Flutter 3.44 / Dart 3.12 atau versi compatible dengan `pubspec.lock`.
- Android minimum API 23, application ID production `id.familytree.family_tree_mobile`.
- iOS minimum 13, bundle ID production `id.familytree.familyTreeMobile`.
- Ponsel memakai portrait; iPad tetap mendukung adaptive orientation/layout.
- Secret/token disimpan di OS secure storage. Hive hanya berisi cache read-only non-sensitif dan dibersihkan pada logout/account switch.

## Environment

Semua build wajib memberikan `APP_FLAVOR` dan `API_BASE_URL`; URL harus absolut dan berakhir `/api/v1`. Production menolak HTTP.

```bash
flutter run --flavor development --dart-define=APP_FLAVOR=development --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
flutter run --flavor staging --dart-define=APP_FLAVOR=staging --dart-define=API_BASE_URL=https://staging-api.example.id/api/v1
flutter run --flavor production --dart-define=APP_FLAVOR=production --dart-define=API_BASE_URL=https://api.example.id/api/v1
```

Android menyediakan product flavor development/staging/production. Konfigurasi iOS environment berada di `ios/Flutter/{Debug,Staging,Release}.xcconfig`; buat scheme CI/Xcode yang menunjuk ke konfigurasi tersebut sebelum distribusi. Signing production diinjeksikan oleh CI/Xcode dan file key/provisioning tidak boleh masuk Git.

## Firebase

Gunakan project Firebase berbeda per environment. Jangan commit file konfigurasi Firebase.

- Android: letakkan `google-services.json` pada source set flavor yang sesuai (`android/app/src/development`, `staging`, atau `production`) dan aktifkan plugin Google Services hanya pada pipeline yang menyediakan file tersebut.
- iOS: salin `GoogleService-Info.plist` environment terpilih ke `ios/Runner/` melalui build phase/CI.
- Daftarkan application/bundle ID masing-masing flavor di Firebase dan batasi API key sesuai platform.
- Aplikasi tetap dapat startup tanpa Firebase pada debug; push dinonaktifkan dan pesan aman ditulis tanpa credential/PII.

Diagnostics hanya dapat dibuka pada debug non-production melalui `/diagnostics`; layar ini hanya menampilkan flavor, host API yang disanitasi, dan status konektivitas.

## Deep link

Skema `familytree://` dan host HTTPS `familytree.id` diarahkan ke declarative router. Allowlist aplikasi mencakup verifikasi email, reset password, notification target, article, event, dan member; semua target tetap melewati auth/family guard.

## Verifikasi

```bash
flutter pub get
flutter analyze
flutter test
flutter build apk --debug --flavor development --dart-define=APP_FLAVOR=development --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
flutter build ios --simulator --debug --no-codesign --dart-define=APP_FLAVOR=development --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1
```
