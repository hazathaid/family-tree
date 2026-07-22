import '../../../core/models.dart';
import '../../../core/http/page_data.dart';

abstract interface class NotificationRepository {
  Future<PageData<AppNotification>> all({int page = 1, String? status});
  Future<void> markRead(String uuid);
  Future<void> markAllRead();
  Future<void> registerDevice(
      {required String platform, required String token});
  Future<void> removeDevice(String uuid);
}
