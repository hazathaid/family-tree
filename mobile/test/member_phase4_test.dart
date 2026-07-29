import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/members/domain/member_repository.dart';
import 'package:family_tree_mobile/features/members/presentation/member_screens.dart';

void main() {
  const family =
      Family(uuid: 'family-uuid', name: 'Keluarga', currentUserRole: 'owner');
  const member = FamilyMember(
      uuid: 'member-uuid',
      familyUuid: 'family-uuid',
      fullName: 'Budi Santoso',
      isAlive: false,
      gender: 'male',
      religion: 'islam',
      memorialPrefix: 'Alm. ',
      branchName: 'Utama');

  testWidgets('directory renders memorial status in phone cards',
      (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider.overrideWithValue(_FakeMemberRepository(member)),
      currentFamilyProvider.overrideWith((ref) => family)
    ], child: const MaterialApp(home: MemberDirectoryScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Alm. Budi Santoso'), findsOneWidget);
    expect(find.textContaining('Meninggal'), findsOneWidget);
  });

  testWidgets('resolver exposes source target pickers without local labels',
      (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider.overrideWithValue(_FakeMemberRepository(member)),
      currentFamilyProvider.overrideWith((ref) => family)
    ], child: const MaterialApp(home: RelationshipResolverScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Pilih anggota'), findsNWidgets(2));
    expect(find.text('Temukan relationship'), findsOneWidget);
  });

  test('member and relationship response models parse UUID contracts', () {
    final parsed = FamilyMember.fromJson({
      'uuid': 'member-uuid',
      'family_uuid': 'family-uuid',
      'full_name': 'Siti',
      'is_alive': false,
      'religion': 'islam',
      'memorial_prefix': 'Almh. ',
      'family_branch_name': 'Barat'
    });
    final resolution = RelationshipResolution.fromJson({
      'relationship': 'Ibu',
      'path': [
        {
          'relationship': 'mother',
          'from_member_name': 'Anak',
          'to_member_name': 'Ibu'
        }
      ]
    });
    expect(parsed.branchName, 'Barat');
    expect(parsed.religion, 'islam');
    expect(parsed.memorialPrefix, 'Almh. ');
    expect(resolution.relationship, 'Ibu');
    expect(resolution.path.single.toName, 'Ibu');
  });
}

class _FakeMemberRepository implements MemberRepository {
  _FakeMemberRepository(this.value);
  final FamilyMember value;
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
  Future<FamilyMember> member(String uuid) async => value;
  @override
  Future<RelationshipResolution> resolve(
          String sourceUuid, String targetUuid) async =>
      const RelationshipResolution(relationship: 'Saya', path: []);
  @override
  Future<PageData<MemberRelationship>> relationships(String familyUuid,
          {String? memberUuid, int page = 1}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
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
  Future<MemberRelationship> createRelationship(Map<String, dynamic> values) =>
      throw UnimplementedError();
  @override
  Future<MemberRelationship> updateRelationship(
          String uuid, Map<String, dynamic> values) =>
      throw UnimplementedError();
  @override
  Future<void> deleteRelationship(String uuid) async {}
}
