import '../../../core/models.dart';

class TreeRenderPolicy {
  const TreeRenderPolicy._();

  static const maxActiveNodes = 250;

  static List<TreeNode> project(Iterable<TreeNode> nodes,
          {required bool livingOnly}) =>
      nodes
          .where((node) => !livingOnly || node.isAlive)
          .take(maxActiveNodes)
          .toList(growable: false);
}
