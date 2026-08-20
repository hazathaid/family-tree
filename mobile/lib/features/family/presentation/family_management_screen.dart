import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';

class FamilyManagementScreen extends ConsumerStatefulWidget {
  const FamilyManagementScreen({super.key});
  @override
  ConsumerState<FamilyManagementScreen> createState() =>
      _FamilyManagementScreenState();
}

class _FamilyManagementScreenState extends ConsumerState<FamilyManagementScreen>
    with SingleTickerProviderStateMixin {
  late final TabController tabs;
  @override
  void initState() {
    super.initState();
    tabs = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    if (family == null) {
      return Center(child: Text(l10n.selectFamilyFirst));
    }
    return Scaffold(
        appBar: AppBar(
            title: Text(l10n.manageFamilyTitle),
            bottom: TabBar(controller: tabs, tabs: [
              Tab(text: l10n.tabProfile),
              Tab(text: l10n.tabBranches),
              Tab(text: l10n.tabAccess)
            ])),
        body: TabBarView(controller: tabs, children: [
          _Settings(family: family),
          _Branches(family: family),
          _Access(family: family),
        ]));
  }
}

class _Settings extends ConsumerStatefulWidget {
  const _Settings({required this.family});
  final Family family;
  @override
  ConsumerState<_Settings> createState() => _SettingsState();
}

class _SettingsState extends ConsumerState<_Settings> {
  late final name = TextEditingController(text: widget.family.name),
      description = TextEditingController(text: widget.family.description),
      city = TextEditingController(text: widget.family.originCity);
  bool busy = false;
  Future<void> save() async {
    final l10n = AppLocalizations.of(context);
    setState(() => busy = true);
    try {
      final updated = await ref.read(familyRepositoryProvider).update(
          widget.family,
          name: name.text.trim(),
          description: description.text.trim(),
          originCity: city.text.trim());
      ref.read(currentFamilyProvider.notifier).state = updated;
      ref.invalidate(familiesProvider);
      _notice(l10n.familySettingsSaved);
    } on AppError catch (e) {
      _notice(e.message);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> pick(bool logo) async {
    final l10n = AppLocalizations.of(context);
    final file = await ImagePicker().pickImage(
        source: ImageSource.gallery,
        maxWidth: logo ? 800 : 2000,
        imageQuality: 88);
    if (file == null) return;
    setState(() => busy = true);
    try {
      final updated = await ref.read(familyRepositoryProvider).uploadAssets(
          widget.family.uuid,
          logoPath: logo ? file.path : null,
          coverPath: logo ? null : file.path);
      ref.read(currentFamilyProvider.notifier).state = updated;
      _notice(logo ? l10n.logoUpdated : l10n.coverUpdated);
    } on AppError catch (e) {
      _notice(e.message);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  void _notice(String value) {
    if (mounted) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(value)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return ListView(padding: const EdgeInsets.all(16), children: [
        ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.lock),
            title: Text(l10n.familyPrivacyTitle),
            subtitle: Text(l10n.familyPrivacyBody)),
        TextField(
            controller: name,
            enabled: widget.family.canManage,
            decoration: InputDecoration(labelText: l10n.familyName)),
        const SizedBox(height: 12),
        TextField(
            controller: city,
            enabled: widget.family.canManage,
            decoration: InputDecoration(labelText: l10n.originCity)),
        const SizedBox(height: 12),
        TextField(
            controller: description,
            enabled: widget.family.canManage,
            maxLines: 3,
            decoration: InputDecoration(labelText: l10n.description)),
        const SizedBox(height: 16),
        if (widget.family.canManage)
          Wrap(spacing: 8, runSpacing: 8, children: [
            OutlinedButton.icon(
                onPressed: busy ? null : () => pick(true),
                icon: const Icon(Icons.image),
                label: Text(l10n.replaceLogo)),
            OutlinedButton.icon(
                onPressed: busy ? null : () => pick(false),
                icon: const Icon(Icons.panorama),
                label: Text(l10n.replaceCover)),
            FilledButton(
                onPressed: busy ? null : save,
                child: Text(busy ? l10n.saving : l10n.save))
          ]),
      ]);
  }
}

class _Branches extends ConsumerStatefulWidget {
  const _Branches({required this.family});
  final Family family;
  @override
  ConsumerState<_Branches> createState() => _BranchesState();
}

class _BranchesState extends ConsumerState<_Branches> {
  late Future<List<FamilyBranch>> future = load();
  Future<List<FamilyBranch>> load() =>
      ref.read(familyRepositoryProvider).branches(widget.family.uuid);
  void refresh() => setState(() => future = load());
  Future<void> edit([FamilyBranch? branch]) async {
    final l10n = AppLocalizations.of(context);
    final name = TextEditingController(text: branch?.name),
        description = TextEditingController(text: branch?.description);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(branch == null ? l10n.addBranch : l10n.editBranch),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(
                      controller: name,
                      decoration: InputDecoration(labelText: l10n.nameLabel)),
                  TextField(
                      controller: description,
                      decoration:
                          InputDecoration(labelText: l10n.description))
                ]),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text(l10n.save))
                ]));
    if (ok != true) return;
    try {
      if (branch == null) {
        await ref.read(familyRepositoryProvider).createBranch(
            widget.family.uuid, name.text.trim(), description.text.trim());
      } else {
        await ref.read(familyRepositoryProvider).updateBranch(
            widget.family.uuid,
            branch,
            name.text.trim(),
            description.text.trim());
      }
      refresh();
    } on AppError catch (e) {
      _error(e.message);
    }
  }

  Future<void> remove(FamilyBranch branch) async {
    final l10n = AppLocalizations.of(context);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(l10n.deleteBranchTitle(branch.name)),
                content: Text(l10n.deleteBranchConfirmation),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text(l10n.delete))
                ]));
    if (ok != true) return;
    try {
      await ref
          .read(familyRepositoryProvider)
          .deleteBranch(widget.family.uuid, branch.uuid);
      refresh();
    } on AppError catch (e) {
      _error(e.message);
    }
  }

  void _error(String message) => ScaffoldMessenger.of(context)
      .showSnackBar(SnackBar(content: Text(message)));
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return FutureBuilder(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
              child: FilledButton(
                  onPressed: refresh, child: Text(l10n.retry)));
        }
        final items = snapshot.data ?? const <FamilyBranch>[];
        return ListView(padding: const EdgeInsets.all(16), children: [
          if (widget.family.canManage)
            Align(
                alignment: Alignment.centerRight,
                child: FilledButton.icon(
                    onPressed: () => edit(),
                    icon: const Icon(Icons.add),
                    label: Text(l10n.addBranch))),
          if (items.isEmpty)
            ListTile(title: Text(l10n.noBranches))
          else
            ...items.map((branch) => Card(
                child: ListTile(
                    title: Text(branch.name),
                    subtitle: Text(branch.description ?? ''),
                    trailing: widget.family.canManage
                        ? PopupMenuButton(
                            itemBuilder: (_) => [
                                  PopupMenuItem(
                                      value: 'edit',
                                      child: Text(l10n.editLabel)),
                                  PopupMenuItem(
                                      value: 'delete',
                                      child: Text(l10n.delete))
                                ],
                            onSelected: (value) =>
                                value == 'edit' ? edit(branch) : remove(branch))
                        : null)))
        ]);
      });
  }
}

class _Access extends ConsumerStatefulWidget {
  const _Access({required this.family});
  final Family family;
  @override
  ConsumerState<_Access> createState() => _AccessState();
}

class _AccessState extends ConsumerState<_Access> {
  late Future<List<FamilyMembership>> future = load();
  Future<List<FamilyMembership>> load() =>
      ref.read(familyRepositoryProvider).memberships(widget.family.uuid);
  void refresh() => setState(() => future = load());
  Future<void> invite() async {
    final l10n = AppLocalizations.of(context);
    final email = TextEditingController();
    var role = 'member';
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => StatefulBuilder(
            builder: (context, setDialog) => AlertDialog(
                    title: Text(l10n.inviteMember),
                    content: Column(mainAxisSize: MainAxisSize.min, children: [
                      TextField(
                          controller: email,
                          keyboardType: TextInputType.emailAddress,
                          decoration: InputDecoration(labelText: l10n.email)),
                      DropdownButtonFormField(
                          initialValue: role,
                          items: [
                            DropdownMenuItem(
                                value: 'member',
                                child: Text(l10n.roleMember)),
                            DropdownMenuItem(
                                value: 'admin', child: Text(l10n.roleAdmin)),
                            DropdownMenuItem(
                                value: 'owner', child: Text(l10n.roleOwner))
                          ],
                          onChanged: (value) => setDialog(() => role = value!))
                    ]),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: Text(l10n.cancel)),
                      FilledButton(
                          onPressed: () => Navigator.pop(context, true),
                          child: Text(l10n.invite))
                    ])));
    if (ok != true) return;
    try {
      await ref
          .read(familyRepositoryProvider)
          .invite(widget.family.uuid, email.text.trim(), role);
      refresh();
    } on AppError catch (e) {
      _error(e.message);
    }
  }

  Future<void> role(FamilyMembership member, String value) async {
    try {
      await ref
          .read(familyRepositoryProvider)
          .assignRole(widget.family.uuid, member.uuid, value);
      refresh();
    } on AppError catch (e) {
      _error(e.message);
    }
  }

  Future<void> remove(FamilyMembership member) async {
    final l10n = AppLocalizations.of(context);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(l10n.deleteAccessTitle(member.user.name)),
                content: Text(l10n.deleteOwnerConfirmation),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text(l10n.delete))
                ]));
    if (ok != true) return;
    try {
      await ref
          .read(familyRepositoryProvider)
          .removeMembership(widget.family.uuid, member.uuid);
      refresh();
    } on AppError catch (e) {
      _error(e.message);
    }
  }

  void _error(String message) => ScaffoldMessenger.of(context)
      .showSnackBar(SnackBar(content: Text(message)));
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    if (!widget.family.canManageRoles) {
      return Center(
          child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(l10n.ownerOnlyAccess)));
    }
    return FutureBuilder<List<FamilyMembership>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
                child: FilledButton(
                    onPressed: refresh, child: Text(l10n.retry)));
          }
          final items = snapshot.data ?? const <FamilyMembership>[];
          return ListView(padding: const EdgeInsets.all(16), children: [
            Align(
                alignment: Alignment.centerRight,
                child: FilledButton.icon(
                    onPressed: invite,
                    icon: const Icon(Icons.person_add),
                    label: Text(l10n.invite))),
            ...items.map((member) => Card(
                child: ListTile(
                    title: Text(member.user.name),
                    subtitle: Text(member.user.email),
                    trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                      DropdownButton<String>(
                          value: member.role,
                          items: [
                            DropdownMenuItem(
                                value: 'member',
                                child: Text(l10n.roleMember)),
                            DropdownMenuItem(
                                value: 'admin', child: Text(l10n.roleAdmin)),
                            DropdownMenuItem(
                                value: 'owner', child: Text(l10n.roleOwner))
                          ],
                          onChanged: (value) {
                            if (value != null) role(member, value);
                          }),
                      IconButton(
                          tooltip: l10n.deleteAccess,
                          onPressed: () => remove(member),
                          icon: const Icon(Icons.delete_outline))
                    ])))),
          ]);
        });
  }
}
