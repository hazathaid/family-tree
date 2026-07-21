import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/family_repository.dart';

class ApiFamilyRepository implements FamilyRepository {
  const ApiFamilyRepository(this.api);
  final ApiClient api;
  @override
  Future<List<Family>> all() async {
    final result = await api.get('/families');
    final items = result is Map<String, dynamic>
        ? result['data'] as List<dynamic>? ?? const []
        : result as List<dynamic>;
    return items
        .map((item) => Family.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<DashboardSummary> dashboard(String familyUuid) async =>
      DashboardSummary.fromJson(await api.get('/families/$familyUuid/dashboard')
          as Map<String, dynamic>);
  @override
  Future<List<TimelineItem>> timeline(String familyUuid) async {
    final result = await api
        .get('/timeline', query: {'family_uuid': familyUuid, 'limit': 20});
    final items = result is Map<String, dynamic>
        ? result['data'] as List<dynamic>? ?? const []
        : result as List<dynamic>;
    return items
        .map((item) => TimelineItem.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'}) async =>
      FamilyTree.fromJson(await api.get('/tree/generate', query: {
        'member_uuid': memberUuid,
        'mode': mode,
        'depth': 3,
        'layout': 'vertical'
      }) as Map<String, dynamic>);
}
