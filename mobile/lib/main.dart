import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'app.dart';
import 'core/api_client.dart';
import 'core/providers.dart';
import 'core/push_notification_service.dart';
import 'core/repositories.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Hive.initFlutter();
  final session = await Hive.openBox<String>('session');
  const baseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api/v1');
  final api = ApiClient(baseUrl: baseUrl, sessionBox: session);

  await Firebase.initializeApp();
  if (api.hasToken) {
    await PushNotificationService(NotificationRepository(api), FirebaseMessaging.instance).initialize();
  }

  runApp(ProviderScope(overrides: [apiClientProvider.overrideWithValue(api)], child: const FamilyTreeApp()));
}
