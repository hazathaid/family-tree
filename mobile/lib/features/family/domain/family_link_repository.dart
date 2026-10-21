import '../../../core/models.dart';
import 'family_link_models.dart';

abstract interface class FamilyLinkRepository {
  Future<List<FamilyLink>> links(String familyUuid);

  Future<List<FamilyLinkCandidate>> candidates(String familyUuid,
      {required String search, int limit = 25});

  Future<FamilyLink> request(String familyUuid,
      {required String memberUuid, required String linkedMemberUuid});

  Future<FamilyLink> approve(String linkUuid);

  Future<FamilyLink> revoke(String linkUuid);

  Future<FamilyTree> partnerTree(String linkUuid,
      {required String familyUuid,
      String mode = 'full',
      int depth = 4,
      String layout = 'vertical'});
}
