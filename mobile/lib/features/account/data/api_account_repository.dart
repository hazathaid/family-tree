import 'package:dio/dio.dart';

import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/account_repository.dart';

class ApiAccountRepository implements AccountRepository {
  const ApiAccountRepository(this.api);
  final ApiClient api;
  @override
  Future<User> profile() async =>
      User.fromJson(await api.get('/profile') as Map<String, dynamic>);
  @override
  Future<User> updateProfile(String name, String email, String? phone,
          {String? currentPassword}) async =>
      User.fromJson(await api.put('/profile', data: {
        'name': name,
        'email': email,
        'phone': phone,
        'current_password': currentPassword
      }) as Map<String, dynamic>);
  @override
  Future<User> uploadAvatar(String path) async => User.fromJson(await api.post(
          '/profile/avatar',
          data:
              FormData.fromMap({'avatar': await MultipartFile.fromFile(path)}))
      as Map<String, dynamic>);
  @override
  Future<void> changePassword(String currentPassword, String password) =>
      api.patch('/profile/password', data: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': password
      });
  @override
  Future<NotificationPreferences> preferences() async =>
      NotificationPreferences.fromJson(await api
          .get('/profile/notification-preferences') as Map<String, dynamic>);
  @override
  Future<NotificationPreferences> updatePreferences(
          NotificationPreferences value) async =>
      NotificationPreferences.fromJson(await api.put(
          '/profile/notification-preferences',
          data: value.toJson()) as Map<String, dynamic>);
  @override
  Future<List<AccountSession>> sessions() async {
    final result = await api.get('/profile/sessions');
    final list = result is Map<String, dynamic>
        ? result['data'] as List<dynamic>? ?? const []
        : result as List<dynamic>;
    return list
        .map((item) => AccountSession.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<bool> revokeSession(String uuid) async =>
      (await api.delete('/profile/sessions/$uuid')
          as Map<String, dynamic>)['revoked_current'] as bool? ??
      false;

  @override
  Future<TwoFactorStatus> twoFactorStatus() async =>
      TwoFactorStatus.fromJson(await api.get('/profile/two-factor')
          as Map<String, dynamic>);

  @override
  Future<TwoFactorSetup> enableTwoFactor(String currentPassword) async =>
      TwoFactorSetup.fromJson(await api.post('/profile/two-factor',
          data: {'current_password': currentPassword}) as Map<String, dynamic>);

  @override
  Future<List<String>> confirmTwoFactor(String code) async {
    final data = await api.post('/profile/two-factor/confirm',
        data: {'code': code}) as Map<String, dynamic>;
    return _codes(data);
  }

  @override
  Future<void> disableTwoFactor(String currentPassword) =>
      api.delete('/profile/two-factor',
          data: {'current_password': currentPassword});

  @override
  Future<List<String>> regenerateRecoveryCodes(String currentPassword) async {
    final data = await api.post('/profile/two-factor/recovery-codes',
        data: {'current_password': currentPassword}) as Map<String, dynamic>;
    return _codes(data);
  }

  List<String> _codes(Map<String, dynamic> data) =>
      (data['recovery_codes'] as List<dynamic>? ?? const [])
          .map((item) => '$item')
          .toList(growable: false);
}
