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
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Ekspor tidak dapat dibuka atau dibagikan.')));
    }
  }

  Future<void> _previewAndShare(String format, Uint8List bytes) async {
    final mime = format == 'png' ? 'image/png' : 'application/pdf';
    final share = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
                title: Text('Ekspor ${format.toUpperCase()} siap'),
                content: format == 'png'
                    ? ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 420),
                        child: Image.memory(bytes,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __, ___) =>
                                const Text('Pratinjau tidak tersedia.')))
                    : Text(
                        'PDF berhasil dibuat (${(bytes.length / 1024).ceil()} KB). Bagikan untuk membuka atau menyimpannya.'),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Tutup')),
                  FilledButton.icon(
                      onPressed: () => Navigator.pop(context, true),
                      icon: const Icon(Icons.share),
                      label: const Text('Bagikan'))
                ]));
    if (share == true) {
      await SharePlus.instance.share(ShareParams(files: [
        XFile.fromData(bytes, mimeType: mime, name: 'family-tree.$format')
      ]));
    }
  }

  void showDetails(TreeNode node) {
    showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        builder: (sheetContext) => Padding(
            padding: const EdgeInsets.all(16),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              ListTile(
                  leading:
                      Icon(node.isAlive ? Icons.person : Icons.local_florist),
                  title: Text(node.name),
                  subtitle: Text(
                      '${node.relationshipToRoot ?? 'Relationship tidak tersedia'} · ${node.isAlive ? 'Hidup' : 'Meninggal'}')),
              SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                      onPressed: () {
                        Navigator.pop(sheetContext);
                        context.push('/members/${node.uuid}');
                      },
                      child: const Text('Buka detail anggota')))
            ])));
  }

  @override
  Widget build(BuildContext context) => Column(children: [
        _controls(),
        if (exportCancellation != null)
          ListTile(
              leading: const CircularProgressIndicator(),
              title: const Text('Menyiapkan ekspor…'),
              subtitle: LinearProgressIndicator(value: exportProgress),
              trailing: IconButton(
                  tooltip: 'Batalkan ekspor',
                  onPressed: exportCancellation!.cancel,
                  icon: const Icon(Icons.close))),
        Expanded(child: _content()),
      ]);

  Widget _controls() => Material(
      elevation: 1,
      child: ExpansionTile(
          initiallyExpanded: true,
          title: Text(root?.fullName ?? 'Pilih pusat pohon'),
          leading: const Icon(Icons.account_tree),
          childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
          children: [
            Row(children: [
              Expanded(
                  child: OutlinedButton.icon(
                      onPressed: pickRoot,
                      icon: const Icon(Icons.person_search),
                      label: const Text('Pilih pusat'))),
              const SizedBox(width: 8),
              PopupMenuButton<String>(
                  tooltip: 'Ekspor pohon',
                  onSelected: export,
                  itemBuilder: (_) => const [
                        PopupMenuItem(value: 'png', child: Text('Ekspor PNG')),
                        PopupMenuItem(value: 'pdf', child: Text('Ekspor PDF'))
                      ],
                  child: const Padding(
                      padding: EdgeInsets.all(12), child: Icon(Icons.download)))
            ]),
            Wrap(spacing: 8, runSpacing: 8, children: [
              DropdownButton<String>(
                  value: mode,
                  items: const [
                    DropdownMenuItem(value: 'ancestor', child: Text('Leluhur')),
                    DropdownMenuItem(
                        value: 'descendant', child: Text('Keturunan')),
                    DropdownMenuItem(value: 'full', child: Text('Lengkap'))
                  ],
                  onChanged: (value) {
                    if (value != null) {
                      setState(() => mode = value);
                      generate();
                    }
                  }),
              DropdownButton<String>(
                  value: layout,
                  items: const [
                    DropdownMenuItem(
                        value: 'vertical', child: Text('Vertikal')),
                    DropdownMenuItem(
                        value: 'horizontal', child: Text('Horizontal')),
                    DropdownMenuItem(value: 'radial', child: Text('Radial')),
                    DropdownMenuItem(value: 'compact', child: Text('Ringkas'))
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
                  label: Text('Ciutkan ($depth)')),
              OutlinedButton.icon(
                  onPressed: tree?.canExpand == true
                      ? () {
                          setState(() => depth++);
                          generate();
                        }
                      : null,
                  icon: const Icon(Icons.unfold_more),
                  label: Text('Perluas ($depth/20)')),
              FilterChip(
                  label: const Text('Hanya hidup'),
                  selected: livingOnly,
                  onSelected: (value) => setState(() => livingOnly = value)),
              FilterChip(
                  label: const Text('Daftar aksesibel'),
                  selected: showSemanticList,
                  onSelected: (value) =>
                      setState(() => showSemanticList = value)),
            ]),
            Row(children: [
              Expanded(
                  child: TextField(
                      controller: search,
                      onSubmitted: (_) => searchNode(),
                      decoration: const InputDecoration(
                          labelText: 'Cari/fokus anggota',
                          prefixIcon: Icon(Icons.search)))),
              IconButton(
                  tooltip: 'Perkecil',
                  onPressed: () => zoom(.8),
                  icon: const Icon(Icons.zoom_out)),
              IconButton(
                  tooltip: 'Perbesar',
                  onPressed: () => zoom(1.25),
                  icon: const Icon(Icons.zoom_in)),
              IconButton(
                  tooltip: 'Pusatkan',
                  onPressed: centerRoot,
                  icon: const Icon(Icons.center_focus_strong))
            ])
          ]));

  Widget _content() {
    if (root == null) {
      return Center(
          child: FilledButton.icon(
              onPressed: pickRoot,
              icon: const Icon(Icons.person_search),
              label: const Text('Pilih anggota pusat')));
    }
    if (loading && tree == null) {
      return Center(
          child: Semantics(
              liveRegion: true,
              label: 'Memuat pohon keluarga',
              child: const CircularProgressIndicator()));
    }
    if (error != null) {
      return Center(
          child: FilledButton.icon(
              onPressed: generate,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba lagi')));
    }
    final current = tree;
    if (current == null || current.nodes.isEmpty) {
      return const Center(child: Text('Pohon keluarga masih kosong.'));
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
                title: Text(node.name),
                subtitle: Text(
                    '${node.relationshipToRoot ?? 'Tidak diketahui'} · Generasi ${node.generation}'),
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
                                  label:
                                      '${node.name}, ${node.relationshipToRoot ?? 'relationship tidak diketahui'}, ${node.isAlive ? 'hidup' : 'meninggal'}',
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
                                                Text(node.name,
                                                    textAlign: TextAlign.center,
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis),
                                                Text(
                                                    node.relationshipToRoot ??
                                                        '—',
                                                    style: Theme.of(context)
                                                        .textTheme
                                                        .labelSmall),
                                                if (!node.isAlive)
                                                  const Icon(
                                                      Icons.local_florist,
                                                      size: 14,
                                                      semanticLabel:
                                                          'Meninggal')
                                              ]))))))
                          .toList())))),
      if (current.nodes.length > TreeRenderPolicy.maxActiveNodes)
        Positioned(
            left: 12,
            bottom: 12,
            child: Chip(
                label: Text(
                    'Menampilkan ${TreeRenderPolicy.maxActiveNodes} dari ${current.nodes.length} node'))),
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
  Widget build(BuildContext context) => AlertDialog(
          title: const Text('Pilih pusat pohon'),
          content: SizedBox(
              width: 480,
              height: 480,
              child: Column(children: [
                SearchBar(
                    controller: search,
                    hintText: 'Cari anggota',
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
                                            title: Text(member.fullName),
                                            subtitle: Text(member.isAlive
                                                ? 'Hidup'
                                                : 'Meninggal'),
                                            onTap: () =>
                                                Navigator.pop(context, member)))
                                        .toList())),
                            Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  IconButton(
                                      tooltip: 'Sebelumnya',
                                      onPressed: result.currentPage > 1
                                          ? () => reload(result.currentPage - 1)
                                          : null,
                                      icon: const Icon(Icons.chevron_left)),
                                  Text(
                                      '${result.currentPage} / ${result.lastPage}'),
                                  IconButton(
                                      tooltip: 'Berikutnya',
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
