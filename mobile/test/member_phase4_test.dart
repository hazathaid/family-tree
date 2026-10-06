import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/members/domain/member_repository.dart';
import 'package:family_tree_mobile/features/members/presentation/member_screens.dart';
import 'package:family_tree_mobile/l10n/app_localizations.dart';

Widget _app(Widget home) => MaterialApp(
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      locale: const Locale('id'),
      home: home,
    );

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
    ], child: _app(const MemberDirectoryScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Alm. Budi Santoso'), findsOneWidget);
    expect(find.textContaining('Meninggal'), findsOneWidget);
  });

  testWidgets('resolver exposes source target pickers without local labels',
      (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider.overrideWithValue(_FakeMemberRepository(member)),
      currentFamilyProvider.overrideWith((ref) => family)
    ], child: _app(const RelationshipResolverScreen())));
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

  test('member document and duplicate candidate models parse contracts', () {
    final document = MemberDocument.fromJson({
      'uuid': 'doc-uuid',
      'title': 'Akta Lahir',
      'category': 'legal',
      'size': 1234,
      'document_date': '2020-01-01',
      'download_url': 'https://api.test/api/v1/member-documents/doc-uuid/download',
      'uploaded_by': {'uuid': 'user-uuid', 'name': 'Budi'},
      'created_at': '2024-01-01T00:00:00Z'
    });
    expect(document.category, 'legal');
    expect(document.uploadedByName, 'Budi');
    expect(document.documentDate?.year, 2020);

    final candidate = DuplicateCandidate.fromJson({
      'primary': {'uuid': 'p', 'family_uuid': 'f', 'full_name': 'Budi'},
      'duplicate': {'uuid': 'd', 'family_uuid': 'f', 'full_name': 'BUDI'},
      'confidence': 'high',
      'reasons': ['same_name', 'same_birth_date']
    });
    expect(candidate.primary.fullName, 'Budi');
    expect(candidate.duplicate.fullName, 'BUDI');
    expect(candidate.confidence, 'high');
    expect(candidate.reasons, contains('same_birth_date'));
  });

  testWidgets('documents screen lists member documents with write action',
      (tester) async {
    const document = MemberDocument(
        uuid: 'doc-uuid',
        title: 'Akta Lahir',
        category: 'legal',
        documentDate: null);
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider
          .overrideWithValue(_FakeMemberRepository(member, documentItems: [document])),
      currentFamilyProvider.overrideWith((ref) => family)
    ], child: _app(const MemberDocumentsScreen(
        memberUuid: 'member-uuid', canWrite: true))));
    await tester.pumpAndSettle();
    expect(find.text('Akta Lahir'), findsOneWidget);
    expect(find.byIcon(Icons.delete_outline), findsOneWidget);
  });

  testWidgets('duplicates screen shows candidate and merge action',
      (tester) async {
    const duplicate = FamilyMember(
        uuid: 'duplicate-uuid',
        familyUuid: 'family-uuid',
        fullName: 'Budi S.',
        isAlive: true);
    const candidate = DuplicateCandidate(
        primary: member,
        duplicate: duplicate,
        confidence: 'high',
        reasons: ['same_name']);
    await tester.pumpWidget(ProviderScope(overrides: [
      memberRepositoryProvider
          .overrideWithValue(_FakeMemberRepository(member, candidateItems: [candidate])),
      currentFamilyProvider.overrideWith((ref) => family)
    ], child: _app(const MemberDuplicatesScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Kandidat duplikat'), findsOneWidget);
    expect(find.text('Gabungkan'), findsOneWidget);
  });
}

class _FakeMemberRepository implements MemberRepository {
  _FakeMemberRepository(this.value,
      {this.documentItems = const [], this.candidateItems = const []});
  final FamilyMember value;
  final List<MemberDocument> documentItems;
  final List<DuplicateCandidate> candidateItems;
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
  @override
  Future<List<MemberDocument>> documents(String memberUuid) async =>
      documentItems;
  @override
  Future<MemberDocument> uploadDocument(String memberUuid,
          {required String path,
          required String title,
          String? category,
          String? documentDate,
          String? notes}) =>
      throw UnimplementedError();
  @override
  Future<void> deleteDocument(String documentUuid) async {}
  @override
  Future<List<DuplicateCandidate>> duplicateCandidates(String familyUuid,
          {int limit = 25}) async =>
      candidateItems;
  @override
  Future<FamilyMember> mergeMember(String primaryUuid, String duplicateUuid) =>
      throw UnimplementedError();
}
