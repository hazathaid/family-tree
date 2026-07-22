import '../../../core/models.dart';
import '../../content/domain/content_models.dart';

class SearchResults {
  const SearchResults(
      {required this.members,
      required this.articles,
      required this.events,
      required this.page,
      required this.hasMore});
  final List<FamilyMember> members;
  final List<Article> articles;
  final List<FamilyEvent> events;
  final int page;
  final bool hasMore;
  factory SearchResults.fromJson(Map<String, dynamic> json) {
    final pagination = json['pagination'] as Map<String, dynamic>? ?? const {};
    return SearchResults(
      members: (json['members'] as List<dynamic>? ?? const [])
          .map((e) => FamilyMember.fromJson(e as Map<String, dynamic>))
          .toList(),
      articles: (json['articles'] as List<dynamic>? ?? const [])
          .map((e) => Article.fromJson(e as Map<String, dynamic>))
          .toList(),
      events: (json['events'] as List<dynamic>? ?? const [])
          .map((e) => FamilyEvent.fromJson(e as Map<String, dynamic>))
          .toList(),
      page: pagination['page'] as int? ?? 1,
      hasMore: pagination['has_more'] as bool? ?? false,
    );
  }
}

class ReportPoint {
  const ReportPoint(this.label, this.total);
  final String label;
  final int total;
  factory ReportPoint.fromJson(Map<String, dynamic> json) =>
      ReportPoint('${json['label']}', (json['total'] as num).toInt());
}

class FamilyStatistics {
  const FamilyStatistics(
      {required this.totalMembers,
      required this.aliveMembers,
      required this.deceasedMembers,
      required this.totalGenerations,
      required this.generations});
  final int totalMembers, aliveMembers, deceasedMembers, totalGenerations;
  final List<ReportPoint> generations;
  factory FamilyStatistics.fromJson(Map<String, dynamic> json) {
    final values =
        json['members_by_generation'] as Map<String, dynamic>? ?? const {};
    return FamilyStatistics(
        totalMembers: json['total_members'] as int? ?? 0,
        aliveMembers: json['alive_members'] as int? ?? 0,
        deceasedMembers: json['deceased_members'] as int? ?? 0,
        totalGenerations: json['total_generations'] as int? ?? 0,
        generations: values.entries
            .map((e) => ReportPoint(e.key, (e.value as num).toInt()))
            .toList());
  }
}

class ActivityReport {
  const ActivityReport(
      {required this.activeUsers,
      required this.uploads,
      required this.articles});
  final int activeUsers, uploads, articles;
  factory ActivityReport.fromJson(Map<String, dynamic> json) => ActivityReport(
      activeUsers: json['active_users'] as int? ?? 0,
      uploads:
          ((json['uploads'] as Map<String, dynamic>?)?['total'] as int?) ?? 0,
      articles:
          ((json['articles'] as Map<String, dynamic>?)?['total'] as int?) ?? 0);
}

class ReportInsights {
  const ReportInsights(
      {required this.cities, required this.growth, required this.activity});
  final List<ReportPoint> cities, growth, activity;
  factory ReportInsights.fromJson(Map<String, dynamic> json) => ReportInsights(
      cities: _points(json['cities']),
      growth: _points(json['growth']),
      activity: _points(json['activity']));
  static List<ReportPoint> _points(dynamic value) =>
      (value as List<dynamic>? ?? const [])
          .map((e) => ReportPoint.fromJson(e as Map<String, dynamic>))
          .toList();
}

class BadgeAward {
  const BadgeAward(
      {required this.uuid,
      required this.name,
      required this.description,
      this.awardedAt});
  final String uuid, name, description;
  final DateTime? awardedAt;
  factory BadgeAward.fromJson(Map<String, dynamic> json) => BadgeAward(
      uuid: json['uuid'] as String,
      name: json['name'] as String,
      description: json['description'] as String? ?? '',
      awardedAt: DateTime.tryParse(json['awarded_at'] as String? ?? ''));
}

class GamificationProfile {
  const GamificationProfile({required this.points, required this.badges});
  final int points;
  final List<BadgeAward> badges;
  factory GamificationProfile.fromJson(Map<String, dynamic> json) =>
      GamificationProfile(
          points: json['points'] as int? ?? 0,
          badges: (json['badges'] as List<dynamic>? ?? const [])
              .map((e) => BadgeAward.fromJson(e as Map<String, dynamic>))
              .toList());
}

class LeaderboardEntry {
  const LeaderboardEntry(
      {required this.rank,
      required this.uuid,
      required this.name,
      required this.points,
      this.image});
  final int rank, points;
  final String uuid, name;
  final String? image;
  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) =>
      LeaderboardEntry(
          rank: json['rank'] as int,
          uuid: json['uuid'] as String,
          name: json['name'] as String,
          points: json['points'] as int,
          image: json['image'] as String?);
}
