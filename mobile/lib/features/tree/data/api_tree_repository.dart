import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/api_client.dart';
import '../../../core/models.dart';
import '../domain/tree_repository.dart';

class ApiTreeRepository implements TreeRepository {
  const ApiTreeRepository(this.api);
  final ApiClient api;

  Map<String, dynamic> query(
          String rootUuid, String mode, int depth, String layout,
          [String? paperSize]) =>
      {
        'member_uuid': rootUuid,
        'mode': mode,
        'depth': depth,
        'layout': layout,
        if (paperSize != null) 'paper_size': paperSize,
      };

  @override
  Future<FamilyTree> generate(String rootUuid,
          {required String mode,
          required int depth,
          required String layout}) async =>
      FamilyTree.fromJson(await api.get('/tree/generate',
          query: query(rootUuid, mode, depth, layout)) as Map<String, dynamic>);

  @override
  Future<Uint8List> export(String format, String rootUuid,
          {required String mode,
          required int depth,
          required String layout,
          required String paperSize,
          CancelToken? cancelToken,
          ProgressCallback? onProgress}) =>
      api.download('/tree/export/$format',
          query: query(rootUuid, mode, depth, layout, paperSize),
          cancelToken: cancelToken,
          onReceiveProgress: onProgress);
}
