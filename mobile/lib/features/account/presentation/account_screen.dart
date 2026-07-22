import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';

class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    return ListView(padding: const EdgeInsets.all(16), children: [
      ListTile(
          leading: CircleAvatar(
              backgroundImage: user?.avatarUrl == null
                  ? null
                  : NetworkImage(user!.avatarUrl!),
              child: user?.avatarUrl == null ? const Icon(Icons.person) : null),
          title: Text(user?.name ?? 'Akun'),
          subtitle: Text(user?.email ?? ''),
          contentPadding: const EdgeInsets.all(8)),
      _tile(context, Icons.person_outline, 'Profil', const ProfileScreen()),
      _tile(context, Icons.notifications_outlined, 'Preferensi notifikasi',
          const PreferencesScreen()),
      _tile(context, Icons.security_outlined, 'Keamanan dan sesi',
          const SecurityScreen()),
      ListTile(
          minTileHeight: 56,
          leading: const Icon(Icons.family_restroom),
          title: const Text('Ganti keluarga'),
          onTap: () =>
              ref.read(sessionControllerProvider).requireFamilySelection()),
      ListTile(
          minTileHeight: 56,
          leading: const Icon(Icons.logout),
          title: const Text('Keluar'),
          onTap: () async {
            try {
              await ref.read(authRepositoryProvider).logout();
            } finally {
              await ref.read(sessionControllerProvider).endSession();
              ref.read(currentUserProvider.notifier).state = null;
              ref.read(currentFamilyProvider.notifier).state = null;
            }
          }),
    ]);
  }

  Widget _tile(
          BuildContext context, IconData icon, String title, Widget page) =>
      ListTile(
          minTileHeight: 56,
          leading: Icon(icon),
          title: Text(title),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.of(context)
              .push(MaterialPageRoute(builder: (_) => page)));
}

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});
  @override
  ConsumerState<ProfileScreen> createState() => _ProfileState();
}

class _ProfileState extends ConsumerState<ProfileScreen> {
  late final name =
      TextEditingController(text: ref.read(currentUserProvider)?.name);
  late final email =
      TextEditingController(text: ref.read(currentUserProvider)?.email);
  late final phone =
      TextEditingController(text: ref.read(currentUserProvider)?.phone);
  final currentPassword = TextEditingController();
  bool loading = false;
  String? error;

  Future<void> pickAvatar() async {
    final image = await ImagePicker().pickImage(source: ImageSource.gallery);
    if (image == null) return;
    if (await image.length() > 5 * 1024 * 1024) {
      setState(() => error = 'Ukuran avatar maksimal 5 MB.');
      return;
    }
    setState(() => loading = true);
    try {
      final user =
          await ref.read(accountRepositoryProvider).uploadAvatar(image.path);
      ref.read(currentUserProvider.notifier).state = user;
    } on AppError catch (exception) {
      setState(() => error = exception.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> save() async {
    setState(() => loading = true);
    try {
      final user = await ref.read(accountRepositoryProvider).updateProfile(
          name.text.trim(),
          email.text.trim(),
          phone.text.trim().isEmpty ? null : phone.text.trim(),
          currentPassword:
              currentPassword.text.isEmpty ? null : currentPassword.text);
      ref.read(currentUserProvider.notifier).state = user;
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Profil diperbarui.')));
      }
    } on AppError catch (e) {
      setState(() => error = e.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => _Page(title: 'Profil', children: [
        OutlinedButton.icon(
            onPressed: loading ? null : pickAvatar,
            icon: const Icon(Icons.photo_camera_outlined),
            label: const Text('Pilih avatar')),
        TextField(
            controller: name,
            decoration: const InputDecoration(labelText: 'Nama')),
        TextField(
            controller: email,
            decoration: const InputDecoration(labelText: 'Email')),
        TextField(
            controller: phone,
            decoration: const InputDecoration(labelText: 'Telepon')),
        TextField(
            controller: currentPassword,
            obscureText: true,
            decoration: const InputDecoration(
                labelText: 'Kata sandi saat ini (jika email berubah)')),
        if (error != null)
          Text(error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error)),
        FilledButton(
            onPressed: loading ? null : save,
            child: Text(loading ? 'Menyimpan…' : 'Simpan'))
      ]);
}

class PreferencesScreen extends ConsumerWidget {
  const PreferencesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
      appBar: AppBar(title: const Text('Preferensi notifikasi')),
      body: ref.watch(notificationPreferencesProvider).when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (_, __) => Center(
              child: FilledButton(
                  onPressed: () =>
                      ref.invalidate(notificationPreferencesProvider),
                  child: const Text('Coba lagi'))),
          data: (value) => _PreferencesForm(value: value)));
}

class _PreferencesForm extends ConsumerStatefulWidget {
  const _PreferencesForm({required this.value});
  final NotificationPreferences value;
  @override
  ConsumerState<_PreferencesForm> createState() => _PreferencesState();
}

class _PreferencesState extends ConsumerState<_PreferencesForm> {
  late bool email = widget.value.email,
      push = widget.value.push,
      events = widget.value.eventReminders,
      family = widget.value.familyUpdates;
  bool loading = false;
  Future<void> save() async {
    setState(() => loading = true);
    await ref.read(accountRepositoryProvider).updatePreferences(
        NotificationPreferences(
            email: email,
            push: push,
            eventReminders: events,
            familyUpdates: family));
    ref.invalidate(notificationPreferencesProvider);
    if (mounted) setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) =>
      ListView(padding: const EdgeInsets.all(16), children: [
        SwitchListTile(
            title: const Text('Email'),
            value: email,
            onChanged: (v) => setState(() => email = v)),
        SwitchListTile(
            title: const Text('Push'),
            value: push,
            onChanged: (v) => setState(() => push = v)),
        SwitchListTile(
            title: const Text('Pengingat acara'),
            value: events,
            onChanged: (v) => setState(() => events = v)),
        SwitchListTile(
            title: const Text('Pembaruan keluarga'),
            value: family,
            onChanged: (v) => setState(() => family = v)),
        FilledButton(
            onPressed: loading ? null : save, child: const Text('Simpan'))
      ]);
}

class SecurityScreen extends ConsumerStatefulWidget {
  const SecurityScreen({super.key});
  @override
  ConsumerState<SecurityScreen> createState() => _SecurityState();
}

class _SecurityState extends ConsumerState<SecurityScreen> {
  final current = TextEditingController();
  final password = TextEditingController();
  bool loading = false;

  Future<void> change() async {
    setState(() => loading = true);
    try {
      await ref
          .read(accountRepositoryProvider)
          .changePassword(current.text, password.text);
      await ref.read(sessionControllerProvider).endSession();
    } on AppError catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Keamanan')),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          TextField(
              controller: current,
              obscureText: true,
              decoration:
                  const InputDecoration(labelText: 'Kata sandi saat ini')),
          const SizedBox(height: 12),
          TextField(
              controller: password,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Kata sandi baru')),
          const SizedBox(height: 12),
          FilledButton(
              onPressed: loading ? null : change,
              child: const Text('Ubah kata sandi')),
          const Divider(height: 32),
          Text('Sesi perangkat',
              style: Theme.of(context).textTheme.titleMedium),
          ref.watch(accountSessionsProvider).when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => FilledButton(
                    onPressed: () => ref.invalidate(accountSessionsProvider),
                    child: const Text('Muat ulang sesi')),
                data: (items) => Column(
                    children: items
                        .map((session) => ListTile(
                              minTileHeight: 56,
                              leading: const Icon(Icons.devices),
                              title: Text(session.deviceName),
                              subtitle: Text(session.isCurrent
                                  ? 'Perangkat ini'
                                  : 'Terakhir aktif: ${session.lastActiveAt?.toLocal()}'),
                              trailing: IconButton(
                                  tooltip: 'Cabut sesi',
                                  icon: const Icon(Icons.logout),
                                  onPressed: () => revoke(session)),
                            ))
                        .toList()),
              ),
        ]),
      );

  Future<void> revoke(AccountSession session) async {
    final currentRevoked =
        await ref.read(accountRepositoryProvider).revokeSession(session.uuid);
    ref.invalidate(accountSessionsProvider);
    if (currentRevoked) await ref.read(sessionControllerProvider).endSession();
  }
}

class _Page extends StatelessWidget {
  const _Page({required this.title, required this.children});
  final String title;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(title)),
      body: ListView(
          padding: const EdgeInsets.all(16),
          children: children
              .map((e) =>
                  Padding(padding: const EdgeInsets.only(bottom: 12), child: e))
              .toList()));
}
