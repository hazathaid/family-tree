import '../../../core/models.dart';

abstract interface class FamilyRepository {
  Future<List<Family>> all();
  Future<Family> create(String name, {String? description, String? originCity});
  Future<DashboardSummary> dashboard(String familyUuid);
  Future<List<TimelineItem>> timeline(String familyUuid);
  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'});
}
