import '../../../core/models.dart';

abstract interface class FamilyRepository {
  Future<List<Family>> all();
  Future<Family> create(String name, {String? description, String? originCity});
  Future<Family> update(Family family,
      {required String name, String? description, String? originCity});
  Future<Family> uploadAssets(String familyUuid,
      {String? logoPath, String? coverPath});
  Future<List<FamilyBranch>> branches(String familyUuid);
  Future<FamilyBranch> createBranch(
      String familyUuid, String name, String? description);
  Future<FamilyBranch> updateBranch(
      String familyUuid, FamilyBranch branch, String name, String? description);
  Future<void> deleteBranch(String familyUuid, String branchUuid);
  Future<List<FamilyMembership>> memberships(String familyUuid);
  Future<FamilyMembership> invite(String familyUuid, String email, String role);
  Future<FamilyMembership> assignRole(
      String familyUuid, String membershipUuid, String role);
  Future<void> removeMembership(String familyUuid, String membershipUuid);
  Future<DashboardSummary> dashboard(String familyUuid);
  Future<List<TimelineItem>> timeline(String familyUuid);
  Future<FamilyTree> tree(String memberUuid, {String mode = 'full'});
}
