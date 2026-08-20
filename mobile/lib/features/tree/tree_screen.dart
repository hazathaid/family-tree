import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../../core/errors/app_error.dart';
import '../../core/http/page_data.dart';
import '../../core/models.dart';
import '../../core/providers.dart';
import '../../l10n/app_localizations.dart';
import 'domain/tree_render_policy.dart';

class TreeScreen extends ConsumerStatefulWidget {
  const TreeScreen({super.key});
  @override
  ConsumerState<TreeScreen> createState() => _TreeScreenState();
}

class _TreeScreenState extends ConsumerState<TreeScreen> {
  final transformation = TransformationController();
  final search = TextEditingController();
  FamilyMember? root;
  FamilyTree? tree;
  Object? error;
  String mode = 'full';
  String layout = 'vertical';
  int depth = 3;
  bool livingOnly = false;
  bool loading = false;
  bool showSemanticList = false;
  String? focusedUuid;
  CancelToken? exportCancellation;
  double? exportProgress;

  @override
  void initState() {
    super.initState();
    final uuid = ref.read(currentMemberUuidProvider);
    if (uuid != null) {
      Future.microtask(() async {
        try {
          final member = await ref.read(memberRepositoryProvider).member(uuid);
          if (mounted) {
            setState(() => root = member);
            await generate();
          }
        } catch (_) {}
      });
    }
  }

  @override
  void dispose() {
    exportCancellation?.cancel();
    transformation.dispose();
    search.dispose();
    super.dispose();
  }

  Future<void> pickRoot() async {
    final family = ref.read(currentFamilyProvider);
    if (family == null) return;
    final selected = await showDialog<FamilyMember>(
        context: context,
        builder: (_) => _TreeRootPicker(familyUuid: family.uuid));
    if (selected == null) return;
    ref.read(currentMemberUuidProvider.notifier).state = selected.uuid;
    setState(() {
      root = selected;
      focusedUuid = null;
    });
    await generate();
  }

  Future<void> generate() async {
    if (root == null) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final value = await ref
          .read(treeRepositoryProvider)
          .generate(root!.uuid, mode: mode, depth: depth, layout: layout);
      if (!mounted) return;
      setState(() {
        tree = value;
        loading = false;
      });
      WidgetsBinding.instance.addPostFrameCallback((_) => centerRoot());
    } catch (exception) {
      if (mounted) {
        setState(() {
          error = exception;
          loading = false;
        });
      }
    }
  }

  List<TreeNode> get filteredNodes {
    return TreeRenderPolicy.project(tree?.nodes ?? const [],
        livingOnly: livingOnly);
  }

  void centerRoot() {
    final current = tree;
    if (current == null || !mounted) return;
    final node = current.nodes
        .firstWhere((item) => item.isRoot, orElse: () => current.nodes.first);
    focus(node);
  }

  void focus(TreeNode node) {
    final viewport = MediaQuery.sizeOf(context);
    const scale = 1.0;
    transformation.value = Matrix4.identity()
      ..translateByDouble(viewport.width / 2 - node.x * scale - 80,
          viewport.height / 2 - node.y * scale - 50, 0, 1)
      ..scaleByDouble(scale, scale, scale, 1);
    setState(() => focusedUuid = node.uuid);
  }

  void searchNode() {
    final query = search.text.trim().toLowerCase();
    if (query.isEmpty || tree == null) return;
    final matches =
        tree!.nodes.where((node) => node.name.toLowerCase().contains(query));
    if (matches.isNotEmpty) focus(matches.first);
  }

  void zoom(double factor) {
    final value = transformation.value.clone();
    value.scaleByDouble(factor, factor, factor, 1);
    transformation.value = value;
  }

  Future<void> export(String format) async {
    if (root == null || exportCancellation != null) return;
    final cancellation = CancelToken();
    setState(() {
      exportCancellation = cancellation;
      exportProgress = 0;
    });
    try {
      final bytes = await ref.read(treeRepositoryProvider).export(
          format, root!.uuid,
          mode: mode,
          depth: depth,
          layout: layout,
          paperSize: 'A4',
          cancelToken: cancellation, onProgress: (received, total) {
        if (mounted && total > 0) {
          setState(() => exportProgress = received / total);
        }
      });
      if (!mounted) return;
      setState(() {
        exportCancellation = null;
        exportProgress = null;
      });
      await _previewAndShare(format, bytes);
    } on AppError catch (exception) {
      if (!mounted) return;
      setState(() {
        exportCancellation = null;
        exportProgress = null;
      });
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(exception.message)));
    } catch (_) {
      if (!mounted) return;
      setState(() {
        exportCancellation = null;
        exportProgress = null;
      });
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(l10n.exportShareFailed)));
    }
  }

  Future<void> _previewAndShare(String format, Uint8List bytes) async {
    final l10n = AppLocalizations.of(context);
    final mime = format == 'png' ? 'image/png' : 'application/pdf';
    final share = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text(l10n.exportReady(format.toUpperCase())),
                content: format == 'png'
                    ? ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 420),
                        child: Image.memory(bytes,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __, ___) =>
                                Text(l10n.previewUnavailable)))
                    : Text(l10n.pdfExportReady((bytes.length / 1024).ceil())),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: Text(l10n.close)),
                  FilledButton.icon(
                      onPressed: () => Navigator.pop(context, true),
                      icon: const Icon(Icons.share),
                      label: Text(l10n.share))
                ]));
    if (share == true) {
      await SharePlus.instance.share(ShareParams(files: [
        XFile.fromData(bytes, mimeType: mime, name: 'family-tree.$format')
      ]));
    }
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
                  leading:
                      Icon(node.isAlive ? Icons.person : Icons.local_florist),
                  title: Text(node.displayName),
                  subtitle: Text(
                      '${node.relationshipToRoot ?? l10n.relationshipUnavailable} · ${node.isAlive ? l10n.alive : l10n.deceased}')),
              SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                      onPressed: () {
                        Navigator.pop(sheetContext);
                        context.push('/members/${node.uuid}');
                      },
                      child: Text(l10n.openMemberDetail))),
              if (node.canAddRelative) ...[
                const SizedBox(height: 12),
                Wrap(spacing: 8, runSpacing: 8, children: [
                  OutlinedButton.icon(
                      onPressed: () =>
                          _openRelativeForm(sheetContext, node, 'parent'),
                      icon: const Icon(Icons.supervisor_account_outlined),
                      label: Text(l10n.addParent)),
                  OutlinedButton.icon(
                      onPressed: () =>
                          _openRelativeForm(sheetContext, node, 'spouse'),
                      icon: const Icon(Icons.favorite_border),
                      label: Text(l10n.addSpouse)),
                  OutlinedButton.icon(
                      onPressed: () =>
                          _openRelativeForm(sheetContext, node, 'child'),
                      icon: const Icon(Icons.child_care_outlined),
                      label: Text(l10n.addChild)),
                ]),
              ]
            ])));
  }

  void _openRelativeForm(
      BuildContext sheetContext, TreeNode node, String relation) {
    Navigator.pop(sheetContext);
    final l10n = AppLocalizations.of(context);
    final name = TextEditingController();
    final formKey = GlobalKey<FormState>();
    var gender = 'male';
    var saving = false;
    final label = switch (relation) {
      'parent' => l10n.relativeLabelParent,
      'spouse' => l10n.relativeLabelSpouse,
      _ => l10n.relativeLabelChild,
    };

    showDialog<void>(
        context: context,
        builder: (dialogContext) => StatefulBuilder(
            builder: (dialogStateContext, setDialogState) => AlertDialog(
                    title: Text(l10n.addRelative(label)),
                    content: Form(
                        key: formKey,
                        child:
                            Column(mainAxisSize: MainAxisSize.min, children: [
                          Text(l10n.forMember(node.displayName)),
                          const SizedBox(height: 12),
                          TextFormField(
                              controller: name,
                              autofocus: true,
                              decoration: InputDecoration(
                                  labelText: l10n.fullName),
                              validator: (value) =>
                                  value?.trim().isEmpty ?? true
                                      ? l10n.nameRequired
                                      : null),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            initialValue: gender,
                            decoration: InputDecoration(
                                labelText: l10n.gender),
                            items: [
                              DropdownMenuItem(
                                  value: 'male', child: Text(l10n.male)),
                              DropdownMenuItem(
                                  value: 'female', child: Text(l10n.female)),
                            ],
                            onChanged: saving
                                ? null
                                : (value) =>
                                    setDialogState(() => gender = value!),
                          ),
                        ])),
                    actions: [
                      TextButton(
                          onPressed: saving
                              ? null
                              : () => Navigator.pop(dialogStateContext),
                          child: Text(l10n.cancel)),
                      FilledButton(
                          onPressed: saving
                              ? null
                              : () async {
                                  if (!formKey.currentState!.validate()) return;
                                  setDialogState(() => saving = true);
                                  final navigator =
                                      Navigator.of(dialogStateContext);
                                  final messenger =
                                      ScaffoldMessenger.of(context);
                                  try {
                                    await ref
                                        .read(treeRepositoryProvider)
                                        .createRelative(node.uuid, {
                                      'relation': relation,
                                      'full_name': name.text.trim(),
                                      'gender': gender,
                                      'is_alive': true,
                                    });
                                    if (!mounted) return;
                                    navigator.pop();
                                    await generate();
                                    if (mounted) {
                                      messenger.showSnackBar(SnackBar(
                                          content: Text(l10n.relativeAdded(
                                              name.text.trim()))));
                                    }
                                  } on AppError catch (error) {
                                    setDialogState(() => saving = false);
                                    messenger.showSnackBar(
                                        SnackBar(content: Text(error.message)));
                                  } catch (_) {
                                    setDialogState(() => saving = false);
                                    messenger.showSnackBar(SnackBar(
                                        content: Text(l10n.relativeAddFailed)));
                                  }
                                },
                          child: saving
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator())
                              : Text(l10n.addAction)),
                    ])));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Column(children: [
        _controls(),
        if (exportCancellation != null)
          ListTile(
              leading: const CircularProgressIndicator(),
              title: Text(l10n.preparingExport),
              subtitle: LinearProgressIndicator(value: exportProgress),
              trailing: IconButton(
                  tooltip: l10n.cancelExport,
                  onPressed: exportCancellation!.cancel,
                  icon: const Icon(Icons.close))),
        Expanded(child: _content()),
      ]);
  }

  Widget _controls() {
    final l10n = AppLocalizations.of(context);
    return Material(
        elevation: 1,
        child: ExpansionTile(
            initiallyExpanded: true,
            title: Text(root?.displayName ?? l10n.pickTreeRoot),
            leading: const Icon(Icons.account_tree),
            childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
            children: [
              Row(children: [
                Expanded(
                    child: OutlinedButton.icon(
                        onPressed: pickRoot,
                        icon: const Icon(Icons.person_search),
                        label: Text(l10n.chooseCenter))),
                const SizedBox(width: 8),
                PopupMenuButton<String>(
                    tooltip: l10n.exportTree,
                    onSelected: export,
                    itemBuilder: (_) => [
                          PopupMenuItem(
                              value: 'png', child: Text(l10n.exportPng)),
                          PopupMenuItem(
                              value: 'pdf', child: Text(l10n.exportPdf))
                        ],
                    child: const Padding(
                        padding: EdgeInsets.all(12), child: Icon(Icons.download)))
              ]),
              Wrap(spacing: 8, runSpacing: 8, children: [
                DropdownButton<String>(
                    value: mode,
                    items: [
                      DropdownMenuItem(
                          value: 'ancestor', child: Text(l10n.ancestor)),
                      DropdownMenuItem(
                          value: 'descendant',
                          child: Text(l10n.descendant)),
                      DropdownMenuItem(
                          value: 'full', child: Text(l10n.fullTree))
                    ],
                    onChanged: (value) {
                      if (value != null) {
                        setState(() => mode = value);
                        generate();
                      }
                    }),
                DropdownButton<String>(
                    value: layout,
                    items: [
                      DropdownMenuItem(
                          value: 'vertical', child: Text(l10n.vertical)),
                      DropdownMenuItem(
                          value: 'horizontal',
                          child: Text(l10n.horizontal)),
                      DropdownMenuItem(
                          value: 'radial', child: Text(l10n.radial)),
                      DropdownMenuItem(
                          value: 'compact', child: Text(l10n.compact))
                    ],
                    onChanged: (value) {
                      if (value != null) {
                        setState(() => layout = value);
                        generate();
                      }
                    }),
                OutlinedButton.icon(
                    onPressed: tree?.canCollapse == true
                        ? () {
                            setState(() => depth--);
                            generate();
                          }
                        : null,
                    icon: const Icon(Icons.unfold_less),
                    label: Text(l10n.collapseDepth(depth))),
                OutlinedButton.icon(
                    onPressed: tree?.canExpand == true
                        ? () {
                            setState(() => depth++);
                            generate();
                          }
                        : null,
                    icon: const Icon(Icons.unfold_more),
                    label: Text(l10n.expandDepth(depth))),
                FilterChip(
                    label: Text(l10n.livingOnly),
                    selected: livingOnly,
                    onSelected: (value) => setState(() => livingOnly = value)),
                FilterChip(
                    label: Text(l10n.semanticList),
                    selected: showSemanticList,
                    onSelected: (value) =>
                        setState(() => showSemanticList = value)),
              ]),
              Row(children: [
                Expanded(
                    child: TextField(
                        controller: search,
                        onSubmitted: (_) => searchNode(),
                        decoration: InputDecoration(
                            labelText: l10n.searchFocusMember,
                            prefixIcon: const Icon(Icons.search)))),
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
                    icon: const Icon(Icons.center_focus_strong))
              ])
            ]));
  }

  Widget _content() {
    final l10n = AppLocalizations.of(context);
    if (root == null) {
      return Center(
          child: FilledButton.icon(
              onPressed: pickRoot,
              icon: const Icon(Icons.person_search),
              label: Text(l10n.pickCenterMember)));
    }
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
              onPressed: generate,
              icon: const Icon(Icons.refresh),
              label: Text(l10n.retry)));
    }
    final current = tree;
    if (current == null || current.nodes.isEmpty) {
      return Center(child: Text(l10n.treeEmpty));
    }
    final rendered = filteredNodes;
    if (showSemanticList) {
      return ListView.builder(
          itemCount: rendered.length,
          itemBuilder: (_, index) {
            final node = rendered[index];
            return ListTile(
                leading: Icon(
                    node.isAlive ? Icons.person_outline : Icons.local_florist),
                title: Text(node.displayName),
                subtitle: Text(
                    '${node.relationshipToRoot ?? l10n.treeUnknownRelationship} · ${l10n.generationLabel(node.generation)}'),
                onTap: () => showDetails(node));
          });
    }
    final nodeUuids = rendered.map((node) => node.uuid).toSet();
    final edges = current.edges
        .where((edge) =>
            nodeUuids.contains(edge.sourceUuid) &&
            nodeUuids.contains(edge.targetUuid))
        .toList(growable: false);
    return Stack(children: [
      InteractiveViewer(
          transformationController: transformation,
          minScale: .2,
          maxScale: 4,
          boundaryMargin: const EdgeInsets.all(600),
          constrained: false,
          child: CustomPaint(
              size: Size(current.viewportWidth, current.viewportHeight),
              painter: _TreePainter(rendered, edges),
              child: SizedBox(
                  width: current.viewportWidth,
                  height: current.viewportHeight,
                  child: Stack(
                      children: rendered
                          .map((node) => Positioned(
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
                                              color: node.uuid == focusedUuid
                                                  ? Colors.orange.shade100
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
                                                    textAlign:
                                                        TextAlign.center,
                                                    maxLines: 2,
                                                    overflow: TextOverflow
                                                        .ellipsis),
                                                Text(
                                                    node.relationshipToRoot ??
                                                        '—',
                                                    style: Theme.of(context)
                                                        .textTheme
                                                        .labelSmall),
                                                if (!node.isAlive)
                                                  Icon(
                                                      Icons.local_florist,
                                                      size: 14,
                                                      semanticLabel:
                                                          l10n.deceased)
                                              ]))))))
                          .toList())))),
      if (current.nodes.length > TreeRenderPolicy.maxActiveNodes)
        Positioned(
            left: 12,
            bottom: 12,
            child: Chip(
                label: Text(l10n.showingNodes(
                    TreeRenderPolicy.maxActiveNodes, current.nodes.length)))),
      if (loading)
        const Positioned(top: 8, right: 8, child: CircularProgressIndicator()),
    ]);
  }
}

class _TreePainter extends CustomPainter {
  const _TreePainter(this.nodes, this.edges);
  final List<TreeNode> nodes;
  final List<TreeEdge> edges;

  @override
  void paint(Canvas canvas, Size size) {
    final byUuid = {for (final node in nodes) node.uuid: node};
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
  bool shouldRepaint(covariant _TreePainter oldDelegate) =>
      oldDelegate.nodes != nodes || oldDelegate.edges != edges;
}

class _TreeRootPicker extends ConsumerStatefulWidget {
  const _TreeRootPicker({required this.familyUuid});
  final String familyUuid;
  @override
  ConsumerState<_TreeRootPicker> createState() => _TreeRootPickerState();
}

class _TreeRootPickerState extends ConsumerState<_TreeRootPicker> {
  final search = TextEditingController();
  int page = 1;
  late Future<PageData<FamilyMember>> future = load();
  Future<PageData<FamilyMember>> load() => ref
      .read(memberRepositoryProvider)
      .members(widget.familyUuid, page: page, limit: 20, search: search.text);
  void reload([int next = 1]) => setState(() {
        page = next;
        future = load();
      });

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return AlertDialog(
          title: Text(l10n.pickTreeRoot),
          content: SizedBox(
              width: 480,
              height: 480,
              child: Column(children: [
                SearchBar(
                    controller: search,
                    hintText: l10n.searchMembers,
                    onSubmitted: (_) => reload()),
                Expanded(
                    child: FutureBuilder<PageData<FamilyMember>>(
                        future: future,
                        builder: (_, snapshot) {
                          if (!snapshot.hasData) {
                            return const Center(
                                child: CircularProgressIndicator());
                          }
                          final result = snapshot.data!;
                          return Column(children: [
                            Expanded(
                                child: ListView(
                                    children: result.items
                                        .map((member) => ListTile(
                                            title: Text(member.displayName),
                                            subtitle: Text(member.isAlive
                                                ? l10n.alive
                                                : l10n.deceased),
                                            onTap: () => Navigator.pop(
                                                context, member)))
                                        .toList())),
                            Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  IconButton(
                                      tooltip: l10n.previousPage,
                                      onPressed: result.currentPage > 1
                                          ? () =>
                                              reload(result.currentPage - 1)
                                          : null,
                                      icon: const Icon(Icons.chevron_left)),
                                  Text(l10n.pageFraction(result.currentPage,
                                      result.lastPage)),
                                  IconButton(
                                      tooltip: l10n.nextPage,
                                      onPressed: result.hasMore
                                          ? () =>
                                              reload(result.currentPage + 1)
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
