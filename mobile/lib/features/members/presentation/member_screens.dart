import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/http/page_data.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';

class MemberDirectoryScreen extends ConsumerStatefulWidget {
  const MemberDirectoryScreen({super.key});
  @override
  ConsumerState<MemberDirectoryScreen> createState() =>
      _MemberDirectoryScreenState();
}

class _MemberDirectoryScreenState extends ConsumerState<MemberDirectoryScreen> {
  final search = TextEditingController();
  PageData<FamilyMember>? data;
  Object? error;
  bool loading = true;
  String? gender;
  bool? alive;
  String? branch;
  String sort = 'name';

  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  Future<void> load([int page = 1]) async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final result = await ref.read(memberRepositoryProvider).members(
          family.uuid,
          page: page,
          search: search.text.trim(),
          gender: gender,
          isAlive: alive,
          branchUuid: branch,
          sort: sort);
      if (mounted) {
        setState(() {
          data = result;
          loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = e;
          loading = false;
        });
      }
    }
  }

  Future<void> filters() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    final branches =
        await ref.read(familyRepositoryProvider).branches(family.uuid);
    if (!mounted) return;
    await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        builder: (context) => StatefulBuilder(
            builder: (context, setSheet) => Padding(
                padding: EdgeInsets.fromLTRB(
                    16, 16, 16, MediaQuery.viewInsetsOf(context).bottom + 24),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  const Text('Filter anggota',
                      style:
                          TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  DropdownButtonFormField<String?>(
                      initialValue: gender,
                      decoration: const InputDecoration(labelText: 'Gender'),
                      items: const [
                        DropdownMenuItem(value: null, child: Text('Semua')),
                        DropdownMenuItem(
                            value: 'male', child: Text('Laki-laki')),
                        DropdownMenuItem(
                            value: 'female', child: Text('Perempuan'))
                      ],
                      onChanged: (v) => setSheet(() => gender = v)),
                  DropdownButtonFormField<bool?>(
                      initialValue: alive,
                      decoration: const InputDecoration(labelText: 'Status'),
                      items: const [
                        DropdownMenuItem(value: null, child: Text('Semua')),
                        DropdownMenuItem(value: true, child: Text('Hidup')),
                        DropdownMenuItem(value: false, child: Text('Meninggal'))
                      ],
                      onChanged: (v) => setSheet(() => alive = v)),
                  DropdownButtonFormField<String?>(
                      initialValue: branch,
                      decoration: const InputDecoration(labelText: 'Cabang'),
                      items: [
                        const DropdownMenuItem(
                            value: null, child: Text('Semua')),
                        ...branches.map((b) => DropdownMenuItem(
                            value: b.uuid, child: Text(b.name)))
                      ],
                      onChanged: (v) => setSheet(() => branch = v)),
                  DropdownButtonFormField<String>(
                      initialValue: sort,
                      decoration: const InputDecoration(labelText: 'Urutkan'),
                      items: const [
                        DropdownMenuItem(
                            value: 'name', child: Text('Nama A–Z')),
                        DropdownMenuItem(
                            value: 'name_desc', child: Text('Nama Z–A')),
                        DropdownMenuItem(
                            value: 'newest', child: Text('Terbaru')),
                        DropdownMenuItem(
                            value: 'oldest', child: Text('Terlama'))
                      ],
                      onChanged: (v) => setSheet(() => sort = v!)),
                  const SizedBox(height: 16),
                  SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                          onPressed: () {
                            Navigator.pop(context);
                            load();
                          },
                          child: const Text('Terapkan'))),
                ]))));
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final canManage = family?.canManage ?? false;
    return Scaffold(
        appBar: AppBar(title: const Text('Anggota keluarga'), actions: [
          IconButton(
              tooltip: 'Kelola relationship',
              onPressed: () => context.push('/relationships'),
              icon: const Icon(Icons.device_hub)),
          IconButton(
              tooltip: 'Resolver relationship',
              onPressed: () => context.push('/relationship-resolver'),
              icon: const Icon(Icons.route))
        ]),
        floatingActionButton: canManage
            ? FloatingActionButton(
                onPressed: () async {
                  await context.push('/members/new');
                  load();
                },
                tooltip: 'Tambah anggota',
                child: const Icon(Icons.person_add))
            : null,
        body: Column(children: [
          Padding(
              padding: const EdgeInsets.all(16),
              child: Row(children: [
                Expanded(
                    child: SearchBar(
                        controller: search,
                        hintText: 'Cari nama atau panggilan',
                        leading: const Icon(Icons.search),
                        onSubmitted: (_) => load())),
                const SizedBox(width: 8),
                IconButton.filledTonal(
                    onPressed: filters,
                    tooltip: 'Filter dan urutkan',
                    icon: const Icon(Icons.tune))
              ])),
          Expanded(child: _body(canManage)),
        ]));
  }

  Widget _body(bool canManage) {
    if (loading && data == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: load,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi')));
    }
    final page = data;
    if (page == null || page.items.isEmpty) {
      return const Center(child: Text('Belum ada anggota yang sesuai.'));
    }
    final tablet = MediaQuery.sizeOf(context).width >= 600;
    final content = tablet
        ? SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
                columns: const [
                  DataColumn(label: Text('Nama')),
                  DataColumn(label: Text('Gender')),
                  DataColumn(label: Text('Status')),
                  DataColumn(label: Text('Cabang'))
                ],
                rows: page.items
                    .map((m) => DataRow(
                            onSelectChanged: (_) =>
                                context.push('/members/${m.uuid}'),
                            cells: [
                              DataCell(Text(m.displayName)),
                              DataCell(Text(_gender(m.gender))),
                              DataCell(Text(m.isAlive ? 'Hidup' : 'Meninggal')),
                              DataCell(Text(m.branchName ?? '—'))
                            ]))
                    .toList()))
        : ListView.builder(
            itemCount: page.items.length,
            itemBuilder: (_, i) {
              final m = page.items[i];
              return Card(
                  margin:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  child: ListTile(
                      minTileHeight: 64,
                      leading: _MemberAvatar(member: m),
                      title: Text(m.displayName),
                      subtitle: Text(
                          '${_gender(m.gender)} · ${m.isAlive ? 'Hidup' : 'Meninggal'}'),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () => context.push('/members/${m.uuid}')));
            });
    return Column(children: [
      Expanded(
          child: RefreshIndicator(
              onRefresh: () => load(page.currentPage), child: content)),
      Padding(
          padding: const EdgeInsets.all(12),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            IconButton(
                tooltip: 'Halaman sebelumnya',
                onPressed: page.currentPage > 1
                    ? () => load(page.currentPage - 1)
                    : null,
                icon: const Icon(Icons.chevron_left)),
            Text('Halaman ${page.currentPage} dari ${page.lastPage}'),
            IconButton(
                tooltip: 'Halaman berikutnya',
                onPressed:
                    page.hasMore ? () => load(page.currentPage + 1) : null,
                icon: const Icon(Icons.chevron_right))
          ]))
    ]);
  }
}

class MemberDetailScreen extends ConsumerWidget {
  const MemberDetailScreen({required this.uuid, super.key});
  final String uuid;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final familyUuid = ref.read(currentFamilyProvider)!.uuid;
    return FutureBuilder<List<Object>>(
      future: Future.wait<Object>([
        ref.read(memberRepositoryProvider).member(uuid),
        ref
            .read(memberRepositoryProvider)
            .relationships(familyUuid, memberUuid: uuid),
      ]),
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Scaffold(
              body: Center(child: CircularProgressIndicator()));
        }
        if (snapshot.hasError) {
          return Scaffold(
              appBar: AppBar(),
              body: const Center(
                  child: Text('Detail anggota tidak dapat dimuat.')));
        }
        final member = snapshot.data![0] as FamilyMember;
        final relations = snapshot.data![1] as PageData<MemberRelationship>;
        final canManage = ref.read(currentFamilyProvider)?.canManage ?? false;
        return Scaffold(
          appBar: AppBar(title: Text(member.displayName), actions: [
            if (canManage)
              IconButton(
                  tooltip: 'Edit anggota',
                  onPressed: () => context.push('/members/${member.uuid}/edit',
                      extra: member),
                  icon: const Icon(Icons.edit)),
          ]),
          body: ListView(padding: const EdgeInsets.all(16), children: [
            Center(child: _MemberAvatar(member: member, large: true)),
            if (member.relationshipToViewer != null)
              Center(
                  child: Chip(
                      avatar: const Icon(Icons.family_restroom, size: 18),
                      label: Text('${member.relationshipToViewer} untuk Anda'),
                      backgroundColor:
                          Theme.of(context).colorScheme.primaryContainer,
                      labelStyle: TextStyle(
                          color: Theme.of(context).colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.w600))),
            if (!member.isAlive)
              const Center(
                  child: Chip(
                      avatar: Icon(Icons.local_florist, size: 18),
                      label: Text('Dalam kenangan'))),
            const SizedBox(height: 16),
            _Section(title: 'Informasi dasar', children: [
              _Info('Nama lengkap', member.fullName),
              _Info('Nama panggilan', member.nickname),
              _Info('Gender', _gender(member.gender)),
              _Info('Agama/kepercayaan', _religion(member.religion)),
              _Info('Lahir', _datePlace(member.birthDate, member.birthPlace)),
              if (!member.isAlive)
                _Info('Wafat', _datePlace(member.deathDate, member.deathPlace))
            ]),
            _Section(title: 'Keluarga', children: [
              _Info('Cabang', member.branchName ?? 'Tidak ada cabang')
            ]),
            _Section(title: 'Biografi', children: [
              Text(member.biography?.isNotEmpty == true
                  ? member.biography!
                  : 'Belum ada biografi.')
            ]),
            _Section(
                title: 'Relationship dasar',
                children: relations.items.isEmpty
                    ? [const Text('Belum ada relationship dasar.')]
                    : relations.items
                        .map((r) => ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.device_hub),
                            title: Text(r.sourceUuid == uuid
                                ? r.targetName
                                : r.sourceName),
                            subtitle: Text(r.type)))
                        .toList()),
            const _Section(title: 'Konten terkait', children: [
              ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(Icons.photo_outlined),
                  title: Text('Foto terkait'),
                  subtitle:
                      Text('Belum ada konten terkait untuk ditampilkan.')),
              ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(Icons.article_outlined),
                  title: Text('Artikel terkait'),
                  subtitle: Text('Belum ada konten terkait untuk ditampilkan.'))
            ]),
          ]),
        );
      },
    );
  }
}

class MemberFormScreen extends ConsumerStatefulWidget {
  const MemberFormScreen({this.member, super.key});
  final FamilyMember? member;
  @override
  ConsumerState<MemberFormScreen> createState() => _MemberFormScreenState();
}

class _MemberFormScreenState extends ConsumerState<MemberFormScreen> {
  final form = GlobalKey<FormState>();
  late final name = TextEditingController(text: widget.member?.fullName);
  late final nickname = TextEditingController(text: widget.member?.nickname);
  late final birthPlace =
      TextEditingController(text: widget.member?.birthPlace);
  late final deathPlace =
      TextEditingController(text: widget.member?.deathPlace);
  late final biography = TextEditingController(text: widget.member?.biography);
  late final birthDate =
      TextEditingController(text: _iso(widget.member?.birthDate));
  late final deathDate =
      TextEditingController(text: _iso(widget.member?.deathDate));
  String? gender;
  String? religion;
  String? branch;
  bool alive = true;
  bool saving = false;
  Map<String, List<String>> errors = const {};
  @override
  void initState() {
    super.initState();
    gender = widget.member?.gender;
    religion = widget.member?.religion;
    branch = widget.member?.branchUuid;
    alive = widget.member?.isAlive ?? true;
  }

  Future<void> save() async {
    if (!form.currentState!.validate()) return;
    setState(() => saving = true);
    final values = {
      'family_branch_uuid': branch,
      'full_name': name.text.trim(),
      'nickname': _null(nickname.text),
      'gender': gender,
      'religion': religion,
      'birth_date': _null(birthDate.text),
      'birth_place': _null(birthPlace.text),
      'is_alive': alive,
      'death_date': alive ? null : _null(deathDate.text),
      'death_place': alive ? null : _null(deathPlace.text),
      'biography': _null(biography.text)
    };
    try {
      final repo = ref.read(memberRepositoryProvider);
      final family = ref.read(currentFamilyProvider)!;
      final saved = widget.member == null
          ? await repo.create(family.uuid, values)
          : await repo.update(widget.member!.uuid, values);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Anggota berhasil disimpan.')));
        context.go('/members/${saved.uuid}');
      }
    } on AppError catch (e) {
      if (mounted) {
        setState(() {
          saving = false;
          errors = e.fieldErrors;
        });
      }
    }
  }

  Future<void> photo() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery);
    if (picked == null || widget.member == null) return;
    try {
      await ref
          .read(memberRepositoryProvider)
          .uploadPhoto(widget.member!.uuid, picked.path);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Foto berhasil diperbarui.')));
      }
    } on AppError catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  Future<void> remove() async {
    final ok = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text('Hapus ${widget.member!.fullName}?'),
                content: const Text(
                    'Anggota akan dihapus secara lunak dan tidak tampil lagi.'),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Batal')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Hapus'))
                ]));
    if (ok == true) {
      await ref
          .read(memberRepositoryProvider)
          .deleteMember(widget.member!.uuid);
      if (mounted) context.go('/members');
    }
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    return Scaffold(
        appBar: AppBar(
            title: Text(
                widget.member == null ? 'Tambah anggota' : 'Edit anggota')),
        body: FutureBuilder(
            future: ref.read(familyRepositoryProvider).branches(family!.uuid),
            builder: (context, snapshot) {
              final branches = snapshot.data ?? const <FamilyBranch>[];
              return Form(
                  key: form,
                  child: ListView(padding: const EdgeInsets.all(16), children: [
                    TextFormField(
                        controller: name,
                        decoration: InputDecoration(
                            labelText: 'Nama lengkap',
                            errorText: errors['full_name']?.first),
                        validator: (v) => v?.trim().isEmpty == true
                            ? 'Nama lengkap wajib diisi.'
                            : null),
                    TextFormField(
                        controller: nickname,
                        decoration:
                            const InputDecoration(labelText: 'Nama panggilan')),
                    DropdownButtonFormField<String?>(
                        initialValue: gender,
                        decoration: const InputDecoration(labelText: 'Gender'),
                        items: const [
                          DropdownMenuItem(
                              value: null, child: Text('Tidak ditentukan')),
                          DropdownMenuItem(
                              value: 'male', child: Text('Laki-laki')),
                          DropdownMenuItem(
                              value: 'female', child: Text('Perempuan'))
                        ],
                        onChanged: (v) => setState(() => gender = v)),
                    DropdownButtonFormField<String?>(
                        initialValue: religion,
                        decoration: InputDecoration(
                            labelText: 'Agama/kepercayaan',
                            errorText: errors['religion']?.first),
                        items: const [
                          DropdownMenuItem(
                              value: null, child: Text('Belum ditentukan')),
                          DropdownMenuItem(value: 'islam', child: Text('Islam')),
                          DropdownMenuItem(
                              value: 'christian', child: Text('Kristen')),
                          DropdownMenuItem(
                              value: 'catholic', child: Text('Katolik')),
                          DropdownMenuItem(value: 'hindu', child: Text('Hindu')),
                          DropdownMenuItem(
                              value: 'buddhist', child: Text('Buddha')),
                          DropdownMenuItem(
                              value: 'confucian', child: Text('Konghucu')),
                          DropdownMenuItem(
                              value: 'belief',
                              child: Text('Penghayat kepercayaan')),
                          DropdownMenuItem(
                              value: 'other', child: Text('Lainnya'))
                        ],
                        onChanged: (v) => setState(() => religion = v)),
                    DropdownButtonFormField<String?>(
                        initialValue: branch,
                        decoration: const InputDecoration(labelText: 'Cabang'),
                        items: [
                          const DropdownMenuItem(
                              value: null, child: Text('Tanpa cabang')),
                          ...branches.map((b) => DropdownMenuItem(
                              value: b.uuid, child: Text(b.name)))
                        ],
                        onChanged: (v) => setState(() => branch = v)),
                    TextFormField(
                        controller: birthDate,
                        decoration: const InputDecoration(
                            labelText: 'Tanggal lahir (YYYY-MM-DD)')),
                    TextFormField(
                        controller: birthPlace,
                        decoration:
                            const InputDecoration(labelText: 'Tempat lahir')),
                    SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Masih hidup'),
                        value: alive,
                        onChanged: (v) => setState(() => alive = v)),
                    if (!alive) ...[
                      TextFormField(
                          controller: deathDate,
                          decoration: InputDecoration(
                              labelText: 'Tanggal wafat (YYYY-MM-DD)',
                              errorText: errors['death_date']?.first)),
                      TextFormField(
                          controller: deathPlace,
                          decoration:
                              const InputDecoration(labelText: 'Tempat wafat'))
                    ],
                    TextFormField(
                        controller: biography,
                        maxLines: 5,
                        decoration:
                            const InputDecoration(labelText: 'Biografi')),
                    const SizedBox(height: 20),
                    FilledButton(
                        onPressed: saving ? null : save,
                        child: Text(saving ? 'Menyimpan…' : 'Simpan')),
                    if (widget.member != null) ...[
                      OutlinedButton.icon(
                          onPressed: photo,
                          icon: const Icon(Icons.photo_camera),
                          label: const Text('Ganti foto')),
                      TextButton(
                          onPressed: remove,
                          child: const Text('Hapus anggota',
                              style: TextStyle(color: Colors.red)))
                    ]
                  ]));
            }));
  }
}

class RelationshipManagementScreen extends ConsumerStatefulWidget {
  const RelationshipManagementScreen({super.key});
  @override
  ConsumerState<RelationshipManagementScreen> createState() =>
      _RelationshipManagementScreenState();
}

class _RelationshipManagementScreenState
    extends ConsumerState<RelationshipManagementScreen> {
  late Future<PageData<MemberRelationship>> future = load();
  Future<PageData<MemberRelationship>> load() => ref
      .read(memberRepositoryProvider)
      .relationships(ref.read(currentFamilyProvider)!.uuid);
  void refresh() => setState(() => future = load());
  @override
  Widget build(BuildContext context) {
    final canManage = ref.watch(currentFamilyProvider)?.canManage ?? false;
    return Scaffold(
        appBar: AppBar(title: const Text('Relationship dasar')),
        floatingActionButton: canManage
            ? FloatingActionButton(
                onPressed: () async {
                  await _relationshipDialog(context, ref);
                  refresh();
                },
                tooltip: 'Tambah relationship',
                child: const Icon(Icons.add))
            : null,
        body: FutureBuilder(
            future: future,
            builder: (_, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return Center(
                    child: FilledButton(
                        onPressed: refresh, child: const Text('Coba lagi')));
              }
              final items = snapshot.data!.items;
              if (items.isEmpty) {
                return const Center(
                    child: Text('Belum ada relationship dasar.'));
              }
              return ListView(
                  children: items
                      .map((r) => ListTile(
                          title: Text('${r.sourceName} → ${r.targetName}'),
                          subtitle: Text(r.type),
                          trailing: canManage
                              ? PopupMenuButton<String>(
                                  onSelected: (action) async {
                                    if (action == 'edit') {
                                      await _relationshipDialog(context, ref,
                                          relationship: r);
                                    } else {
                                      await ref
                                          .read(memberRepositoryProvider)
                                          .deleteRelationship(r.uuid);
                                    }
                                    refresh();
                                  },
                                  itemBuilder: (_) => const [
                                        PopupMenuItem(
                                            value: 'edit', child: Text('Edit')),
                                        PopupMenuItem(
                                            value: 'delete',
                                            child: Text('Hapus'))
                                      ])
                              : null))
                      .toList());
            }));
  }
}

class RelationshipResolverScreen extends ConsumerStatefulWidget {
  const RelationshipResolverScreen({super.key});
  @override
  ConsumerState<RelationshipResolverScreen> createState() =>
      _RelationshipResolverScreenState();
}

class _RelationshipResolverScreenState
    extends ConsumerState<RelationshipResolverScreen> {
  String? source;
  String? target;
  String? sourceName;
  String? targetName;
  RelationshipResolution? result;
  bool loading = false;
  Future<void> resolve() async {
    if (source == null || target == null) return;
    setState(() => loading = true);
    try {
      final value =
          await ref.read(memberRepositoryProvider).resolve(source!, target!);
      if (mounted) {
        setState(() {
          result = value;
          loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final familyUuid = ref.read(currentFamilyProvider)!.uuid;
    return Scaffold(
        appBar: AppBar(title: const Text('Resolver relationship')),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          _memberPickerTile('Anggota sumber', sourceName, () async {
            final member = await _selectMember(context, familyUuid);
            if (member != null) {
              setState(() {
                source = member.uuid;
                sourceName = member.displayName;
              });
            }
          }),
          _memberPickerTile('Anggota tujuan', targetName, () async {
            final member = await _selectMember(context, familyUuid);
            if (member != null) {
              setState(() {
                target = member.uuid;
                targetName = member.displayName;
              });
            }
          }),
          FilledButton.icon(
              onPressed:
                  loading || source == null || target == null ? null : resolve,
              icon: const Icon(Icons.route),
              label: Text(loading ? 'Menghitung…' : 'Temukan relationship')),
          if (result != null)
            Card(
                child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(result!.relationship ?? 'Tidak terhubung',
                              style: Theme.of(context).textTheme.headlineSmall),
                          const SizedBox(height: 8),
                          if (result!.path.isEmpty)
                            Text(result!.isConnected
                                ? 'Anggota yang sama.'
                                : 'Tidak ditemukan jalur relationship.')
                          else
                            ...result!.path.map((s) => ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: const Icon(Icons.arrow_forward),
                                title: Text('${s.fromName} → ${s.toName}'),
                                subtitle: Text(s.relationship)))
                        ])))
        ]));
  }
}

Future<void> _relationshipDialog(BuildContext context, WidgetRef ref,
    {MemberRelationship? relationship}) async {
  final family = ref.read(currentFamilyProvider)!;
  String? source = relationship?.sourceUuid;
  String? target = relationship?.targetUuid;
  String? sourceName = relationship?.sourceName;
  String? targetName = relationship?.targetName;
  String type = relationship?.type ?? 'father';
  await showDialog<void>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
          builder: (_, setDialog) => AlertDialog(
                  title: Text(relationship == null
                      ? 'Tambah relationship'
                      : 'Edit relationship'),
                  content: SingleChildScrollView(
                      child: Column(mainAxisSize: MainAxisSize.min, children: [
                    _memberPickerTile('Sumber', sourceName, () async {
                      final member =
                          await _selectMember(dialogContext, family.uuid);
                      if (member != null) {
                        setDialog(() {
                          source = member.uuid;
                          sourceName = member.displayName;
                        });
                      }
                    }),
                    _memberPickerTile('Tujuan', targetName, () async {
                      final member =
                          await _selectMember(dialogContext, family.uuid);
                      if (member != null) {
                        setDialog(() {
                          target = member.uuid;
                          targetName = member.displayName;
                        });
                      }
                    }),
                    DropdownButtonFormField(
                        initialValue: type,
                        decoration:
                            const InputDecoration(labelText: 'Tipe dasar'),
                        items: const [
                          'father',
                          'mother',
                          'child',
                          'husband',
                          'wife'
                        ]
                            .map((v) =>
                                DropdownMenuItem(value: v, child: Text(v)))
                            .toList(),
                        onChanged: (v) => setDialog(() => type = v!))
                  ])),
                  actions: [
                    TextButton(
                        onPressed: () => Navigator.pop(dialogContext),
                        child: const Text('Batal')),
                    FilledButton(
                        onPressed: source == null || target == null
                            ? null
                            : () async {
                                final values = {
                                  'family_uuid': family.uuid,
                                  'source_member_uuid': source,
                                  'target_member_uuid': target,
                                  'relationship_type': type
                                };
                                if (relationship == null) {
                                  await ref
                                      .read(memberRepositoryProvider)
                                      .createRelationship(values);
                                } else {
                                  await ref
                                      .read(memberRepositoryProvider)
                                      .updateRelationship(
                                          relationship.uuid, values);
                                }
                                if (dialogContext.mounted) {
                                  Navigator.pop(dialogContext);
                                }
                              },
                        child: const Text('Simpan'))
                  ])));
}

Widget _memberPickerTile(String label, String? name, VoidCallback onTap) =>
    ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(label),
        subtitle: Text(name ?? 'Pilih anggota'),
        trailing: const Icon(Icons.search),
        onTap: onTap);

Future<FamilyMember?> _selectMember(BuildContext context, String familyUuid) =>
    showDialog<FamilyMember>(
        context: context,
        builder: (_) => _PaginatedMemberPickerDialog(familyUuid: familyUuid));

class _PaginatedMemberPickerDialog extends ConsumerStatefulWidget {
  const _PaginatedMemberPickerDialog({required this.familyUuid});
  final String familyUuid;
  @override
  ConsumerState<_PaginatedMemberPickerDialog> createState() =>
      _PaginatedMemberPickerDialogState();
}

class _PaginatedMemberPickerDialogState
    extends ConsumerState<_PaginatedMemberPickerDialog> {
  final search = TextEditingController();
  int page = 1;
  late Future<PageData<FamilyMember>> future = load();

  Future<PageData<FamilyMember>> load() => ref
      .read(memberRepositoryProvider)
      .members(widget.familyUuid, page: page, limit: 20, search: search.text);

  void reload([int? nextPage]) {
    setState(() {
      page = nextPage ?? 1;
      future = load();
    });
  }

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
          title: const Text('Pilih anggota'),
          content: SizedBox(
              width: 480,
              height: 480,
              child: Column(children: [
                SearchBar(
                    controller: search,
                    hintText: 'Cari anggota',
                    onSubmitted: (_) => reload()),
                const SizedBox(height: 8),
                Expanded(
                    child: FutureBuilder<PageData<FamilyMember>>(
                        future: future,
                        builder: (_, snapshot) {
                          if (snapshot.connectionState !=
                              ConnectionState.done) {
                            return const Center(
                                child: CircularProgressIndicator());
                          }
                          if (snapshot.hasError) {
                            return Center(
                                child: FilledButton(
                                    onPressed: reload,
                                    child: const Text('Coba lagi')));
                          }
                          final result = snapshot.data!;
                          return Column(children: [
                            Expanded(
                                child: ListView(
                                    children: result.items
                                        .map((member) => ListTile(
                                            title: Text(member.displayName),
                                            subtitle: Text(member.branchName ??
                                                'Tanpa cabang'),
                                            onTap: () =>
                                                Navigator.pop(context, member)))
                                        .toList())),
                            Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  IconButton(
                                      tooltip: 'Halaman sebelumnya',
                                      onPressed: result.currentPage > 1
                                          ? () => reload(result.currentPage - 1)
                                          : null,
                                      icon: const Icon(Icons.chevron_left)),
                                  Text(
                                      '${result.currentPage} / ${result.lastPage}'),
                                  IconButton(
                                      tooltip: 'Halaman berikutnya',
                                      onPressed: result.hasMore
                                          ? () => reload(result.currentPage + 1)
                                          : null,
                                      icon: const Icon(Icons.chevron_right))
                                ])
                          ]);
                        }))
              ])),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Batal'))
          ]);
}

class _MemberAvatar extends StatelessWidget {
  const _MemberAvatar({required this.member, this.large = false});
  final FamilyMember member;
  final bool large;
  @override
  Widget build(BuildContext context) {
    final radius = large ? 48.0 : 24.0;
    return Semantics(
        label: '${member.displayName}, ${member.isAlive ? 'hidup' : 'meninggal'}',
        child: CircleAvatar(
            radius: radius,
            foregroundImage:
                member.photoUrl == null ? null : NetworkImage(member.photoUrl!),
            child: member.photoUrl == null
                ? Icon(member.isAlive ? Icons.person : Icons.local_florist,
                    size: large ? 44 : 24)
                : null));
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});
  final String title;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Card(
      child: Padding(
          padding: const EdgeInsets.all(16),
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const Divider(),
            ...children
          ])));
}

class _Info extends StatelessWidget {
  const _Info(this.label, this.value);
  final String label;
  final String? value;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(width: 120, child: Text(label)),
        Expanded(child: Text(value?.isNotEmpty == true ? value! : '—'))
      ]));
}

String _gender(String? value) => value == 'male'
    ? 'Laki-laki'
    : value == 'female'
        ? 'Perempuan'
        : 'Tidak ditentukan';
String _religion(String? value) => const {
      'islam': 'Islam',
      'christian': 'Kristen',
      'catholic': 'Katolik',
      'hindu': 'Hindu',
      'buddhist': 'Buddha',
      'confucian': 'Konghucu',
      'belief': 'Penghayat kepercayaan',
      'other': 'Lainnya',
    }[value] ??
    'Belum ditentukan';
String _datePlace(DateTime? date, String? place) => [
      if (date != null) _iso(date),
      if (place?.isNotEmpty == true) place!
    ].join(' · ');
String _iso(DateTime? date) => date == null
    ? ''
    : '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
String? _null(String value) => value.trim().isEmpty ? null : value.trim();
