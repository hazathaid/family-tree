import '../../../core/models.dart';

abstract interface class NotificationRepository {
  Future<List<AppNotification>> all();
  Future<void> markRead(String uuid);
  Future<void> markAllRead();
  Future<void> registerDevice(
      {required String platform, required String token});
}
