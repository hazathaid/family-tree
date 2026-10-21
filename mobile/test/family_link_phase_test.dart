import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/family/domain/family_link_models.dart';
import 'package:family_tree_mobile/features/family/domain/family_link_repository.dart';
import 'package:family_tree_mobile/features/family/presentation/family_links_screen.dart';
import 'package:family_tree_mobile/features/members/domain/member_repository.dart';
import 'package:family_tree_mobile/l10n/app_localizations.dart';

Widget _app(Widget home) => MaterialApp(
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      locale: const Locale('id'),
      home: home,
    );

const familyA =
    Family(uuid: 'family-a', name: 'Keluarga A', currentUserRole: 'owner');
const familyB =
    Family(uuid: 'family-b', name: 'Keluarga B', currentUserRole: 'member');

const partyA = FamilyLinkParty(uuid: 'family-a', name: 'Keluarga A');
const partyB = FamilyLinkParty(uuid: 'family-b', name: 'Keluarga B');
const memberA = FamilyLinkMember(uuid: 'member-a', fullName: 'Budi');
const memberB = FamilyLinkMember(uuid: 'member-b', fullName: 'Siti');

const pendingLocal = FamilyLink(
    uuid: 'link-local',
    status: 'pending',
    family: partyA,
    linkedFamily: partyB,
    member: memberA,
    linkedMember: memberB,
    canRevoke: true);

const pendingRemote = FamilyLink(
    uuid: 'link-remote',
    status: 'pending',
    family: partyB,
    linkedFamily: partyA,
    member: memberB,
    linkedMember: memberA,
    canApprove: true);

const activeLink = FamilyLink(
    uuid: 'link-active',
    status: 'active',
    family: partyA,
    linkedFamily: partyB,
    member: memberA,
    linkedMember: memberB,
    canRevoke: true);

const tree = FamilyTree(
    rootUuid: 'member-b',
    mode: 'full',
    depth: 2,
    layout: 'vertical',
    nodes: [
      TreeNode(
          uuid: 'member-b',
          name: 'Siti',
          x: 200,
          y: 160,
          generation: 0,
          distance: 0,
          isAlive: true,
          isRoot: true,
          isBoundary: false,
          relationshipToRoot: 'Saya'),
      TreeNode(
          uuid: 'member-anon',
          name: 'Anggota Hidup',
          x: 200,
          y: 320,
          generation: -1,
          distance: 1,
          isAlive: true,
          isRoot: false,
          isBoundary: true,
          relationshipToRoot: 'Kerabat',
          isRedacted: true)
    ],
    edges: [],
    viewportWidth: 960,
    viewportHeight: 720,
    canExpand: true,
    canCollapse: true,
    cached: false,
    readOnly: true,
    redacted: true);

void main() {
  test('family link model parses status and party direction', () {
    final link = FamilyLink.fromJson({
      'uuid': 'link-uuid',
      'status': 'pending',
      'link_type': 'marriage',
      'family': {'uuid': 'family-a', 'name': 'Keluarga A'},
      'linked_family': {'uuid': 'family-b', 'name': 'Keluarga B'},
      'member': {'uuid': 'member-a', 'full_name': 'Budi'},
      'linked_member': {'uuid': 'member-b', 'full_name': 'Siti'},
      'can_approve': false,
      'can_revoke': true,
      'expires_at': '2026-11-01T00:00:00Z',
    });

    expect(link.isPending, isTrue);
    expect(link.localMember('family-a').fullName, 'Budi');
    expect(link.remoteMember('family-a').fullName, 'Siti');
    expect(link.remoteFamily('family-a').name, 'Keluarga B');
    expect(link.canRevoke, isTrue);
    expect(link.expiresAt?.year, 2026);
  });

  testWidgets('family links screen lists, approves, and opens partner tree',
      (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final links = _FakeLinks([pendingLocal, pendingRemote, activeLink]);
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => familyA),
      memberRepositoryProvider.overrideWithValue(_FakeMembers()),
      familyLinkRepositoryProvider.overrideWithValue(links),
    ], child: _app(const FamilyLinksScreen())));
    await tester.pumpAndSettle();

    expect(find.text('Tautan Keluarga'), findsOneWidget);
    expect(find.text('Menunggu persetujuan'), findsNWidgets(2));
    expect(find.text('Aktif'), findsOneWidget);
    expect(find.text('Setujui'), findsOneWidget);
    expect(find.text('Lihat silsilah pasangan'), findsOneWidget);

    await tester.tap(find.text('Setujui'));
    await tester.pumpAndSettle();
    expect(links.approved, contains('link-remote'));
    expect(find.text('Tautan keluarga disetujui'), findsOneWidget);
  });

  testWidgets('partner tree renders read-only redacted nodes', (tester) async {
    tester.view.physicalSize = const Size(1000, 1600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(ProviderScope(
        overrides: [
          currentFamilyProvider.overrideWith((ref) => familyA),
          familyLinkRepositoryProvider.overrideWithValue(_FakeLinks(const [])),
        ],
        child: _app(const FamilyLinkTreeScreen(
            linkUuid: 'link-active', familyUuid: 'family-a'))));
    await tester.pumpAndSettle();

    expect(find.text('Baca-saja'), findsOneWidget);
    expect(find.text('Anggota Hidup'), findsOneWidget);
    expect(find.text('Siti'), findsOneWidget);
    expect(find.textContaining('Perluas'), findsOneWidget);
  });
}

class _FakeLinks implements FamilyLinkRepository {
  _FakeLinks(this.items);

  List<FamilyLink> items;
  final List<String> approved = [];

  @override
  Future<List<FamilyLink>> links(String familyUuid) async => items;

  @override
  Future<List<FamilyLinkCandidate>> candidates(String familyUuid,
          {required String search, int limit = 25}) async =>
      const [];

  @override
  Future<FamilyLink> request(String familyUuid,
          {required String memberUuid,
          required String linkedMemberUuid}) async =>
      pendingLocal;

  @override
  Future<FamilyLink> approve(String linkUuid) async {
    approved.add(linkUuid);
    items = items
        .map((link) => link.uuid == linkUuid
            ? link.copyWith(status: 'active', canApprove: false)
            : link)
        .toList(growable: false);
    return items.firstWhere((link) => link.uuid == linkUuid);
  }

  @override
  Future<FamilyLink> revoke(String linkUuid) async => items
      .firstWhere((link) => link.uuid == linkUuid)
      .copyWith(status: 'revoked', canRevoke: false);

  @override
  Future<FamilyTree> partnerTree(String linkUuid,
          {required String familyUuid,
          String mode = 'full',
          int depth = 4,
          String layout = 'vertical'}) async =>
      tree;
}

class _FakeMembers implements MemberRepository {
  @override
  Future<PageData<FamilyMember>> members(String familyUuid,
          {int page = 1,
          int limit = 20,
          String? search,
          String? gender,
          bool? isAlive,
          String? branchUuid,
          String sort = 'name'}) async =>
      const PageData(items: [
        FamilyMember(
            uuid: 'member-a',
            familyUuid: 'family-a',
            fullName: 'Budi',
            isAlive: true)
      ], currentPage: 1, lastPage: 1, total: 1);

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
