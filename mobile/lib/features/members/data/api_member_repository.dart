import 'package:dio/dio.dart';

import '../../../core/api_client.dart';
import '../../../core/http/page_data.dart';
import '../../../core/models.dart';
import '../domain/member_repository.dart';

class ApiMemberRepository implements MemberRepository {
  const ApiMemberRepository(this.api);
  final ApiClient api;

  @override
  Future<PageData<FamilyMember>> members(String familyUuid,
          {int page = 1,
          int limit = 20,
          String? search,
          String? gender,
          bool? isAlive,
          String? branchUuid,
          String sort = 'name'}) async =>
      PageData.fromJson(
          await api.get('/family-members', query: {
            'family_uuid': familyUuid,
            'page': page,
            'limit': limit,
            'sort': sort,
            if (search?.isNotEmpty == true) 'search': search,
            if (gender != null) 'gender': gender,
            if (isAlive != null) 'is_alive': isAlive,
            if (branchUuid != null) 'branch_uuid': branchUuid,
          }) as Map<String, dynamic>,
          FamilyMember.fromJson);

  @override
  Future<FamilyMember> member(String uuid) async => FamilyMember.fromJson(
      await api.get('/family-members/$uuid') as Map<String, dynamic>);
  @override
  Future<FamilyMember> create(
          String familyUuid, Map<String, dynamic> values) async =>
      FamilyMember.fromJson(await api.post('/family-members',
              data: {'family_uuid': familyUuid, ...values})
          as Map<String, dynamic>);
  @override
  Future<FamilyMember> update(String uuid, Map<String, dynamic> values) async =>
      FamilyMember.fromJson(await api.put('/family-members/$uuid', data: values)
          as Map<String, dynamic>);
  @override
  Future<FamilyMember> uploadPhoto(String uuid, String path) async =>
      FamilyMember.fromJson(await api.post('/family-members/$uuid/photo',
              data: FormData.fromMap(
                  {'photo': await MultipartFile.fromFile(path)}))
          as Map<String, dynamic>);
  @override
  Future<void> deleteMember(String uuid) async {
    await api.delete('/family-members/$uuid');
  }

  @override
  Future<PageData<MemberRelationship>> relationships(String familyUuid,
          {String? memberUuid, int page = 1}) async =>
      PageData.fromJson(
          await api.get('/relationships', query: {
            'family_uuid': familyUuid,
            'page': page,
            'limit': 20,
            if (memberUuid != null) 'member_uuid': memberUuid
          }) as Map<String, dynamic>,
          MemberRelationship.fromJson);
  @override
  Future<MemberRelationship> createRelationship(
          Map<String, dynamic> values) async =>
      MemberRelationship.fromJson(await api.post('/relationships', data: values)
          as Map<String, dynamic>);
  @override
  Future<MemberRelationship> updateRelationship(
          String uuid, Map<String, dynamic> values) async =>
      MemberRelationship.fromJson(await api.put('/relationships/$uuid',
          data: values) as Map<String, dynamic>);
  @override
  Future<void> deleteRelationship(String uuid) async {
    await api.delete('/relationships/$uuid');
  }

  @override
  Future<RelationshipResolution> resolve(
          String sourceUuid, String targetUuid) async =>
      RelationshipResolution.fromJson(await api.get('/relationship-engine',
          query: {
            'source_member_uuid': sourceUuid,
            'target_member_uuid': targetUuid
          }) as Map<String, dynamic>);
}
