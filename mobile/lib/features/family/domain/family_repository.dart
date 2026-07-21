import '../../../core/models.dart';

abstract interface class FamilyRepository {
  Future<List<Family>> all();
  Future<DashboardSummary> dashboard(String familyUuid);
  Future<List<TimelineItem>> timeline(String familyUuid);
  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'});
}
