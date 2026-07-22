import '../../../core/models.dart';

abstract interface class AuthRepository {
  Future<User> register(String name, String email, String password,
      {String? phone});
  Future<User> login(String email, String password);
  Future<User> me();
  Future<void> forgotPassword(String email);
  Future<void> resetPassword(String token, String email, String password);
  Future<void> resendVerification();
  Future<void> verifyEmail(String id, String hash, Map<String, String> query);
  Future<void> logout();
}
