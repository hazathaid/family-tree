import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/members/domain/member_repository.dart';
import 'package:family_tree_mobile/features/tree/domain/tree_repository.dart';
import 'package:family_tree_mobile/features/tree/domain/tree_render_policy.dart';
import 'package:family_tree_mobile/features/tree/tree_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

void main() {
  const member = FamilyMember(
      uuid: 'root', familyUuid: 'family', fullName: 'Budi Root', isAlive: true);
  const tree = FamilyTree(
      rootUuid: 'root',
      mode: 'full',
      depth: 3,
      layout: 'vertical',
      nodes: [
        TreeNode(
            uuid: 'root',
            name: 'Budi Root',
            x: 200,
            y: 200,
            generation: 0,
            distance: 0,
            isAlive: true,
            isRoot: true,
            isBoundary: false,
            relationshipToRoot: 'Saya')
      ],
      edges: [],
      viewportWidth: 960,
      viewportHeight: 720,
      canExpand: true,
      canCollapse: true,
      cached: false);

  testWidgets('tree viewer exposes controls and semantic list', (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      currentMemberUuidProvider.overrideWith((ref) => 'root'),
      memberRepositoryProvider.overrideWithValue(_Members(member)),
      treeRepositoryProvider.overrideWithValue(_Trees(tree)),
    ], child: const MaterialApp(home: Scaffold(body: TreeScreen()))));
    await tester.pumpAndSettle();
    expect(find.text('Budi Root'), findsWidgets);
    expect(find.textContaining('Perluas'), findsOneWidget);
    await tester.tap(find.text('Daftar aksesibel'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Saya · Generasi 0'), findsOneWidget);
  });

  test('tree contract retains server labels and boundary metadata', () {
    final parsed = FamilyTree.fromJson({
      'root_member_uuid': 'root',
      'mode': 'ancestor',
      'depth': 1,
      'layout': 'radial',
      'cached': false,
      'viewport': {'width': 1000, 'height': 800},
      'expansion': {'can_expand': true, 'can_collapse': false},
      'nodes': [
        {
          'uuid': 'father',
          'name': 'Ayah',
          'generation': -1,
          'distance': 1,
          'is_alive': true,
          'is_root': false,
          'is_boundary': true,
          'relationship_to_root': 'Ayah',
          'position': {'x': 10, 'y': 20}
        }
      ],
      'edges': []
    });
    expect(parsed.layout, 'radial');
    expect(parsed.nodes.single.isBoundary, isTrue);
    expect(parsed.nodes.single.relationshipToRoot, 'Ayah');
  });

  test('100k-node projection keeps active widgets bounded', () {
    final largeSegment = List<TreeNode>.filled(100000, tree.nodes.single);
    final stopwatch = Stopwatch()..start();
    final projected = TreeRenderPolicy.project(largeSegment, livingOnly: false);
    stopwatch.stop();

    expect(projected, hasLength(TreeRenderPolicy.maxActiveNodes));
    expect(stopwatch.elapsed, lessThan(const Duration(seconds: 1)));
  });
}

class _Trees implements TreeRepository {
  const _Trees(this.value);
  final FamilyTree value;
  @override
  Future<FamilyTree> generate(String rootUuid,
          {required String mode,
          required int depth,
          required String layout}) async =>
      value;
  @override
  Future<Uint8List> export(String format, String rootUuid,
          {required String mode,
          required int depth,
          required String layout,
          required String paperSize,
          CancelToken? cancelToken,
          ProgressCallback? onProgress}) async =>
      Uint8List.fromList([1, 2, 3]);
}

class _Members implements MemberRepository {
  const _Members(this.value);
  final FamilyMember value;
  @override
  Future<FamilyMember> member(String uuid) async => value;
  @override
  Future<PageData<FamilyMember>> members(String familyUuid,
          {String? branchUuid,
          String? gender,
          bool? isAlive,
          int limit = 20,
          int page = 1,
          String? search,
          String sort = 'name'}) async =>
      PageData(items: [value], currentPage: 1, lastPage: 1, total: 1);
  @override
  Future<FamilyMember> create(
          String familyUuid, Map<String, dynamic> values) async =>
      value;
  @override
  Future<FamilyMember> update(String uuid, Map<String, dynamic> values) async =>
      value;
  @override
  Future<FamilyMember> uploadPhoto(String uuid, String path) async => value;
  @override
  Future<void> deleteMember(String uuid) async {}
  @override
  Future<PageData<MemberRelationship>> relationships(String familyUuid,
          {String? memberUuid, int page = 1}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
  @override
  Future<MemberRelationship> createRelationship(Map<String, dynamic> values) =>
      throw UnimplementedError();
  @override
  Future<MemberRelationship> updateRelationship(
          String uuid, Map<String, dynamic> values) =>
      throw UnimplementedError();
  @override
  Future<void> deleteRelationship(String uuid) async {}
  @override
  Future<RelationshipResolution> resolve(
          String sourceUuid, String targetUuid) =>
      throw UnimplementedError();
}
