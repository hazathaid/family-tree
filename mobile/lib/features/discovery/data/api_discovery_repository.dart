import '../../../core/api_client.dart';
import '../domain/discovery_models.dart';
import '../domain/discovery_repository.dart';

class ApiDiscoveryRepository implements DiscoveryRepository {
  const ApiDiscoveryRepository(this.api);
  final ApiClient api;
  @override
  Future<SearchResults> search(String familyUuid,
          {String? keyword,
          String? name,
          String? city,
          String? status,
          int? generation,
          String? rootMemberUuid,
          int page = 1}) async =>
      SearchResults.fromJson(await api.get('/search', query: {
        'family_uuid': familyUuid,
        'page': page,
        'limit': 20,
        if (keyword?.isNotEmpty == true) 'keyword': keyword,
        if (name?.isNotEmpty == true) 'name': name,
        if (city?.isNotEmpty == true) 'city': city,
        if (status != null) 'status': status,
        if (generation != null) 'generation': generation,
        if (generation != null) 'root_member_uuid': rootMemberUuid
      }) as Map<String, dynamic>);
  @override
  Future<FamilyStatistics> statistics(String familyUuid) async =>
      FamilyStatistics.fromJson(
          await api.get('/families/$familyUuid/reports/family-statistics')
              as Map<String, dynamic>);
  Map<String, dynamic> _period(DateTime from, DateTime to) =>
      {'from': _date(from), 'to': _date(to)};
  String _date(DateTime value) =>
      '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
  @override
  Future<ActivityReport> activity(
          String familyUuid, DateTime from, DateTime to) async =>
      ActivityReport.fromJson(await api.get(
          '/families/$familyUuid/reports/activity',
          query: _period(from, to)) as Map<String, dynamic>);
  @override
  Future<ReportInsights> insights(
          String familyUuid, DateTime from, DateTime to) async =>
      ReportInsights.fromJson(await api.get(
          '/families/$familyUuid/reports/insights',
          query: _period(from, to)) as Map<String, dynamic>);
  @override
  Future<GamificationProfile> profile(String familyUuid) async =>
      GamificationProfile.fromJson(await api
          .get('/families/$familyUuid/gamification') as Map<String, dynamic>);
  List<LeaderboardEntry> _leaders(dynamic raw) => (raw as List<dynamic>)
      .map((e) => LeaderboardEntry.fromJson(e as Map<String, dynamic>))
      .toList();
  @override
  Future<List<LeaderboardEntry>> userLeaderboard(String familyUuid) async =>
      _leaders(await api
          .get('/families/$familyUuid/leaderboard', query: {'limit': 100}));
  @override
  Future<List<LeaderboardEntry>> familyLeaderboard() async =>
      _leaders(await api.get('/leaderboard/families', query: {'limit': 100}));
}
