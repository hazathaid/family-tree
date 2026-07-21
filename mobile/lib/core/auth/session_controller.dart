import 'package:flutter/foundation.dart';

import '../storage/scoped_cache.dart';
import '../storage/secure_token_store.dart';

enum SessionStatus {
  bootstrapping,
  unauthenticated,
  authenticated,
  needsVerification,
  needsOnboarding,
  needsFamily
}

class SessionController extends ChangeNotifier {
  SessionController(this._tokens, this._cache);
  final TokenStore _tokens;
  final ScopedCache _cache;
  SessionStatus status = SessionStatus.bootstrapping;
  String? intendedLocation;

  Future<void> bootstrap() async {
    status = (await _tokens.read()) == null
        ? SessionStatus.unauthenticated
        : SessionStatus.authenticated;
    notifyListeners();
  }

  void authenticated() {
    status = SessionStatus.authenticated;
    notifyListeners();
  }

  Future<void> endSession() async {
    await _tokens.clear();
    await _cache.clearAll();
    status = SessionStatus.unauthenticated;
    notifyListeners();
  }
}
