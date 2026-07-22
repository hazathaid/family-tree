import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/auth_repository.dart';

class ApiAuthRepository implements AuthRepository {
  const ApiAuthRepository(this.api);
  final ApiClient api;
  @override
  Future<User> register(String name, String email, String password,
          {String? phone}) async =>
      User.fromJson(await api.post('/auth/register', data: {
        'name': name,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': password,
      }) as Map<String, dynamic>);
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
  Future<void> forgotPassword(String email) =>
      api.post('/auth/forgot-password', data: {'email': email});
  @override
  Future<void> resetPassword(String token, String email, String password) =>
      api.post('/auth/reset-password', data: {
        'token': token,
        'email': email,
        'password': password,
        'password_confirmation': password
      });
  @override
  Future<void> resendVerification() =>
      api.post('/auth/email/verification-notification');
  @override
  Future<void> verifyEmail(String id, String hash, Map<String, String> query) =>
      api.get('/auth/email/verify/$id/$hash', query: query);
  @override
  Future<void> logout() async {
    try {
      await api.post('/auth/logout');
    } finally {
      await api.clearToken();
    }
  }
}
