import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import '../../core/providers.dart';
import '../../core/push_notification_service.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({required this.onLoggedIn, super.key});
  final VoidCallback onLoggedIn;

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;

  Future<void> _login() async {
    setState(() => _loading = true);
    try {
      await ref
          .read(authRepositoryProvider)
          .login(_email.text.trim(), _password.text);
      await PushNotificationService(
        ref.read(notificationRepositoryProvider),
        FirebaseMessaging.instance,
      ).initialize();
      widget.onLoggedIn();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('$error')));
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Icon(Icons.account_tree,
                          size: 72, color: Color(0xff1e88e5)),
                      const SizedBox(height: 16),
                      Text('Family Tree Indonesia',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.headlineSmall),
                      const SizedBox(height: 32),
                      TextField(
                          controller: _email,
                          keyboardType: TextInputType.emailAddress,
                          autofillHints: const [AutofillHints.email],
                          textInputAction: TextInputAction.next,
                          decoration:
                              const InputDecoration(labelText: 'Email')),
                      const SizedBox(height: 12),
                      TextField(
                          controller: _password,
                          obscureText: true,
                          autofillHints: const [AutofillHints.password],
                          onSubmitted: (_) => _loading ? null : _login(),
                          decoration:
                              const InputDecoration(labelText: 'Password')),
                      const SizedBox(height: 24),
                      FilledButton(
                          onPressed: _loading ? null : _login,
                          child: Text(_loading ? 'Memuat…' : 'Masuk')),
                    ]),
              ),
            ),
          ),
        ),
      );
}
