import '../../../core/models.dart';

abstract interface class AccountRepository {
  Future<User> profile();
  Future<User> updateProfile(String name, String email, String? phone,
      {String? currentPassword});
  Future<User> uploadAvatar(String path);
  Future<void> changePassword(String currentPassword, String password);
  Future<NotificationPreferences> preferences();
  Future<NotificationPreferences> updatePreferences(
      NotificationPreferences value);
  Future<List<AccountSession>> sessions();
  Future<bool> revokeSession(String uuid);
  Future<TwoFactorStatus> twoFactorStatus();
  Future<TwoFactorSetup> enableTwoFactor(String currentPassword);
  Future<List<String>> confirmTwoFactor(String code);
  Future<void> disableTwoFactor(String currentPassword);
  Future<List<String>> regenerateRecoveryCodes(String currentPassword);
}
