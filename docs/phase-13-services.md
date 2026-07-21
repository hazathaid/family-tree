# Phase 13 Service Documentation

Source Flutter berada di `mobile/` dan dibagi menjadi komponen sederhana:

* `ApiClient`: envelope JSON, Bearer token, dan normalisasi error.
* `AuthRepository`: login, session, dan logout.
* `FamilyRepository`: keluarga, dashboard, timeline, dan tree.
* `NotificationRepository`: inbox, status read, dan registrasi perangkat.
* `PushNotificationService`: izin Firebase Messaging, token awal, dan token refresh.
* Riverpod providers: state keluarga aktif dan pemuatan data asynchronous.

Tree dirender dari node/edge hasil backend menggunakan `InteractiveViewer` untuk pinch zoom dan pan. Mobile meminta tiga generasi per layar; kalkulasi dan traversal tetap sepenuhnya dijalankan `FamilyTreeService` Laravel.

Konfigurasi Firebase native tidak disimpan di repository karena berisi identifier khusus environment. Runner Android/iOS dan file konfigurasi Firebase dibuat sesuai petunjuk `mobile/README.md`.
