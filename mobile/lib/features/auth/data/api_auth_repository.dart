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
  Future<AuthLoginResult> login(String email, String password) async {
    final data = await api.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': 'family-tree-mobile'
    }) as Map<String, dynamic>;
    if (data['two_factor_required'] == true) {
      return AuthLoginResult(challengeToken: data['challenge_token'] as String);
    }
    await _storeTokens(data);
    return AuthLoginResult(user: User.fromJson(data['user'] as Map<String, dynamic>));
  }

  @override
  Future<User> twoFactorChallenge(String challengeToken,
      {String? code, String? recoveryCode}) async {
    final data = await api.post('/auth/two-factor-challenge', data: {
      'challenge_token': challengeToken,
      if (code != null) 'code': code,
      if (recoveryCode != null) 'recovery_code': recoveryCode,
    }) as Map<String, dynamic>;
    await _storeTokens(data);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> _storeTokens(Map<String, dynamic> data) async {
    await api.saveToken(data['token'] as String);
    final refreshToken = data['refresh_token'];
    if (refreshToken is String && refreshToken.isNotEmpty) {
      await api.saveRefreshToken(refreshToken);
    }
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
