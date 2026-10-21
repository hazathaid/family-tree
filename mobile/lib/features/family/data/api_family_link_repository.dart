import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/family_link_models.dart';
import '../domain/family_link_repository.dart';

class ApiFamilyLinkRepository implements FamilyLinkRepository {
  const ApiFamilyLinkRepository(this.api);

  final ApiClient api;

  @override
  Future<List<FamilyLink>> links(String familyUuid) async {
    final data =
        await api.get('/families/$familyUuid/family-links') as List<dynamic>;
    return data
        .map((item) => FamilyLink.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<List<FamilyLinkCandidate>> candidates(String familyUuid,
      {required String search, int limit = 25}) async {
    final data = await api.get('/families/$familyUuid/family-links/candidates',
        query: {'search': search, 'limit': limit}) as List<dynamic>;
    return data
        .map((item) =>
            FamilyLinkCandidate.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<FamilyLink> request(String familyUuid,
          {required String memberUuid,
          required String linkedMemberUuid}) async =>
      FamilyLink.fromJson(
          await api.post('/families/$familyUuid/family-links', data: {
        'member_uuid': memberUuid,
        'linked_member_uuid': linkedMemberUuid,
      }) as Map<String, dynamic>);

  @override
  Future<FamilyLink> approve(String linkUuid) async =>
      FamilyLink.fromJson(await api.post('/family-links/$linkUuid/approve')
          as Map<String, dynamic>);

  @override
  Future<FamilyLink> revoke(String linkUuid) async => FamilyLink.fromJson(
      await api.post('/family-links/$linkUuid/revoke') as Map<String, dynamic>);

  @override
  Future<FamilyTree> partnerTree(String linkUuid,
          {required String familyUuid,
          String mode = 'full',
          int depth = 4,
          String layout = 'vertical'}) async =>
      FamilyTree.fromJson(
          await api.get('/family-links/$linkUuid/partner-tree', query: {
        'family_uuid': familyUuid,
        'mode': mode,
        'depth': depth,
        'layout': layout,
      }) as Map<String, dynamic>);
}
