import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/errors/app_error.dart';
import '../../../l10n/app_localizations.dart';
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
    final l10n = AppLocalizations.of(context);
    await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        builder: (context) => StatefulBuilder(
            builder: (context, setSheet) => Padding(
                padding: EdgeInsets.fromLTRB(
                    16, 16, 16, MediaQuery.viewInsetsOf(context).bottom + 24),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  Text(l10n.memberFilters,
                      style: const TextStyle(
                          fontSize: 20, fontWeight: FontWeight.bold)),
                  DropdownButtonFormField<String?>(
                      initialValue: gender,
                      decoration: InputDecoration(labelText: l10n.gender),
                      items: [
                        DropdownMenuItem(value: null, child: Text(l10n.all)),
                        DropdownMenuItem(value: 'male', child: Text(l10n.male)),
                        DropdownMenuItem(
                            value: 'female', child: Text(l10n.female))
                      ],
                      onChanged: (v) => setSheet(() => gender = v)),
                  DropdownButtonFormField<bool?>(
                      initialValue: alive,
                      decoration: InputDecoration(labelText: l10n.status),
                      items: [
                        DropdownMenuItem(value: null, child: Text(l10n.all)),
                        DropdownMenuItem(value: true, child: Text(l10n.alive)),
                        DropdownMenuItem(
                            value: false, child: Text(l10n.deceased))
                      ],
                      onChanged: (v) => setSheet(() => alive = v)),
                  DropdownButtonFormField<String?>(
                      initialValue: branch,
                      decoration: InputDecoration(labelText: l10n.branch),
                      items: [
                        DropdownMenuItem(value: null, child: Text(l10n.all)),
                        ...branches.map((b) => DropdownMenuItem(
                            value: b.uuid, child: Text(b.name)))
                      ],
                      onChanged: (v) => setSheet(() => branch = v)),
                  DropdownButtonFormField<String>(
                      initialValue: sort,
                      decoration: InputDecoration(labelText: l10n.sort),
                      items: [
                        DropdownMenuItem(
                            value: 'name', child: Text(l10n.sortNameAscending)),
                        DropdownMenuItem(
                            value: 'name_desc',
                            child: Text(l10n.sortNameDescending)),
                        DropdownMenuItem(
                            value: 'newest', child: Text(l10n.sortNewest)),
                        DropdownMenuItem(
                            value: 'oldest', child: Text(l10n.sortOldest))
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
                          child: Text(l10n.apply))),
                ]))));
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final canManage = family?.canManage ?? false;
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.membersTitle), actions: [
          IconButton(
              tooltip: l10n.manageRelationships,
              onPressed: () => context.push('/relationships'),
              icon: const Icon(Icons.device_hub)),
          IconButton(
              tooltip: l10n.relationshipResolver,
              onPressed: () => context.push('/relationship-resolver'),
              icon: const Icon(Icons.route)),
          if (canManage)
            IconButton(
                tooltip: l10n.duplicatesTooltip,
                onPressed: () => context.push('/members/duplicates'),
                icon: const Icon(Icons.content_copy))
        ]),
        floatingActionButton: canManage
            ? FloatingActionButton(
                onPressed: () async {
                  await context.push('/members/new');
                  load();
                },
                tooltip: l10n.addMember,
                child: const Icon(Icons.person_add))
            : null,
        body: Column(children: [
          Padding(
              padding: const EdgeInsets.all(16),
              child: Row(children: [
                Expanded(
                    child: SearchBar(
                        controller: search,
                        hintText: l10n.searchMemberNameOrNickname,
                        leading: const Icon(Icons.search),
                        onSubmitted: (_) => load())),
                const SizedBox(width: 8),
                IconButton.filledTonal(
                    onPressed: filters,
                    tooltip: l10n.filterAndSort,
                    icon: const Icon(Icons.tune))
              ])),
          Expanded(child: _body(canManage, l10n)),
        ]));
  }

  Widget _body(bool canManage, AppLocalizations l10n) {
    if (loading && data == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: load,
              icon: const Icon(Icons.refresh),
              label: Text(l10n.retry)));
    }
    final page = data;
    if (page == null || page.items.isEmpty) {
      return Center(child: Text(l10n.noMatchingMembers));
    }
    final tablet = MediaQuery.sizeOf(context).width >= 600;
    final content = tablet
        ? SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
                columns: [
                  DataColumn(label: Text(l10n.memberName)),
                  DataColumn(label: Text(l10n.gender)),
                  DataColumn(label: Text(l10n.status)),
                  DataColumn(label: Text(l10n.branch))
                ],
                rows: page.items
                    .map((m) => DataRow(
                            onSelectChanged: (_) =>
                                context.push('/members/${m.uuid}'),
                            cells: [
                              DataCell(Text(m.displayName)),
                              DataCell(Text(_gender(l10n, m.gender))),
                              DataCell(Text(m.isAlive ? l10n.alive : l10n.deceased)),
                              DataCell(Text(m.branchName ?? l10n.noValue))
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
                          '${_gender(l10n, m.gender)} · ${m.isAlive ? l10n.alive : l10n.deceased}'),
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
                tooltip: l10n.previousPage,
                onPressed: page.currentPage > 1
                    ? () => load(page.currentPage - 1)
                    : null,
                icon: const Icon(Icons.chevron_left)),
            Text(l10n.pageOf(page.currentPage, page.lastPage)),
            IconButton(
                tooltip: l10n.nextPage,
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
              body: Center(
                  child: Text(AppLocalizations.of(context).memberDetailLoadFailed)));
        }
        final member = snapshot.data![0] as FamilyMember;
        final relations = snapshot.data![1] as PageData<MemberRelationship>;
        final canManage = ref.read(currentFamilyProvider)?.canManage ?? false;
        final l10n = AppLocalizations.of(context);
        return Scaffold(
          appBar: AppBar(title: Text(member.displayName), actions: [
            if (canManage)
              IconButton(
                  tooltip: l10n.editMember,
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
                      label: Text(l10n.relationshipToYou(
                          member.relationshipToViewer!)),
                      backgroundColor:
                          Theme.of(context).colorScheme.primaryContainer,
                      labelStyle: TextStyle(
                          color: Theme.of(context).colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.w600))),
            if (!member.isAlive)
              Center(
                  child: Chip(
                      avatar: const Icon(Icons.local_florist, size: 18),
                      label: Text(l10n.inMemory))),
            const SizedBox(height: 16),
            _Section(title: l10n.basicInformation, children: [
              _Info(l10n.fullName, member.fullName),
              _Info(l10n.nickname, member.nickname),
              _Info(l10n.gender, _gender(l10n, member.gender)),
              _Info(l10n.religionBelief, _religion(l10n, member.religion)),
              _Info(l10n.born, _datePlace(member.birthDate, member.birthPlace)),
              if (!member.isAlive)
                _Info(l10n.died, _datePlace(member.deathDate, member.deathPlace))
            ]),
            _Section(title: l10n.family, children: [
              _Info(l10n.branch, member.branchName ?? l10n.noBranch)
            ]),
            _Section(title: l10n.biography, children: [
              Text(member.biography?.isNotEmpty == true
                  ? member.biography!
                  : l10n.noBiography)
            ]),
            _Section(
                title: l10n.basicRelationships,
                children: relations.items.isEmpty
                    ? [Text(l10n.noBasicRelationships)]
                    : relations.items
                        .map((r) => ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.device_hub),
                            title: Text(r.sourceUuid == uuid
                                ? r.targetName
                                : r.sourceName),
                            subtitle: Text(_relationshipType(l10n, r.type))))
                        .toList()),
            if (canManage || member.isOwnProfile)
              _Section(title: l10n.documentsTitle, children: [
                ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.folder_outlined),
                    title: Text(l10n.manageDocuments),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.push('/members/$uuid/documents',
                        extra: canManage || member.isOwnProfile))
              ]),
            _Section(title: l10n.relatedContent, children: [
              ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.photo_outlined),
                  title: Text(l10n.relatedPhotos),
                  subtitle: Text(l10n.noRelatedContent)),
              ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.article_outlined),
                  title: Text(l10n.relatedArticles),
                  subtitle: Text(l10n.noRelatedContent))
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
            SnackBar(content: Text(AppLocalizations.of(context).memberSaved)));
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
            SnackBar(content: Text(AppLocalizations.of(context).photoUpdated)));
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
        builder: (dialogContext) {
          final l10n = AppLocalizations.of(dialogContext);
          return AlertDialog(
                title: Text(l10n.deleteMemberTitle(widget.member!.fullName)),
                content: Text(l10n.deleteMemberConfirmation),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: Text(l10n.delete))
                ]);
        });
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
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(
            title: Text(widget.member == null ? l10n.addMember : l10n.editMember)),
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
                            labelText: l10n.fullName,
                            errorText: errors['full_name']?.first),
                        validator: (v) => v?.trim().isEmpty == true
                            ? l10n.fullNameRequired
                            : null),
                    TextFormField(
                        controller: nickname,
                        decoration: InputDecoration(labelText: l10n.nickname)),
                    DropdownButtonFormField<String?>(
                        initialValue: gender,
                        decoration: InputDecoration(labelText: l10n.gender),
                        items: [
                          DropdownMenuItem(
                              value: null, child: Text(l10n.unspecified)),
                          DropdownMenuItem(
                              value: 'male', child: Text(l10n.male)),
                          DropdownMenuItem(
                              value: 'female', child: Text(l10n.female))
                        ],
                        onChanged: (v) => setState(() => gender = v)),
                    DropdownButtonFormField<String?>(
                        initialValue: religion,
                        decoration: InputDecoration(
                            labelText: l10n.religionBelief,
                            errorText: errors['religion']?.first),
                        items: [
                          DropdownMenuItem(
                              value: null, child: Text(l10n.unspecified)),
                          DropdownMenuItem(value: 'islam', child: Text(l10n.islam)),
                          DropdownMenuItem(
                              value: 'christian', child: Text(l10n.christian)),
                          DropdownMenuItem(
                              value: 'catholic', child: Text(l10n.catholic)),
                          DropdownMenuItem(value: 'hindu', child: Text(l10n.hindu)),
                          DropdownMenuItem(
                              value: 'buddhist', child: Text(l10n.buddhist)),
                          DropdownMenuItem(
                              value: 'confucian', child: Text(l10n.confucian)),
                          DropdownMenuItem(
                              value: 'belief', child: Text(l10n.belief)),
                          DropdownMenuItem(value: 'other', child: Text(l10n.other))
                        ],
                        onChanged: (v) => setState(() => religion = v)),
                    DropdownButtonFormField<String?>(
                        initialValue: branch,
                        decoration: InputDecoration(labelText: l10n.branch),
                        items: [
                          DropdownMenuItem(
                              value: null, child: Text(l10n.noBranchShort)),
                          ...branches.map((b) => DropdownMenuItem(
                              value: b.uuid, child: Text(b.name)))
                        ],
                        onChanged: (v) => setState(() => branch = v)),
                    TextFormField(
                        controller: birthDate,
                        decoration: InputDecoration(labelText: l10n.birthDate)),
                    TextFormField(
                        controller: birthPlace,
                        decoration: InputDecoration(labelText: l10n.birthPlace)),
                    SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(l10n.stillAlive),
                        value: alive,
                        onChanged: (v) => setState(() => alive = v)),
                    if (!alive) ...[
                      TextFormField(
                          controller: deathDate,
                          decoration: InputDecoration(
                              labelText: l10n.deathDate,
                              errorText: errors['death_date']?.first)),
                      TextFormField(
                          controller: deathPlace,
                          decoration: InputDecoration(labelText: l10n.deathPlace))
                    ],
                    TextFormField(
                        controller: biography,
                        maxLines: 5,
                        decoration: InputDecoration(labelText: l10n.biography)),
                    const SizedBox(height: 20),
                    FilledButton(
                        onPressed: saving ? null : save,
                        child: Text(saving ? l10n.saving : l10n.save)),
                    if (widget.member != null) ...[
                      OutlinedButton.icon(
                          onPressed: photo,
                          icon: const Icon(Icons.photo_camera),
                          label: Text(l10n.replacePhoto)),
                      TextButton(
                          onPressed: remove,
                          child: Text(l10n.deleteMember,
                              style: const TextStyle(color: Colors.red)))
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
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.basicRelationships)),
        floatingActionButton: canManage
            ? FloatingActionButton(
                onPressed: () async {
                  await _relationshipDialog(context, ref);
                  refresh();
                },
                tooltip: l10n.addRelationship,
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
                        onPressed: refresh, child: Text(l10n.retry)));
              }
              final items = snapshot.data!.items;
              if (items.isEmpty) {
                return Center(child: Text(l10n.noBasicRelationships));
              }
              return ListView(
                  children: items
                      .map((r) => ListTile(
                          title: Text('${r.sourceName} → ${r.targetName}'),
                          subtitle: Text(_relationshipType(l10n, r.type)),
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
                                  itemBuilder: (_) => [
                                        PopupMenuItem(
                                            value: 'edit', child: Text(l10n.editRelationship)),
                                        PopupMenuItem(
                                            value: 'delete',
                                            child: Text(l10n.delete))
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
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.relationshipResolver)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          _memberPickerTile(l10n.sourceMember, sourceName, l10n, () async {
            final member = await _selectMember(context, familyUuid);
            if (member != null) {
              setState(() {
                source = member.uuid;
                sourceName = member.displayName;
              });
            }
          }),
          _memberPickerTile(l10n.targetMember, targetName, l10n, () async {
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
              label: Text(loading ? l10n.calculating : l10n.findRelationship)),
          if (result != null)
            Card(
                child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(result!.relationship ?? l10n.notConnected,
                              style: Theme.of(context).textTheme.headlineSmall),
                          const SizedBox(height: 8),
                          if (result!.path.isEmpty)
                            Text(result!.isConnected
                                ? l10n.sameMember
                                : l10n.relationshipPathNotFound)
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
  final l10n = AppLocalizations.of(context);
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
                      ? l10n.addRelationship
                      : l10n.editRelationship),
                  content: SingleChildScrollView(
                      child: Column(mainAxisSize: MainAxisSize.min, children: [
                    _memberPickerTile(l10n.source, sourceName, l10n, () async {
                      final member =
                          await _selectMember(dialogContext, family.uuid);
                      if (member != null) {
                        setDialog(() {
                          source = member.uuid;
                          sourceName = member.displayName;
                        });
                      }
                    }),
                    _memberPickerTile(l10n.target, targetName, l10n, () async {
                      final member =
                          await _selectMember(dialogContext, family.uuid);
                      if (member != null) {
                        setDialog(() {
                          target = member.uuid;
                          targetName = member.displayName;
                        });
                      }
                    }),
                    DropdownButtonFormField<String>(
                        initialValue: type,
                        decoration: InputDecoration(labelText: l10n.relationshipType),
                        items: const ['father', 'mother', 'child', 'husband', 'wife']
                            .map((value) => DropdownMenuItem<String>(
                                value: value,
                                child: Text(_relationshipType(l10n, value))))
                            .toList(),
                        onChanged: (v) => setDialog(() => type = v!))
                  ])),
                  actions: [
                    TextButton(
                        onPressed: () => Navigator.pop(dialogContext),
                        child: Text(l10n.cancel)),
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
                        child: Text(l10n.save))
                  ])));
}

Widget _memberPickerTile(
        String label, String? name, AppLocalizations l10n, VoidCallback onTap) =>
    ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(label),
        subtitle: Text(name ?? l10n.selectMember),
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return AlertDialog(
          title: Text(l10n.selectMemberTitle),
          content: SizedBox(
              width: 480,
              height: 480,
              child: Column(children: [
                SearchBar(
                    controller: search,
                    hintText: l10n.searchMembers,
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
                                    child: Text(l10n.retry)));
                          }
                          final result = snapshot.data!;
                          return Column(children: [
                            Expanded(
                                child: ListView(
                                    children: result.items
                                        .map((member) => ListTile(
                                            title: Text(member.displayName),
                                            subtitle: Text(member.branchName ??
                                                l10n.noBranchShort),
                                            onTap: () =>
                                                Navigator.pop(context, member)))
                                        .toList())),
                            Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  IconButton(
                                      tooltip: l10n.previousPage,
                                      onPressed: result.currentPage > 1
                                          ? () => reload(result.currentPage - 1)
                                          : null,
                                      icon: const Icon(Icons.chevron_left)),
                                  Text(l10n.pageFraction(
                                      result.currentPage, result.lastPage)),
                                  IconButton(
                                      tooltip: l10n.nextPage,
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
                child: Text(l10n.cancel))
          ]);
  }
}

class _MemberAvatar extends StatelessWidget {
  const _MemberAvatar({required this.member, this.large = false});
  final FamilyMember member;
  final bool large;
  @override
  Widget build(BuildContext context) {
    final radius = large ? 48.0 : 24.0;
    return Semantics(
        label: AppLocalizations.of(context).memberSemantics(
            member.displayName,
            member.isAlive
                ? AppLocalizations.of(context).alive
                : AppLocalizations.of(context).deceased),
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
        Expanded(child: Text(value?.isNotEmpty == true
            ? value!
            : AppLocalizations.of(context).noValue))
      ]));
}

String _gender(AppLocalizations l10n, String? value) => value == 'male'
    ? l10n.male
    : value == 'female'
        ? l10n.female
        : l10n.unspecified;

String _religion(AppLocalizations l10n, String? value) => switch (value) {
      'islam' => l10n.islam,
      'christian' => l10n.christian,
      'catholic' => l10n.catholic,
      'hindu' => l10n.hindu,
      'buddhist' => l10n.buddhist,
      'confucian' => l10n.confucian,
      'belief' => l10n.belief,
      'other' => l10n.other,
      _ => l10n.unspecified,
    };

String _relationshipType(AppLocalizations l10n, String value) => switch (value) {
      'father' => l10n.father,
      'mother' => l10n.mother,
      'child' => l10n.child,
      'husband' => l10n.husband,
      'wife' => l10n.wife,
      _ => value,
    };
String _datePlace(DateTime? date, String? place) => [
      if (date != null) _iso(date),
      if (place?.isNotEmpty == true) place!
    ].join(' · ');
String _iso(DateTime? date) => date == null
    ? ''
    : '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
String? _null(String value) => value.trim().isEmpty ? null : value.trim();

String _documentCategory(AppLocalizations l10n, String? value) =>
    switch (value) {
      'identity' => l10n.categoryIdentity,
      'family_card' => l10n.categoryFamilyCard,
      'certificate' => l10n.categoryCertificate,
      'education' => l10n.categoryEducation,
      'legal' => l10n.categoryLegal,
      'medical' => l10n.categoryMedical,
      _ => l10n.categoryUnspecified,
    };

String _confidence(AppLocalizations l10n, String value) => switch (value) {
      'high' => l10n.confidenceHigh,
      'low' => l10n.confidenceLow,
      _ => l10n.confidenceMedium,
    };

String _reason(AppLocalizations l10n, String value) => switch (value) {
      'same_name' => l10n.reasonSameName,
      'same_birth_date' => l10n.reasonSameBirthDate,
      'different_birth_date' => l10n.reasonDifferentBirthDate,
      _ => value,
    };

class MemberDocumentsScreen extends ConsumerStatefulWidget {
  const MemberDocumentsScreen(
      {required this.memberUuid, this.canWrite = false, super.key});
  final String memberUuid;
  final bool canWrite;
  @override
  ConsumerState<MemberDocumentsScreen> createState() =>
      _MemberDocumentsScreenState();
}

class _MemberDocumentsScreenState extends ConsumerState<MemberDocumentsScreen> {
  List<MemberDocument>? documents;
  Object? error;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final items = await ref
          .read(memberRepositoryProvider)
          .documents(widget.memberUuid);
      if (mounted) {
        setState(() {
          documents = items;
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

  Future<void> add() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery);
    if (picked == null || !mounted) return;
    final form = await _documentForm(context);
    if (form == null || !mounted) return;
    try {
      await ref.read(memberRepositoryProvider).uploadDocument(
            widget.memberUuid,
            path: picked.path,
            title: form.title,
            category: form.category,
            documentDate: form.documentDate,
            notes: form.notes,
          );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppLocalizations.of(context).documentUploaded)));
      }
      await load();
    } on AppError catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  Future<void> remove(MemberDocument document) async {
    final ok = await showDialog<bool>(
        context: context,
        builder: (dialogContext) {
          final l10n = AppLocalizations.of(dialogContext);
          return AlertDialog(
              title: Text(l10n.deleteDocumentTitle),
              content: Text(l10n.deleteDocumentConfirmation),
              actions: [
                TextButton(
                    onPressed: () => Navigator.pop(dialogContext, false),
                    child: Text(l10n.cancel)),
                FilledButton(
                    onPressed: () => Navigator.pop(dialogContext, true),
                    child: Text(l10n.delete))
              ]);
        });
    if (ok != true) return;
    try {
      await ref.read(memberRepositoryProvider).deleteDocument(document.uuid);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppLocalizations.of(context).documentDeleted)));
      }
      await load();
    } on AppError catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.documentsTitle)),
        floatingActionButton: widget.canWrite
            ? FloatingActionButton(
                onPressed: add,
                tooltip: l10n.addDocument,
                child: const Icon(Icons.note_add))
            : null,
        body: _body(l10n));
  }

  Widget _body(AppLocalizations l10n) {
    if (loading && documents == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: load,
              icon: const Icon(Icons.refresh),
              label: Text(l10n.retry)));
    }
    final items = documents ?? const <MemberDocument>[];
    if (items.isEmpty) {
      return Center(child: Text(l10n.noDocuments));
    }
    return ListView.builder(
        itemCount: items.length,
        itemBuilder: (_, i) {
          final doc = items[i];
          return Card(
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: ListTile(
                  minTileHeight: 64,
                  leading: const Icon(Icons.description_outlined),
                  title: Text(doc.title),
                  subtitle: Text([
                    _documentCategory(l10n, doc.category),
                    if (doc.documentDate != null)
                      l10n.documentDated(_iso(doc.documentDate))
                  ].join(' · ')),
                  trailing: widget.canWrite
                      ? IconButton(
                          tooltip: l10n.delete,
                          onPressed: () => remove(doc),
                          icon: const Icon(Icons.delete_outline))
                      : null));
        });
  }
}

Future<({String title, String? category, String? documentDate, String? notes})?>
    _documentForm(BuildContext context) async {
  final l10n = AppLocalizations.of(context);
  final title = TextEditingController();
  final date = TextEditingController();
  final notes = TextEditingController();
  String? category;
  return showDialog<
      ({String title, String? category, String? documentDate, String? notes})>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
          builder: (_, setDialog) => AlertDialog(
                  title: Text(l10n.addDocument),
                  content: SingleChildScrollView(
                      child: Column(mainAxisSize: MainAxisSize.min, children: [
                    TextField(
                        controller: title,
                        decoration:
                            InputDecoration(labelText: l10n.documentTitleLabel)),
                    DropdownButtonFormField<String?>(
                        initialValue: category,
                        decoration: InputDecoration(
                            labelText: l10n.documentCategoryLabel),
                        items: [
                          DropdownMenuItem(
                              value: null,
                              child: Text(l10n.categoryUnspecified)),
                          DropdownMenuItem(
                              value: 'identity',
                              child: Text(l10n.categoryIdentity)),
                          DropdownMenuItem(
                              value: 'family_card',
                              child: Text(l10n.categoryFamilyCard)),
                          DropdownMenuItem(
                              value: 'certificate',
                              child: Text(l10n.categoryCertificate)),
                          DropdownMenuItem(
                              value: 'education',
                              child: Text(l10n.categoryEducation)),
                          DropdownMenuItem(
                              value: 'legal', child: Text(l10n.categoryLegal)),
                          DropdownMenuItem(
                              value: 'medical',
                              child: Text(l10n.categoryMedical))
                        ],
                        onChanged: (value) => setDialog(() => category = value)),
                    TextField(
                        controller: date,
                        decoration: InputDecoration(
                            labelText: l10n.documentDateLabel,
                            hintText: 'YYYY-MM-DD')),
                    TextField(
                        controller: notes,
                        maxLines: 2,
                        decoration:
                            InputDecoration(labelText: l10n.documentNotesLabel)),
                    const SizedBox(height: 8),
                    Text(l10n.documentFileHint,
                        style: Theme.of(context).textTheme.bodySmall)
                  ])),
                  actions: [
                    TextButton(
                        onPressed: () => Navigator.pop(dialogContext),
                        child: Text(l10n.cancel)),
                    FilledButton(
                        onPressed: () {
                          if (title.text.trim().isEmpty) return;
                          Navigator.pop(dialogContext, (
                            title: title.text.trim(),
                            category: category,
                            documentDate: _null(date.text),
                            notes: _null(notes.text)
                          ));
                        },
                        child: Text(l10n.uploadDocument))
                  ])));
}

class MemberDuplicatesScreen extends ConsumerStatefulWidget {
  const MemberDuplicatesScreen({super.key});
  @override
  ConsumerState<MemberDuplicatesScreen> createState() =>
      _MemberDuplicatesScreenState();
}

class _MemberDuplicatesScreenState
    extends ConsumerState<MemberDuplicatesScreen> {
  List<DuplicateCandidate>? candidates;
  Object? error;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  Future<void> load() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final items = await ref
          .read(memberRepositoryProvider)
          .duplicateCandidates(family.uuid);
      if (mounted) {
        setState(() {
          candidates = items;
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

  Future<void> merge(DuplicateCandidate candidate) async {
    final l10n = AppLocalizations.of(context);
    final keepPrimary = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
                title: Text(l10n.mergeDialogTitle),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  Text(l10n.mergeKeepHint),
                  ListTile(
                      title: Text(candidate.primary.displayName),
                      onTap: () => Navigator.pop(dialogContext, true)),
                  ListTile(
                      title: Text(candidate.duplicate.displayName),
                      onTap: () => Navigator.pop(dialogContext, false))
                ]),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(dialogContext),
                      child: Text(l10n.cancel))
                ]));
    if (keepPrimary == null) return;
    final primary =
        keepPrimary ? candidate.primary.uuid : candidate.duplicate.uuid;
    final duplicate =
        keepPrimary ? candidate.duplicate.uuid : candidate.primary.uuid;
    try {
      await ref.read(memberRepositoryProvider).mergeMember(primary, duplicate);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppLocalizations.of(context).mergedSuccessfully)));
      }
      await load();
    } on AppError catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.duplicatesTitle)),
        body: _body(l10n));
  }

  Widget _body(AppLocalizations l10n) {
    if (loading && candidates == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: load,
              icon: const Icon(Icons.refresh),
              label: Text(l10n.retry)));
    }
    final items = candidates ?? const <DuplicateCandidate>[];
    if (items.isEmpty) {
      return Center(child: Text(l10n.noDuplicates));
    }
    return RefreshIndicator(
        onRefresh: load,
        child: ListView.builder(
            itemCount: items.length,
            itemBuilder: (_, i) {
              final candidate = items[i];
              return Card(
                  margin: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 4),
                  child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(candidate.primary.displayName,
                                style:
                                    Theme.of(context).textTheme.titleMedium),
                            const SizedBox(height: 4),
                            Text('↔ ${candidate.duplicate.displayName}'),
                            const SizedBox(height: 8),
                            Wrap(spacing: 8, children: [
                              Chip(
                                  label: Text(
                                      _confidence(l10n, candidate.confidence))),
                              ...candidate.reasons.map((reason) =>
                                  Chip(label: Text(_reason(l10n, reason))))
                            ]),
                            Align(
                                alignment: Alignment.centerRight,
                                child: FilledButton.icon(
                                    onPressed: () => merge(candidate),
                                    icon: const Icon(Icons.merge_type),
                                    label: Text(l10n.mergeMembers)))
                          ])));
            }));
  }
}
