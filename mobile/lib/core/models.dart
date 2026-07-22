class User {
  const User(
      {required this.uuid,
      required this.name,
      required this.email,
      this.phone,
      this.avatarUrl,
      this.emailVerifiedAt,
      this.status = 'active'});

  final String uuid;
  final String name;
  final String email;
  final String? phone;
  final String? avatarUrl;
  final DateTime? emailVerifiedAt;
  final String status;

  bool get isVerified => emailVerifiedAt != null;

  factory User.fromJson(Map<String, dynamic> json) => User(
        uuid: json['uuid'] as String,
        name: json['name'] as String,
        email: json['email'] as String,
        phone: json['phone'] as String?,
        avatarUrl: json['avatar_url'] as String?,
        emailVerifiedAt:
            DateTime.tryParse(json['email_verified_at'] as String? ?? ''),
        status: json['status'] as String? ?? 'active',
      );
}

class Family {
  const Family({
    required this.uuid,
    required this.name,
    this.description,
    this.originCity,
    this.logoUrl,
    this.coverImageUrl,
    this.privacy = 'members_only',
    this.currentUserRole = 'member',
  });

  final String uuid;
  final String name;
  final String? description;
  final String? originCity;
  final String? logoUrl;
  final String? coverImageUrl;
  final String privacy;
  final String currentUserRole;

  bool get canManage =>
      currentUserRole == 'owner' || currentUserRole == 'admin';
  bool get canManageRoles => currentUserRole == 'owner';

  factory Family.fromJson(Map<String, dynamic> json) => Family(
        uuid: json['uuid'] as String,
        name: json['name'] as String,
        description: json['description'] as String?,
        originCity: json['origin_city'] as String?,
        logoUrl: json['logo_url'] as String?,
        coverImageUrl: json['cover_image_url'] as String?,
        privacy: json['privacy'] as String? ?? 'members_only',
        currentUserRole: json['current_user_role'] as String? ?? 'member',
      );
}

class DashboardEntry {
  const DashboardEntry(
      {required this.uuid, required this.title, this.subtitle, this.date});
  final String uuid;
  final String title;
  final String? subtitle;
  final DateTime? date;
}

class NotificationPreferences {
  const NotificationPreferences(
      {required this.email,
      required this.push,
      required this.eventReminders,
      required this.familyUpdates});
  final bool email;
  final bool push;
  final bool eventReminders;
  final bool familyUpdates;
  factory NotificationPreferences.fromJson(Map<String, dynamic> json) =>
      NotificationPreferences(
          email: json['email'] as bool? ?? true,
          push: json['push'] as bool? ?? true,
          eventReminders: json['event_reminders'] as bool? ?? true,
          familyUpdates: json['family_updates'] as bool? ?? true);
  Map<String, dynamic> toJson() => {
        'email': email,
        'push': push,
        'event_reminders': eventReminders,
        'family_updates': familyUpdates
      };
}

class AccountSession {
  const AccountSession(
      {required this.uuid,
      required this.deviceName,
      required this.isCurrent,
      this.lastActiveAt});
  final String uuid;
  final String deviceName;
  final bool isCurrent;
  final DateTime? lastActiveAt;
  factory AccountSession.fromJson(Map<String, dynamic> json) => AccountSession(
      uuid: json['uuid'] as String,
      deviceName: json['device_name'] as String,
      isCurrent: json['is_current'] as bool? ?? false,
      lastActiveAt: DateTime.tryParse(json['last_active_at'] as String? ?? ''));
}

class DashboardSummary {
  const DashboardSummary({
    required this.totalMembers,
    required this.livingMembers,
    required this.deceasedMembers,
    required this.totalArticles,
    required this.totalEvents,
    required this.totalPhotos,
    this.activity = const [],
    this.birthdays = const [],
    this.events = const [],
    this.notifications = const [],
    this.recentMembers = const [],
    this.unreadNotifications = 0,
    this.originCity,
    this.oldestMember,
    this.youngestMember,
  });

  final int totalMembers;
  final int livingMembers;
  final int deceasedMembers;
  final int totalArticles;
  final int totalEvents;
  final int totalPhotos;
  final List<DashboardEntry> activity;
  final List<DashboardEntry> birthdays;
  final List<DashboardEntry> events;
  final List<DashboardEntry> notifications;
  final List<DashboardEntry> recentMembers;
  final int unreadNotifications;
  final String? originCity;
  final DashboardEntry? oldestMember;
  final DashboardEntry? youngestMember;

  factory DashboardSummary.fromJson(Map<String, dynamic> json) =>
      DashboardSummary(
        totalMembers: json['total_members'] as int? ?? 0,
        livingMembers: json['living_members'] as int? ?? 0,
        deceasedMembers: json['deceased_members'] as int? ?? 0,
        totalArticles: json['total_articles'] as int? ?? 0,
        totalEvents: json['total_events'] as int? ?? 0,
        totalPhotos: json['total_photos'] as int? ?? 0,
        activity: _entries(json['recent_activity'],
            title: 'message', date: 'created_at'),
        birthdays: _entries(json['upcoming_birthdays'],
            title: 'full_name', date: 'next_birthday'),
        events: _entries(json['upcoming_events'],
            title: 'title', subtitle: 'location', date: 'event_date'),
        notifications: _entries(
            (json['notification_summary'] as Map<String, dynamic>?)?['recent'],
            title: 'title',
            subtitle: 'body',
            date: 'created_at'),
        recentMembers: _entries(json['recent_members'], title: 'full_name'),
        unreadNotifications: (json['notification_summary']
                as Map<String, dynamic>?)?['unread_count'] as int? ??
            0,
        originCity: (json['family_facts']
            as Map<String, dynamic>?)?['origin_city'] as String?,
        oldestMember: _entry(
            (json['family_facts']
                as Map<String, dynamic>?)?['oldest_living_member'],
            'full_name'),
        youngestMember: _entry(
            (json['family_facts']
                as Map<String, dynamic>?)?['youngest_living_member'],
            'full_name'),
      );

  static List<DashboardEntry> _entries(dynamic value,
          {required String title, String? subtitle, String? date}) =>
      (value as List<dynamic>? ?? const []).map((item) {
        final json = item as Map<String, dynamic>;
        return DashboardEntry(
            uuid: json['uuid'] as String,
            title: json[title] as String? ?? '',
            subtitle: subtitle == null ? null : json[subtitle] as String?,
            date: date == null
                ? null
                : DateTime.tryParse(json[date] as String? ?? ''));
      }).toList(growable: false);

  static DashboardEntry? _entry(dynamic value, String title) {
    if (value is! Map<String, dynamic>) return null;
    return DashboardEntry(
        uuid: value['uuid'] as String, title: value[title] as String? ?? '');
  }
}

class FamilyBranch {
  const FamilyBranch(
      {required this.uuid, required this.name, this.description});
  final String uuid;
  final String name;
  final String? description;
  factory FamilyBranch.fromJson(Map<String, dynamic> json) => FamilyBranch(
      uuid: json['uuid'] as String,
      name: json['name'] as String,
      description: json['description'] as String?);
}

class FamilyMembership {
  const FamilyMembership(
      {required this.uuid, required this.user, required this.role});
  final String uuid;
  final User user;
  final String role;
  factory FamilyMembership.fromJson(Map<String, dynamic> json) =>
      FamilyMembership(
          uuid: json['uuid'] as String,
          user: User.fromJson(json['user'] as Map<String, dynamic>),
          role: json['role'] as String);
}

class FamilyMember {
  const FamilyMember(
      {required this.uuid,
      required this.familyUuid,
      required this.fullName,
      required this.isAlive,
      this.branchUuid,
      this.branchName,
      this.nickname,
      this.gender,
      this.birthDate,
      this.birthPlace,
      this.deathDate,
      this.deathPlace,
      this.biography,
      this.photoUrl});
  final String uuid;
  final String familyUuid;
  final String fullName;
  final bool isAlive;
  final String? branchUuid;
  final String? branchName;
  final String? nickname;
  final String? gender;
  final DateTime? birthDate;
  final String? birthPlace;
  final DateTime? deathDate;
  final String? deathPlace;
  final String? biography;
  final String? photoUrl;

  factory FamilyMember.fromJson(Map<String, dynamic> json) => FamilyMember(
        uuid: json['uuid'] as String,
        familyUuid: json['family_uuid'] as String,
        fullName: json['full_name'] as String,
        isAlive: json['is_alive'] as bool? ?? true,
        branchUuid: json['family_branch_uuid'] as String?,
        branchName: json['family_branch_name'] as String?,
        nickname: json['nickname'] as String?,
        gender: json['gender'] as String?,
        birthDate: DateTime.tryParse(json['birth_date'] as String? ?? ''),
        birthPlace: json['birth_place'] as String?,
        deathDate: DateTime.tryParse(json['death_date'] as String? ?? ''),
        deathPlace: json['death_place'] as String?,
        biography: json['biography'] as String?,
        photoUrl: (json['profile_photo_thumbnail_url'] ??
            json['profile_photo_url']) as String?,
      );
}

class MemberRelationship {
  const MemberRelationship(
      {required this.uuid,
      required this.sourceUuid,
      required this.sourceName,
      required this.targetUuid,
      required this.targetName,
      required this.type,
      this.notes});
  final String uuid;
  final String sourceUuid;
  final String sourceName;
  final String targetUuid;
  final String targetName;
  final String type;
  final String? notes;
  factory MemberRelationship.fromJson(Map<String, dynamic> json) =>
      MemberRelationship(
        uuid: json['uuid'] as String,
        sourceUuid: json['source_member_uuid'] as String,
        sourceName: json['source_member_name'] as String,
        targetUuid: json['target_member_uuid'] as String,
        targetName: json['target_member_name'] as String,
        type: json['relationship_type'] as String,
        notes: json['notes'] as String?,
      );
}

class RelationshipResolution {
  const RelationshipResolution(
      {required this.relationship, required this.path});
  final String? relationship;
  final List<RelationshipPathStep> path;
  bool get isConnected => relationship != null;
  factory RelationshipResolution.fromJson(Map<String, dynamic> json) =>
      RelationshipResolution(
        relationship: json['relationship'] as String?,
        path: (json['path'] as List<dynamic>? ?? const [])
            .map((item) =>
                RelationshipPathStep.fromJson(item as Map<String, dynamic>))
            .toList(growable: false),
      );
}

class RelationshipPathStep {
  const RelationshipPathStep(
      {required this.relationship,
      required this.fromName,
      required this.toName});
  final String relationship;
  final String fromName;
  final String toName;
  factory RelationshipPathStep.fromJson(Map<String, dynamic> json) =>
      RelationshipPathStep(
        relationship: json['relationship'] as String? ?? '',
        fromName: json['from_member_name'] as String? ?? '',
        toName: json['to_member_name'] as String? ?? '',
      );
}

class TimelineItem {
  const TimelineItem(
      {required this.uuid, required this.message, required this.createdAt});

  final String uuid;
  final String message;
  final DateTime? createdAt;

  factory TimelineItem.fromJson(Map<String, dynamic> json) => TimelineItem(
        uuid: json['uuid'] as String,
        message: json['message'] as String,
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''),
      );
}

class AppNotification {
  const AppNotification({
    required this.uuid,
    required this.title,
    required this.body,
    required this.isRead,
  });

  final String uuid;
  final String title;
  final String body;
  final bool isRead;

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        uuid: json['uuid'] as String,
        title: json['title'] as String,
        body: json['body'] as String,
        isRead: json['is_read'] as bool? ?? false,
      );
}

class TreeNode {
  const TreeNode(
      {required this.uuid,
      required this.name,
      required this.x,
      required this.y});

  final String uuid;
  final String name;
  final double x;
  final double y;

  factory TreeNode.fromJson(Map<String, dynamic> json) {
    final position = json['position'] as Map<String, dynamic>? ?? const {};
    return TreeNode(
      uuid: json['uuid'] as String,
      name: (json['full_name'] ?? json['name'] ?? '') as String,
      x: (position['x'] as num? ?? 0).toDouble(),
      y: (position['y'] as num? ?? 0).toDouble(),
    );
  }
}

class TreeEdge {
  const TreeEdge({required this.sourceUuid, required this.targetUuid});

  final String sourceUuid;
  final String targetUuid;

  factory TreeEdge.fromJson(Map<String, dynamic> json) => TreeEdge(
        sourceUuid: (json['source_uuid'] ?? json['source']) as String,
        targetUuid: (json['target_uuid'] ?? json['target']) as String,
      );
}

class FamilyTree {
  const FamilyTree({required this.nodes, required this.edges});

  final List<TreeNode> nodes;
  final List<TreeEdge> edges;

  factory FamilyTree.fromJson(Map<String, dynamic> json) => FamilyTree(
        nodes: (json['nodes'] as List<dynamic>? ?? const [])
            .map((item) => TreeNode.fromJson(item as Map<String, dynamic>))
            .toList(),
        edges: (json['edges'] as List<dynamic>? ?? const [])
            .map((item) => TreeEdge.fromJson(item as Map<String, dynamic>))
            .toList(),
      );
}
