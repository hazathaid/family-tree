import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/notification_repository.dart';
import '../../../core/http/page_data.dart';

class ApiNotificationRepository implements NotificationRepository {
  const ApiNotificationRepository(this.api);
  final ApiClient api;
  @override
  Future<PageData<AppNotification>> all({int page = 1, String? status}) async {
    final result = await api.get('/notifications', query: {
      'page': page,
      'limit': 20,
      if (status != null) 'status': status
    });
    if (result is List<dynamic>) {
      return PageData(
          items: result
              .map((e) => AppNotification.fromJson(e as Map<String, dynamic>))
              .toList(),
          currentPage: 1,
          lastPage: 1,
          total: result.length);
    }
    return PageData.fromJson(
        result as Map<String, dynamic>, AppNotification.fromJson);
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
  @override
  Future<void> removeDevice(String uuid) async =>
      api.delete('/push-devices/$uuid');
}
