import 'api_client.dart';
import 'models.dart';

class AuthRepository {
  const AuthRepository(this.api);
  final ApiClient api;

  Future<User> login(String email, String password) async {
    final data = await api.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': 'family-tree-mobile',
    }) as Map<String, dynamic>;
    await api.saveToken(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> me() async => User.fromJson(await api.get('/auth/me') as Map<String, dynamic>);

  Future<void> logout() async {
    await api.post('/auth/logout');
    await api.clearToken();
  }
}

class FamilyRepository {
  const FamilyRepository(this.api);
  final ApiClient api;

  Future<List<Family>> all() async => (await api.get('/families') as List<dynamic>)
      .map((item) => Family.fromJson(item as Map<String, dynamic>))
      .toList();

  Future<DashboardSummary> dashboard(String familyUuid) async => DashboardSummary.fromJson(
      await api.get('/families/$familyUuid/dashboard') as Map<String, dynamic>);

  Future<List<TimelineItem>> timeline(String familyUuid) async =>
      (await api.get('/timeline', query: {'family_uuid': familyUuid, 'limit': 20}) as List<dynamic>)
          .map((item) => TimelineItem.fromJson(item as Map<String, dynamic>))
          .toList();

  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'}) async => FamilyTree.fromJson(
        await api.get('/tree/generate', query: {
          'member_uuid': memberUuid,
          'mode': mode,
          'depth': 3,
          'layout': 'vertical',
        }) as Map<String, dynamic>,
      );
}

class NotificationRepository {
  const NotificationRepository(this.api);
  final ApiClient api;

  Future<List<AppNotification>> all() async => (await api.get('/notifications') as List<dynamic>)
      .map((item) => AppNotification.fromJson(item as Map<String, dynamic>))
      .toList();

  Future<void> markRead(String uuid) async {
    await api.post('/notifications/$uuid/read');
  }

  Future<void> markAllRead() async {
    await api.post('/notifications/read-all');
  }

  Future<void> registerDevice({required String platform, required String token}) async {
    await api.post('/push-devices', data: {'platform': platform, 'token': token});
  }
}
