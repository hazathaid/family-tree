import 'dart:io';

import 'package:firebase_messaging/firebase_messaging.dart';

import '../features/notifications/domain/notification_repository.dart';

class PushNotificationService {
  const PushNotificationService(this.repository, this.messaging);

  final NotificationRepository repository;
  final FirebaseMessaging messaging;

  Future<void> initialize() async {
    final settings = await messaging.requestPermission(
        alert: true, badge: true, sound: true);
    if (settings.authorizationStatus == AuthorizationStatus.denied) return;

    final token = await messaging.getToken();
    if (token != null) await _register(token);
    messaging.onTokenRefresh.listen(_register);
  }

  Future<void> _register(String token) => repository.registerDevice(
        platform: Platform.isIOS ? 'ios' : 'android',
        token: token,
      );
}
