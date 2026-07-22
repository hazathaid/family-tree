import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/errors/app_error.dart';
import '../../core/providers.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});
  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final name = TextEditingController(),
      email = TextEditingController(),
      phone = TextEditingController(),
      password = TextEditingController();
  bool loading = false, obscure = true;
  Map<String, List<String>> errors = {};
  Future<void> submit() async {
    setState(() {
      loading = true;
      errors = {};
    });
    try {
      await ref.read(authRepositoryProvider).register(
          name.text.trim(), email.text.trim(), password.text,
          phone: phone.text.trim().isEmpty ? null : phone.text.trim());
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content: Text('Akun dibuat. Silakan masuk dan verifikasi email.')));
        context.go('/login');
      }
    } on AppError catch (e) {
      if (mounted) {
        setState(() => errors = e.fieldErrors.isEmpty
            ? {
                'email': [e.message]
              }
            : e.fieldErrors);
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => _AuthPage(title: 'Daftar', children: [
        _field(name, 'Nama', errors['name']),
        _field(email, 'Email', errors['email'], email: true),
        _field(phone, 'Nomor telepon (opsional)', errors['phone']),
        TextField(
            controller: password,
            obscureText: obscure,
            decoration: InputDecoration(
                labelText: 'Kata sandi',
                errorText: errors['password']?.first,
                suffixIcon: IconButton(
                    tooltip: 'Tampilkan kata sandi',
                    onPressed: () => setState(() => obscure = !obscure),
                    icon: const Icon(Icons.visibility)))),
        FilledButton(
            onPressed: loading ? null : submit,
            child: Text(loading ? 'Memuat…' : 'Daftar')),
        TextButton(
            onPressed: () => context.go('/login'),
            child: const Text('Sudah punya akun? Masuk'))
      ]);
}

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});
  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordState();
}

class _ForgotPasswordState extends ConsumerState<ForgotPasswordScreen> {
  final email = TextEditingController();
  bool loading = false;
  String? error;
  Future<void> submit() async {
    setState(() => loading = true);
    try {
      await ref.read(authRepositoryProvider).forgotPassword(email.text.trim());
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content:
                Text('Jika email terdaftar, tautan reset telah dikirim.')));
      }
    } on AppError catch (e) {
      if (mounted) {
        setState(() => error = e.fieldErrors['email']?.first ?? e.message);
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) =>
      _AuthPage(title: 'Lupa kata sandi', children: [
        _field(email, 'Email', error == null ? null : [error!], email: true),
        FilledButton(
            onPressed: loading ? null : submit,
            child: Text(loading ? 'Mengirim…' : 'Kirim tautan reset')),
        TextButton(
            onPressed: () => context.go('/login'),
            child: const Text('Kembali ke masuk'))
      ]);
}

class ResetPasswordScreen extends ConsumerStatefulWidget {
  const ResetPasswordScreen(
      {required this.token, required this.email, super.key});
  final String token, email;
  @override
  ConsumerState<ResetPasswordScreen> createState() => _ResetPasswordState();
}

class _ResetPasswordState extends ConsumerState<ResetPasswordScreen> {
  final password = TextEditingController();
  bool loading = false;
  String? error;
  Future<void> submit() async {
    setState(() => loading = true);
    try {
      await ref
          .read(authRepositoryProvider)
          .resetPassword(widget.token, widget.email, password.text);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Kata sandi berhasil diubah.')));
        context.go('/login');
      }
    } on AppError catch (e) {
      if (mounted) {
        setState(() => error = e.fieldErrors['password']?.first ?? e.message);
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) =>
      _AuthPage(title: 'Reset kata sandi', children: [
        Text(widget.email),
        TextField(
            controller: password,
            obscureText: true,
            decoration: InputDecoration(
                labelText: 'Kata sandi baru', errorText: error)),
        FilledButton(
            onPressed: widget.token.isEmpty || widget.email.isEmpty || loading
                ? null
                : submit,
            child: Text(loading ? 'Memuat…' : 'Simpan kata sandi'))
      ]);
}

class VerificationScreen extends ConsumerStatefulWidget {
  const VerificationScreen(
      {this.id, this.hash, this.query = const {}, super.key});
  final String? id, hash;
  final Map<String, String> query;
  @override
  ConsumerState<VerificationScreen> createState() => _VerificationState();
}

class _VerificationState extends ConsumerState<VerificationScreen> {
  int cooldown = 0;
  Timer? timer;
  bool loading = false;
  String? message;
  @override
  void initState() {
    super.initState();
    if (widget.id != null && widget.hash != null) Future.microtask(verify);
  }

  Future<void> verify() async {
    setState(() => loading = true);
    try {
      await ref
          .read(authRepositoryProvider)
          .verifyEmail(widget.id!, widget.hash!, widget.query);
      final user = await ref.read(authRepositoryProvider).me();
      final families = await ref.read(familyRepositoryProvider).all();
      ref.read(currentUserProvider.notifier).state = user;
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
    } on AppError catch (e) {
      if (mounted) setState(() => message = e.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> resend() async {
    try {
      await ref.read(authRepositoryProvider).resendVerification();
      setState(() {
        cooldown = 60;
        message = 'Tautan verifikasi dikirim.';
      });
      timer?.cancel();
      timer = Timer.periodic(const Duration(seconds: 1), (value) {
        if (!mounted || cooldown <= 1) {
          value.cancel();
          if (mounted) setState(() => cooldown = 0);
        } else {
          setState(() => cooldown--);
        }
      });
    } on AppError catch (e) {
      setState(() => message = e.message);
    }
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      _AuthPage(title: 'Verifikasi email', children: [
        const Icon(Icons.mark_email_unread_outlined, size: 64),
        Text(message ?? 'Periksa email Anda lalu buka tautan verifikasi.',
            textAlign: TextAlign.center),
        if (loading) const Center(child: CircularProgressIndicator()),
        FilledButton(
            onPressed: loading || cooldown > 0 ? null : resend,
            child: Text(cooldown > 0
                ? 'Kirim ulang dalam ${cooldown}d'
                : 'Kirim ulang email')),
        TextButton(
            onPressed: () async {
              await ref.read(authRepositoryProvider).logout();
              await ref.read(sessionControllerProvider).endSession();
            },
            child: const Text('Keluar'))
      ]);
}

Widget _field(
        TextEditingController controller, String label, List<String>? errors,
        {bool email = false}) =>
    Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
            controller: controller,
            keyboardType:
                email ? TextInputType.emailAddress : TextInputType.text,
            decoration:
                InputDecoration(labelText: label, errorText: errors?.first)));

class _AuthPage extends StatelessWidget {
  const _AuthPage({required this.title, required this.children});
  final String title;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(title)),
      body: SafeArea(
          child: Center(
              child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 480),
                      child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: children
                              .map((child) => Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: child))
                              .toList()))))));
}
