import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:hive_flutter/hive_flutter.dart';

import 'app.dart';
import 'core/api_client.dart';
import 'core/auth/session_controller.dart';
import 'core/config/app_environment.dart';
import 'core/models.dart';
import 'core/providers.dart';
import 'core/push_notification_service.dart';
import 'core/repositories.dart';
import 'core/storage/scoped_cache.dart';
import 'core/storage/secure_token_store.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final environment = AppEnvironment.fromDefines();
  await Hive.initFlutter();
  final cache = ScopedCache(await Hive.openBox<String>('safe_read_cache'));
  const tokens = SecureTokenStore();
  final session = SessionController(tokens, cache);
  final api = ApiClient(
      baseUrl: environment.apiBaseUrl.toString(),
      tokenStore: tokens,
      onUnauthorized: session.endSession);
  await session.bootstrap();
  User? initialUser;
  Family? initialFamily;
  if (session.status == SessionStatus.authenticated) {
    try {
      final user = await ApiAuthRepository(api).me();
      final families = await ApiFamilyRepository(api).all();
      initialUser = user;
      session.resolveUser(
          uuid: user.uuid,
          verified: user.isVerified,
          familyCount: families.length);
      for (final family in families) {
        if (family.uuid == session.activeFamilyUuid) initialFamily = family;
      }
      if (session.status == SessionStatus.authenticated &&
          initialFamily == null) {
        session.requireFamilySelection();
      }
    } on Object {
      await session.endSession();
    }
  }

  try {
    await Firebase.initializeApp();
    if (session.status == SessionStatus.authenticated) {
      await PushNotificationService(
              ApiNotificationRepository(api), FirebaseMessaging.instance)
          .initialize();
    }
  } on FirebaseException catch (error) {
    if (kDebugMode) {
      debugPrint(
          'Firebase belum dikonfigurasi untuk flavor ini: ${error.code}');
    }
  }

  runApp(ProviderScope(
    overrides: [
      environmentProvider.overrideWithValue(environment),
      sessionControllerProvider.overrideWithValue(session),
      scopedCacheProvider.overrideWithValue(cache),
      apiClientProvider.overrideWithValue(api),
      currentUserProvider.overrideWith((ref) => initialUser),
      currentFamilyProvider.overrideWith((ref) => initialFamily),
    ],
    child: const FamilyTreeApp(),
  ));
}
