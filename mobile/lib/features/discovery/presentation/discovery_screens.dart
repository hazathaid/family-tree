import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/providers.dart';
import '../domain/discovery_models.dart';

class DiscoverySearchScreen extends ConsumerStatefulWidget {
  const DiscoverySearchScreen({super.key});
  @override
  ConsumerState<DiscoverySearchScreen> createState() =>
      _DiscoverySearchScreenState();
}

class _DiscoverySearchScreenState extends ConsumerState<DiscoverySearchScreen> {
  final keyword = TextEditingController(),
      name = TextEditingController(),
      city = TextEditingController(),
      generation = TextEditingController(),
      root = TextEditingController();
  String? status;
  SearchResults? results;
  AppError? error;
  bool loading = false;
  @override
  void dispose() {
    keyword.dispose();
    name.dispose();
    city.dispose();
    generation.dispose();
    root.dispose();
    super.dispose();
  }

  Future<void> load({bool more = false}) async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final value = await ref.read(discoveryRepositoryProvider).search(
          family.uuid,
          keyword: keyword.text.trim(),
          name: name.text.trim(),
          city: city.text.trim(),
          status: status,
          generation: int.tryParse(generation.text),
          rootMemberUuid: root.text.trim(),
          page: more ? (results?.page ?? 0) + 1 : 1);
      if (!mounted) return;
      setState(() => results = more && results != null
          ? SearchResults(
              members: [...results!.members, ...value.members],
              articles: [...results!.articles, ...value.articles],
              events: [...results!.events, ...value.events],
              page: value.page,
              hasMore: value.hasMore)
          : value);
    } on AppError catch (value) {
      if (mounted) setState(() => error = value);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Pencarian keluarga')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(
            controller: keyword,
            textInputAction: TextInputAction.search,
            decoration: const InputDecoration(labelText: 'Kata kunci'),
            onSubmitted: (_) => load()),
        ExpansionTile(title: const Text('Filter lanjutan'), children: [
          TextField(
              controller: name,
              decoration: const InputDecoration(labelText: 'Nama anggota')),
          TextField(
              controller: city,
              decoration:
                  const InputDecoration(labelText: 'Kota lahir/meninggal')),
          DropdownButtonFormField<String>(
              initialValue: status,
              decoration: const InputDecoration(labelText: 'Status hidup'),
              items: const [
                DropdownMenuItem(value: 'alive', child: Text('Hidup')),
                DropdownMenuItem(value: 'deceased', child: Text('Meninggal'))
              ],
              onChanged: (v) => setState(() => status = v)),
          TextField(
              controller: generation,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Generasi relatif')),
          TextField(
              controller: root,
              decoration: const InputDecoration(
                  labelText: 'UUID anggota akar',
                  helperText: 'Wajib bila filter generasi digunakan'))
        ]),
        const SizedBox(height: 12),
        FilledButton.icon(
            onPressed: loading ? null : load,
            icon: const Icon(Icons.search),
            label: const Text('Cari')),
        if (loading)
          const Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: CircularProgressIndicator())),
        if (error != null) _Error(error!.message, () => load()),
        if (!loading &&
            error == null &&
            results != null &&
            results!.members.isEmpty &&
            results!.articles.isEmpty &&
            results!.events.isEmpty)
          const _Empty('Tidak ada hasil yang cocok.'),
        if (results != null) ...[
          _Group(
            title: 'Anggota',
            children: results!.members.map((m) => ListTile(
                  minTileHeight: 56,
                  title: Text(m.fullName),
                  subtitle: Text([
                    m.birthPlace,
                    if (m.generation != null) 'Generasi ${m.generation}'
                  ].whereType<String>().join(' · ')),
                  onTap: () => context.push('/members/${m.uuid}'),
                )),
          ),
          _Group(
            title: 'Artikel',
            children: results!.articles.map((a) => ListTile(
                  minTileHeight: 56,
                  title: Text(a.title),
                  subtitle: Text(a.authorName),
                  onTap: () => context.push('/articles/${a.uuid}'),
                )),
          ),
          _Group(
            title: 'Acara',
            children: results!.events.map((e) => ListTile(
                  minTileHeight: 56,
                  title: Text(e.title),
                  subtitle: Text(e.location ?? ''),
                  onTap: () => context.push('/events/${e.uuid}'),
                )),
          ),
          if (results!.hasMore)
            OutlinedButton(
                onPressed: loading ? null : () => load(more: true),
                child: const Text('Muat berikutnya')),
        ],
      ]));
}

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});
  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  DateTime from = DateTime.now().subtract(const Duration(days: 29)),
      to = DateTime.now();
  FamilyStatistics? statistics;
  ActivityReport? activity;
  ReportInsights? insights;
  AppError? error;
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
      final repo = ref.read(discoveryRepositoryProvider);
      final values = await Future.wait([
        repo.statistics(family.uuid),
        repo.activity(family.uuid, from, to),
        repo.insights(family.uuid, from, to)
      ]);
      if (!mounted) return;
      setState(() {
        statistics = values[0] as FamilyStatistics;
        activity = values[1] as ActivityReport;
        insights = values[2] as ReportInsights;
      });
    } on AppError catch (value) {
      if (mounted) setState(() => error = value);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> pick(bool start) async {
    final value = await showDatePicker(
        context: context,
        firstDate: DateTime(1900),
        lastDate: DateTime.now(),
        initialDate: start ? from : to);
    if (value == null) return;
    setState(() {
      if (start) {
        from = value;
      } else {
        to = value;
      }
    });
    await load();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Laporan & insight')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? _Error(error!.message, load)
              : RefreshIndicator(
                  onRefresh: load,
                  child: ListView(padding: const EdgeInsets.all(16), children: [
                    Wrap(spacing: 8, children: [
                      ActionChip(
                          label: Text('Dari ${_date(from)}'),
                          onPressed: () => pick(true)),
                      ActionChip(
                          label: Text(
                              'Sampai ${_date(to)} ${DateTime.now().timeZoneName}'),
                          onPressed: () => pick(false))
                    ]),
                    GridView.count(
                        crossAxisCount:
                            MediaQuery.sizeOf(context).width >= 600 ? 4 : 2,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        childAspectRatio: 1.6,
                        children: [
                          _Stat('Anggota', statistics!.totalMembers),
                          _Stat('Hidup', statistics!.aliveMembers),
                          _Stat('Meninggal', statistics!.deceasedMembers),
                          _Stat('Pengguna aktif', activity!.activeUsers),
                          _Stat('Foto', activity!.uploads),
                          _Stat('Artikel', activity!.articles)
                        ]),
                    _DataSection(
                        'Distribusi generasi', statistics!.generations),
                    _DataSection('Kota', insights!.cities),
                    _DataSection('Pertumbuhan anggota', insights!.growth),
                    _DataSection('Tren aktivitas', insights!.activity)
                  ])));
}

class GamificationScreen extends ConsumerStatefulWidget {
  const GamificationScreen({super.key});
  @override
  ConsumerState<GamificationScreen> createState() => _GamificationScreenState();
}

class _GamificationScreenState extends ConsumerState<GamificationScreen> {
  GamificationProfile? profile;
  List<LeaderboardEntry> users = const [], families = const [];
  AppError? error;
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
      final repo = ref.read(discoveryRepositoryProvider);
      final values = await Future.wait([
        repo.profile(family.uuid),
        repo.userLeaderboard(family.uuid),
        repo.familyLeaderboard()
      ]);
      if (!mounted) return;
      setState(() {
        profile = values[0] as GamificationProfile;
        users = values[1] as List<LeaderboardEntry>;
        families = values[2] as List<LeaderboardEntry>;
      });
    } on AppError catch (value) {
      if (mounted) setState(() => error = value);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Kontribusi & peringkat')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? _Error(error!.message, load)
              : RefreshIndicator(
                  onRefresh: load,
                  child: ListView(padding: const EdgeInsets.all(16), children: [
                    Semantics(
                        label: '${profile!.points} poin kontribusi',
                        child: Card(
                            child: ListTile(
                                minTileHeight: 64,
                                leading: const Icon(Icons.stars),
                                title: Text('${profile!.points} poin'),
                                subtitle: const Text('Kontribusi Anda')))),
                    Text('Badge saya',
                        style: Theme.of(context).textTheme.titleLarge),
                    if (profile!.badges.isEmpty)
                      const _Empty('Belum ada badge.')
                    else
                      Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: profile!.badges
                              .map((b) => Tooltip(
                                  message: b.description,
                                  child: Chip(
                                      avatar: const Icon(
                                          Icons.workspace_premium,
                                          size: 18),
                                      label: Text(b.name))))
                              .toList()),
                    _Leaderboard('Peringkat pengguna keluarga', users),
                    _Leaderboard('Peringkat keluarga', families)
                  ])));
}

class _Group extends StatelessWidget {
  const _Group({required this.title, required this.children});
  final String title;
  final Iterable<Widget> children;
  @override
  Widget build(BuildContext context) {
    final list = children.toList();
    if (list.isEmpty) return const SizedBox.shrink();
    return Semantics(
        container: true,
        label: '$title, ${list.length} hasil',
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const SizedBox(height: 20),
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          ...list
        ]));
  }
}

class _Stat extends StatelessWidget {
  const _Stat(this.label, this.value);
  final String label;
  final int value;
  @override
  Widget build(BuildContext context) => Card(
          child: Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
        Text('$value', style: Theme.of(context).textTheme.headlineSmall),
        Text(label)
      ])));
}

class _DataSection extends StatelessWidget {
  const _DataSection(this.title, this.rows);
  final String title;
  final List<ReportPoint> rows;
  @override
  Widget build(BuildContext context) => Semantics(
      container: true,
      label: 'Data $title',
      child: Card(
          child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: Theme.of(context).textTheme.titleMedium),
                    if (rows.isEmpty)
                      const _Empty('Belum ada data.')
                    else
                      ...rows.map((r) => Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Row(children: [
                            Expanded(flex: 2, child: Text(r.label)),
                            Expanded(
                                flex: 3,
                                child: LinearProgressIndicator(
                                    value: r.total /
                                        rows.fold<int>(
                                            1,
                                            (max, e) =>
                                                e.total > max ? e.total : max),
                                    semanticsLabel: '${r.label}: ${r.total}')),
                            const SizedBox(width: 8),
                            Text('${r.total}')
                          ])))
                  ]))));
}

class _Leaderboard extends StatelessWidget {
  const _Leaderboard(this.title, this.rows);
  final String title;
  final List<LeaderboardEntry> rows;
  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              if (rows.isEmpty)
                const _Empty('Belum ada peringkat.')
              else
                ...rows.map((r) => ListTile(
                      minTileHeight: 56,
                      leading: CircleAvatar(child: Text('${r.rank}')),
                      title: Text(r.name),
                      trailing: Text('${r.points} poin'),
                    )),
            ],
          ),
        ),
      );
}

class _Empty extends StatelessWidget {
  const _Empty(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.all(20), child: Center(child: Text(text)));
}

class _Error extends StatelessWidget {
  const _Error(this.text, this.retry);
  final String text;
  final VoidCallback retry;
  @override
  Widget build(BuildContext context) => Center(
      child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Text(text, textAlign: TextAlign.center),
            const SizedBox(height: 8),
            OutlinedButton(onPressed: retry, child: const Text('Coba lagi'))
          ])));
}

String _date(DateTime value) =>
    '${value.day.toString().padLeft(2, '0')}/${value.month.toString().padLeft(2, '0')}/${value.year}';
