import '../../../core/models.dart';

abstract interface class AuthRepository {
  Future<User> login(String email, String password);
  Future<User> me();
  Future<void> logout();
}
