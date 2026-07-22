import 'discovery_models.dart';

abstract interface class DiscoveryRepository {
  Future<SearchResults> search(String familyUuid,
      {String? keyword,
      String? name,
      String? city,
      String? status,
      int? generation,
      String? rootMemberUuid,
      int page = 1});
  Future<FamilyStatistics> statistics(String familyUuid);
  Future<ActivityReport> activity(
      String familyUuid, DateTime from, DateTime to);
  Future<ReportInsights> insights(
      String familyUuid, DateTime from, DateTime to);
  Future<GamificationProfile> profile(String familyUuid);
  Future<List<LeaderboardEntry>> userLeaderboard(String familyUuid);
  Future<List<LeaderboardEntry>> familyLeaderboard();
}
