import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';

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
    if (family == null) {
      return const Center(child: Text('Pilih keluarga terlebih dahulu.'));
    }
    return Scaffold(
        appBar: AppBar(
            title: const Text('Kelola keluarga'),
            bottom: TabBar(controller: tabs, tabs: const [
              Tab(text: 'Profil'),
              Tab(text: 'Cabang'),
              Tab(text: 'Akses')
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
    setState(() => busy = true);
    try {
      final updated = await ref.read(familyRepositoryProvider).update(
          widget.family,
          name: name.text.trim(),
          description: description.text.trim(),
          originCity: city.text.trim());
      ref.read(currentFamilyProvider.notifier).state = updated;
      ref.invalidate(familiesProvider);
      _notice('Pengaturan keluarga disimpan.');
    } on AppError catch (e) {
      _notice(e.message);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> pick(bool logo) async {
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
      _notice(logo ? 'Logo diperbarui.' : 'Sampul diperbarui.');
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
  Widget build(BuildContext context) =>
      ListView(padding: const EdgeInsets.all(16), children: [
        const ListTile(
            contentPadding: EdgeInsets.zero,
            leading: Icon(Icons.lock),
            title: Text('Privasi: hanya anggota keluarga'),
            subtitle: Text(
                'Privasi keluarga mengikuti keanggotaan dan tidak dapat diubah. Preferensi notifikasi dikelola per akun.')),
        TextField(
            controller: name,
            enabled: widget.family.canManage,
            decoration: const InputDecoration(labelText: 'Nama keluarga')),
        const SizedBox(height: 12),
        TextField(
            controller: city,
            enabled: widget.family.canManage,
            decoration: const InputDecoration(labelText: 'Kota asal')),
        const SizedBox(height: 12),
        TextField(
            controller: description,
            enabled: widget.family.canManage,
            maxLines: 3,
            decoration: const InputDecoration(labelText: 'Deskripsi')),
        const SizedBox(height: 16),
        if (widget.family.canManage)
          Wrap(spacing: 8, runSpacing: 8, children: [
            OutlinedButton.icon(
                onPressed: busy ? null : () => pick(true),
                icon: const Icon(Icons.image),
                label: const Text('Ganti logo')),
            OutlinedButton.icon(
                onPressed: busy ? null : () => pick(false),
                icon: const Icon(Icons.panorama),
                label: const Text('Ganti sampul')),
            FilledButton(
                onPressed: busy ? null : save,
                child: Text(busy ? 'Menyimpan…' : 'Simpan'))
          ]),
      ]);
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
    final name = TextEditingController(text: branch?.name),
        description = TextEditingController(text: branch?.description);
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(branch == null ? 'Tambah cabang' : 'Ubah cabang'),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(
                      controller: name,
                      decoration: const InputDecoration(labelText: 'Nama')),
                  TextField(
                      controller: description,
                      decoration: const InputDecoration(labelText: 'Deskripsi'))
                ]),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Simpan'))
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
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text('Hapus ${branch.name}?'),
                content: const Text(
                    'Cabang akan dihapus. Anggota yang terkait tetap dipertahankan sesuai aturan server.'),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Hapus'))
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
  Widget build(BuildContext context) => FutureBuilder(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
              child: FilledButton(
                  onPressed: refresh, child: const Text('Coba lagi')));
        }
        final items = snapshot.data ?? const <FamilyBranch>[];
        return ListView(padding: const EdgeInsets.all(16), children: [
          if (widget.family.canManage)
            Align(
                alignment: Alignment.centerRight,
                child: FilledButton.icon(
                    onPressed: () => edit(),
                    icon: const Icon(Icons.add),
                    label: const Text('Cabang'))),
          if (items.isEmpty)
            const ListTile(title: Text('Belum ada cabang.'))
          else
            ...items.map((branch) => Card(
                child: ListTile(
                    title: Text(branch.name),
                    subtitle: Text(branch.description ?? ''),
                    trailing: widget.family.canManage
                        ? PopupMenuButton(
                            itemBuilder: (_) => const [
                                  PopupMenuItem(
                                      value: 'edit', child: Text('Ubah')),
                                  PopupMenuItem(
                                      value: 'delete', child: Text('Hapus'))
                                ],
                            onSelected: (value) =>
                                value == 'edit' ? edit(branch) : remove(branch))
                        : null)))
        ]);
      });
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
    final email = TextEditingController();
    var role = 'member';
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => StatefulBuilder(
            builder: (context, setDialog) => AlertDialog(
                    title: const Text('Undang anggota'),
                    content: Column(mainAxisSize: MainAxisSize.min, children: [
                      TextField(
                          controller: email,
                          keyboardType: TextInputType.emailAddress,
                          decoration:
                              const InputDecoration(labelText: 'Email')),
                      DropdownButtonFormField(
                          initialValue: role,
                          items: const [
                            DropdownMenuItem(
                                value: 'member', child: Text('Member')),
                            DropdownMenuItem(
                                value: 'admin', child: Text('Admin')),
                            DropdownMenuItem(
                                value: 'owner', child: Text('Owner'))
                          ],
                          onChanged: (value) => setDialog(() => role = value!))
                    ]),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: const Text('Batal')),
                      FilledButton(
                          onPressed: () => Navigator.pop(context, true),
                          child: const Text('Undang'))
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
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text('Hapus akses ${member.user.name}?'),
                content: const Text('Pemilik terakhir tidak dapat dihapus.'),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Hapus'))
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
    if (!widget.family.canManageRoles) {
      return const Center(
          child: Padding(
              padding: EdgeInsets.all(24),
              child:
                  Text('Hanya pemilik yang dapat mengelola akses keluarga.')));
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
                    onPressed: refresh, child: const Text('Coba lagi')));
          }
          final items = snapshot.data ?? const <FamilyMembership>[];
          return ListView(padding: const EdgeInsets.all(16), children: [
            Align(
                alignment: Alignment.centerRight,
                child: FilledButton.icon(
                    onPressed: invite,
                    icon: const Icon(Icons.person_add),
                    label: const Text('Undang'))),
            ...items.map((member) => Card(
                child: ListTile(
                    title: Text(member.user.name),
                    subtitle: Text(member.user.email),
                    trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                      DropdownButton<String>(
                          value: member.role,
                          items: const [
                            DropdownMenuItem(
                                value: 'member', child: Text('Member')),
                            DropdownMenuItem(
                                value: 'admin', child: Text('Admin')),
                            DropdownMenuItem(
                                value: 'owner', child: Text('Owner'))
                          ],
                          onChanged: (value) {
                            if (value != null) role(member, value);
                          }),
                      IconButton(
                          tooltip: 'Hapus akses',
                          onPressed: () => remove(member),
                          icon: const Icon(Icons.delete_outline))
                    ])))),
          ]);
        });
  }
}
