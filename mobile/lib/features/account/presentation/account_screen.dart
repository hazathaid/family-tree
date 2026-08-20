import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';

class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final l10n = AppLocalizations.of(context);
    return ListView(padding: const EdgeInsets.all(16), children: [
      ListTile(
          leading: CircleAvatar(
              backgroundImage: user?.avatarUrl == null
                  ? null
                  : NetworkImage(user!.avatarUrl!),
              child: user?.avatarUrl == null ? const Icon(Icons.person) : null),
          title: Text(user?.name ?? l10n.accountTitle),
          subtitle: Text(user?.email ?? ''),
          contentPadding: const EdgeInsets.all(8)),
      _tile(context, Icons.person_outline, l10n.profile, const ProfileScreen()),
      _tile(context, Icons.notifications_outlined, l10n.notificationPreferences,
          const PreferencesScreen()),
      _tile(context, Icons.security_outlined, l10n.securitySessions,
          const SecurityScreen()),
      ListTile(
          minTileHeight: 56,
          leading: const Icon(Icons.family_restroom),
          title: Text(l10n.switchFamily),
          onTap: () =>
              ref.read(sessionControllerProvider).requireFamilySelection()),
      ListTile(
          minTileHeight: 56,
          leading: const Icon(Icons.logout),
          title: Text(l10n.logout),
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
    final l10n = AppLocalizations.of(context);
    final image = await ImagePicker().pickImage(source: ImageSource.gallery);
    if (image == null) return;
    if (await image.length() > 5 * 1024 * 1024) {
      setState(() => error = l10n.avatarSizeLimit);
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
    final l10n = AppLocalizations.of(context);
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
            .showSnackBar(SnackBar(content: Text(l10n.profileUpdated)));
      }
    } on AppError catch (e) {
      setState(() => error = e.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return _Page(title: l10n.profile, children: [
        OutlinedButton.icon(
            onPressed: loading ? null : pickAvatar,
            icon: const Icon(Icons.photo_camera_outlined),
            label: Text(l10n.chooseAvatar)),
        TextField(
            controller: name,
            decoration: InputDecoration(labelText: l10n.nameLabel)),
        TextField(
            controller: email,
            decoration: InputDecoration(labelText: l10n.email)),
        TextField(
            controller: phone,
            decoration: InputDecoration(labelText: l10n.phone)),
        TextField(
            controller: currentPassword,
            obscureText: true,
            decoration: InputDecoration(labelText: l10n.currentPasswordLabel)),
        if (error != null)
          Text(error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error)),
        FilledButton(
            onPressed: loading ? null : save,
            child: Text(loading ? l10n.saving : l10n.save))
      ]);
  }
}

class PreferencesScreen extends ConsumerWidget {
  const PreferencesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.notificationPreferences)),
        body: ref.watch(notificationPreferencesProvider).when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (_, __) => Center(
                child: FilledButton(
                    onPressed: () =>
                        ref.invalidate(notificationPreferencesProvider),
                    child: Text(l10n.retry))),
            data: (value) => _PreferencesForm(value: value)));
  }
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return ListView(padding: const EdgeInsets.all(16), children: [
        SwitchListTile(
            title: Text(l10n.email),
            value: email,
            onChanged: (v) => setState(() => email = v)),
        SwitchListTile(
            title: Text(l10n.push),
            value: push,
            onChanged: (v) => setState(() => push = v)),
        SwitchListTile(
            title: Text(l10n.eventReminders),
            value: events,
            onChanged: (v) => setState(() => events = v)),
        SwitchListTile(
            title: Text(l10n.familyUpdates),
            value: family,
            onChanged: (v) => setState(() => family = v)),
        FilledButton(
            onPressed: loading ? null : save, child: Text(l10n.save))
      ]);
  }
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.security)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          TextField(
              controller: current,
              obscureText: true,
              decoration:
                  InputDecoration(labelText: l10n.currentPassword)),
          const SizedBox(height: 12),
          TextField(
              controller: password,
              obscureText: true,
              decoration: InputDecoration(labelText: l10n.newPassword)),
          const SizedBox(height: 12),
          FilledButton(
              onPressed: loading ? null : change,
              child: Text(l10n.changePassword)),
          const Divider(height: 32),
          Text(l10n.deviceSessions,
              style: Theme.of(context).textTheme.titleMedium),
          ref.watch(accountSessionsProvider).when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => FilledButton(
                    onPressed: () => ref.invalidate(accountSessionsProvider),
                    child: Text(l10n.reloadSessions)),
                data: (items) => Column(
                    children: items
                        .map((session) => ListTile(
                              minTileHeight: 56,
                              leading: const Icon(Icons.devices),
                              title: Text(session.deviceName),
                              subtitle: Text(session.isCurrent
                                  ? l10n.thisDevice
                                  : l10n.lastActive(
                                      '${session.lastActiveAt?.toLocal()}')),
                              trailing: IconButton(
                                  tooltip: l10n.revokeSession,
                                  icon: const Icon(Icons.logout),
                                  onPressed: () => revoke(session)),
                            ))
                        .toList()),
              ),
        ]),
      );
  }

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
