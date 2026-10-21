import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/errors/app_error.dart';
import '../../../core/models.dart';
import '../../../core/providers.dart';
import '../../../l10n/app_localizations.dart';
import '../domain/family_link_models.dart';

class FamilyLinksScreen extends ConsumerStatefulWidget {
  const FamilyLinksScreen({super.key});

  @override
  ConsumerState<FamilyLinksScreen> createState() => _FamilyLinksScreenState();
}

class _FamilyLinksScreenState extends ConsumerState<FamilyLinksScreen> {
  final search = TextEditingController();
  List<FamilyLink>? links;
  List<FamilyMember> localMembers = const [];
  List<FamilyLinkCandidate> candidates = const [];
  String? localMemberUuid;
  Object? error;
  bool loading = true;
  bool searching = false;
  bool searched = false;
  bool requesting = false;

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

  Future<void> load() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final result =
          await ref.read(familyLinkRepositoryProvider).links(family.uuid);
      var members = localMembers;
      if (family.canManage && members.isEmpty) {
        final page = await ref
            .read(memberRepositoryProvider)
            .members(family.uuid, limit: 100);
        members = page.items;
      }
      if (!mounted) return;
      setState(() {
        links = result;
        localMembers = members;
        localMemberUuid ??= members.isEmpty ? null : members.first.uuid;
        loading = false;
      });
    } catch (exception) {
      if (!mounted) return;
      setState(() {
        error = exception;
        loading = false;
      });
    }
  }

  Future<void> searchCandidates() async {
    final family = ref.read(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    if (family == null) return;
    final term = search.text.trim();
    if (localMemberUuid == null) {
      _snack(l10n.familyLinkMemberRequired);
      return;
    }
    if (term.length < 2) {
      _snack(l10n.familyLinkSearchMin);
      return;
    }
    setState(() => searching = true);
    try {
      final result = await ref
          .read(familyLinkRepositoryProvider)
          .candidates(family.uuid, search: term);
      if (!mounted) return;
      setState(() {
        candidates = result;
        searched = true;
        searching = false;
      });
    } catch (exception) {
      if (!mounted) return;
      setState(() => searching = false);
      _snack(_message(exception, l10n));
    }
  }

  Future<void> request(FamilyLinkCandidate candidate) async {
    final family = ref.read(currentFamilyProvider);
    final memberUuid = localMemberUuid;
    if (family == null || memberUuid == null || requesting) return;
    final l10n = AppLocalizations.of(context);
    setState(() => requesting = true);
    try {
      await ref.read(familyLinkRepositoryProvider).request(family.uuid,
          memberUuid: memberUuid, linkedMemberUuid: candidate.uuid);
      if (!mounted) return;
      search.clear();
      setState(() {
        candidates = const [];
        searched = false;
        requesting = false;
      });
      _snack(l10n.familyLinkRequested);
      await load();
    } catch (exception) {
      if (!mounted) return;
      setState(() => requesting = false);
      _snack(_message(exception, l10n));
    }
  }

  Future<void> approve(FamilyLink link) async {
    final l10n = AppLocalizations.of(context);
    try {
      await ref.read(familyLinkRepositoryProvider).approve(link.uuid);
      if (!mounted) return;
      _snack(l10n.familyLinkApproved);
      await load();
    } catch (exception) {
      if (!mounted) return;
      _snack(_message(exception, l10n));
    }
  }

  Future<void> revoke(FamilyLink link) async {
    final l10n = AppLocalizations.of(context);
    final confirmed = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
                title: Text(l10n.familyLinkRevokeConfirmTitle),
                content: Text(l10n.familyLinkRevokeConfirmBody),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(dialogContext, false),
                      child: Text(l10n.cancel)),
                  FilledButton(
                      onPressed: () => Navigator.pop(dialogContext, true),
                      child: Text(l10n.familyLinkRevoke))
                ]));
    if (confirmed != true) return;
    try {
      await ref.read(familyLinkRepositoryProvider).revoke(link.uuid);
      if (!mounted) return;
      _snack(l10n.familyLinkRevoked);
      await load();
    } catch (exception) {
      if (!mounted) return;
      _snack(_message(exception, l10n));
    }
  }

  String _message(Object exception, AppLocalizations l10n) =>
      exception is AppError ? exception.message : l10n.genericError;

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final family = ref.watch(currentFamilyProvider);
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(title: Text(l10n.familyLinksTitle)),
        body: RefreshIndicator(
            onRefresh: load,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Text(l10n.familyLinksIntro),
              const SizedBox(height: 16),
              if (family?.canManage == true) _requestCard(l10n),
              if (family?.canManage == true) const SizedBox(height: 16),
              Text(l10n.familyLinksList,
                  style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              _linksBody(family?.uuid, l10n),
            ])));
  }

  Widget _requestCard(AppLocalizations l10n) {
    return Card(
        child: Padding(
            padding: const EdgeInsets.all(16),
            child:
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(l10n.familyLinkRequestTitle,
                  style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              Text(l10n.familyLinkRequestHint),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                  initialValue: localMemberUuid,
                  decoration:
                      InputDecoration(labelText: l10n.familyLinkLocalMember),
                  items: localMembers
                      .map((member) => DropdownMenuItem(
                          value: member.uuid, child: Text(member.displayName)))
                      .toList(),
                  onChanged: (value) =>
                      setState(() => localMemberUuid = value)),
              const SizedBox(height: 12),
              SearchBar(
                  controller: search,
                  hintText: l10n.familyLinkSearchHint,
                  leading: const Icon(Icons.search),
                  onSubmitted: (_) => searchCandidates()),
              const SizedBox(height: 8),
              Align(
                  alignment: Alignment.centerRight,
                  child: FilledButton.tonalIcon(
                      onPressed: searching ? null : searchCandidates,
                      icon: searching
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.person_search),
                      label: Text(l10n.familyLinkSearchAction))),
              if (searching) const LinearProgressIndicator(),
              if (!searching && searched && candidates.isEmpty)
                Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(l10n.familyLinkNoCandidates)),
              ...candidates.map((candidate) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(candidate.isAlive
                      ? Icons.person_outline
                      : Icons.local_florist),
                  title: Text(candidate.fullName),
                  subtitle:
                      Text(l10n.familyLinkFamilyLabel(candidate.family.name)),
                  trailing: requesting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2))
                      : const Icon(Icons.link),
                  onTap: requesting ? null : () => request(candidate))),
            ])));
  }

  Widget _linksBody(String? familyUuid, AppLocalizations l10n) {
    if (loading && links == null) {
      return const Padding(
          padding: EdgeInsets.all(24),
          child: Center(child: CircularProgressIndicator()));
    }
    if (error != null) {
      return Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
              child: FilledButton.icon(
                  onPressed: load,
                  icon: const Icon(Icons.refresh),
                  label: Text(l10n.retry))));
    }
    final items = links ?? const <FamilyLink>[];
    if (items.isEmpty) {
      return Padding(
          padding: const EdgeInsets.all(24),
          child: Center(child: Text(l10n.familyLinksEmpty)));
    }
    return Column(children: [
      if (loading) const LinearProgressIndicator(),
      ...items.map((link) => _linkTile(link, familyUuid, l10n))
    ]);
  }

  Widget _linkTile(FamilyLink link, String? familyUuid, AppLocalizations l10n) {
    final local = familyUuid == null ? null : link.localMember(familyUuid);
    final remote = familyUuid == null ? null : link.remoteMember(familyUuid);
    final remoteFamily =
        familyUuid == null ? link.linkedFamily : link.remoteFamily(familyUuid);
    final statusLabel = switch (link.status) {
      'active' => l10n.familyLinkStatusActive,
      'pending' => l10n.familyLinkStatusPending,
      'revoked' => l10n.familyLinkStatusRevoked,
      _ => l10n.familyLinkStatusExpired,
    };
    final statusColor = switch (link.status) {
      'active' => Colors.green,
      'pending' => Colors.orange,
      _ => Colors.grey,
    };
    return Card(
        child: Padding(
            padding: const EdgeInsets.all(12),
            child:
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Chip(
                    label: Text(statusLabel),
                    backgroundColor: statusColor.withValues(alpha: .12),
                    side: BorderSide(color: statusColor),
                    visualDensity: VisualDensity.compact),
                const SizedBox(width: 8),
                Expanded(child: Text(remoteFamily.name))
              ]),
              const SizedBox(height: 8),
              Text('${local?.fullName ?? ''} ↔ ${remote?.fullName ?? ''}',
                  style: Theme.of(context).textTheme.bodyLarge),
              const SizedBox(height: 8),
              Wrap(spacing: 8, runSpacing: 8, children: [
                if (link.isActive && familyUuid != null)
                  OutlinedButton.icon(
                      onPressed: () => context.push(
                          '/family-links/${link.uuid}/tree',
                          extra: remoteFamily.name),
                      icon: const Icon(Icons.account_tree_outlined),
                      label: Text(l10n.familyLinkViewTree)),
                if (link.canApprove && link.isPending)
                  FilledButton(
                      onPressed: () => approve(link),
                      child: Text(l10n.familyLinkApprove)),
                if (link.canRevoke && !link.isRevoked && !link.isExpired)
                  TextButton(
                      onPressed: () => revoke(link),
                      child: Text(l10n.familyLinkRevoke)),
              ])
            ])));
  }
}

class FamilyLinkTreeScreen extends ConsumerStatefulWidget {
  const FamilyLinkTreeScreen(
      {super.key, required this.linkUuid, this.familyUuid, this.familyName});

  final String linkUuid;
  final String? familyUuid;
  final String? familyName;

  @override
  ConsumerState<FamilyLinkTreeScreen> createState() =>
      _FamilyLinkTreeScreenState();
}

class _FamilyLinkTreeScreenState extends ConsumerState<FamilyLinkTreeScreen> {
  final transformation = TransformationController();
  FamilyTree? tree;
  Object? error;
  bool loading = true;
  bool semanticList = false;
  int depth = 3;
  String layout = 'vertical';

  @override
  void initState() {
    super.initState();
    Future.microtask(load);
  }

  @override
  void dispose() {
    transformation.dispose();
    super.dispose();
  }

  Future<void> load() async {
    final familyUuid =
        widget.familyUuid ?? ref.read(currentFamilyProvider)?.uuid;
    if (familyUuid == null) {
      setState(() {
        loading = false;
        error = const AppError(
            AppErrorType.validation, 'Pilih keluarga terlebih dahulu.');
      });
      return;
    }
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final result = await ref.read(familyLinkRepositoryProvider).partnerTree(
          widget.linkUuid,
          familyUuid: familyUuid,
          depth: depth,
          layout: layout);
      if (!mounted) return;
      setState(() {
        tree = result;
        loading = false;
      });
      WidgetsBinding.instance.addPostFrameCallback((_) => centerRoot());
    } catch (exception) {
      if (!mounted) return;
      setState(() {
        error = exception;
        loading = false;
      });
    }
  }

  void centerRoot() {
    final current = tree;
    if (current == null || !mounted || current.nodes.isEmpty) return;
    final node = current.nodes
        .firstWhere((item) => item.isRoot, orElse: () => current.nodes.first);
    final viewport = MediaQuery.sizeOf(context);
    transformation.value = Matrix4.identity()
      ..translateByDouble(viewport.width / 2 - node.x - 80,
          viewport.height / 2 - node.y - 60, 0, 1);
  }

  void zoom(double factor) {
    final value = transformation.value.clone();
    value.scaleByDouble(factor, factor, factor, 1);
    transformation.value = value;
  }

  void showDetails(TreeNode node) {
    final l10n = AppLocalizations.of(context);
    showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        builder: (sheetContext) => Padding(
            padding: const EdgeInsets.all(16),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              ListTile(
                  leading: Icon(node.isRedacted
                      ? Icons.visibility_off
                      : node.isAlive
                          ? Icons.person
                          : Icons.local_florist),
                  title: Text(node.displayName),
                  subtitle: Text(
                      node.relationshipToRoot ?? l10n.relationshipUnavailable)),
              Text(l10n.familyLinkReadOnlyNotice),
              const SizedBox(height: 12),
              SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                      onPressed: () => Navigator.pop(sheetContext),
                      child: Text(l10n.close)))
            ])));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
        appBar: AppBar(
            title: Text(widget.familyName == null
                ? l10n.familyLinkPartnerTreeTitle
                : l10n.familyLinkPartnerTreeTitleFor(widget.familyName!))),
        body: Column(children: [
          if (tree?.readOnly == true)
            Material(
                color: Theme.of(context).colorScheme.secondaryContainer,
                child: ListTile(
                    leading: const Icon(Icons.lock_outline),
                    title: Text(l10n.familyLinkReadOnly),
                    subtitle: Text(l10n.familyLinkReadOnlyNotice))),
          _controls(l10n),
          Expanded(child: _content(l10n)),
        ]));
  }

  Widget _controls(AppLocalizations l10n) {
    return Padding(
        padding: const EdgeInsets.all(12),
        child: Wrap(spacing: 8, runSpacing: 8, children: [
          DropdownButton<String>(
              value: layout,
              items: [
                DropdownMenuItem(value: 'vertical', child: Text(l10n.vertical)),
                DropdownMenuItem(
                    value: 'horizontal', child: Text(l10n.horizontal)),
                DropdownMenuItem(value: 'radial', child: Text(l10n.radial)),
                DropdownMenuItem(value: 'compact', child: Text(l10n.compact))
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() => layout = value);
                  load();
                }
              }),
          OutlinedButton.icon(
              onPressed: depth > 1
                  ? () {
                      setState(() => depth--);
                      load();
                    }
                  : null,
              icon: const Icon(Icons.unfold_less),
              label: Text(l10n.collapseDepth(depth))),
          OutlinedButton.icon(
              onPressed: depth < 20
                  ? () {
                      setState(() => depth++);
                      load();
                    }
                  : null,
              icon: const Icon(Icons.unfold_more),
              label: Text(l10n.expandDepth(depth))),
          FilterChip(
              label: Text(l10n.semanticList),
              selected: semanticList,
              onSelected: (value) => setState(() => semanticList = value)),
          IconButton(
              tooltip: l10n.zoomOut,
              onPressed: () => zoom(.8),
              icon: const Icon(Icons.zoom_out)),
          IconButton(
              tooltip: l10n.zoomIn,
              onPressed: () => zoom(1.25),
              icon: const Icon(Icons.zoom_in)),
          IconButton(
              tooltip: l10n.centerTree,
              onPressed: centerRoot,
              icon: const Icon(Icons.center_focus_strong)),
        ]));
  }

  Widget _content(AppLocalizations l10n) {
    if (loading && tree == null) {
      return Center(
          child: Semantics(
              liveRegion: true,
              label: l10n.loadingTree,
              child: const CircularProgressIndicator()));
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: load,
              icon: const Icon(Icons.refresh),
              label: Text(l10n.retry)));
    }
    final current = tree;
    if (current == null || current.nodes.isEmpty) {
      return Center(child: Text(l10n.treeEmpty));
    }
    if (semanticList) {
      return ListView.builder(
          itemCount: current.nodes.length,
          itemBuilder: (_, index) {
            final node = current.nodes[index];
            return ListTile(
                leading: Icon(node.isRedacted
                    ? Icons.visibility_off
                    : node.isAlive
                        ? Icons.person_outline
                        : Icons.local_florist),
                title: Text(node.displayName),
                subtitle: Text(
                    '${node.relationshipToRoot ?? l10n.treeUnknownRelationship} · ${l10n.generationLabel(node.generation)}'),
                onTap: () => showDetails(node));
          });
    }
    final byUuid = {for (final node in current.nodes) node.uuid: node};
    return Stack(children: [
      InteractiveViewer(
          transformationController: transformation,
          minScale: .2,
          maxScale: 4,
          boundaryMargin: const EdgeInsets.all(600),
          constrained: false,
          child: CustomPaint(
              size: Size(current.viewportWidth, current.viewportHeight),
              painter: _LinkTreePainter(current.nodes, current.edges, byUuid),
              child: SizedBox(
                  width: current.viewportWidth,
                  height: current.viewportHeight,
                  child: Stack(children: [
                    ...current.nodes.map((node) => Positioned(
                        left: node.x - 80,
                        top: node.y - 45,
                        child: Semantics(
                            button: true,
                            label: l10n.treeNodeSemantics(
                                node.displayName,
                                node.relationshipToRoot ??
                                    l10n.treeRelationshipUnknown,
                                node.isAlive
                                    ? l10n.treeAliveLower
                                    : l10n.treeDeceasedLower),
                            child: InkWell(
                                onTap: () => showDetails(node),
                                child: Container(
                                    width: 160,
                                    height: 90,
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                        color: node.isRedacted
                                            ? Colors.grey.shade200
                                            : node.isRoot
                                                ? Colors.blue.shade100
                                                : Colors.white,
                                        border: Border.all(
                                            color: node.isAlive
                                                ? Colors.blueGrey
                                                : Colors.grey,
                                            width: node.isRoot ? 2 : 1),
                                        borderRadius:
                                            BorderRadius.circular(12)),
                                    child: Column(
                                        mainAxisAlignment:
                                            MainAxisAlignment.center,
                                        children: [
                                          Text(node.displayName,
                                              textAlign: TextAlign.center,
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis),
                                          Text(node.relationshipToRoot ?? '—',
                                              style: Theme.of(context)
                                                  .textTheme
                                                  .labelSmall),
                                          if (node.isRedacted)
                                            Icon(Icons.visibility_off,
                                                size: 14,
                                                semanticLabel:
                                                    l10n.familyLinkReadOnly)
                                        ])))))),
                  ])))),
      if (loading)
        const Positioned(top: 8, right: 8, child: CircularProgressIndicator()),
    ]);
  }
}

class _LinkTreePainter extends CustomPainter {
  const _LinkTreePainter(this.nodes, this.edges, this.byUuid);

  final List<TreeNode> nodes;
  final List<TreeEdge> edges;
  final Map<String, TreeNode> byUuid;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xff90a4ae)
      ..strokeWidth = 2;
    for (final edge in edges) {
      final source = byUuid[edge.sourceUuid];
      final target = byUuid[edge.targetUuid];
      if (source != null && target != null) {
        canvas.drawLine(
            Offset(source.x, source.y), Offset(target.x, target.y), paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _LinkTreePainter oldDelegate) =>
      oldDelegate.nodes != nodes || oldDelegate.edges != edges;
}
