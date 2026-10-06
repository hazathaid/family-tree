import 'package:flutter_secure_storage/flutter_secure_storage.dart';

abstract interface class TokenStore {
  Future<String?> read();
  Future<void> write(String token);
  Future<String?> readRefresh();
  Future<void> writeRefresh(String token);
  Future<void> clear();
}

class SecureTokenStore implements TokenStore {
  const SecureTokenStore([this._storage = const FlutterSecureStorage()]);
  static const _tokenKey = 'sanctum_bearer_token';
  static const _refreshKey = 'sanctum_refresh_token';
  final FlutterSecureStorage _storage;

  @override
  Future<String?> read() => _storage.read(key: _tokenKey);
  @override
  Future<void> write(String token) =>
      _storage.write(key: _tokenKey, value: token);
  @override
  Future<String?> readRefresh() => _storage.read(key: _refreshKey);
  @override
  Future<void> writeRefresh(String token) =>
      _storage.write(key: _refreshKey, value: token);
  @override
  Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _refreshKey);
  }
}

class MemoryTokenStore implements TokenStore {
  String? token;
  String? refreshToken;
  @override
  Future<String?> read() async => token;
  @override
  Future<void> write(String token) async => this.token = token;
  @override
  Future<String?> readRefresh() async => refreshToken;
  @override
  Future<void> writeRefresh(String token) async => refreshToken = token;
  @override
  Future<void> clear() async {
    token = null;
    refreshToken = null;
  }
}
