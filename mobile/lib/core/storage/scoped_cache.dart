import 'dart:convert';

import 'package:hive_flutter/hive_flutter.dart';

class CacheEntry {
  const CacheEntry(
      {required this.value, required this.savedAt, required this.isStale});
  final dynamic value;
  final DateTime savedAt;
  final bool isStale;
}

class ScopedCache {
  ScopedCache(this._box, {this.maximumEntries = 100});
  final Box<String> _box;
  final int maximumEntries;

  String key(
          {required String userUuid,
          required String familyUuid,
          required String query}) =>
      '$userUuid:$familyUuid:$query';

  Future<void> put(String key, dynamic value) async {
    await _box.put(
        key,
        jsonEncode({
          'saved_at': DateTime.now().toUtc().toIso8601String(),
          'value': value
        }));
    while (_box.length > maximumEntries) {
      await _box.deleteAt(0);
    }
  }

  CacheEntry? get(String key, {required Duration ttl}) {
    final raw = _box.get(key);
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw) as Map<String, dynamic>;
      final savedAt = DateTime.parse(decoded['saved_at'] as String);
      return CacheEntry(
          value: decoded['value'],
          savedAt: savedAt,
          isStale: DateTime.now().toUtc().difference(savedAt) > ttl);
    } on Object {
      _box.delete(key);
      return null;
    }
  }

  Future<void> clearAll() => _box.clear();
  Future<void> saveActiveFamily(String userUuid, String familyUuid) =>
      _box.put('$userUuid:account:active-family', familyUuid);
  String? activeFamily(String userUuid) =>
      _box.get('$userUuid:account:active-family');
  Future<void> clearScope(
      {required String userUuid, String? familyUuid}) async {
    final prefix = familyUuid == null ? '$userUuid:' : '$userUuid:$familyUuid:';
    await _box.deleteAll(
        _box.keys.whereType<String>().where((key) => key.startsWith(prefix)));
  }
}
