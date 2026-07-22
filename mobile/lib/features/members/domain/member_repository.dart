import '../../../core/http/page_data.dart';
import '../../../core/models.dart';

abstract interface class MemberRepository {
  Future<PageData<FamilyMember>> members(String familyUuid,
      {int page = 1,
      int limit = 20,
      String? search,
      String? gender,
      bool? isAlive,
      String? branchUuid,
      String sort = 'name'});
  Future<FamilyMember> member(String uuid);
  Future<FamilyMember> create(String familyUuid, Map<String, dynamic> values);
  Future<FamilyMember> update(String uuid, Map<String, dynamic> values);
  Future<FamilyMember> uploadPhoto(String uuid, String path);
  Future<void> deleteMember(String uuid);
  Future<PageData<MemberRelationship>> relationships(String familyUuid,
      {String? memberUuid, int page = 1});
  Future<MemberRelationship> createRelationship(Map<String, dynamic> values);
  Future<MemberRelationship> updateRelationship(
      String uuid, Map<String, dynamic> values);
  Future<void> deleteRelationship(String uuid);
  Future<RelationshipResolution> resolve(String sourceUuid, String targetUuid);
}
