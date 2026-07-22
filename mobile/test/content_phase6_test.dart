import 'package:dio/dio.dart';
import 'package:family_tree_mobile/core/http/page_data.dart';
import 'package:family_tree_mobile/core/models.dart';
import 'package:family_tree_mobile/core/providers.dart';
import 'package:family_tree_mobile/features/content/domain/content_models.dart';
import 'package:family_tree_mobile/features/content/domain/content_repository.dart';
import 'package:family_tree_mobile/features/content/presentation/content_screens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart' hide Family;
import 'package:flutter_test/flutter_test.dart';

void main() {
  const family =
      Family(uuid: 'family', name: 'Keluarga', currentUserRole: 'owner');
  const category = ArticleCategory(uuid: 'category', name: 'Sejarah');
  const article = Article(
      uuid: 'article',
      familyUuid: 'family',
      title: 'Kisah Keluarga',
      content: '<p>Aman</p>',
      status: 'published',
      category: category,
      authorUuid: 'user',
      authorName: 'Budi');

  testWidgets('article list exposes published family content and owner action',
      (tester) async {
    await tester.pumpWidget(ProviderScope(overrides: [
      currentFamilyProvider.overrideWith((ref) => family),
      contentRepositoryProvider.overrideWithValue(const _Content(article)),
    ], child: const MaterialApp(home: ArticleListScreen())));
    await tester.pumpAndSettle();
    expect(find.text('Kisah Keluarga'), findsWidgets);
    expect(find.byTooltip('Tulis artikel'), findsOneWidget);
    expect(find.text('Terbit'), findsOneWidget);
  });

  test('phase 6 models preserve server engagement, tags and RSVP contracts',
      () {
    final parsed = Article.fromJson({
      'uuid': 'a',
      'family_uuid': 'f',
      'title': 'Judul',
      'content': '<p>Isi</p>',
      'status': 'published',
      'category': {'uuid': 'c', 'name': 'Cerita'},
      'author': {'uuid': 'u', 'name': 'Siti'},
      'likes_count': 4,
      'comments_count': 2,
      'is_liked_by_me': true
    });
    final photo = FamilyPhoto.fromJson({
      'uuid': 'p',
      'family_uuid': 'f',
      'url': 'https://example.test/p.jpg',
      'thumbnail_url': 'https://example.test/t.jpg',
      'size': 10,
      'tagged_members': [
        {'uuid': 'm', 'full_name': 'Budi'}
      ]
    });
    final event = FamilyEvent.fromJson({
      'uuid': 'e',
      'family_uuid': 'f',
      'title': 'Reuni',
      'event_date': '2026-08-01T10:00:00+07:00',
      'organizer': {'uuid': 'u', 'name': 'Siti'},
      'my_rsvp': 'yes',
      'attendees': []
    });
    expect(parsed.isLikedByMe, isTrue);
    expect(photo.taggedMembers.single.name, 'Budi');
    expect(event.myRsvp, 'yes');
  });

  test('notification deep links only allow known target types', () {
    const articleNotification = AppNotification(
        uuid: 'n',
        title: 'Artikel',
        body: 'Baru',
        isRead: false,
        data: {'target_type': 'article', 'target_uuid': 'a'});
    const unsafeNotification = AppNotification(
        uuid: 'n2',
        title: 'Lain',
        body: 'Baru',
        isRead: false,
        data: {'target_type': 'https://evil.test', 'target_uuid': 'a'});
    expect(articleNotification.targetPath, '/articles/a');
    expect(unsafeNotification.targetPath, isNull);
  });
}

class _Content implements ContentRepository {
  const _Content(this.articleValue);
  final Article articleValue;
  PageData<T> _one<T>(T value) =>
      PageData(items: [value], currentPage: 1, lastPage: 1, total: 1);
  @override
  Future<PageData<Article>> articles(String f,
          {int page = 1,
          String? search,
          String? categoryUuid,
          String? status}) async =>
      _one(articleValue);
  @override
  Future<List<Article>> featuredArticles(String f) async => [articleValue];
  @override
  Future<List<ArticleCategory>> categories() async => [articleValue.category];
  @override
  Future<Article> article(String uuid) async => articleValue;
  @override
  Future<Article> saveArticle(String f, Map<String, dynamic> v,
          {String? uuid}) async =>
      articleValue;
  @override
  Future<void> deleteArticle(String uuid) async {}
  @override
  Future<Article> publishArticle(String uuid) async => articleValue;
  @override
  Future<Article> featureArticle(String uuid, bool featured) async =>
      articleValue;
  @override
  Future<Article> uploadArticleImage(String uuid, String path,
          {ProgressCallback? onProgress}) async =>
      articleValue;
  @override
  Future<PageData<ArticleComment>> comments(String uuid,
          {int page = 1}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
  @override
  Future<ArticleComment> saveComment(String a, String text, {String? uuid}) =>
      throw UnimplementedError();
  @override
  Future<void> deleteComment(String a, String uuid) async {}
  @override
  Future<void> likeArticle(String uuid, bool liked) async {}
  @override
  Future<PageData<PhotoAlbum>> albums(String f, {int page = 1}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
  @override
  Future<PhotoAlbum> saveAlbum(String f, String n, String? d, {String? uuid}) =>
      throw UnimplementedError();
  @override
  Future<void> deleteAlbum(String uuid) async {}
  @override
  Future<PageData<FamilyPhoto>> photos(String f,
          {int page = 1, String? albumUuid}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
  @override
  Future<FamilyPhoto> photo(String uuid) => throw UnimplementedError();
  @override
  Future<FamilyPhoto> uploadPhoto(String f, String path,
          {String? albumUuid,
          String? caption,
          DateTime? capturedAt,
          ProgressCallback? onProgress}) =>
      throw UnimplementedError();
  @override
  Future<FamilyPhoto> tagPhoto(String uuid, List<String> memberUuids) =>
      throw UnimplementedError();
  @override
  Future<void> deletePhoto(String uuid) async {}
  @override
  Future<PageData<FamilyEvent>> events(String f,
          {int page = 1, String? search, bool? upcoming}) async =>
      const PageData(items: [], currentPage: 1, lastPage: 1, total: 0);
  @override
  Future<FamilyEvent> event(String uuid) => throw UnimplementedError();
  @override
  Future<FamilyEvent> saveEvent(String f, Map<String, dynamic> v,
          {String? uuid}) =>
      throw UnimplementedError();
  @override
  Future<void> deleteEvent(String uuid) async {}
  @override
  Future<void> rsvp(String uuid, String status) async {}
}
