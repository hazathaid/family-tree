import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.familySearch)),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          TextField(
              controller: keyword,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(labelText: l10n.keyword),
              onSubmitted: (_) => load()),
          ExpansionTile(title: Text(l10n.advancedFilters), children: [
            TextField(
                controller: name,
                decoration: InputDecoration(labelText: l10n.memberName)),
            TextField(
                controller: city,
                decoration:
                    InputDecoration(labelText: l10n.birthDeathCity)),
            DropdownButtonFormField<String>(
                initialValue: status,
                decoration: InputDecoration(labelText: l10n.livingStatus),
                items: [
                  DropdownMenuItem(value: 'alive', child: Text(l10n.alive)),
                  DropdownMenuItem(
                      value: 'deceased', child: Text(l10n.deceased))
                ],
                onChanged: (v) => setState(() => status = v)),
            TextField(
                controller: generation,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.relativeGeneration)),
            TextField(
                controller: root,
                decoration: InputDecoration(
                    labelText: l10n.rootMemberUuid,
                    helperText: l10n.generationFilterHelper))
          ]),
          const SizedBox(height: 12),
          FilledButton.icon(
              onPressed: loading ? null : load,
              icon: const Icon(Icons.search),
              label: Text(l10n.searchAction)),
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
            _Empty(l10n.noSearchResults),
          if (results != null) ...[
            _Group(
              title: l10n.membersGroup,
              children: results!.members.map((m) => ListTile(
                    minTileHeight: 56,
                    title: Text(m.displayName),
                    subtitle: Text([
                      m.birthPlace,
                      if (m.generation != null)
                        l10n.generationLabel(m.generation!)
                    ].whereType<String>().join(' · ')),
                    onTap: () => context.push('/members/${m.uuid}'),
                  )),
            ),
            _Group(
              title: l10n.articlesGroup,
              children: results!.articles.map((a) => ListTile(
                    minTileHeight: 56,
                    title: Text(a.title),
                    subtitle: Text(a.authorName),
                    onTap: () => context.push('/articles/${a.uuid}'),
                  )),
            ),
            _Group(
              title: l10n.eventsGroup,
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
                  child: Text(l10n.loadMore)),
          ],
        ]));
  }
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.reportsTitle)),
        body: loading
            ? const Center(child: CircularProgressIndicator())
            : error != null
                ? _Error(error!.message, load)
                : RefreshIndicator(
                    onRefresh: load,
                    child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [
                          Wrap(spacing: 8, children: [
                            ActionChip(
                                label: Text(l10n.reportFromDate(_date(from))),
                                onPressed: () => pick(true)),
                            ActionChip(
                                label: Text(l10n.reportToDate(
                                    _date(to), DateTime.now().timeZoneName)),
                                onPressed: () => pick(false))
                          ]),
                          GridView.count(
                              crossAxisCount:
                                  MediaQuery.sizeOf(context).width >= 600
                                      ? 4
                                      : 2,
                              shrinkWrap: true,
                              physics:
                                  const NeverScrollableScrollPhysics(),
                              childAspectRatio: 1.6,
                              children: [
                                _Stat(l10n.membersGroup,
                                    statistics!.totalMembers),
                                _Stat(l10n.alive,
                                    statistics!.aliveMembers),
                                _Stat(l10n.deceased,
                                    statistics!.deceasedMembers),
                                _Stat(l10n.reportsActiveUsers,
                                    activity!.activeUsers),
                                _Stat(l10n.reportsUploads, activity!.uploads),
                                _Stat(l10n.reportsArticles,
                                    activity!.articles)
                              ]),
                          _DataSection(l10n.generationDistribution,
                              statistics!.generations),
                          _DataSection(l10n.reportCities, insights!.cities),
                          _DataSection(
                              l10n.memberGrowth, insights!.growth),
                          _DataSection(
                              l10n.activityTrend, insights!.activity)
                        ])));
  }
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.contributionRanking)),
        body: loading
            ? const Center(child: CircularProgressIndicator())
            : error != null
                ? _Error(error!.message, load)
                : RefreshIndicator(
                    onRefresh: load,
                    child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [
                          Semantics(
                              label: l10n.contributionPointsLabel(
                                  profile!.points),
                              child: Card(
                                  child: ListTile(
                                      minTileHeight: 64,
                                      leading: const Icon(Icons.stars),
                                      title: Text(
                                          l10n.pointsLabel(profile!.points)),
                                      subtitle: Text(
                                          l10n.yourContribution)))),
                          Text(l10n.myBadges,
                              style: Theme.of(context).textTheme.titleLarge),
                          if (profile!.badges.isEmpty)
                            _Empty(l10n.noBadges)
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
                          _Leaderboard(l10n.familyUserRanking, users),
                          _Leaderboard(l10n.familyRanking, families)
                        ])));
  }
}

class _Group extends StatelessWidget {
  const _Group({required this.title, required this.children});
  final String title;
  final Iterable<Widget> children;
  @override
  Widget build(BuildContext context) {
    final list = children.toList();
    if (list.isEmpty) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    return Semantics(
        container: true,
        label: l10n.groupSemantics(title, list.length),
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
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Semantics(
        container: true,
        label: l10n.dataSemantics(title),
        child: Card(
            child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title,
                          style: Theme.of(context).textTheme.titleMedium),
                      if (rows.isEmpty)
                        _Empty(l10n.noData)
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
                                                  e.total > max
                                                      ? e.total
                                                      : max),
                                      semanticsLabel: l10n.reportRowSemantics(
                                          r.label, r.total))),
                              const SizedBox(width: 8),
                              Text('${r.total}')
                            ])))
                    ]))));
  }
}

class _Leaderboard extends StatelessWidget {
  const _Leaderboard(this.title, this.rows);
  final String title;
  final List<LeaderboardEntry> rows;
  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              if (rows.isEmpty)
                _Empty(l10n.noRankings)
              else
                ...rows.map((r) => ListTile(
                      minTileHeight: 56,
                      leading: CircleAvatar(child: Text('${r.rank}')),
                      title: Text(r.name),
                      trailing: Text(l10n.pointsLabel(r.points)),
                    )),
            ],
          ),
        ),
      );
  }
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
            OutlinedButton(
                onPressed: retry,
                child: Text(AppLocalizations.of(context).retry))
          ])));
}

String _date(DateTime value) =>
    '${value.day.toString().padLeft(2, '0')}/${value.month.toString().padLeft(2, '0')}/${value.year}';
