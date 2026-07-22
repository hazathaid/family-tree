class ArticleCategory {
  const ArticleCategory({required this.uuid, required this.name});
  final String uuid;
  final String name;
  factory ArticleCategory.fromJson(Map<String, dynamic> json) =>
      ArticleCategory(
          uuid: json['uuid'] as String, name: json['name'] as String);
}

class Article {
  const Article(
      {required this.uuid,
      required this.familyUuid,
      required this.title,
      required this.content,
      required this.status,
      required this.category,
      required this.authorUuid,
      required this.authorName,
      this.excerpt,
      this.featuredImageUrl,
      this.publishedAt,
      this.isFeatured = false,
      this.likesCount = 0,
      this.commentsCount = 0,
      this.isLikedByMe = false});
  final String uuid, familyUuid, title, content, status, authorUuid, authorName;
  final ArticleCategory category;
  final String? excerpt, featuredImageUrl;
  final DateTime? publishedAt;
  final bool isFeatured, isLikedByMe;
  final int likesCount, commentsCount;
  factory Article.fromJson(Map<String, dynamic> json) {
    final author = json['author'] as Map<String, dynamic>? ?? const {};
    return Article(
        uuid: json['uuid'] as String,
        familyUuid: json['family_uuid'] as String,
        title: json['title'] as String,
        content: json['content'] as String? ?? '',
        status: json['status'] as String? ?? 'draft',
        category:
            ArticleCategory.fromJson(json['category'] as Map<String, dynamic>),
        authorUuid: author['uuid'] as String? ?? '',
        authorName: author['name'] as String? ?? '',
        excerpt: json['excerpt'] as String?,
        featuredImageUrl: json['featured_image_url'] as String?,
        publishedAt: DateTime.tryParse(json['published_at'] as String? ?? ''),
        isFeatured: json['is_featured'] as bool? ?? false,
        likesCount: json['likes_count'] as int? ?? 0,
        commentsCount: json['comments_count'] as int? ?? 0,
        isLikedByMe: json['is_liked_by_me'] as bool? ?? false);
  }
}

class ArticleComment {
  const ArticleComment(
      {required this.uuid,
      required this.userUuid,
      required this.userName,
      required this.comment,
      this.createdAt});
  final String uuid, userUuid, userName, comment;
  final DateTime? createdAt;
  factory ArticleComment.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>? ?? const {};
    return ArticleComment(
        uuid: json['uuid'] as String,
        userUuid: user['uuid'] as String? ?? '',
        userName: user['name'] as String? ?? '',
        comment: json['comment'] as String,
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''));
  }
}

class PhotoAlbum {
  const PhotoAlbum(
      {required this.uuid,
      required this.familyUuid,
      required this.name,
      this.description,
      this.photosCount = 0});
  final String uuid, familyUuid, name;
  final String? description;
  final int photosCount;
  factory PhotoAlbum.fromJson(Map<String, dynamic> json) => PhotoAlbum(
      uuid: json['uuid'] as String,
      familyUuid: json['family_uuid'] as String,
      name: json['name'] as String,
      description: json['description'] as String?,
      photosCount: json['photos_count'] as int? ?? 0);
}

class TaggedMember {
  const TaggedMember({required this.uuid, required this.name});
  final String uuid, name;
  factory TaggedMember.fromJson(Map<String, dynamic> json) => TaggedMember(
      uuid: json['uuid'] as String, name: json['full_name'] as String);
}

class FamilyPhoto {
  const FamilyPhoto(
      {required this.uuid,
      required this.familyUuid,
      required this.url,
      required this.thumbnailUrl,
      required this.size,
      this.caption,
      this.capturedAt,
      this.albumUuid,
      this.albumName,
      this.taggedMembers = const []});
  final String uuid, familyUuid, url, thumbnailUrl;
  final int size;
  final String? caption, albumUuid, albumName;
  final DateTime? capturedAt;
  final List<TaggedMember> taggedMembers;
  factory FamilyPhoto.fromJson(Map<String, dynamic> json) {
    final album = json['album'] as Map<String, dynamic>?;
    return FamilyPhoto(
        uuid: json['uuid'] as String,
        familyUuid: json['family_uuid'] as String,
        url: json['url'] as String,
        thumbnailUrl: json['thumbnail_url'] as String,
        size: json['size'] as int? ?? 0,
        caption: json['caption'] as String?,
        capturedAt: DateTime.tryParse(json['captured_at'] as String? ?? ''),
        albumUuid: album?['uuid'] as String?,
        albumName: album?['name'] as String?,
        taggedMembers: (json['tagged_members'] as List<dynamic>? ?? const [])
            .map((e) => TaggedMember.fromJson(e as Map<String, dynamic>))
            .toList());
  }
}

class EventAttendee {
  const EventAttendee(
      {required this.uuid,
      required this.userUuid,
      required this.userName,
      required this.status});
  final String uuid, userUuid, userName, status;
  factory EventAttendee.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>? ?? const {};
    return EventAttendee(
        uuid: json['uuid'] as String,
        userUuid: user['uuid'] as String? ?? '',
        userName: user['name'] as String? ?? '',
        status: json['status'] as String);
  }
}

class FamilyEvent {
  const FamilyEvent(
      {required this.uuid,
      required this.familyUuid,
      required this.title,
      required this.eventDate,
      required this.organizerName,
      this.description,
      this.location,
      this.myRsvp,
      this.attendees = const []});
  final String uuid, familyUuid, title, organizerName;
  final DateTime eventDate;
  final String? description, location, myRsvp;
  final List<EventAttendee> attendees;
  factory FamilyEvent.fromJson(Map<String, dynamic> json) => FamilyEvent(
      uuid: json['uuid'] as String,
      familyUuid: json['family_uuid'] as String,
      title: json['title'] as String,
      eventDate: DateTime.parse(json['event_date'] as String),
      organizerName:
          (json['organizer'] as Map<String, dynamic>?)?['name'] as String? ??
              '',
      description: json['description'] as String?,
      location: json['location'] as String?,
      myRsvp: json['my_rsvp'] as String?,
      attendees: (json['attendees'] as List<dynamic>? ?? const [])
          .map((e) => EventAttendee.fromJson(e as Map<String, dynamic>))
          .toList());
}
