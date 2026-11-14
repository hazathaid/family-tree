import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:family_tree_mobile/core/api_client.dart';
import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/features/account/domain/account_repository.dart';
import 'package:family_tree_mobile/features/auth/domain/auth_repository.dart';
import 'package:family_tree_mobile/features/content/domain/content_models.dart';
import 'package:family_tree_mobile/features/content/domain/content_repository.dart';
import 'package:family_tree_mobile/features/discovery/domain/discovery_models.dart';
import 'package:family_tree_mobile/features/discovery/domain/discovery_repository.dart';
import 'package:family_tree_mobile/features/family/domain/family_link_models.dart';
import 'package:family_tree_mobile/features/family/domain/family_link_repository.dart';
import 'package:family_tree_mobile/features/family/domain/family_repository.dart';
import 'package:family_tree_mobile/features/members/domain/member_repository.dart';
import 'package:family_tree_mobile/features/notifications/domain/notification_repository.dart';
import 'package:family_tree_mobile/features/tree/domain/tree_repository.dart';

final testUser = User(
    uuid: 'user-uuid',
    name: 'Budi Santoso',
    email: 'budi@example.com',
    phone: '08123456789',
    emailVerifiedAt: DateTime.utc(2024));

const testFamily = Family(
    uuid: 'family-uuid', name: 'Keluarga Santoso', currentUserRole: 'owner');

const testMember = FamilyMember(
    uuid: 'member-uuid',
    familyUuid: 'family-uuid',
    fullName: 'Budi Santoso',
    isAlive: true,
    gender: 'male',
    religion: 'islam',
    branchName: 'Utama');

const testTree = FamilyTree(
    rootUuid: 'member-uuid',
    mode: 'full',
    depth: 3,
    layout: 'vertical',
    nodes: [
      TreeNode(
          uuid: 'member-uuid',
          name: 'Budi Santoso',
          x: 200,
          y: 200,
          generation: 0,
          distance: 0,
          isAlive: true,
          isRoot: true,
          isBoundary: false,
          canAddRelative: true,
          relationshipToRoot: 'Saya')
    ],
    edges: [],
    viewportWidth: 960,
    viewportHeight: 720,
    canExpand: true,
    canCollapse: true,
    cached: false);

const testArticle = Article(
    uuid: 'article-uuid',
    familyUuid: 'family-uuid',
    title: 'Sejarah Keluarga',
    content: '<p>Isi artikel</p>',
    status: 'published',
    category: ArticleCategory(uuid: 'category-uuid', name: 'Sejarah'),
    authorUuid: 'user-uuid',
    authorName: 'Budi Santoso');

final testEvent = FamilyEvent(
    uuid: 'event-uuid',
    familyUuid: 'family-uuid',
    title: 'Reuni Keluarga',
    eventDate: DateTime(2026, 5, 1),
    organizerName: 'Budi Santoso');

final richMember = FamilyMember(
    uuid: 'member-uuid',
    familyUuid: 'family-uuid',
    fullName: 'Budi Santoso',
    isAlive: false,
    nickname: 'Bud',
    gender: 'male',
    religion: 'islam',
    memorialPrefix: 'Alm. ',
    birthDate: DateTime(1950, 1, 2),
    birthPlace: 'Bandung',
    deathDate: DateTime(2020, 3, 4),
    deathPlace: 'Jakarta',
    biography: 'Pendiri keluarga.',
    generation: 2,
    branchName: 'Utama',
    branchUuid: 'branch-uuid',
    isOwnProfile: true,
    relationshipToViewer: 'Ayah');

const sampleBranch = FamilyBranch(
    uuid: 'branch-uuid', name: 'Cabang Utama', description: 'Cabang pertama');

final sampleMembership =
    FamilyMembership(uuid: 'membership-uuid', user: testUser, role: 'owner');

const sampleRelationship = MemberRelationship(
    uuid: 'relationship-uuid',
    sourceUuid: 'member-uuid',
    sourceName: 'Budi Santoso',
    targetUuid: 'child-uuid',
    targetName: 'Siti Santoso',
    type: 'father');

const sampleSpouse = MemberSpouse(
    relationshipUuid: 'spouse-relationship-uuid',
    spouseUuid: 'spouse-uuid',
    spouseName: 'Dewi Santoso',
    label: 'Istri',
    gender: 'female',
    canEnd: true);

const sampleResolution = RelationshipResolution(relationship: 'Ayah', path: [
  RelationshipPathStep(relationship: 'father', fromName: 'Siti', toName: 'Budi')
]);

const sampleDashboard = DashboardSummary(
    totalMembers: 12,
    livingMembers: 10,
    deceasedMembers: 2,
    totalArticles: 3,
    totalEvents: 4,
    totalPhotos: 5,
    activity: [DashboardEntry(uuid: 'a', title: 'Anggota ditambah')],
    birthdays: [DashboardEntry(uuid: 'b', title: 'Ulang tahun Siti')],
    events: [
      DashboardEntry(uuid: 'event-uuid', title: 'Reuni', subtitle: 'Bandung')
    ],
    notifications: [
      DashboardEntry(uuid: 'n', title: 'Notifikasi', subtitle: 'Pesan')
    ],
    recentMembers: [DashboardEntry(uuid: 'member-uuid', title: 'Budi')],
    unreadNotifications: 2,
    originCity: 'Bandung',
    oldestMember: DashboardEntry(uuid: 'old', title: 'Ahmad'),
    youngestMember: DashboardEntry(uuid: 'young', title: 'Siti'));

final sampleTimeline = TimelineItem(
    uuid: 'timeline-uuid',
    message: 'Anggota ditambahkan',
    createdAt: DateTime(2026, 1, 2),
    targetPath: '/members/member-uuid');

const sampleArticleComment = ArticleComment(
    uuid: 'comment-uuid',
    userUuid: 'user-uuid',
    userName: 'Budi',
    comment: 'Komentar');

const sampleAlbum = PhotoAlbum(
    uuid: 'album-uuid',
    familyUuid: 'family-uuid',
    name: 'Album Lama',
    description: 'Foto lama',
    photosCount: 2);

const samplePhoto = FamilyPhoto(
    uuid: 'photo-uuid',
    familyUuid: 'family-uuid',
    url: 'https://example.test/photo.jpg',
    thumbnailUrl: 'https://example.test/thumb.jpg',
    size: 1024,
    caption: 'Foto keluarga',
    albumUuid: 'album-uuid',
    albumName: 'Album Lama',
    taggedMembers: [TaggedMember(uuid: 'member-uuid', name: 'Budi')]);

final sampleSearch = SearchResults(
    members: [testMember],
    articles: [testArticle],
    events: [testEvent],
    page: 1,
    hasMore: false);

const sampleStatistics = FamilyStatistics(
    totalMembers: 10,
    aliveMembers: 8,
    deceasedMembers: 2,
    totalGenerations: 3,
    generations: [ReportPoint('1', 4), ReportPoint('2', 6)]);

const sampleActivityReport =
    ActivityReport(activeUsers: 4, uploads: 7, articles: 3);

const sampleInsights = ReportInsights(
    cities: [ReportPoint('Bandung', 5)],
    growth: [ReportPoint('2026', 8)],
    activity: [ReportPoint('Jan', 2)]);

const sampleGamification = GamificationProfile(points: 120, badges: [
  BadgeAward(uuid: 'badge-uuid', name: 'Kontributor', description: 'Aktif')
]);

const sampleLeaderboard = [
  LeaderboardEntry(rank: 1, uuid: 'user-uuid', name: 'Budi', points: 120)
];

const sampleNotification = AppNotification(
    uuid: 'notification-uuid',
    title: 'Notifikasi',
    body: 'Pesan keluarga',
    isRead: false,
    data: {'target_type': 'member', 'target_uuid': 'member-uuid'});

const sampleDocument = MemberDocument(
    uuid: 'document-uuid', title: 'Akta Lahir', category: 'legal');

const sampleDuplicate = DuplicateCandidate(
    primary: testMember,
    duplicate: testMember,
    confidence: 'high',
    reasons: ['same_name']);

const sampleLink = FamilyLink(
    uuid: 'link-uuid',
    status: 'pending',
    family: FamilyLinkParty(uuid: 'family-uuid', name: 'Keluarga Santoso'),
    linkedFamily: FamilyLinkParty(uuid: 'family-b', name: 'Keluarga Lain'),
    member: FamilyLinkMember(uuid: 'member-uuid', fullName: 'Budi Santoso'),
    linkedMember: FamilyLinkMember(uuid: 'member-b', fullName: 'Siti'),
    canApprove: true,
    canRevoke: true);

const sampleCandidate = FamilyLinkCandidate(
    uuid: 'member-b',
    fullName: 'Siti',
    family: FamilyLinkParty(uuid: 'family-b', name: 'Keluarga Lain'));

class ApiCall {
  ApiCall(this.method, this.path, {this.data, this.query});
  final String method;
  final String path;
  final dynamic data;
  final Map<String, dynamic>? query;
}

typedef ApiResponder = dynamic Function(String method, String path,
    {Map<String, dynamic>? query, dynamic data});

class FakeApiClient extends ApiClient {
  FakeApiClient(this.responder) : super(baseUrl: 'https://api.test/api/v1');

  final ApiResponder responder;
  final List<ApiCall> calls = [];
  final Map<String, String> tokens = {};

  dynamic _respond(String method, String path,
      {Map<String, dynamic>? query, dynamic data}) {
    calls.add(ApiCall(method, path, query: query, data: data));
    return responder(method, path, query: query, data: data);
  }

  @override
  Future<dynamic> get(String path,
          {Map<String, dynamic>? query, CancelToken? cancelToken}) async =>
      _respond('GET', path, query: query);

  @override
  Future<dynamic> post(String path,
          {dynamic data,
          CancelToken? cancelToken,
          ProgressCallback? onSendProgress}) async =>
      _respond('POST', path, data: data);

  @override
  Future<dynamic> put(String path,
          {dynamic data, CancelToken? cancelToken}) async =>
      _respond('PUT', path, data: data);

  @override
  Future<dynamic> patch(String path,
          {dynamic data, CancelToken? cancelToken}) async =>
      _respond('PATCH', path, data: data);

  @override
  Future<dynamic> delete(String path, {dynamic data}) async =>
      _respond('DELETE', path, data: data);

  @override
  Future<Uint8List> download(String path,
          {Map<String, dynamic>? query,
          CancelToken? cancelToken,
          ProgressCallback? onReceiveProgress}) async =>
      Uint8List.fromList([1, 2, 3]);

  @override
  Future<void> saveToken(String token) async => tokens['token'] = token;

  @override
  Future<void> saveRefreshToken(String token) async =>
      tokens['refresh'] = token;

  @override
  Future<void> clearToken() async => tokens.clear();
}

class FakeAuthRepository implements AuthRepository {
  User user = testUser;
  AuthLoginResult loginResult = AuthLoginResult(user: testUser);
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<User> register(String name, String email, String password,
          {String? phone}) =>
      _run('register', () => user);

  @override
  Future<AuthLoginResult> login(String email, String password) =>
      _run('login', () => loginResult);

  @override
  Future<User> twoFactorChallenge(String challengeToken,
          {String? code, String? recoveryCode}) =>
      _run('twoFactorChallenge', () => user);

  @override
  Future<User> me() => _run('me', () => user);

  @override
  Future<void> forgotPassword(String email) => _run('forgotPassword', () {});

  @override
  Future<void> resetPassword(String token, String email, String password) =>
      _run('resetPassword', () {});

  @override
  Future<void> resendVerification() => _run('resendVerification', () {});

  @override
  Future<void> verifyEmail(String id, String hash, Map<String, String> query) =>
      _run('verifyEmail', () {});

  @override
  Future<void> logout() => _run('logout', () {});
}

class FakeFamilyRepository implements FamilyRepository {
  List<Family> families = [testFamily];
  List<FamilyBranch> branchItems = const [sampleBranch];
  List<FamilyMembership> membershipItems = [sampleMembership];
  DashboardSummary dashboardValue = sampleDashboard;
  List<TimelineItem> timelineItems = [sampleTimeline];
  FamilyTree treeValue = testTree;
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<List<Family>> all() => _run('all', () => families);

  @override
  Future<Family> create(String name,
          {String? description, String? originCity}) =>
      _run(
          'create',
          () => Family(
              uuid: 'family-new',
              name: name,
              description: description,
              originCity: originCity,
              currentUserRole: 'owner'));

  @override
  Future<Family> update(Family family,
          {required String name, String? description, String? originCity}) =>
      _run(
          'update',
          () => Family(
              uuid: family.uuid,
              name: name,
              description: description,
              originCity: originCity,
              currentUserRole: family.currentUserRole));

  @override
  Future<Family> uploadAssets(String familyUuid,
          {String? logoPath, String? coverPath}) =>
      _run('uploadAssets', () => testFamily);

  @override
  Future<List<FamilyBranch>> branches(String familyUuid) =>
      _run('branches', () => branchItems);

  @override
  Future<FamilyBranch> createBranch(
          String familyUuid, String name, String? description) =>
      _run(
          'createBranch',
          () => FamilyBranch(
              uuid: 'branch-new', name: name, description: description));

  @override
  Future<FamilyBranch> updateBranch(String familyUuid, FamilyBranch branch,
          String name, String? description) =>
      _run(
          'updateBranch',
          () => FamilyBranch(
              uuid: branch.uuid, name: name, description: description));

  @override
  Future<void> deleteBranch(String familyUuid, String branchUuid) =>
      _run('deleteBranch', () {});

  @override
  Future<List<FamilyMembership>> memberships(String familyUuid) =>
      _run('memberships', () => membershipItems);

  @override
  Future<FamilyMembership> invite(
          String familyUuid, String email, String role) =>
      _run(
          'invite',
          () => FamilyMembership(
              uuid: 'membership-new',
              user: User(uuid: 'user-new', name: email, email: email),
              role: role));

  @override
  Future<FamilyMembership> assignRole(
          String familyUuid, String membershipUuid, String role) =>
      _run(
          'assignRole',
          () => FamilyMembership(
              uuid: membershipUuid, user: testUser, role: role));

  @override
  Future<void> removeMembership(String familyUuid, String membershipUuid) =>
      _run('removeMembership', () {});

  @override
  Future<DashboardSummary> dashboard(String familyUuid) =>
      _run('dashboard', () => dashboardValue);

  @override
  Future<List<TimelineItem>> timeline(String familyUuid) =>
      _run('timeline', () => timelineItems);

  @override
  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'}) =>
      _run('tree', () => treeValue);
}

class FakeMemberRepository implements MemberRepository {
  List<FamilyMember> memberItems = [richMember];
  FamilyMember memberValue = richMember;
  PageData<FamilyMember>? memberPage;
  List<MemberRelationship> relationshipItems = const [sampleRelationship];
  List<MemberSpouse> spouseItems = const [sampleSpouse];
  List<MemberDocument> documentItems = [sampleDocument];
  List<DuplicateCandidate> duplicateItems = [sampleDuplicate];
  RelationshipResolution resolution = sampleResolution;
  Object? error;
  final List<String> calls = [];
  final List<Map<String, dynamic>> created = [];
  final List<Map<String, dynamic>> updated = [];
  final List<String> deleted = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<PageData<FamilyMember>> members(String familyUuid,
          {int page = 1,
          int limit = 20,
          String? search,
          String? gender,
          bool? isAlive,
          String? branchUuid,
          String sort = 'name'}) =>
      _run(
          'members',
          () =>
              memberPage ??
              PageData(
                  items: memberItems,
                  currentPage: page,
                  lastPage: page,
                  total: memberItems.length));

  @override
  Future<FamilyMember> member(String uuid) => _run('member', () => memberValue);

  @override
  Future<FamilyMember> create(String familyUuid, Map<String, dynamic> values) =>
      _run('create', () {
        created.add(values);
        return memberValue;
      });

  @override
  Future<FamilyMember> update(String uuid, Map<String, dynamic> values) =>
      _run('update', () {
        updated.add(values);
        return memberValue;
      });

  @override
  Future<FamilyMember> uploadPhoto(String uuid, String path) =>
      _run('uploadPhoto', () => memberValue);

  @override
  Future<void> deleteMember(String uuid) => _run('deleteMember', () {
        deleted.add(uuid);
      });

  @override
  Future<PageData<MemberRelationship>> relationships(String familyUuid,
          {String? memberUuid, int page = 1}) =>
      _run(
          'relationships',
          () => PageData(
              items: relationshipItems,
              currentPage: 1,
              lastPage: 1,
              total: relationshipItems.length));

  @override
  Future<MemberRelationship> createRelationship(Map<String, dynamic> values) =>
      _run('createRelationship', () => sampleRelationship);

  @override
  Future<MemberRelationship> updateRelationship(
          String uuid, Map<String, dynamic> values) =>
      _run('updateRelationship', () => sampleRelationship);

  @override
  Future<void> deleteRelationship(String uuid) =>
      _run('deleteRelationship', () {});

  @override
  Future<List<MemberSpouse>> spouses(String memberUuid) =>
      _run('spouses', () => spouseItems);

  @override
  Future<MemberRelationship> endMarriage(
          String memberUuid, String relationshipUuid,
          {required String endDate, required String endReason}) =>
      _run('endMarriage', () => sampleRelationship);

  @override
  Future<MemberRelationship> correctMarriage(
          String memberUuid, String relationshipUuid,
          {required String endDate, required String endReason}) =>
      _run('correctMarriage', () => sampleRelationship);

  @override
  Future<MemberRelationship> reopenMarriage(
          String memberUuid, String relationshipUuid) =>
      _run('reopenMarriage', () => sampleRelationship);

  @override
  Future<RelationshipResolution> resolve(
          String sourceUuid, String targetUuid) =>
      _run('resolve', () => resolution);

  @override
  Future<List<MemberDocument>> documents(String memberUuid) =>
      _run('documents', () => documentItems);

  @override
  Future<MemberDocument> uploadDocument(String memberUuid,
          {required String path,
          required String title,
          String? category,
          String? documentDate,
          String? notes}) =>
      _run('uploadDocument', () => sampleDocument);

  @override
  Future<void> deleteDocument(String documentUuid) =>
      _run('deleteDocument', () {});

  @override
  Future<List<DuplicateCandidate>> duplicateCandidates(String familyUuid,
          {int limit = 25}) =>
      _run('duplicateCandidates', () => duplicateItems);

  @override
  Future<FamilyMember> mergeMember(String primaryUuid, String duplicateUuid) =>
      _run('mergeMember', () => memberValue);
}

class FakeContentRepository implements ContentRepository {
  List<Article> articleItems = [testArticle];
  List<Article> featuredItems = [testArticle];
  Article articleValue = testArticle;
  ArticleComment commentValue = sampleArticleComment;
  PhotoAlbum albumValue = sampleAlbum;
  FamilyPhoto photoValue = samplePhoto;
  FamilyEvent eventValue = testEvent;
  List<ArticleCategory> categoryItems = const [
    ArticleCategory(uuid: 'category-uuid', name: 'Sejarah')
  ];
  List<ArticleComment> commentItems = [sampleArticleComment];
  List<PhotoAlbum> albumsValue = [sampleAlbum];
  List<FamilyPhoto> photoItems = [samplePhoto];
  List<FamilyEvent> eventItems = [testEvent];
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  PageData<T> _page<T>(List<T> items) =>
      PageData(items: items, currentPage: 1, lastPage: 1, total: items.length);

  @override
  Future<PageData<Article>> articles(String familyUuid,
          {int page = 1,
          String? search,
          String? categoryUuid,
          String? status}) =>
      _run('articles', () => _page(articleItems));

  @override
  Future<List<Article>> featuredArticles(String familyUuid) =>
      _run('featuredArticles', () => featuredItems);

  @override
  Future<List<ArticleCategory>> categories() =>
      _run('categories', () => categoryItems);

  @override
  Future<Article> article(String uuid) => _run('article', () => articleValue);

  @override
  Future<Article> saveArticle(String familyUuid, Map<String, dynamic> values,
          {String? uuid}) =>
      _run('saveArticle', () => articleValue);

  @override
  Future<void> deleteArticle(String uuid) => _run('deleteArticle', () {});

  @override
  Future<Article> publishArticle(String uuid) =>
      _run('publishArticle', () => articleValue);

  @override
  Future<Article> featureArticle(String uuid, bool featured) =>
      _run('featureArticle', () => articleValue);

  @override
  Future<Article> uploadArticleImage(String uuid, String path,
          {ProgressCallback? onProgress}) =>
      _run('uploadArticleImage', () => articleValue);

  @override
  Future<PageData<ArticleComment>> comments(String articleUuid,
          {int page = 1}) =>
      _run('comments', () => _page(commentItems));

  @override
  Future<ArticleComment> saveComment(String articleUuid, String text,
          {String? uuid}) =>
      _run('saveComment', () => commentValue);

  @override
  Future<void> deleteComment(String articleUuid, String uuid) =>
      _run('deleteComment', () {});

  @override
  Future<void> likeArticle(String uuid, bool liked) =>
      _run('likeArticle', () {});

  @override
  Future<PageData<PhotoAlbum>> albums(String familyUuid, {int page = 1}) =>
      _run('albums', () => _page(albumsValue));

  @override
  Future<PhotoAlbum> saveAlbum(
          String familyUuid, String name, String? description,
          {String? uuid}) =>
      _run('saveAlbum', () => albumValue);

  @override
  Future<void> deleteAlbum(String uuid) => _run('deleteAlbum', () {});

  @override
  Future<PageData<FamilyPhoto>> photos(String familyUuid,
          {int page = 1, String? albumUuid}) =>
      _run('photos', () => _page(photoItems));

  @override
  Future<FamilyPhoto> photo(String uuid) => _run('photo', () => photoValue);

  @override
  Future<FamilyPhoto> uploadPhoto(String familyUuid, String path,
          {String? albumUuid,
          String? caption,
          DateTime? capturedAt,
          ProgressCallback? onProgress}) =>
      _run('uploadPhoto', () {
        onProgress?.call(1, 2);
        return samplePhoto;
      });

  @override
  Future<FamilyPhoto> tagPhoto(String uuid, List<String> memberUuids) =>
      _run('tagPhoto', () => photoValue);

  @override
  Future<void> deletePhoto(String uuid) => _run('deletePhoto', () {});

  @override
  Future<PageData<FamilyEvent>> events(String familyUuid,
          {int page = 1, String? search, bool? upcoming}) =>
      _run('events', () => _page(eventItems));

  @override
  Future<FamilyEvent> event(String uuid) => _run('event', () => eventValue);

  @override
  Future<FamilyEvent> saveEvent(String familyUuid, Map<String, dynamic> values,
          {String? uuid}) =>
      _run('saveEvent', () => eventValue);

  @override
  Future<void> deleteEvent(String uuid) => _run('deleteEvent', () {});

  @override
  Future<void> rsvp(String uuid, String status) => _run('rsvp', () {});
}

class FakeDiscoveryRepository implements DiscoveryRepository {
  SearchResults searchValue = sampleSearch;
  FamilyStatistics statisticsValue = sampleStatistics;
  ActivityReport activityValue = sampleActivityReport;
  ReportInsights insightsValue = sampleInsights;
  GamificationProfile profileValue = sampleGamification;
  List<LeaderboardEntry> leaderboardValue = sampleLeaderboard;
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<SearchResults> search(String familyUuid,
          {String? keyword,
          String? name,
          String? city,
          String? status,
          int? generation,
          String? rootMemberUuid,
          int page = 1}) =>
      _run('search', () => searchValue);

  @override
  Future<FamilyStatistics> statistics(String familyUuid) =>
      _run('statistics', () => statisticsValue);

  @override
  Future<ActivityReport> activity(
          String familyUuid, DateTime from, DateTime to) =>
      _run('activity', () => activityValue);

  @override
  Future<ReportInsights> insights(
          String familyUuid, DateTime from, DateTime to) =>
      _run('insights', () => insightsValue);

  @override
  Future<GamificationProfile> profile(String familyUuid) =>
      _run('profile', () => profileValue);

  @override
  Future<List<LeaderboardEntry>> userLeaderboard(String familyUuid) =>
      _run('userLeaderboard', () => leaderboardValue);

  @override
  Future<List<LeaderboardEntry>> familyLeaderboard() =>
      _run('familyLeaderboard', () => leaderboardValue);
}

class FakeNotificationRepository implements NotificationRepository {
  List<AppNotification> items = [sampleNotification];
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<PageData<AppNotification>> all({int page = 1, String? status}) => _run(
      'all',
      () => PageData(
          items: items, currentPage: 1, lastPage: 1, total: items.length));

  @override
  Future<void> markRead(String uuid) => _run('markRead', () {});

  @override
  Future<void> markAllRead() => _run('markAllRead', () {});

  @override
  Future<void> registerDevice(
          {required String platform, required String token}) =>
      _run('registerDevice', () {});

  @override
  Future<void> removeDevice(String uuid) => _run('removeDevice', () {});
}

class FakeTreeRepository implements TreeRepository {
  FamilyTree treeValue = testTree;
  TreeExportJob job = const TreeExportJob(
      uuid: 'export-uuid', status: 'completed', format: 'png');
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<FamilyTree> generate(String rootUuid,
          {required String mode, required int depth, required String layout}) =>
      _run('generate', () => treeValue);

  @override
  Future<FamilyMember> createRelative(
          String memberUuid, Map<String, dynamic> values) =>
      _run('createRelative', () => testMember);

  @override
  Future<Uint8List> export(String format, String rootUuid,
          {required String mode,
          required int depth,
          required String layout,
          required String paperSize,
          CancelToken? cancelToken,
          ProgressCallback? onProgress}) =>
      _run('export', () => Uint8List.fromList([1, 2, 3]));

  @override
  Future<TreeExportJob> requestExport(String rootUuid,
          {required String format,
          required String mode,
          required int depth,
          required String layout,
          required String paperSize}) =>
      _run('requestExport', () => job);

  @override
  Future<TreeExportJob> exportStatus(String uuid) =>
      _run('exportStatus', () => job);

  @override
  Future<Uint8List> downloadExport(String uuid,
          {CancelToken? cancelToken, ProgressCallback? onProgress}) =>
      _run('downloadExport', () {
        onProgress?.call(1, 2);
        return Uint8List.fromList([1, 2, 3]);
      });
}

class FakeFamilyLinkRepository implements FamilyLinkRepository {
  List<FamilyLink> linkItems = [sampleLink];
  List<FamilyLinkCandidate> candidateItems = [sampleCandidate];
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<List<FamilyLink>> links(String familyUuid) =>
      _run('links', () => linkItems);

  @override
  Future<List<FamilyLinkCandidate>> candidates(String familyUuid,
          {required String search, int limit = 25}) =>
      _run('candidates', () => candidateItems);

  @override
  Future<FamilyLink> request(String familyUuid,
          {required String memberUuid, required String linkedMemberUuid}) =>
      _run('request', () => sampleLink);

  @override
  Future<FamilyLink> approve(String linkUuid) =>
      _run('approve', () => sampleLink);

  @override
  Future<FamilyLink> revoke(String linkUuid) =>
      _run('revoke', () => sampleLink);

  @override
  Future<FamilyTree> partnerTree(String linkUuid,
          {required String familyUuid,
          String mode = 'full',
          int depth = 4,
          String layout = 'vertical'}) =>
      _run('partnerTree', () => testTree);
}

class FakeAccountRepository implements AccountRepository {
  User user = testUser;
  NotificationPreferences preferencesValue = const NotificationPreferences(
      email: true, push: true, eventReminders: true, familyUpdates: true);
  List<AccountSession> sessionItems = [
    AccountSession(
        uuid: 'session-current',
        deviceName: 'Pixel',
        isCurrent: true,
        lastActiveAt: DateTime(2026, 1, 1))
  ];
  TwoFactorStatus twoFactorValue = const TwoFactorStatus(
      enabled: false, pending: false, recoveryCodesCount: 0);
  TwoFactorSetup setupValue =
      const TwoFactorSetup(secret: 'SECRET', otpauthUrl: 'otpauth://totp/test');
  List<String> recoveryCodes = const ['CODE1', 'CODE2'];
  bool revokeCurrent = false;
  Object? error;
  final List<String> calls = [];

  Future<T> _run<T>(String name, T Function() action) async {
    calls.add(name);
    if (error != null) throw error!;
    return action();
  }

  @override
  Future<User> profile() => _run('profile', () => user);

  @override
  Future<User> updateProfile(String name, String email, String? phone,
          {String? currentPassword}) =>
      _run('updateProfile', () => user);

  @override
  Future<User> uploadAvatar(String path) => _run('uploadAvatar', () => user);

  @override
  Future<void> changePassword(String currentPassword, String password) =>
      _run('changePassword', () {});

  @override
  Future<NotificationPreferences> preferences() =>
      _run('preferences', () => preferencesValue);

  @override
  Future<NotificationPreferences> updatePreferences(
          NotificationPreferences value) =>
      _run('updatePreferences', () {
        preferencesValue = value;
        return value;
      });

  @override
  Future<List<AccountSession>> sessions() =>
      _run('sessions', () => sessionItems);

  @override
  Future<bool> revokeSession(String uuid) =>
      _run('revokeSession', () => revokeCurrent);

  @override
  Future<TwoFactorStatus> twoFactorStatus() =>
      _run('twoFactorStatus', () => twoFactorValue);

  @override
  Future<TwoFactorSetup> enableTwoFactor(String currentPassword) =>
      _run('enableTwoFactor', () => setupValue);

  @override
  Future<List<String>> confirmTwoFactor(String code) =>
      _run('confirmTwoFactor', () => recoveryCodes);

  @override
  Future<void> disableTwoFactor(String currentPassword) =>
      _run('disableTwoFactor', () {});

  @override
  Future<List<String>> regenerateRecoveryCodes(String currentPassword) =>
      _run('regenerateRecoveryCodes', () => recoveryCodes);
}
