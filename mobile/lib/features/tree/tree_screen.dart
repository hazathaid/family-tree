import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/models.dart';
import '../../core/providers.dart';

class TreeScreen extends ConsumerWidget {
  const TreeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final controller = TransformationController();
    return Column(children: [
      Padding(
        padding: const EdgeInsets.all(12),
        child: TextField(
          decoration: const InputDecoration(labelText: 'UUID anggota pusat', suffixIcon: Icon(Icons.search)),
          onSubmitted: (value) => ref.read(currentMemberUuidProvider.notifier).state = value.trim(),
        ),
      ),
      Expanded(child: ref.watch(treeProvider).when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('$error', textAlign: TextAlign.center)),
        data: (tree) => InteractiveViewer(
          transformationController: controller,
          minScale: .25,
          maxScale: 4,
          boundaryMargin: const EdgeInsets.all(400),
          constrained: false,
          child: CustomPaint(size: const Size(1600, 1200), painter: _TreePainter(tree),
            child: SizedBox(width: 1600, height: 1200, child: Stack(children: tree.nodes.map((node) =>
              Positioned(left: node.x, top: node.y, child: Semantics(label: node.name, child: Card(
                child: SizedBox(width: 150, height: 72, child: Center(child: Text(node.name,
                  textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis))),
              )))).toList())),
          ),
        ),
      )),
    ]);
  }
}

class _TreePainter extends CustomPainter {
  const _TreePainter(this.tree);
  final FamilyTree tree;

  @override
  void paint(Canvas canvas, Size size) {
    final nodes = {for (final node in tree.nodes) node.uuid: node};
    final paint = Paint()..color = const Color(0xff90a4ae)..strokeWidth = 2;
    for (final edge in tree.edges) {
      final source = nodes[edge.sourceUuid];
      final target = nodes[edge.targetUuid];
      if (source != null && target != null) {
        canvas.drawLine(Offset(source.x + 75, source.y + 36), Offset(target.x + 75, target.y + 36), paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _TreePainter oldDelegate) => oldDelegate.tree != tree;
}
