import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/family_repository.dart';
import 'package:dio/dio.dart';

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
  Future<Family> create(String name,
          {String? description, String? originCity}) async =>
      Family.fromJson(await api.post('/families', data: {
        'name': name,
        'description': description,
        'origin_city': originCity,
      }) as Map<String, dynamic>);

  @override
  Future<Family> update(Family family,
          {required String name,
          String? description,
          String? originCity}) async =>
      Family.fromJson(await api.put('/families/${family.uuid}', data: {
        'name': name,
        'description': description,
        'origin_city': originCity
      }) as Map<String, dynamic>);

  @override
  Future<Family> uploadAssets(String familyUuid,
      {String? logoPath, String? coverPath}) async {
    final data = FormData();
    if (logoPath != null) {
      data.files.add(MapEntry('logo', await MultipartFile.fromFile(logoPath)));
    }
    if (coverPath != null) {
      data.files.add(
          MapEntry('cover_image', await MultipartFile.fromFile(coverPath)));
    }
    return Family.fromJson(await api.post('/families/$familyUuid/assets',
        data: data) as Map<String, dynamic>);
  }

  List<T> _list<T>(dynamic value, T Function(Map<String, dynamic>) parse) {
    final items = value is Map<String, dynamic>
        ? value['data'] as List<dynamic>? ?? const []
        : value as List<dynamic>? ?? const [];
    return items
        .map((item) => parse(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  @override
  Future<List<FamilyBranch>> branches(String familyUuid) async => _list(
      await api.get('/families/$familyUuid/branches', query: {'limit': 100}),
      FamilyBranch.fromJson);
  @override
  Future<FamilyBranch> createBranch(
          String familyUuid, String name, String? description) async =>
      FamilyBranch.fromJson(await api.post('/families/$familyUuid/branches',
              data: {'name': name, 'description': description})
          as Map<String, dynamic>);
  @override
  Future<FamilyBranch> updateBranch(String familyUuid, FamilyBranch branch,
          String name, String? description) async =>
      FamilyBranch.fromJson(await api.put(
              '/families/$familyUuid/branches/${branch.uuid}',
              data: {'name': name, 'description': description})
          as Map<String, dynamic>);
  @override
  Future<void> deleteBranch(String familyUuid, String branchUuid) async {
    await api.delete('/families/$familyUuid/branches/$branchUuid');
  }

  @override
  Future<List<FamilyMembership>> memberships(String familyUuid) async => _list(
      await api.get('/families/$familyUuid/roles'), FamilyMembership.fromJson);
  @override
  Future<FamilyMembership> invite(
          String familyUuid, String email, String role) async =>
      FamilyMembership.fromJson(await api.post(
          '/families/$familyUuid/roles/invite',
          data: {'email': email, 'role': role}) as Map<String, dynamic>);
  @override
  Future<FamilyMembership> assignRole(
          String familyUuid, String membershipUuid, String role) async =>
      FamilyMembership.fromJson(await api.patch(
          '/families/$familyUuid/roles/$membershipUuid',
          data: {'role': role}) as Map<String, dynamic>);
  @override
  Future<void> removeMembership(
      String familyUuid, String membershipUuid) async {
    await api.delete('/families/$familyUuid/roles/$membershipUuid');
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
