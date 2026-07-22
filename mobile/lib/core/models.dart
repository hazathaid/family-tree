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
  const Family({required this.uuid, required this.name});

  final String uuid;
  final String name;

  factory Family.fromJson(Map<String, dynamic> json) => Family(
        uuid: json['uuid'] as String,
        name: json['name'] as String,
      );
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
  });

  final int totalMembers;
  final int livingMembers;
  final int deceasedMembers;
  final int totalArticles;
  final int totalEvents;

  factory DashboardSummary.fromJson(Map<String, dynamic> json) =>
      DashboardSummary(
        totalMembers: json['total_members'] as int? ?? 0,
        livingMembers: json['living_members'] as int? ?? 0,
        deceasedMembers: json['deceased_members'] as int? ?? 0,
        totalArticles: json['total_articles'] as int? ?? 0,
        totalEvents: json['total_events'] as int? ?? 0,
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
