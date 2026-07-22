import 'package:dio/dio.dart';

import '../../../core/http/page_data.dart';
import 'content_models.dart';

abstract interface class ContentRepository {
  Future<PageData<Article>> articles(String familyUuid,
      {int page = 1, String? search, String? categoryUuid, String? status});
  Future<List<Article>> featuredArticles(String familyUuid);
  Future<List<ArticleCategory>> categories();
  Future<Article> article(String uuid);
  Future<Article> saveArticle(String familyUuid, Map<String, dynamic> values,
      {String? uuid});
  Future<void> deleteArticle(String uuid);
  Future<Article> publishArticle(String uuid);
  Future<Article> featureArticle(String uuid, bool featured);
  Future<Article> uploadArticleImage(String uuid, String path,
      {ProgressCallback? onProgress});
  Future<PageData<ArticleComment>> comments(String articleUuid, {int page = 1});
  Future<ArticleComment> saveComment(String articleUuid, String text,
      {String? uuid});
  Future<void> deleteComment(String articleUuid, String uuid);
  Future<void> likeArticle(String uuid, bool liked);
  Future<PageData<PhotoAlbum>> albums(String familyUuid, {int page = 1});
  Future<PhotoAlbum> saveAlbum(
      String familyUuid, String name, String? description,
      {String? uuid});
  Future<void> deleteAlbum(String uuid);
  Future<PageData<FamilyPhoto>> photos(String familyUuid,
      {int page = 1, String? albumUuid});
  Future<FamilyPhoto> photo(String uuid);
  Future<FamilyPhoto> uploadPhoto(String familyUuid, String path,
      {String? albumUuid,
      String? caption,
      DateTime? capturedAt,
      ProgressCallback? onProgress});
  Future<FamilyPhoto> tagPhoto(String uuid, List<String> memberUuids);
  Future<void> deletePhoto(String uuid);
  Future<PageData<FamilyEvent>> events(String familyUuid,
      {int page = 1, String? search, bool? upcoming});
  Future<FamilyEvent> event(String uuid);
  Future<FamilyEvent> saveEvent(String familyUuid, Map<String, dynamic> values,
      {String? uuid});
  Future<void> deleteEvent(String uuid);
  Future<void> rsvp(String uuid, String status);
}
