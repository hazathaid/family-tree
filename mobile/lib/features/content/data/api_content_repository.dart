import 'package:dio/dio.dart';

import '../../../core/api_client.dart';
import '../../../core/http/page_data.dart';
import '../domain/content_models.dart';
import '../domain/content_repository.dart';

class ApiContentRepository implements ContentRepository {
  const ApiContentRepository(this.api);
  final ApiClient api;

  PageData<T> _page<T>(dynamic raw, T Function(Map<String, dynamic>) decode) {
    if (raw is List<dynamic>) {
      return PageData(
          items: raw.map((e) => decode(e as Map<String, dynamic>)).toList(),
          currentPage: 1,
          lastPage: 1,
          total: raw.length);
    }
    return PageData.fromJson(raw as Map<String, dynamic>, decode);
  }

  @override
  Future<PageData<Article>> articles(String familyUuid,
          {int page = 1,
          String? search,
          String? categoryUuid,
          String? status}) async =>
      _page(
          await api.get('/articles', query: {
            'family_uuid': familyUuid,
            'page': page,
            'limit': 20,
            if (search?.isNotEmpty == true) 'search': search,
            if (categoryUuid != null) 'category_uuid': categoryUuid,
            if (status != null) 'status': status
          }),
          Article.fromJson);
  @override
  Future<List<Article>> featuredArticles(String familyUuid) async {
    final raw = await api
        .get('/families/$familyUuid/articles/featured', query: {'limit': 10});
    final items = raw is Map<String, dynamic>
        ? raw['data'] as List<dynamic>? ?? const []
        : raw as List<dynamic>;
    return items
        .map((e) => Article.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  @override
  Future<List<ArticleCategory>> categories() async {
    final raw = await api.get('/article-categories', query: {'limit': 100});
    return _page(raw, ArticleCategory.fromJson).items;
  }

  @override
  Future<Article> article(String uuid) async => Article.fromJson(
      await api.get('/articles/$uuid') as Map<String, dynamic>);
  @override
  Future<Article> saveArticle(String familyUuid, Map<String, dynamic> values,
      {String? uuid}) async {
    final body = {'family_uuid': familyUuid, ...values};
    final raw = uuid == null
        ? await api.post('/articles', data: body)
        : await api.put('/articles/$uuid', data: values);
    return Article.fromJson(raw as Map<String, dynamic>);
  }

  @override
  Future<void> deleteArticle(String uuid) async =>
      api.delete('/articles/$uuid');
  @override
  Future<Article> publishArticle(String uuid) async => Article.fromJson(
      await api.post('/articles/$uuid/publish') as Map<String, dynamic>);
  @override
  Future<Article> featureArticle(String uuid, bool featured) async =>
      Article.fromJson((featured
              ? await api.post('/articles/$uuid/feature')
              : await api.delete('/articles/$uuid/feature'))
          as Map<String, dynamic>);
  @override
  Future<Article> uploadArticleImage(String uuid, String path,
          {ProgressCallback? onProgress}) async =>
      Article.fromJson(await api.post('/articles/$uuid/featured-image',
          data: FormData.fromMap({'image': await MultipartFile.fromFile(path)}),
          onSendProgress: onProgress) as Map<String, dynamic>);
  @override
  Future<PageData<ArticleComment>> comments(String articleUuid,
          {int page = 1}) async =>
      _page(
          await api.get('/articles/$articleUuid/comments',
              query: {'page': page, 'limit': 20}),
          ArticleComment.fromJson);
  @override
  Future<ArticleComment> saveComment(String articleUuid, String text,
          {String? uuid}) async =>
      ArticleComment.fromJson((uuid == null
          ? await api
              .post('/articles/$articleUuid/comments', data: {'comment': text})
          : await api.put('/articles/$articleUuid/comments/$uuid',
              data: {'comment': text})) as Map<String, dynamic>);
  @override
  Future<void> deleteComment(String articleUuid, String uuid) async =>
      api.delete('/articles/$articleUuid/comments/$uuid');
  @override
  Future<void> likeArticle(String uuid, bool liked) async {
    liked
        ? await api.post('/articles/$uuid/like')
        : await api.delete('/articles/$uuid/like');
  }

  @override
  Future<PageData<PhotoAlbum>> albums(String familyUuid,
          {int page = 1}) async =>
      _page(
          await api.get('/photo-albums',
              query: {'family_uuid': familyUuid, 'page': page, 'limit': 20}),
          PhotoAlbum.fromJson);
  @override
  Future<PhotoAlbum> saveAlbum(
          String familyUuid, String name, String? description,
          {String? uuid}) async =>
      PhotoAlbum.fromJson((uuid == null
              ? await api.post('/photo-albums', data: {
                  'family_uuid': familyUuid,
                  'name': name,
                  'description': description
                })
              : await api.put('/photo-albums/$uuid',
                  data: {'name': name, 'description': description}))
          as Map<String, dynamic>);
  @override
  Future<void> deleteAlbum(String uuid) async =>
      api.delete('/photo-albums/$uuid');
  @override
  Future<PageData<FamilyPhoto>> photos(String familyUuid,
          {int page = 1, String? albumUuid}) async =>
      _page(
          await api.get('/member-photos', query: {
            'family_uuid': familyUuid,
            'page': page,
            'limit': 20,
            if (albumUuid != null) 'album_uuid': albumUuid
          }),
          FamilyPhoto.fromJson);
  @override
  Future<FamilyPhoto> photo(String uuid) async => FamilyPhoto.fromJson(
      await api.get('/member-photos/$uuid') as Map<String, dynamic>);
  @override
  Future<FamilyPhoto> uploadPhoto(String familyUuid, String path,
          {String? albumUuid,
          String? caption,
          DateTime? capturedAt,
          ProgressCallback? onProgress}) async =>
      FamilyPhoto.fromJson(await api.post('/member-photos',
          data: FormData.fromMap({
            'family_uuid': familyUuid,
            'image': await MultipartFile.fromFile(path),
            if (albumUuid != null) 'album_uuid': albumUuid,
            if (caption?.isNotEmpty == true) 'caption': caption,
            if (capturedAt != null) 'captured_at': capturedAt.toIso8601String()
          }),
          onSendProgress: onProgress) as Map<String, dynamic>);
  @override
  Future<FamilyPhoto> tagPhoto(String uuid, List<String> memberUuids) async =>
      FamilyPhoto.fromJson(await api.put('/member-photos/$uuid/tags',
          data: {'member_uuids': memberUuids}) as Map<String, dynamic>);
  @override
  Future<void> deletePhoto(String uuid) async =>
      api.delete('/member-photos/$uuid');

  @override
  Future<PageData<FamilyEvent>> events(String familyUuid,
          {int page = 1, String? search, bool? upcoming}) async =>
      _page(
          await api.get('/events', query: {
            'family_uuid': familyUuid,
            'page': page,
            'limit': 20,
            if (search?.isNotEmpty == true) 'search': search,
            if (upcoming != null) 'upcoming': upcoming ? 1 : 0
          }),
          FamilyEvent.fromJson);
  @override
  Future<FamilyEvent> event(String uuid) async => FamilyEvent.fromJson(
      await api.get('/events/$uuid') as Map<String, dynamic>);
  @override
  Future<FamilyEvent> saveEvent(String familyUuid, Map<String, dynamic> values,
          {String? uuid}) async =>
      FamilyEvent.fromJson((uuid == null
              ? await api
                  .post('/events', data: {'family_uuid': familyUuid, ...values})
              : await api.put('/events/$uuid', data: values))
          as Map<String, dynamic>);
  @override
  Future<void> deleteEvent(String uuid) async => api.delete('/events/$uuid');
  @override
  Future<void> rsvp(String uuid, String status) async =>
      api.post('/events/$uuid/rsvp', data: {'status': status});
}
