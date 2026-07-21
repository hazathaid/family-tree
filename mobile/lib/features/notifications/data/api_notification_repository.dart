import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/notification_repository.dart';

class ApiNotificationRepository implements NotificationRepository {
  const ApiNotificationRepository(this.api);
  final ApiClient api;
  @override
  Future<List<AppNotification>> all() async {
    final result = await api.get('/notifications');
    final items = result is Map<String, dynamic>
        ? result['data'] as List<dynamic>? ?? const []
        : result as List<dynamic>;
    return items
        .map((item) => AppNotification.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<void> markRead(String uuid) async =>
      api.post('/notifications/$uuid/read');
  @override
  Future<void> markAllRead() async => api.post('/notifications/read-all');
  @override
  Future<void> registerDevice(
          {required String platform, required String token}) async =>
      api.post('/push-devices', data: {'platform': platform, 'token': token});
}
