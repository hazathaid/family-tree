import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/errors/app_error.dart';
import '../../core/providers.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});
  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final email = TextEditingController();
  final password = TextEditingController();
  bool loading = false;
  bool obscure = true;
  String? emailError;

  Future<void> submit() async {
    setState(() {
      loading = true;
      emailError = null;
    });
    try {
      final user = await ref
          .read(authRepositoryProvider)
          .login(email.text.trim(), password.text);
      ref.read(currentUserProvider.notifier).state = user;
      final families = await ref.read(familyRepositoryProvider).all();
      ref.read(sessionControllerProvider).resolveUser(
          uuid: user.uuid,
          verified: user.isVerified,
          familyCount: families.length);
      final activeUuid = ref.read(sessionControllerProvider).activeFamilyUuid;
      for (final family in families) {
        if (family.uuid == activeUuid) {
          ref.read(currentFamilyProvider.notifier).state = family;
        }
      }
    } on AppError catch (error) {
      if (mounted) {
        setState(() =>
            emailError = error.fieldErrors['email']?.first ?? error.message);
      }
    } finally {
      if (mounted) setState(() => loading = false);
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
                      child: AutofillGroup(
                          child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                            const Icon(Icons.account_tree,
                                size: 72, color: Color(0xff1e88e5)),
                            const SizedBox(height: 16),
                            Text('Family Tree Indonesia',
                                textAlign: TextAlign.center,
                                style:
                                    Theme.of(context).textTheme.headlineSmall),
                            const SizedBox(height: 32),
                            TextField(
                                controller: email,
                                keyboardType: TextInputType.emailAddress,
                                autofillHints: const [AutofillHints.email],
                                textInputAction: TextInputAction.next,
                                decoration: InputDecoration(
                                    labelText: 'Email', errorText: emailError)),
                            const SizedBox(height: 12),
                            TextField(
                                controller: password,
                                obscureText: obscure,
                                autofillHints: const [AutofillHints.password],
                                onSubmitted: (_) => loading ? null : submit(),
                                decoration: InputDecoration(
                                    labelText: 'Kata sandi',
                                    suffixIcon: IconButton(
                                        tooltip: obscure
                                            ? 'Tampilkan kata sandi'
                                            : 'Sembunyikan kata sandi',
                                        onPressed: () =>
                                            setState(() => obscure = !obscure),
                                        icon: Icon(obscure
                                            ? Icons.visibility
                                            : Icons.visibility_off)))),
                            Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                    onPressed: () =>
                                        context.go('/forgot-password'),
                                    child: const Text('Lupa kata sandi?'))),
                            FilledButton(
                                onPressed: loading ? null : submit,
                                child: Text(loading ? 'Memuat…' : 'Masuk')),
                            TextButton(
                                onPressed: () => context.go('/register'),
                                child: const Text('Buat akun baru')),
                          ])))))));
}
