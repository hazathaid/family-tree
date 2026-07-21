import 'package:family_tree_mobile/core/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('dashboard response is parsed with safe defaults', () {
    final summary =
        DashboardSummary.fromJson({'total_members': 100, 'living_members': 80});
    expect(summary.totalMembers, 100);
    expect(summary.livingMembers, 80);
    expect(summary.deceasedMembers, 0);
  });

  test('tree response maps nodes and edges', () {
    final tree = FamilyTree.fromJson({
      'nodes': [
        {
          'uuid': 'root',
          'name': 'Ahmad',
          'position': {'x': 10, 'y': 20}
        }
      ],
      'edges': [
        {'source_uuid': 'root', 'target_uuid': 'child'}
      ],
    });
    expect(tree.nodes.single.name, 'Ahmad');
    expect(tree.edges.single.targetUuid, 'child');
  });
}
