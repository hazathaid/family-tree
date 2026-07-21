import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/auth_repository.dart';

class ApiAuthRepository implements AuthRepository {
  const ApiAuthRepository(this.api);
  final ApiClient api;
  @override
  Future<User> login(String email, String password) async {
    final data = await api.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': 'family-tree-mobile'
    }) as Map<String, dynamic>;
    await api.saveToken(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  @override
  Future<User> me() async =>
      User.fromJson(await api.get('/auth/me') as Map<String, dynamic>);
  @override
  Future<void> logout() async {
    try {
      await api.post('/auth/logout');
    } finally {
      await api.clearToken();
    }
  }
}
