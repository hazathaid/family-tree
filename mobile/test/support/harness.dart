import 'dart:io';

import 'package:family_tree_mobile/core/auth/session_controller.dart';
import 'package:family_tree_mobile/core/storage/scoped_cache.dart';
import 'package:family_tree_mobile/core/storage/secure_token_store.dart';
import 'package:family_tree_mobile/l10n/app_localizations.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hive_flutter/hive_flutter.dart';

bool _hiveReady = false;

Future<ScopedCache> createTestCache({int maximumEntries = 5}) async {
  if (!_hiveReady) {
    Hive.init(Directory.systemTemp.path);
    _hiveReady = true;
  }
  final box = await Hive.openBox<String>(
      'test-cache-${DateTime.now().microsecondsSinceEpoch}');
  await box.clear();
  return ScopedCache(box, maximumEntries: maximumEntries);
}

class _NullBox implements Box<String> {
  @override
  dynamic noSuchMethod(Invocation invocation) => null;
}

class MemoryScopedCache extends ScopedCache {
  MemoryScopedCache() : super(_NullBox());

  final Map<String, dynamic> values = {};
  final Map<String, DateTime> savedAt = {};

  @override
  Future<void> put(String key, dynamic value) async {
    values[key] = value;
    savedAt[key] = DateTime.now().toUtc();
  }

  @override
  CacheEntry? get(String key, {required Duration ttl}) {
    if (!values.containsKey(key)) return null;
    final time = savedAt[key]!;
    return CacheEntry(
        value: values[key],
        savedAt: time,
        isStale: DateTime.now().toUtc().difference(time) > ttl);
  }

  @override
  Future<void> clearAll() async {
    values.clear();
    savedAt.clear();
  }

  @override
  Future<void> saveActiveFamily(String userUuid, String familyUuid) async {
    values['$userUuid:account:active-family'] = familyUuid;
  }

  @override
  String? activeFamily(String userUuid) =>
      values['$userUuid:account:active-family'] as String?;

  @override
  Future<void> clearScope(
      {required String userUuid, String? familyUuid}) async {
    final prefix = familyUuid == null ? '$userUuid:' : '$userUuid:$familyUuid:';
    values.removeWhere((key, _) => key.startsWith(prefix));
    savedAt.removeWhere((key, _) => key.startsWith(prefix));
  }
}

Future<SessionController> createTestSession(
    {String? token, ScopedCache? cache}) async {
  final tokens = MemoryTokenStore();
  if (token != null) await tokens.write(token);
  return SessionController(tokens, cache ?? MemoryScopedCache());
}

Future<SessionController> authenticatedSession(
    {String userUuid = 'user-uuid', String? activeFamily}) async {
  final session = await createTestSession(token: 'token');
  session.userUuid = userUuid;
  session.status = SessionStatus.authenticated;
  if (activeFamily != null) session.familySelected(activeFamily);
  return session;
}

Widget testApp(Widget home) => MaterialApp(
      locale: const Locale('id'),
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      home: home,
    );

Widget routedApp(Widget home) {
  final router = GoRouter(initialLocation: '/home', routes: [
    GoRoute(path: '/home', builder: (_, __) => home),
    GoRoute(
        path: '/:rest(.*)',
        builder: (_, state) =>
            Scaffold(body: Text('route:${state.uri.toString()}')))
  ]);
  return MaterialApp.router(
    locale: const Locale('id'),
    localizationsDelegates: AppLocalizations.localizationsDelegates,
    supportedLocales: AppLocalizations.supportedLocales,
    routerConfig: router,
  );
}
