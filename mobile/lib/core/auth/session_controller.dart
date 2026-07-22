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
  String? userUuid;
  String? get activeFamilyUuid =>
      userUuid == null ? null : _cache.activeFamily(userUuid!);

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

  void resolveUser(
      {required String uuid,
      required bool verified,
      required int familyCount}) {
    userUuid = uuid;
    status = !verified
        ? SessionStatus.needsVerification
        : familyCount == 0
            ? SessionStatus.needsOnboarding
            : _cache.activeFamily(uuid) == null
                ? SessionStatus.needsFamily
                : SessionStatus.authenticated;
    notifyListeners();
  }

  void familySelected(String familyUuid) {
    final uuid = userUuid;
    if (uuid != null) _cache.saveActiveFamily(uuid, familyUuid);
    status = SessionStatus.authenticated;
    notifyListeners();
  }

  void requireFamilySelection() {
    status = SessionStatus.needsFamily;
    notifyListeners();
  }

  Future<void> endSession() async {
    await _tokens.clear();
    await _cache.clearAll();
    status = SessionStatus.unauthenticated;
    userUuid = null;
    notifyListeners();
  }
}
